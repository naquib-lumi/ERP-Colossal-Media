<table id="jobOrdersTable" class="table align-middle table-modern">

  <thead>
    <tr>
      <th>Order ID</th>
      <th>Title</th>
      <th>Company</th>
      <th>Salesperson</th>
      <th>Status</th>
      <th>Deadline</th>
      <th class="text-end">Actions</th>
    </tr>
  </thead>
  <tbody>
    @forelse($orders as $row)
    @php
    $oid = $row->getKey() ?? $row->id ?? $row->OrderID ?? null;
    $status = strtolower((string)($row->ui_status ?? '-'));
    $label  = $status === '-' ? '-' : Str::headline(str_replace('_',' ', $status));
    $deadline= $row->deadline ? (\Carbon\Carbon::parse($row->deadline)->format('d/m/Y')) : '-';
    $orderNo = $row->order_number ?? $row->orderNumber ?? $oid;

    $badgeClass = match ($status) {
        'pending'      => 'bg-warning text-dark fw-bold',   // yellow
        'in_progress'  => 'bg-info',      // light blue
        'completed'    => 'bg-success',             // green (kept in case you add it later)
        'rejected'     => 'bg-danger',              // red (kept in case you add it later)
        'assigned', 'to_assign' => 'bg-secondary',  // gray (kept in case you add them later)
        default        => 'bg-secondary-subtle text-muted', // for '-' or anything else
    };
    @endphp
    <tr>
      {{-- @php dump(['ui_status' => $row->ui_status, 'pending' => $row->pending, 'draft' => $row->draft, 'submit' => $row->submit, 'data_entry_id' => $row->data_entry_id, 'orderStatus' => $row->orderStatus]) @endphp --}}

      <td>{{ $orderNo }}</td>
      <td class="text-truncate">{{ $row->orderTitle ?? '-' }}</td>
      <td class="text-truncate">{{ $row->companyName ?? '-' }}</td>
      <td>{{ $row->salesperson->name ?? '-' }}</td>
      <td class="text-capitalize" data-status-code="{{ $status }}">
        @if($status === '-')
          -
        @else
          <span class="badge {{ $badgeClass }}">{{ $label }}</span>
        @endif
      </td>
      <td>{{ $deadline }}</td>
      <td class="text-end">
        <div class="d-inline-flex align-items-center gap-3">
          @if($oid)
          <a href="{{ route('data-entry.orders.show', ['order' => $oid]) }}"
            class="text-secondary fw-bold" title="View">
            <i class="bx bx-show fs-5"></i>
          </a>
          @endif
          @if($oid)
          <form action="{{ route('data-entry.orders.begin', ['order' => $oid]) }}" method="POST">
            @csrf
            @method('PATCH')
            <button type="submit" class="border-0 bg-transparent text-secondary fw-bold" title="Edit">
              <i class="bx bx-edit-alt fs-5"></i>
            </button>
          </form>
          @endif

        </div>
      </td>
    </tr>
    @empty
    <tr>
      <td colspan="8" class="text-center text-muted py-4">No orders found</td>
    </tr>
    @endforelse
  </tbody>
</table>