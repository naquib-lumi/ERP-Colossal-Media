@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<style>
  /* 分页左右布局 */
  .notifications-page .pagination-split{
    margin-top:1rem;
    display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;
  }
  .notifications-page .pagination-info{color:#98A2B3;font-size:16px;}

  /* 方形胶囊按钮风格 */
  .notifications-page .pager-squared{display:flex;align-items:center;gap:10px;}
  .notifications-page .pager-squared .btn-page,
  .notifications-page .pager-squared .btn-page-disabled{
    width:44px;height:44px;border-radius:12px;
    display:inline-flex;align-items:center;justify-content:center;
    text-decoration:none;font-weight:700;
    background:#F5F7FB;color:#667085;
    transition:transform .12s ease, box-shadow .12s ease, background .12s ease, color .12s ease;
    user-select:none;
  }
  .notifications-page .pager-squared .btn-page:hover{
    transform:translateY(-1px);
    box-shadow:0 6px 16px rgba(17,24,39,.08);
  }
  .notifications-page .pager-squared .active{
    background:#6366F1;color:#fff;box-shadow:0 10px 22px rgba(99,102,241,.35);
  }
  .notifications-page .pager-squared .btn-page-disabled{color:#C8D0E0;cursor:not-allowed;}
  .notifications-page .pager-squared .dots{
    width:44px;height:44px;border-radius:12px;
    display:inline-flex;align-items:center;justify-content:center;color:#A3AED0;
  }
</style>

<div class="container-xxl flex-grow-1 container-p-y notifications-page">
  <div class="card shadow-sm border-0">
<div class="card-header bg-gradient-primary text-white d-flex justify-content-between align-items-center p-3">
    <h5 class="mb-0 text-white">All Notifications</h5>
    <div>
        <button id="mark-all-read" class="btn btn-outline-light btn-sm me-2 text-white">Mark All as Read</button>
        <a href="{{ route('sales.leads') }}" class="btn btn-outline-light btn-sm text-white">Back to Leads</a>
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

      {{-- ===== 左：说明；右：自定义方形胶囊分页 ===== --}}
      <div class="pagination-split">
        <div class="pagination-info">
          Showing {{ $notifications->firstItem() ?? 0 }}
          to {{ $notifications->lastItem() ?? 0 }}
          of {{ $notifications->total() }} entries
        </div>

        {{-- 自定义分页（⟪ ‹ 1 … n › ⟫） --}}
        @php
          $current = $notifications->currentPage();
          $last    = $notifications->lastPage();
          $window  = 1; // 当前页左右各显示几个
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
            <a class="btn-page" href="{{ $notifications->url(1) }}" aria-label="First">&laquo;</a>
          @endif

          {{-- Prev --}}
          @if ($current === 1)
            <span class="btn-page-disabled" aria-hidden="true">&lsaquo;</span>
          @else
            <a class="btn-page" rel="prev" href="{{ $notifications->previousPageUrl() }}" aria-label="@lang('pagination.previous')">&lsaquo;</a>
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
            <a class="btn-page" rel="next" href="{{ $notifications->nextPageUrl() }}" aria-label="@lang('pagination.next')">&rsaquo;</a>
          @else
            <span class="btn-page-disabled" aria-hidden="true">&rsaquo;</span>
          @endif

          {{-- Last --}}
          @if ($current === $last || $last === 0)
            <span class="btn-page-disabled" aria-hidden="true">&raquo;</span>
          @else
            <a class="btn-page" href="{{ $notifications->url($last) }}" aria-label="Last">&raquo;</a>
          @endif
        </nav>
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

      if (!notificationId) { if (url) window.location.href = url; return; }

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
