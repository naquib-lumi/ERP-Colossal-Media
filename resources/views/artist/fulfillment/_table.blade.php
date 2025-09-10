<div class="table-responsive">
  <table id="ff-table" class="table table-modern w-100">
    <thead>
      <tr>
        <th>Product ID</th>
        <th>Product Name</th>
        <th>Task Type</th>
        <th>Deadline</th>
        <th>Status</th>
        <th>Delivery Date</th>
        <th>Delivery Location</th>
        <th class="text-end">Action</th>
      </tr>
    </thead>
    <tbody>
    @forelse ($rows as $r)
      @php
        // Prefer values precomputed by the controller/SQL:
        $showR = (int)($r->show_r ?? 0) === 1
                 ? true
                 // fallback if controller hasn't been updated yet
                 : ((isset($r->redoOf) && $r->redoOf) && (int)($r->editable ?? 0) === 1);

        $oid = $r->oid_for_display
              ?? $r->order_id_for_display
              ?? $r->order_id_current
              ?? $r->order_id
              ?? null;

        $pid = $r->pid_for_display
              ?? ($r->redoOf ?? null)
              ?? ($r->pid ?? $r->ProductID ?? null);

        $code = $r->code
              ?? ($oid && $pid ? sprintf('#ORD-%s-P%04d%s', $oid, $pid, $showR ? 'R' : '') : '');
      @endphp

      <tr>
        <td>{{ $code }}</td>
        <td>{{ $r->name }}</td>
        <td>{{ $r->task }}</td>
        <td>{{ $r->deadline }}</td>

        <td>
          <span class="badge-pill
            {{ $r->status === 'completed' ? 'badge-completed' :
               ($r->status === 'rejected' ? 'badge-rejected' :
               ($r->status === 'pending'  ? 'badge-pending'  : 'badge-progress')) }}">
            {{ $r->status }}
          </span>
        </td>

        {{-- Delivery date & time --}}
        <td>
          @if(!empty($r->deliv_date))
            {{ $r->deliv_date }}@if(!empty($r->deliv_time)) {{ $r->deliv_time }}@endif
          @else
            <span></span>
            <i class="bx bx-error-circle text-warning" title="Missing delivery date"></i>
          @endif
        </td>

        {{-- Delivery location --}}
        <td>
          @if(!empty($r->deliv_loc))
            {{ $r->deliv_loc }}
          @else
            <span></span>
            <i class="bx bx-error-circle text-warning" title="Missing delivery location"></i>
          @endif
        </td>

        <td class="text-end">
          <a href="{{ route('artist.fulfillment.product.show', $r->id ?? $r->pid ?? $r->ProductID) }}" class="text-secondary" title="View">
            <i class="bx bx-show fs-5"></i>
          </a>
          <a href="{{ $r->edit_url }}" class="text-secondary" title="Edit">
            <i class="bx bx-edit-alt fs-5"></i>
          </a>
          <a href="{{ $r->assign_url }}" class="text-secondary" title="Report">
            <i class="bx bx-error-alt fs-5"></i>
          </a>
        </td>
      </tr>
    @empty
      {{-- Optional: empty state --}}
    @endforelse
    </tbody>
  </table>
</div>
