<style>
  .bg-complete {
    background-color: rgba(154, 252, 172) !important;
    color:rgb(7, 115, 26)
  }

  .bg-awaiting {
    background-color: rgba(255, 200, 148) !important;
    color:rgb(128, 72, 19)
  }
</style>

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
        $showUrl = route('data-entry.orders.show', ['order' => $oid]);
        $isRedo   = !empty($row->redo);                                    
        $year = null;
        if (!empty($row->order_number) && preg_match('/#?ORD-(\d{4})-/', $row->order_number, $m)) {
            $year = $m[1];
        }
        if (!$year) {
            $year = $row->orderDate
                ? \Carbon\Carbon::parse($row->orderDate)->format('Y')
                : \Carbon\Carbon::parse($row->created_at ?? now())->format('Y');
        }

        $displayId = $isRedo
            ? sprintf('#ORD-%s-%03dR', $year, (int) $row->redo)
            : ($row->order_number ?? sprintf('#ORD-%s-%04d', $year, (int) $oid));

        $sortKey = (int) $oid;
        $deadline = $row->deadline ? (\Carbon\Carbon::parse($row->deadline)->format('d/m/Y')) : '-';
        $orderNo  = $row->order_number ?? $row->orderNumber ?? $oid;
        $status = strtolower((string)($row->orderStatus ?? $row->ui_status ?? ''));
        $label  = $status === 'awaiting_keyin' ? 'Awaiting Key-in' : \Illuminate\Support\Str::headline(str_replace('_',' ',$status));
        $badgeClass = match ($status) {
            'awaiting_keyin' => 'bg-awaiting',
            'completed'      => 'bg-complete',
            default          => 'bg-secondary-subtle text-muted',
        };
      @endphp
      <tr data-order-id="{{ $oid }}" data-href="{{ $showUrl }}" class="cursor-pointer">
        <td>{{ $displayId }}</td>
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
              @if(strtolower($row->orderStatus) !== 'completed')
                <a href="{{ route('data-entry.orders.edit', ['order' => $oid]) }}" class="text-secondary fw-bold">
                  <i class="bx bx-edit-alt"></i>
                </a>
              @else
                <span class="text-secondary fw-bold text-muted" title="Editing disabled for completed orders" style="cursor: not-allowed; opacity: 0.5;">
                  <i class="bx bx-edit-alt"></i>
                </span>
              @endif
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
  // ignore dblclicks on interactive elements inside the row
  if (e.target.closest('a, button, input, select, textarea, label, [role="button"]')) return;

  const tr = e.target.closest('tr[data-href]');
  if (tr) window.location = tr.getAttribute('data-href');
});
</script>
