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
        $oid      = $row->getKey() ?? $row->id ?? $row->OrderID ?? null;
        $status   = strtolower((string)($row->ui_status ?? '-'));
        $label    = $status === '-' ? '-' : Str::headline(str_replace('_',' ', $status));
        $deadline = $row->deadline ? (\Carbon\Carbon::parse($row->deadline)->format('d/m/Y')) : '-';
        $orderNo  = $row->order_number ?? $row->orderNumber ?? $oid;
        $badgeClass = match ($status) {
          'pending'      => 'bg-warning text-dark fw-bold',
          'in_progress'  => 'bg-info',
          'completed'    => 'bg-success',
          'rejected'     => 'bg-danger',
          'assigned', 'to_assign' => 'bg-secondary',
          default        => 'bg-secondary-subtle text-muted',
        };
      @endphp
      <tr>
        <td>{{ $orderNo }}</td>
        <td class="text-truncate">{{ $row->orderTitle ?? '-' }}</td>
        <td class="text-truncate">{{ $row->companyName ?? '-' }}</td>
        <td>{{ $row->salesperson->name ?? '-' }}</td>
        <td class="text-capitalize" data-status-code="{{ $status }}">
          @if($status === '-')-@else <span class="badge {{ $badgeClass }}">{{ $label }}</span>@endif
        </td>
        <td>{{ $deadline }}</td>
        <td class="text-end">
          <div class="d-inline-flex align-items-center gap-3">
            @if($oid)
              <a href="{{ route('data-entry.orders.show', ['order' => $oid]) }}" class="text-secondary fw-bold" title="View">
                <i class="bx bx-show fs-5"></i>
              </a>
              <form action="{{ route('data-entry.orders.begin', ['order' => $oid]) }}" method="POST">
                @csrf @method('PATCH')
                <button type="submit" class="border-0 bg-transparent text-secondary fw-bold" title="Edit">
                  <i class="bx bx-edit-alt fs-5"></i>
                </button>
              </form>
            @endif
          </div>
        </td>
      </tr>
    @empty
    @endforelse
  </tbody>
</table>