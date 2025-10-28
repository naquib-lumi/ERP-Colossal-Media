@php
use Carbon\Carbon;

$isDashboard = ($tableContext ?? null) === 'dashboard';
$tableId     = $tableId ?? ($isDashboard ? 'jobOrdersTop5' : 'jobOrdersTable');

$isPaginator = $orders instanceof \Illuminate\Pagination\AbstractPaginator;
$col         = $isPaginator ? $orders->getCollection() : collect($orders);

$deadlineKey = fn($o) => $o->deadline ? Carbon::parse($o->deadline) : Carbon::parse('2100-01-01');

if ($isDashboard) {
  $col = $col
    ->reject(fn($o) => strtolower((string)$o->orderStatus) === 'completed')
    ->sortByDesc($deadlineKey)   // Dashboard keeps your DESC
    ->take(5)
    ->values();
} else {
  // Orders page: keep controller's ordering (do not override)
  $col = $col->values();
}

$orders = $isPaginator ? $orders->setCollection($col) : $col;

$isHead = auth()->user()->role === 'head-artist';
@endphp
@php
  $currentSort = request('deadline_sort');
  $nextSort    = $currentSort === 'nearest' ? 'furthest' : 'nearest';
  $sortIcon    = $currentSort === 'nearest' ? 'bx-sort-up'
                : ($currentSort === 'furthest' ? 'bx-sort-down' : 'bx-sort');
  $deadlineSortUrl = route('artist.orders', array_merge(request()->query(), ['deadline_sort' => $nextSort]));
@endphp
<style>
  .bg-amber {
  background-color: #ff6f00 !important; /* vivid amber-orange */
}
</style>
<table id="{{ $tableId }}" class="table table-modern table-hover w-100">
  <thead>
    <tr>
      <th>Order ID</th>
      <th>Job Title</th>
      <th>Company</th>
      <th>{{ $isHead ? 'Artist' : 'Salesperson' }}</th>
      <th>Status</th>
      <th>
        <a href="{{ $deadlineSortUrl }}" class="text-decoration-none">
          Deadline <i class="bx {{ $sortIcon }}"></i>
        </a>
      </th>
      <th class="text-end">Actions</th>
    </tr>
  </thead>
  <tbody>
@forelse ($orders as $order)
  {{-- do NOT skip status=1 --}}
  @php
    $isArchived = (int)($order->status ?? 0) === 1;

    // If archived → treat as rejected for display
    $rawStatus = $isArchived ? 'redo' : ($order->orderStatus ?? '');

    $isPending = (!$isHead) && $rawStatus === 'assigned' && (int)$order->pending === 1;

    $label = $isPending
      ? 'Pending'
      : \Illuminate\Support\Str::of($rawStatus)->replace('_', ' ')->title();

    $badgeClass = match (true) {
      $isPending                   => 'badge bg-warning text-dark fw-bold',
      $rawStatus === 'to_assign'   => 'badge bg-secondary',
      $rawStatus === 'assigned'    => 'badge bg-warning text-dark',
      $rawStatus === 'in_progress' => 'badge bg-info',
      $rawStatus === 'completed'   => 'badge bg-success',
      $rawStatus === 'rejected'    => 'badge bg-danger',
      $rawStatus === 'redo' => 'badge bg-amber text-white',
      default                      => 'badge bg-light text-dark',
    };

    $deadline = $order->deadline ? \Carbon\Carbon::parse($order->deadline)->format('M d, Y') : '-';

    $displayOrderNo = $order->order_number;
    if (!is_null($order->redo) && optional($order->originalOrder)->order_number) {
      $orig = $order->originalOrder->order_number;
      $displayOrderNo = preg_match('/R\d*$/', $orig) ? $orig : ($orig . 'R');
    }

    $status        = strtolower($rawStatus);
    $isCompleted   = $status === 'completed';
    $isRejected    = $status === 'rejected';
    $reportBlocked = in_array($status, ['to_assign','assigned','pending','in_progress', 'awaiting_keyin', 'rejected'], true);
    $canEdit       = !($isCompleted) && !$isArchived; // archived → no edit
  @endphp

  <tr data-href="{{ route('artist.orders.show', $order->id) }}" style="cursor:pointer;">
    <td>
      <div class="d-flex align-items-center gap-2">
        <span>{{ $displayOrderNo }}</span>
        @if($isArchived)
          <span class="badge rounded-pill bg-light text-danger border border-danger"
                title="This order was rejected.">
            Rejected for redo
          </span>
        @endif
      </div>
    </td>

    <td>{{ $order->orderTitle ?? '-' }}</td>
    <td>{{ $order->companyName ?? '-' }}</td>
    <td>
      @if($isHead)
        @php $needsAssign = ($order->orderStatus === 'to_assign') && empty($order->artist_id); @endphp
        <span class="me-2">{{ optional($order->artist)->name ?? '—' }}</span>
        @if($needsAssign && !$isArchived)
          <a href="{{ route('artist.orders.assign.show', $order->id) }}"
             class="btn btn-outline-primary btn-sm align-middle" title="Assign this order">
            <i class="bx bx-user-plus"></i>
          </a>
        @endif
      @else
        {{ optional($order->salesperson)->name ?? '-' }}
      @endif
    </td>

    <td data-status-code="{{ $isPending ? 'pending' : $rawStatus }}">
      <span class="{{ $badgeClass }}">{{ $label }}</span>
    </td>

    <td data-deadline="{{ $order->deadline ? \Carbon\Carbon::parse($order->deadline)->toDateString() : '' }}">
      {{ $deadline }}
    </td>

    <td class="text-end">
      <div class="d-inline-flex align-items-center gap-3">
        {{-- View (always enabled) --}}
        <a href="{{ route('artist.orders.show', $order->id) }}" class="text-secondary fw-bold" title="View">
          <i class="bx bx-show fs-5"></i>
        </a>

        {{-- Edit (disabled for completed/rejected/archived) --}}
        @if($canEdit)
          <a href="{{ route('artist.orders.edit', $order->id) }}" class="text-secondary fw-bold" title="Edit">
            <i class="bx bx-edit-alt fs-5"></i>
          </a>
        @else
          <span class="text-muted opacity-25" data-bs-toggle="tooltip" data-bs-placement="top"
                title="Edit disabled: {{ $isArchived ? 'Rejected order' : ($isCompleted ? 'Completed' : 'Rejected') }}"
                style="cursor:not-allowed;">
            <i class="bx bx-edit-alt fs-5"></i>
          </span>
        @endif

        {{-- Report (never for archived; also blocked for other statuses per your rule) --}}
        @if(!$isArchived && !$reportBlocked)
          <a href="{{ route('artist.orders.redo.create', $order->id) }}" class="text-secondary fw-bold" title="Report">
            <i class="bx bx-error-alt fs-5"></i>
          </a>
        @else
          <span class="text-muted opacity-25" data-bs-toggle="tooltip" data-bs-placement="top"
                title="{{ $isArchived ? 'Report disabled: Rejected order' : 'Report not available for this status' }}"
                style="cursor:not-allowed;">
            <i class="bx bx-error-alt fs-5"></i>
          </span>
        @endif
      </div>
    </td>
  </tr>
@empty
@endforelse
</tbody>
</table>

<script>
  document.addEventListener('dblclick', function (e) {
    const tr = e.target.closest('tr[data-href]');
    if (tr) window.location = tr.getAttribute('data-href');
  });
</script>