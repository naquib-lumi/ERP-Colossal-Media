<table id="jobOrdersTable" class="table table-modern table-hover w-100">
  @php $isHead = auth()->user()->role === 'head-artist'; @endphp
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
    @php
    $rawStatus = $order->orderStatus; // e.g. 'in_progress'
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
    @endphp
    <tr>
      <td>#ORD-{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</td>
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
      <td>{{ $deadline }}</td>
      <td class="text-end">
        <a href="{{ route('artist.orders.edit', $order->id) }}"
          class="btn btn-outline-secondary btn-icon" title="Edit">
          <i class="bx bx-edit-alt"></i>
        </a>
      </td>
    </tr>
    @empty
    <tr>
      <td colspan="7" class="text-center text-muted py-4">No matching orders.</td>
    </tr>
    @endforelse
  </tbody>
</table>