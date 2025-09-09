@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center p-3">
            <h5 class="mb-0">All Notifications</h5>
            <div>
                <button id="mark-all-read" class="btn btn-outline-light btn-sm me-2">Mark All as Read</button>
                <a href="{{ route('sales.leads') }}" class="btn btn-outline-light btn-sm">Back to Leads</a>
            </div>
        </div>
        <div class="card-body p-4">
            <ul class="list-group list-group-flush notifications-list">
                @forelse ($notifications as $notification)
                    <li class="list-group-item d-flex justify-content-between align-items-center {{ $notification->unread() ? '' : 'bg-light' }}" data-id="{{ $notification->id }}">
                        <div>
                            <strong>{{ $notification->data['message'] ?? 'No Message' }}</strong><br>
                            <small class="text-muted">{{ $notification->created_at->format('Y-m-d H:i') }}</small>
                        </div>
                        <div>
                            <a href="{{ $notification->data['url'] ?? '#' }}" class="btn btn-sm btn-primary dropdown-notifications-read">View</a>
                            <a href="javascript:void(0)" class="btn btn-sm btn-danger dropdown-notifications-archive">Archive</a>
                        </div>
                    </li>
                @empty
                    <li class="list-group-item text-muted">No notifications available.</li>
                @endforelse
            </ul>
            <div class="mt-3">
                {{ $notifications->links() }}
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
    $(document).ready(function() {
        console.log('Notifications JS loaded');

        $('#mark-all-read').on('click', function() {
            $.ajax({
                url: '{{ route('notifications.markAllAsRead') }}',
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function() {
                    $('.notifications-list li:not(.bg-light)').addClass('bg-light');
                    $('.badge-notifications').remove();
                },
                error: function(xhr) {
                    console.error('Error marking all as read:', xhr.responseText);
                }
            });
        });

        // Event delegation for main page notifications
        $('.notifications-list').on('click', '.dropdown-notifications-read', function(e) {
            e.preventDefault();
            handleNotificationRead($(this), '.list-group-item');
        });

        // Event delegation for dropdown notifications (navbar)
        $('body').on('click', '.dropdown-notifications-read', function(e) {
            e.preventDefault();
            handleNotificationRead($(this), '.dropdown-notifications-item');
        });

        function handleNotificationRead($element, parentClass) {
            let url = $element.attr('href');
            let notificationItem = $element.closest(parentClass);
            let notificationId = notificationItem ? notificationItem.data('id') : null;

            if (!notificationId) {
                console.error('Notification ID is undefined');
                return;
            }

            let markAsReadUrl = '{{ route('notifications.markAsRead', ['id' => 'PLACEHOLDER']) }}'.replace('PLACEHOLDER', notificationId);

            $.ajax({
                url: markAsReadUrl,
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function() {
                    if (notificationItem) notificationItem.addClass('bg-light');
                    let count = parseInt($('.badge-notifications').text() || 0);
                    if (count > 0) {
                        $('.badge-notifications').text(count - 1);
                        if (count - 1 === 0) $('.badge-notifications').remove();
                    }
                    window.location.href = url;
                },
                error: function(xhr) {
                    console.error('Error marking as read:', xhr.responseText);
                }
            });
        }

        // Event delegation for archive (main page and dropdown)
        $('body').on('click', '.dropdown-notifications-archive', function(e) {
            e.preventDefault();
            let notificationItem = $(this).closest('.dropdown-notifications-item, .list-group-item');
            let notificationId = notificationItem ? notificationItem.data('id') : null;

            if (!notificationId) {
                console.error('Notification ID is undefined');
                return;
            }

            let archiveUrl = '{{ route('notifications.archive', ['id' => 'PLACEHOLDER']) }}'.replace('PLACEHOLDER', notificationId);

            $.ajax({
                url: archiveUrl,
                type: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function() {
                    if (notificationItem) notificationItem.remove();
                    let count = parseInt($('.badge-notifications').text() || 0);
                    if (count > 0) {
                        $('.badge-notifications').text(count - 1);
                        if (count - 1 === 0) $('.badge-notifications').remove();
                    }
                },
                error: function(xhr) {
                    console.error('Error archiving:', xhr.responseText);
                }
            });
        });

        setInterval(function() {
            $.ajax({
                url: '{{ route('notifications.count') }}',
                success: function(count) {
                    if (count > 0) {
                        $('.badge-notifications').text(count).show();
                        $('.bx-bell').addClass('animate__animated animate__tada');
                        setTimeout(() => $('.bx-bell').removeClass('animate__animated animate__tada'), 1000);
                    } else {
                        $('.badge-notifications').remove();
                    }
                },
                error: function(xhr) {
                    console.error('Error fetching count:', xhr.responseText);
                }
            });
        }, 60000);
    });
</script>
@endpush
@endsection