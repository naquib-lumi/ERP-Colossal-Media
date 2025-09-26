@php
  use Carbon\Carbon;

  $isDashboard = ($tableContext ?? null) === 'dashboard';
  $tableId     = $tableId ?? ($isDashboard ? 'jobOrdersTop5' : 'jobOrdersTable');

  $isPaginator = $orders instanceof \Illuminate\Pagination\AbstractPaginator;
  $col         = $isPaginator ? $orders->getCollection() : collect($orders);

  $deadlineKey = fn($o) => $o->deadline
      ? Carbon::parse($o->deadline)
      : Carbon::parse('2100-01-01');

  if ($isDashboard) {
      $col = $col
        ->reject(fn($o) => strtolower((string)$o->orderStatus) === 'completed')
        ->sortByDesc($deadlineKey)  // DESC
        ->take(5)
        ->values();
  } else {
      $col = $col->sortByDesc($deadlineKey)->values(); // Orders page DESC
  }

  $orders = $isPaginator ? $orders->setCollection($col) : $col;

  $isHead = auth()->user()->role === 'head-artist';
@endphp

<table id="{{ $tableId }}" class="table table-modern table-hover w-100">
  <thead>
    <tr>
      <th>Order ID</th>
      <th>Job Title</th>
      <th>Company</th>
      <th>{{ $isHead ? 'Artist' : 'Salesperson' }}</th>
      <th>Status</th>
      <th>Deadline</th>
      <th class="text-end">Actions</th>
    </tr>
  </thead>
  <tbody>
    @forelse ($orders as $order)
    @continue( (int) ($order->status ?? 0) === 1 )
    @php
      $rawStatus = $order->orderStatus;
      $isPending = (!$isHead) && $rawStatus === 'assigned' && (int) $order->pending === 1;

      $label = $isPending
      ? 'Pending'
      : \Illuminate\Support\Str::of($rawStatus)->replace('_', ' ')->title();

      $badgeClass = match (true) {
      $isPending => 'badge bg-warning text-dark fw-bold',
      $rawStatus === 'to_assign' => 'badge bg-secondary',
      $rawStatus === 'assigned' => 'badge bg-warning text-dark', // head-only
      $rawStatus === 'in_progress'=> 'badge bg-info',
      $rawStatus === 'completed' => 'badge bg-success',
      $rawStatus === 'rejected' => 'badge bg-danger',
      default => 'badge bg-light text-dark',
      };

      $deadline = $order->deadline ? \Carbon\Carbon::parse($order->deadline)->format('M d, Y') : '-';

      $displayOrderNo = $order->order_number;

      if (!is_null($order->redo) && optional($order->originalOrder)->order_number) {
          $orig = $order->originalOrder->order_number;
          $displayOrderNo = preg_match('/R\d*$/', $orig) ? $orig : ($orig . 'R');
      }
    @endphp
    <tr>
      <td>{{ $displayOrderNo }}</td>
      <td>{{ $order->orderTitle ?? '-' }}</td>
      <td>{{ $order->companyName ?? '-' }}</td>
      <td>
        @if($isHead)
        @php
        $needsAssign = ($order->orderStatus === 'to_assign') && empty($order->artist_id);
        @endphp

        {{-- If already assigned, show name; else show dash --}}
        <span class="me-2">
          {{ optional($order->artist)->name ?? '—' }}
        </span>

        {{-- Assign icon (head-only, only when to_assign and no artist yet) --}}
        @if($needsAssign)
        <a href="{{ route('artist.orders.assign.show', $order->id) }}"
          class="btn btn-outline-primary btn-sm align-middle"
          title="Assign this order">
          <i class="bx bx-user-plus"></i>
        </a>
        @endif
        @else
        {{-- normal artist sees salesperson instead (your existing code) --}}
        {{ optional($order->salesperson)->name ?? '-' }}
        @endif
      </td>
      <td data-status-code="{{ $isPending ? 'pending' : $rawStatus }}">
        <span class="{{ $badgeClass }}">{{ $label }}</span>
      </td>
      <td data-deadline="{{ $order->deadline ? \Carbon\Carbon::parse($order->deadline)->toDateString() : '' }}">
        {{ $deadline }}
      </td> 
      @php
      $status = strtolower($order->orderStatus ?? '');
      $isCompleted = $status === 'completed';
      $reportBlocked = in_array($status, ['to_assign','assigned','pending', 'in_progress'], true);
      @endphp

      <td class="text-end">
        <div class="d-inline-flex align-items-center gap-3">

          {{-- View (always enabled) --}}
          <a href="{{ route('artist.orders.show', $order->id) }}"
            class="text-secondary fw-bold" title="View">
            <i class="bx bx-show fs-5"></i>
          </a>

          {{-- Edit --}}
          @if(!$isCompleted)
          <a href="{{ route('artist.orders.edit', $order->id) }}"
            class="text-secondary fw-bold" title="Edit">
            <i class="bx bx-edit-alt fs-5"></i>
          </a>
          @else
          <span class="text-muted opacity-25"
            data-bs-toggle="tooltip"
            data-bs-placement="top"
            title="Edit disabled: Order already completed"
            style="cursor: not-allowed;">
            <i class="bx bx-edit-alt fs-5"></i>
          </span>
          @endif

          {{-- Report --}}
          @if(!$reportBlocked)
          <a href="{{ route('artist.orders.redo.create', $order->id) }}"
            class="text-secondary fw-bold" title="Report">
            <i class="bx bx-error-alt fs-5"></i>
          </a>
          @else
          <span class="text-muted opacity-25"
            data-bs-toggle="tooltip"
            data-bs-placement="top"
            title="Report not available for this status"
            style="cursor: not-allowed;">
            <i class="bx bx-error-alt fs-5"></i>
          </span>
          @endif

        </div>
      </td>
    </tr>
    @empty
    <!-- <tr>
      <td colspan="7" class="text-center text-muted py-4">No matching orders.</td>
    </tr> -->
    @endforelse
  </tbody>
</table>