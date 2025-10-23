@extends('layouts.app')

@section('title', 'Job Order')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
  :root{
    --bg:#F9FAFB; --card:#FFFFFF; --border:#E5E7EB; --thead:#F9FAFB;
    --text:#101828; --muted:#667085; --chip:#F2F4F7;
    --shadow:0 3px 10px rgba(16,24,40,.06);
    --primary:#4F46E5; --primary-hover:#4338CA;
    --success:#16A34A; --danger:#DC2626;
  }
  body{background:var(--bg);}
  .page-wrap{max-width:1200px;margin:0 auto}

  /* ===== Card ===== */
  .jo-card{background:var(--card);border:1px solid var(--border);border-radius:16px;box-shadow:var(--shadow);overflow:hidden}
  .jo-head{
    display:flex;flex-wrap:wrap;gap:10px;
    align-items:center;justify-content:space-between;
    padding:14px 16px;border-bottom:1px solid var(--border);
  }
  .jo-title{margin:0;font-weight:800;color:var(--text);font-size:18px}

  /* 右上角筛选控件（放在标题右侧） */
  .jo-head-controls{display:flex;flex-wrap:wrap;gap:8px;align-items:center}
  .control{height:38px;border:1px solid var(--border);border-radius:10px;background:#fff;outline:none;color:var(--text);font-size:14px}
  .control.input{padding:0 12px;min-width:260px}
  .control.select{
    padding:0 36px 0 12px;min-width:170px;appearance:none;padding-right:42px;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16'%3E%3Cpath fill='%23667085' d='M4.47 6.97a.75.75 0 0 1 1.06 0L8 9.44l2.47-2.47a.75.75 0 0 1 1.06 1.06l-3 3a.75.75 0 0 1-1.06 0l-3-3a.75.75 0 0 1 0-1.06Z'/%3E%3C/svg%3E");
    background-repeat:no-repeat;background-position:right 12px center;
  }

  /* ===== DataTable 外观 ===== */
  .table-wrap{padding:0 16px 12px}
  table.dataTable{width:100%!important}
  thead.sticky-top{position:sticky;top:0;z-index:2}
  .table thead th{background:var(--thead);color:#344054;font-weight:600;letter-spacing:.02em}
  .table>:not(caption)>*>*{padding:14px 16px;vertical-align:middle}
  .table tbody tr+tr td{border-top:1px solid #EEF2F7}
  .table tbody tr:hover{background:#F9FAFB}

  /* 状态徽章兜底 */
  .badge-status{display:inline-block;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:700}
  .status-completed{background:rgba(22,163,74,.12);color:#16A34A}
  .status-in_progress{background:rgba(59,130,246,.12);color:#2563EB}
  .status-pending{background:#F2F4F7;color:#344054}
  .status-assigned{background:rgba(234,179,8,.15);color:#B45309}
  .status-to_assign{background:#E5E7EB;color:#374151}
  .status-rejected{background:rgba(220,38,38,.12);color:#DC2626}

  /* 行操作 */
  .row-actions{display:flex;justify-content:flex-end;gap:8px}
  .icon-btn{width:34px;height:34px;border:1px solid var(--border);border-radius:10px;background:#fff;color:#475467;display:grid;place-items:center}
  .icon-btn:hover{background:#F2F4F7}
  .icon-btn .bi{font-size:16px}

  /* DataTables：隐藏默认 filter；length 放底部 */
  div.dataTables_wrapper .dataTables_filter{display:none}
  .dt-footer{padding:8px 16px}

  @media (max-width: 992px){
    .control.input{min-width:220px}
  }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
  <div class="page-wrap">
    <div class="jo-card">
      <!-- Header（右侧放搜索与状态） -->
      <div class="jo-head">
        <h5 class="jo-title">Job Orders</h5>

        <form class="jo-head-controls" onsubmit="return false;">
          <input type="text" class="control input" id="globalSearch" placeholder="Search by company, lead name">
          <select class="control select" id="statusFilter">
            <option value="">All Status</option>
            <option value="to_assign">To Assign</option>
            <option value="assigned">Assigned</option>
            <option value="pending">Pending</option>
            <option value="in_progress">In Progress</option>
            <option value="completed">Completed</option>
            <option value="rejected">Rejected</option>
          </select>
          @if (Auth::user()->hasRole('head-salesperson'))
            <select class="control select" id="salespersonFilter">
              <option value="">All Salespersons</option>
              @foreach (App\Models\User::role('salesperson')->get() as $sp)
                <option value="{{ $sp->id }}">{{ $sp->name }}</option>
              @endforeach
            </select>
          @endif
        </form>
      </div>

      <!-- Table -->
      <div class="table-wrap">
        <div class="card-datatable table-responsive">
          <table id="orderTable" class="table table-hover align-middle w-100">
            <thead class="table-light sticky-top">
              <tr>
                <th>Order ID</th>
                <th>Order Name</th>
                <th>Company Info</th>
                <th>Lead Details</th>
                <th>Status</th>
                <th class="text-end">Actions</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>

      <!-- Footer（DataTables：length + info + paginate） -->
      <div class="dt-footer border-top"></div>
    </div>
  </div>
</div>

<!-- Product Details Modal -->
<div class="modal fade" id="productsModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content" style="border-radius:14px">
      <div class="modal-header">
        <h5 class="modal-title">Product Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table class="table" id="productsTable">
          <thead>
            <tr>
              <th>Name</th><th>Quantity</th><th>Remark</th><th>Material</th><th>Location</th><th>Date Time</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
  const debounce = (fn, ms = 300) => { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; };

  $(function () {
    const table = $('#orderTable').DataTable({
      processing: true,
      serverSide: true,
      ajax: {
        url : '{{ route('admin.orders.data') }}',
        type: 'POST',
        data: function(d) {
          d._token      = '{{ csrf_token() }}';
          d.search      = { value: $('#globalSearch').val() };
          d.status      = $('#statusFilter').val();
          d.salesperson = $('#salespersonFilter').val();
          return d;
        }
      },
      dom: 'rt<"d-flex justify-content-between align-items-center dt-footer"lip>',
      lengthMenu: [[10,20,30,50,100],[10,20,30,50,100]],
      pageLength: 10,
      order: [[0,'desc']],
      autoWidth: false,
      columns: [
        { data: 'order_id' },
        { data: 'order_name' },
        { data: 'company_info' },
        { data: 'lead_details' },
        {
          data: 'status',
          render: function (data) {
            if (/<\/?span|class=/.test(data||'')) return data;
            const cls = {
              completed:'status-completed', in_progress:'status-in_progress', pending:'status-pending',
              assigned:'status-assigned', to_assign:'status-to_assign', rejected:'status-rejected'
            }[(data||'').toLowerCase()] || 'status-pending';
            const label=(data||'').toString().replace(/_/g,' ').replace(/\b\w/g,s=>s.toUpperCase());
            return `<span class="badge-status ${cls}">${label||'-'}</span>`;
          }
        },
        {
          data: 'actions',
          className: 'text-end',
          render: function(data, type, row){
            if (/<button|<a|bi-/.test(data||'')) return data;
            const id = row.id || row.order_id || '';
            return `
              <div class="row-actions">
                <button class="icon-btn view-products" data-id="${id}" title="View products"><i class="bi bi-eye"></i></button>
                <button class="icon-btn submit-order" data-id="${id}" title="Submit order"><i class="bi bi-send"></i></button>
              </div>`;
          }
        }
      ],
      language:{
        lengthMenu:'Show _MENU_',
        info:'Showing _START_ to _END_ of _TOTAL_ results',
        infoEmpty:'Showing 0 to 0 of 0 results',
        zeroRecords:'No matching records found',
        processing:'Loading...',
        paginate:{ previous:'Previous', next:'Next' }
      },
      drawCallback: function(){ this.api().columns.adjust(); }
    });

    $('#statusFilter, #salespersonFilter').on('change', ()=> table.draw());
    $('#globalSearch').on('input', debounce(()=> table.draw(), 250));

    // 查看产品
    $(document).on('click', '.view-products', function(){
      const id = $(this).data('id');
      $.get('/orders/'+id+'/products', function(list){
        const $tb = $('#productsTable tbody').empty();
        (list||[]).forEach(p=>{
          $tb.append(`<tr>
            <td>${p.product_name}</td><td>${p.quantity}</td><td>${p.remark||''}</td>
            <td>${p.material_info||''}</td><td>${p.location||''}</td><td>${p.date_time||''}</td>
          </tr>`);
        });
        $('#productsModal').modal('show');
      });
    });

    // 提交订单
    $(document).on('click', '.submit-order', function(){
      const id = $(this).data('id');
      $.post('/orders/'+id+'/submit', {_token:'{{ csrf_token() }}'})
        .done(()=> table.ajax.reload(null,false));
    });
  });
</script>
@endsection
