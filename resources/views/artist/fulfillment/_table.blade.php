<style>
  #fulfillTable_wrapper .dataTables_length {
    display: block !important;
  }

  .badge.bg-purple {
    background: #6f42c1;
  }
</style>
<div class="table-responsive">
  <table class="table table-modern table-hover w-100" id="fulfillTable">
    <thead>
      <tr>
        <th>Product ID</th>
        <th>Product Name</th>
        <th>Task Type</th>
        <th>Deadline</th>
        <th>Status</th>
        @php
        $current = request('deliv_sort', '');
        $next = $current === 'nearest' ? 'furthest' : 'nearest';
        $q = request()->all(); $q['deliv_sort'] = $next;
        @endphp

        <th>
          <a href="{{ route('artist.fulfillment.index', $q) }}" class="text-decoration-none">
            Delivery Date {!! $current === 'nearest' ? '↑' : ($current === 'furthest' ? '↓' : '') !!}
          </a>
        </th>
        <th>Delivery Location</th>
        <th class="text-end">Action</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($rows as $r)
      <tr style="cursor:pointer" data-href="{{ $r->view_url }}" ondblclick="location.href=this.dataset.href">
        <td>{{ $r->code }}</td>
        <td>{{ $r->name }}</td>

        {{-- TASK TYPE (friendly label) --}}
        @php
        $taskLabel = match (strtolower($r->task)) {
        'delivery' => 'Dispatch Control',
        'installation' => 'Delivery & Installation',
        default => \Illuminate\Support\Str::of($r->task ?? '')->replace('_',' ')->title(),
        };

        $taskClass = match (strtolower($taskLabel)) {
        'printing' => 'bg-secondary',
        'furnishing' => 'bg-purple',
        'dispatch control' => 'bg-warning text-dark',
        'delivery & installation' => 'bg-primary',
        default => 'bg-light text-dark',
        };

        $statusLabel = \Illuminate\Support\Str::of($r->status ?? '')->replace('_',' ')->title();
        $statusClass = match (strtolower($r->status)) {
        'completed' => 'bg-success',
        'rejected' => 'bg-danger',
        'in_progress' => 'bg-info',
        default => 'bg-light text-dark',
        };
        @endphp

        <td><span class="badge {{ $taskClass }}">{{ $taskLabel }}</span></td>
        <td>{{ $r->deadline ?: '-' }}</td>
        <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
        <td>{{ $r->deliv_date ?: '-' }}</td>
        <td>{{ $r->deliv_loc ?: '-' }}</td>

        <td class="text-end">
          <a class="text-secondary me-2" title="View" href="{{ $r->view_url }}"><i class="bx bx-show fs-5"></i></a>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>
<script>
  document.querySelectorAll('#fulfillTable tbody tr').forEach(tr => {
    tr.addEventListener('dblclick', () => {
      const url = tr.getAttribute('data-href');
      if (url) window.location = url;
    });
  });

  $(function() {
    const dt = $('#fulfillTable').DataTable({
      dom: '<"d-flex justify-content-between align-items-center"lB>rt<"d-flex justify-content-between align-items-center"ip>',
      paging: true,
      pageLength: 10,
      lengthMenu: [
        [10, 20, 30, 50, 100],
        [10, 20, 30, 50, 100]
      ],
      order: [], // server handles delivery sort via ?deliv_sort
      autoWidth: false,
      responsive: true,
      buttons: [{
        extend: 'excel',
        className: 'd-none',
        title: 'Fulfillment'
      }]
    });
  });
</script>