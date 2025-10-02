
<table class="table align-middle">
  <thead>
    <tr>
      <th style="width:110px">Order #</th>
      <th>Title</th>
      <th>Company</th>
      <th style="width:160px">Salesperson</th>
      <th style="width:130px">Status</th>
      <th style="width:130px">Deadline</th>
      <th class="text-end" style="width:110px">Actions</th>
    </tr>
  </thead>
  <tbody>
  @forelse($orders as $row)
    @php
      $oid     = $row->getKey() ?? $row->id ?? $row->OrderID ?? null;
      $status  = $row->ui_status ?? $row->orderStatus ?? '-';
      $label   = \Illuminate\Support\Str::headline(str_replace('_', ' ', (string)$status));
      $deadline= $row->deadline ? (\Carbon\Carbon::parse($row->deadline)->format('d/m/Y')) : '-';
      $orderNo = $row->order_number ?? $row->orderNumber ?? $oid;
    @endphp
    <tr>
      <td>{{ $orderNo }}</td>
      <td class="text-truncate" style="max-width:280px">{{ $row->orderTitle ?? '-' }}</td>
      <td class="text-truncate" style="max-width:220px">{{ $row->companyName ?? '-' }}</td>
      <td>{{ $row->salesperson->name ?? '-' }}</td>
      <td class="text-capitalize">
        @if(($row->submit ?? 0) == 1 && ($row->pending ?? 0) == 1)
          <span class="badge bg-warning text-dark">Pending</span>
        @else
          {{ $label }}
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
          @if($oid && Route::has('data-entry.orders.edit'))
            <a href="{{ route('data-entry.orders.edit', ['order' => $oid]) }}"
               class="text-secondary fw-bold" title="Edit">
              <i class="bx bx-edit-alt fs-5"></i>
            </a>
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
