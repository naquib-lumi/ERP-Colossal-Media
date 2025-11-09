@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<style>
  /* 分页左右布局 */
  .notifications-page .pagination-split{
    margin-top:1rem;
    display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;
  }
  .notifications-page .pagination-info{color:#6c757d;font-size:.875rem;}

  /* 方形胶囊按钮风格 */
  .notifications-page .pager-squared{display:flex;align-items:center;gap:.5rem;}
  .notifications-page .pager-squared .btn-page,
  .notifications-page .pager-squared .btn-page-disabled{
    width:2.75rem;height:2.75rem;border-radius:.75rem;
    display:inline-flex;align-items:center;justify-content:center;
    text-decoration:none;font-weight:600;font-size:.875rem;
    background:#f8f9fa;color:#6c757d;
    transition:all .15s ease;
    user-select:none;
  }
  .notifications-page .pager-squared .btn-page:hover{
    transform:translateY(-.125rem);
    box-shadow:0 .5rem 1rem rgba(0,0,0,.075);
    background:#e9ecef;
  }
  .notifications-page .pager-squared .active{
    background:#0d6efd;color:#fff;box-shadow:0 .375rem .75rem rgba(13,110,253,.4);
  }
  .notifications-page .pager-squared .btn-page-disabled{color:#dee2e6;cursor:not-allowed;}
  .notifications-page .pager-squared .dots{
    width:2.75rem;height:2.75rem;border-radius:.75rem;
    display:inline-flex;align-items:center;justify-content:center;color:#adb5bd;
  }

  /* 通知列表 */
  .notifications-page .notifications-list .list-group-item{
    border-left:3px solid transparent;transition:border-left-color .15s ease;
  }
  .notifications-page .notifications-list .list-group-item:hover{
    background:#f8f9fa;border-left-color:#0d6efd;
  }
  .notifications-page .notifications-list .list-group-item.unread{
    background:#fff3cd;border-left-color:#ffc107;
  }
  .notifications-page .notifications-list .notification-actions{
    display:flex;gap:.5rem;align-items:center;
  }
  .notifications-page .notifications-list .notification-actions .btn{
    padding:.25rem .5rem;font-size:.75rem;border-radius:.375rem;
  }
  .notifications-page .notifications-list .notification-actions .btn-outline-primary:hover{
    background:#0d6efd;color:#fff;
  }
  .notifications-page .notifications-list .notification-actions .btn-danger:hover{
    background:#dc3545;color:#fff;
  }
  .notifications-page .empty-state{
    text-align:center;padding:3rem 1rem;color:#6c757d;
  }
  .notifications-page .empty-state i{font-size:3rem;margin-bottom:1rem;opacity:.5;}
  .notifications-page .card-header{
    background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
    border:none;box-shadow:0 .5rem 1rem rgba(0,0,0,.1);
  }
  .notifications-page .card-header .btn{
    border:1px solid rgba(255,255,255,.2);color:#fff;
  }
  .notifications-page .card-header .btn:hover{
    background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.4);
  }
</style>

<div class="container-xxl flex-grow-1 container-p-y notifications-page">
  <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
    <div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center p-3">
      <div>
        <h5 class="mb-0 text-white"><i class="bx bx-bell me-2"></i>All Notifications</h5>
        <small class="text-white-50">Manage your alerts and updates</small>
      </div>
      <div class="d-flex gap-2">
        <button id="mark-all-read" class="btn btn-outline-light btn-sm"><i class="bx bx-check-circle me-1"></i>Mark All Read</button>
        <button id="archive-all" class="btn btn-outline-light btn-sm"><i class="bx bx-archive me-1"></i>Archive All</button>
        <a href="{{ url()->previous() }}" class="btn btn-secondary btn-sm"><i class="bx bx-arrow-back me-1"></i>Back</a>
      </div>
    </div>

    <div class="card-body p-0">
      <ul class="list-group list-group-flush notifications-list">
        @forelse ($notifications as $notification)
          <li class="list-group-item d-flex justify-content-between align-items-start p-3 {{ $notification->unread() ? 'unread' : '' }}" data-id="{{ $notification->id }}">
            <div class="flex-grow-1">
              <div class="d-flex align-items-start">
                <div class="flex-shrink-0 me-3">
                  <span class="avatar-initial rounded-circle bg-primary d-flex align-items-center justify-content-center" style="width:40px;height:40px;">
                    <i class="bx bx-bell text-white"></i>
                  </span>
                </div>
                <div class="flex-grow-1">
                  <h6 class="mb-1">{{ $notification->data['message'] ?? 'No Message' }}</h6>
                  <small class="text-muted">{{ $notification->created_at->format('M d, Y H:i') }}</small>
                  @if($notification->unread())
                    <span class="badge bg-warning ms-2">New</span>
                  @endif
                </div>
              </div>
            </div>
            <div class="notification-actions ms-3">
              <a href="{{ $notification->data['url'] ?? '#' }}" class="btn btn-sm btn-outline-primary dropdown-notifications-read">
                <i class="bx bx-eye me-1"></i>View
              </a>
              <button class="btn btn-sm btn-outline-danger dropdown-notifications-archive">
                <i class="bx bx-archive me-1"></i>Archive
              </button>
            </div>
          </li>
        @empty
          <li class="empty-state">
            <i class="bx bx-bell-slash"></i>
            <h5 class="mb-1">No notifications yet</h5>
            <p class="mb-0">Stay tuned for updates and alerts.</p>
          </li>
        @endforelse
      </ul>

      {{-- ===== 左：说明；右：自定义方形胶囊分页 ===== --}}
      @if($notifications->hasPages())
      <div class="pagination-split">
        <div class="pagination-info">
          Showing {{ $notifications->firstItem() ?? 0 }} to {{ $notifications->lastItem() ?? 0 }} of {{ $notifications->total() }} results
        </div>

        {{-- 自定义分页（⟪ ‹ 1 … n › ⟫） --}}
        @php
          $current = $notifications->currentPage();
          $last    = $notifications->lastPage();
          $window  = 2; // 当前页左右各显示几个
          $pages   = [];

          if ($last >= 1) { $pages[] = 1; }                              // 首页
          for ($i = max(2, $current - $window); $i <= min($last - 1, $current + $window); $i++) {
            $pages[] = $i;                                               // 中间窗口
          }
          if ($last > 1) { $pages[] = $last; }                           // 末页

          $pages = array_values(array_unique($pages));
          sort($pages);
        @endphp

        <nav class="pager-squared" role="navigation" aria-label="Pagination">
          {{-- First --}}
          @if ($current === 1)
            <span class="btn-page-disabled" aria-hidden="true">&laquo;</span>
          @else
            <a class="btn-page" href="{{ $notifications->url(1) }}" aria-label="First"><i class="bx bx-chevrons-left"></i></a>
          @endif

          {{-- Prev --}}
          @if ($current === 1)
            <span class="btn-page-disabled" aria-hidden="true">&lsaquo;</span>
          @else
            <a class="btn-page" rel="prev" href="{{ $notifications->previousPageUrl() }}" aria-label="Previous"><i class="bx bx-chevron-left"></i></a>
          @endif

          {{-- Numbers with dots --}}
          @php $prev = null; @endphp
          @foreach ($pages as $p)
            @if (!is_null($prev) && $p > $prev + 1)
              <span class="dots">…</span>
            @endif
            @if ($p == $current)
              <span class="btn-page active" aria-current="page">{{ $p }}</span>
            @else
              <a class="btn-page" href="{{ $notifications->url($p) }}">{{ $p }}</a>
            @endif
            @php $prev = $p; @endphp
          @endforeach

          {{-- Next --}}
          @if ($notifications->hasMorePages())
            <a class="btn-page" rel="next" href="{{ $notifications->nextPageUrl() }}" aria-label="Next"><i class="bx bx-chevron-right"></i></a>
          @else
            <span class="btn-page-disabled" aria-hidden="true">&rsaquo;</span>
          @endif

          {{-- Last --}}
          @if ($current === $last || $last === 0)
            <span class="btn-page-disabled" aria-hidden="true">&raquo;</span>
          @else
            <a class="btn-page" href="{{ $notifications->url($last) }}" aria-label="Last"><i class="bx bx-chevrons-right"></i></a>
          @endif
        </nav>
      </div>
      @endif
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
          $('.notifications-list li.unread').removeClass('unread').addClass('bg-light');
          $('.badge-notifications').remove();
          Swal.fire('Success!', 'All notifications marked as read.', 'success');
        },
        error: function(xhr) {
          console.error('Error marking all as read:', xhr.responseText);
          Swal.fire('Error!', 'Failed to mark all as read.', 'error');
        }
      });
    });

    $('#archive-all').on('click', function() {
      Swal.fire({
        title: 'Archive All?',
        text: "This will archive all notifications. You can view archived ones later.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, archive all!'
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: '{{ route('notifications.archiveAll') }}',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function() {
              $('.notifications-list').empty().append('<li class="list-group-item text-center text-muted py-4"><i class="bx bx-archive fs-1 mb-2 d-block"></i><h6 class="mb-0">All notifications archived</h6></li>');
              $('.badge-notifications').remove();
              Swal.fire('Archived!', 'All notifications have been archived.', 'success');
            },
            error: function(xhr) {
              console.error('Error archiving all:', xhr.responseText);
              Swal.fire('Error!', 'Failed to archive all notifications.', 'error');
            }
          });
        }
      });
    });

    // 列表页
    $('.notifications-list').on('click', '.dropdown-notifications-read', function(e) {
      e.preventDefault();
      handleNotificationRead($(this), '.list-group-item');
    });

    // 顶部下拉（避免双绑定冲突，这里限定 .dropdown-menu）
    $('body').on('click', '.dropdown-menu .dropdown-notifications-read', function(e) {
      e.preventDefault();
      handleNotificationRead($(this), '.dropdown-notifications-item');
    });

    function handleNotificationRead($element, parentClass) {
      let url = $element.attr('href');
      let notificationItem = $element.closest(parentClass);
      let notificationId = notificationItem ? notificationItem.data('id') : null;

      if (!notificationId) { 
        if (url) window.location.href = url; 
        return; 
      }

      let markAsReadUrl = '{{ route('notifications.markAsRead', ['id' => 'PLACEHOLDER']) }}'.replace('PLACEHOLDER', notificationId);

      $.ajax({
        url: markAsReadUrl,
        type: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: function() {
          if (notificationItem) notificationItem.removeClass('unread').addClass('bg-light');
          let count = parseInt($('.badge-notifications').text() || 0);
          if (count > 0) {
            $('.badge-notifications').text(count - 1);
            if (count - 1 === 0) $('.badge-notifications').remove();
          }
          if (url) window.location.href = url;
        },
        error: function(xhr) {
          console.error('Error marking as read:', xhr.responseText);
          if (url) window.location.href = url;
        }
      });
    }

    // 归档
    $('body').on('click', '.dropdown-notifications-archive', function(e) {
      e.preventDefault();
      let notificationItem = $(this).closest('.dropdown-notifications-item, .list-group-item');
      let notificationId = notificationItem ? notificationItem.data('id') : null;

      if (!notificationId) return;

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
          Swal.fire('Error!', 'Failed to archive notification.', 'error');
        }
      });
    });

    // 未读数轮询
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