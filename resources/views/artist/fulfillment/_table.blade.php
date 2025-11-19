<style>
  #fulfillTable_wrapper .dataTables_length {
    display: block !important;
  }

  .badge.bg-purple {
    background: #6f42c1;
  }

  .table-actions {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 12px; /* 图标之间的间距 */
  }
  .table-actions a {
    color: #6b7280; /* 默认灰色 */
    transition: all 0.15s ease;
  }
  .table-actions a:hover {
    color: #111827; /* hover 更深色 */
    opacity: 0.85;
  }
  .table-actions i {
    font-size: 1.25rem; /* 适中大小 */
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
        $taskKey = strtolower($r->task ?? '');

        $taskLabel = match ($taskKey) {
          'delivery', 'dispatch_control'       => 'Dispatch Control',
          'installation', 'delivery_installation' => 'Delivery & Installation',
          default => \Illuminate\Support\Str::of($r->task ?? '')->replace('_',' ')->title(),
        };

        $taskClass = match (strtolower($taskLabel)) {
          'printing'                 => 'bg-secondary',
          'furnishing'               => 'bg-purple',
          'dispatch control'         => 'bg-warning text-dark',
          'delivery & installation'  => 'bg-primary',
          default                    => 'bg-light text-dark',
        };

        $statusLabel = \Illuminate\Support\Str::of($r->status ?? '')->replace('_',' ')->title();
        $statusClass = match (strtolower($r->status)) {
          'completed'   => 'bg-success',
          'rejected'    => 'bg-danger',
          'in_progress' => 'bg-info',
          default       => 'bg-light text-dark',
        };
        @endphp

        <td><span class="badge {{ $taskClass }}">{{ $taskLabel }}</span></td>
        <td>{{ $r->deadline ?: '-' }}</td>
        <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
        <td>{{ $r->deliv_date ?: '-' }}</td>
        <td>{{ $r->deliv_loc ?: '-' }}</td>

        <td>
          <div class="table-actions">
            <a title="View" href="{{ $r->view_url }}"><i class="bx bx-show"></i></a>
            <a title="Report" href="{{ $r->assign_url }}"><i class="bx bx-error-alt fs-5"></i></a>
          </div>
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

  $(function () {
    $('#fulfillTable').DataTable({
      dom: '<"d-flex justify-content-between align-items-center"B>rt<"d-flex justify-content-between align-items-center"lip>',
      paging: true,
      pageLength: 10,
      lengthMenu: [[10,20,30,50,100],[10,20,30,50,100]],
      order: [],
      autoWidth: false,
      responsive: true,
      buttons: [{
        extend: 'excel',
        className: 'd-none',
        title: 'Fulfillment'
      }],
      language: {
        lengthMenu: ' _MENU_ entries per page',
        info: 'Showing _START_ to _END_ of _TOTAL_ entries',
        infoEmpty: 'Showing 0 to 0 of 0 entries',
        zeroRecords: 'No matching records found',
        paginate: { previous: 'Previous', next: 'Next' }
      },
      drawCallback: function() {
        this.api().columns.adjust().responsive.recalc();
      }
    });
  });
</script>