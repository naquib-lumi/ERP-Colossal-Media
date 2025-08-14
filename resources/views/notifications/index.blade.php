@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center p-3">
            <h5 class="mb-0">All Notifications</h5>
            <a href="{{ route('sales.leads') }}" class="btn btn-outline-light btn-sm">Back to Leads</a>
        </div>
        <div class="card-body p-4">
            <ul class="list-group list-group-flush notifications-list">
                @forelse ($notifications as $notification)
                    <li class="list-group-item d-flex justify-content-between align-items-center {{ $notification->unread() ? '' : 'bg-light' }}" data-id="{{ $notification->id }}">
                        <div>
                            <strong>{{ $notification->data['title'] ?? 'No Title' }}</strong><br>
                            <small class="text-muted">Due: {{ $notification->data['due_date'] ?? 'N/A' }}</small><br>
                            <small class="text-muted">Lead ID: {{ $notification->data['lead_id'] ?? 'N/A' }}</small>
                        </div>
                        <div>
                            <a href="{{ url('/leads/' . ($notification->data['lead_id'] ?? '#')) }}" class="btn btn-sm btn-primary dropdown-notifications-read">View Lead</a>
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

            console.log('Clicked Element:', $element);
            console.log('Notification Item:', notificationItem);
            console.log('Data-id:', notificationId);
            console.log('Parent HTML:', notificationItem ? notificationItem.html() : 'Not found');

            if (!notificationId) {
                console.error('Notification ID is undefined', { item: notificationItem, context: $element.parent().html() });
                alert('Error: Notification ID is missing. Check console for details.');
                return;
            }

            let markAsReadUrl = '{{ route('notifications.markAsRead', ['id' => 'PLACEHOLDER']) }}'.replace('PLACEHOLDER', notificationId);

            console.log('Mark as read URL:', markAsReadUrl);

            $.ajax({
                url: markAsReadUrl,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function() {
                    console.log('Notification marked as read:', notificationId);
                    if (notificationItem) notificationItem.addClass('marked-as-read');
                    let count = parseInt($('.badge-notifications').text() || 0);
                    if (count > 0) {
                        $('.badge-notifications').text(count - 1);
                        if (count - 1 === 0) $('.badge-notifications').remove();
                    }
                    window.location.href = url;
                },
                error: function(xhr) {
                    console.error('Error marking notification as read:', xhr.status, xhr.responseText);
                    alert('Error marking notification as read: ' + xhr.responseText);
                }
            });
        }

        // Event delegation for archive (main page and dropdown)
        $('body').on('click', '.dropdown-notifications-archive', function(e) {
            e.preventDefault();
            let notificationItem = $(this).closest('.dropdown-notifications-item, .list-group-item');
            let notificationId = notificationItem ? notificationItem.data('id') : null;

            console.log('Clicked Element:', $(this));
            console.log('Notification Item:', notificationItem);
            console.log('Data-id:', notificationId);

            if (!notificationId) {
                console.error('Notification ID is undefined', { item: notificationItem, context: $(this).parent().html() });
                alert('Error: Notification ID is missing. Check console for details.');
                return;
            }

            let archiveUrl = '{{ route('notifications.archive', ['id' => 'PLACEHOLDER']) }}'.replace('PLACEHOLDER', notificationId);

            console.log('Archive URL:', archiveUrl);

            $.ajax({
                url: archiveUrl,
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function() {
                    console.log('Notification archived:', notificationId);
                    if (notificationItem) notificationItem.remove();
                    let count = parseInt($('.badge-notifications').text() || 0);
                    if (count > 0) {
                        $('.badge-notifications').text(count - 1);
                        if (count - 1 === 0) $('.badge-notifications').remove();
                    }
                },
                error: function(xhr) {
                    console.error('Error archiving notification:', xhr.status, xhr.responseText);
                    alert('Error archiving notification: ' + xhr.responseText);
                }
            });
        });

        setInterval(function() {
            console.log('Checking notification count');
            $.ajax({
                url: '{{ route('notifications.count') }}',
                success: function(count) {
                    console.log('Notification count:', count);
                    if (count > 0) {
                        $('.badge-notifications').text(count).show();
                        $('.bx-bell').addClass('animate__animated animate__tada');
                        setTimeout(() => $('.bx-bell').removeClass('animate__animated animate__tada'), 1000);
                    } else {
                        $('.badge-notifications').remove();
                    }
                },
                error: function(xhr) {
                    console.error('Error fetching notification count:', xhr.status, xhr.responseText);
                }
            });
        }, 60000);
    });
</script>
@endpush
@endsection