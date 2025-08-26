<div class="table-responsive">
    @php
        $COLS = 8;  // Product ID, Product Name, Task Type, Deadline, Status, Delivery Date, Delivery Location, Action
    @endphp
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
    <tr>
      <td>{{ $r->code }}</td>
      <td>{{ $r->name }}</td>
      <td>{{ $r->task }}</td>
      <td>{{ $r->deadline }}</td>
      <td>
        <span class="badge-pill
          {{ $r->status === 'completed' ? 'badge-completed' :
             ($r->status === 'rejected' ? 'badge-rejected' :
             ($r->status === 'pending' ? 'badge-pending' : 'badge-progress')) }}">
          {{ $r->status }}
        </span>
      </td>

      {{-- Delivery Date (one per row) --}}
      <td>
        @if($r->deliv_date)
          {{ $r->deliv_date }}@if($r->deliv_time) {{ ' ' . $r->deliv_time }} @endif
        @else
          <span></span> <i class="bx bx-error-circle text-warning" title="Missing delivery date"></i>
        @endif
      </td>

      {{-- Delivery Location (one per row) --}}
      <td>
        @if($r->deliv_loc)
          {{ $r->deliv_loc }}
        @else
          <span></span> <i class="bx bx-error-circle text-warning" title="Missing delivery location"></i>
        @endif
      </td>

      <td class="text-end">
        <a href="{{ $r->show_url }}" class="text-secondary" title="View">
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
  @endforelse
  </tbody>
</table>
</div>
