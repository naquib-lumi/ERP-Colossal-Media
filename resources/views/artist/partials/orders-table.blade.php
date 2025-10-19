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
  @continue( (int) ($order->status ?? 0) === 1 )
  @php
    $rawStatus   = $order->orderStatus;
    $isPending   = (!$isHead) && $rawStatus === 'assigned' && (int) $order->pending === 1;

    $label = $isPending ? 'Pending' : \Illuminate\Support\Str::of($rawStatus)->replace('_', ' ')->title();

    $badgeClass = match (true) {
      $isPending                 => 'badge bg-warning text-dark fw-bold',
      $rawStatus === 'to_assign' => 'badge bg-secondary',
      $rawStatus === 'assigned'  => 'badge bg-warning text-dark',
      $rawStatus === 'in_progress'=> 'badge bg-info',
      $rawStatus === 'completed' => 'badge bg-success',
      $rawStatus === 'rejected'  => 'badge bg-danger',
      default                    => 'badge bg-light text-dark',
    };

    $deadline = $order->deadline ? \Carbon\Carbon::parse($order->deadline)->format('M d, Y') : '-';

    $displayOrderNo = $order->order_number;
    if (!is_null($order->redo) && optional($order->originalOrder)->order_number) {
      $orig = $order->originalOrder->order_number;
      $displayOrderNo = preg_match('/R\d*$/', $orig) ? $orig : ($orig . 'R');
    }

    $status = strtolower($order->orderStatus ?? '');
    $isCompleted   = $status === 'completed';
    $isRejected    = $status === 'rejected';
    $reportBlocked = in_array($status, ['to_assign','assigned','pending', 'in_progress'], true);
    $canEdit       = !($isCompleted || $isRejected);
  @endphp

  <tr data-href="{{ route('artist.orders.show', $order->id) }}" style="cursor:pointer;">
    <td>{{ $displayOrderNo }}</td>
    <td>{{ $order->orderTitle ?? '-' }}</td>
    <td>{{ $order->companyName ?? '-' }}</td>
    <td>
      @if($isHead)
        @php $needsAssign = ($order->orderStatus === 'to_assign') && empty($order->artist_id); @endphp
        <span class="me-2">{{ optional($order->artist)->name ?? '—' }}</span>
        @if($needsAssign)
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
        {{-- View --}}
        <a href="{{ route('artist.orders.show', $order->id) }}" class="text-secondary fw-bold" title="View">
          <i class="bx bx-show fs-5"></i>
        </a>

        {{-- Edit --}}
        @if($canEdit)
          <a href="{{ route('artist.orders.edit', $order->id) }}" class="text-secondary fw-bold" title="Edit">
            <i class="bx bx-edit-alt fs-5"></i>
          </a>
        @else
          <span class="text-muted opacity-25" data-bs-toggle="tooltip" data-bs-placement="top"
                title="Edit disabled: Order is {{ $isCompleted ? 'completed' : 'rejected' }}" style="cursor:not-allowed;">
            <i class="bx bx-edit-alt fs-5"></i>
          </span>
        @endif

        {{-- Report --}}
        @if(!$reportBlocked)
          <a href="{{ route('artist.orders.redo.create', $order->id) }}" class="text-secondary fw-bold" title="Report">
            <i class="bx bx-error-alt fs-5"></i>
          </a>
        @else
          <span class="text-muted opacity-25" data-bs-toggle="tooltip" data-bs-placement="top"
                title="Report not available for this status" style="cursor:not-allowed;">
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