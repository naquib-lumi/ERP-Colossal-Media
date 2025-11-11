@extends('layouts.app')
@section('title', 'Job Order')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
  @if(session('success'))
  <div id="successAlert" class="alert alert-secondary alert-dismissible fade show mt-3 border-0 shadow-sm" role="alert" style="background-color:#f8f9fa;color:#6c757d;">
    <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
  @endif

  <script>
    @if (session('success'))
      setTimeout(function(){ $('#successAlert').alert('close'); }, 5000);
    @endif
  </script>

  <div class="card">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 p-3">
      <h5 class="mb-0">Job Orders</h5>

      @php 
        $isHead = Auth::user()->hasRole('head-salesperson');
        $statusVal = request('status', '');
        $selectedSalesperson = request('salesperson');
      @endphp

      <div class="w-100 border rounded-3 px-3 py-3">
  <form id="ordersFilterForm">
    <div class="row g-2 align-items-center">
      {{-- Order ID --}}
      <div class="col-12 col-lg-2">
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="bx bx-hash"></i></span>
          <input id="orderIdSearch" type="text" class="form-control" placeholder="Search Order ID" value="{{ request('order_id', '') }}">
        </div>
      </div>

      {{-- Salesperson (head only) --}}
      @if($isHead)
      <div class="col-12 col-lg-3">
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="bx bx-user"></i></span>
          <select id="salespersonFilter" class="form-select" style="border-left:none;">
            <option value="">All Salespersons</option>
            @foreach(App\Models\User::whereIn('role',['salesperson','head-salesperson'])->get() as $sp)
              <option value="{{ $sp->id }}" {{ $selectedSalesperson == $sp->id ? 'selected' : '' }}>{{ $sp->name }}</option>
            @endforeach
          </select>
        </div>
      </div>
      @endif

      {{-- Global search --}}
      <div class="col-12 col-lg-{{ $isHead ? 3 : 5 }}">
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="bx bx-search"></i></span>
          <input id="globalSearch" type="text" class="form-control" placeholder="Search by company, lead name" value="{{ request('q','') }}">
        </div>
      </div>

      {{-- Date from --}}
      <div class="col-6 col-lg-2">
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="bx bx-calendar"></i></span>
          <input type="date" id="dateFrom" class="form-control" value="{{ request('from','') }}" placeholder="YYYY-MM-DD">
        </div>
      </div>

      {{-- Date to --}}
      <div class="col-6 col-lg-2">
        <div class="input-group">
          <span class="input-group-text bg-white"><i class="bx bx-calendar"></i></span>
          <input type="date" id="dateTo" class="form-control" value="{{ request('to','') }}" placeholder="YYYY-MM-DD">
        </div>
      </div>

      {{-- Status --}}
      <div class="col-12 col-lg-2">
        <select id="statusFilter" class="form-select">
          <option value="">All Status</option>
          <option value="to_assign"   {{ $statusVal === 'to_assign' ? 'selected' : '' }}>To Assign</option>
          <option value="assigned"    {{ $statusVal === 'assigned' ? 'selected' : '' }}>Assigned</option>
          <option value="pending"     {{ $statusVal === 'pending' ? 'selected' : '' }}>Pending</option>
          <option value="in_progress" {{ $statusVal === 'in_progress' ? 'selected' : '' }}>In Progress</option>
          <option value="completed"   {{ $statusVal === 'completed' ? 'selected' : '' }}>Completed</option>
          <option value="rejected"    {{ $statusVal === 'rejected' ? 'selected' : '' }}>Rejected</option>
        </select>
      </div>

      {{-- Actions --}}
      <div class="col-12 col-lg d-flex gap-2 justify-content-lg-end">
        <button id="exportExcel" type="button" class="btn btn-dark">
          <i class="bx bx-export me-1"></i> Export
        </button>
        <a href="{{ route('sales.orders') }}" class="btn btn-outline-secondary">Reset</a>
        <a href="{{ route('orders.create') }}" class="btn btn-light text-primary">Add Order</a>
      </div>
    </div>
  </form>
</div>
    </div>

    <div class="card-datatable table-responsive p-3">
      <table class="datatables-ajax table table-hover" id="orderTable" style="width:100%;">
        <thead class="table-light sticky-top">
          <tr>
            <th>Order ID</th>
            <th>Order Name</th>
            <th>Company Info</th>
            <th>Lead Details</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
      </table>
    </div>
  </div>
</div>

{{-- Products Modal --}}
<div class="modal fade" id="productsModal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Product Details</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <table class="table" id="productsTable">
          <thead><tr><th>Name</th><th>Quantity</th><th>Remark</th><th>Material</th><th>Location</th><th>Date Time</th></tr></thead>
          <tbody></tbody>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

{{-- 底部条样式（可保持不动） --}}
<style>
  .dt-bottom .dataTables_length label { margin: 0; }
  .dt-bottom .dataTables_length select { margin: 0 .25rem 0 0; }
  .dt-bottom .dataTables_info { margin: 0 !important; }
  .dt-bottom .dataTables_paginate { margin: 0; }
  .dt-bottom .dataTables_length select#entriesPerPage { width: 92px; }
</style>

<script>
$(function () {
  const LEN_KEY = 'orders_length';
  const defaultLen = parseInt(localStorage.getItem(LEN_KEY) || '10', 10);

  let table = $('#orderTable').DataTable({
    processing: true,
    serverSide: true,
    searching: false,

    // 🔁 把左侧顺序调成：length 在前，info 在后
    dom: 'rt<"dt-bottom d-flex justify-content-between align-items-center mt-3"<"dt-left d-flex align-items-center gap-3"l i><"dt-right"p>>',

    language: {
      lengthMenu: '_MENU_ entries per page',
      info: 'Showing _START_ to _END_ of _TOTAL_ entries'
    },

    pageLength: defaultLen,

    ajax: {
      url: '{{ route('orders.get') }}',
      type: 'POST',
      data: function(d){
        d._token   = '{{ csrf_token() }}';
        d.order_id = $('#orderIdSearch').val();
        d.q        = $('#globalSearch').val();
        @if($isHead)
          d.salesperson = $('#salespersonFilter').val();
        @else
          d.lead_name   = $('#leadSearch').val();
        @endif
        d.from    = $('#dateFrom').val();
        d.to      = $('#dateTo').val();
        d.status  = $('#statusFilter').val();
        return d;
      }
    },

    columns: [
      { data: 'order_id' },
      { data: 'order_name' },
      { data: 'company_info' },
      { data: 'lead_details' },
      { data: 'status' },
      { data: 'actions' }
    ],
    order: [[0, 'desc']],

    initComplete: function () {
      $('#orderIdSearch, #globalSearch, #dateFrom, #dateTo').on('keyup change', () => table.draw());
      @if(!$isHead)
        $('#leadSearch').on('keyup', () => table.draw());
      @endif
      @if($isHead)
        $('#salespersonFilter').on('change', () => table.draw());
      @endif
      $('#statusFilter').on('change', () => table.draw());
    },

    drawCallback: function () {
      const api = this.api();
      localStorage.setItem(LEN_KEY, String(api.page.len()));
      const $sel = $('.dt-bottom .dataTables_length select');
      $sel.addClass('form-select form-select-sm').attr('id','entriesPerPage');
    }
  });

  // Double-click row to view order
  $(document).on('dblclick', '#orderTable tbody tr', function () {
    if ($(this).hasClass('child')) return; // Skip if child row
    let rowData = table.row(this).data();
    let id = rowData.id;
    window.location.href = `/orders/${id}`;
  });

  // 查看产品
  $(document).on('click', '.view-products', function () {
    let id = $(this).data('id');
    $.get('/orders/' + id + '/products', function (data) {
      let tbody = $('#productsTable tbody');
      tbody.empty();
      data.forEach(product => {
        tbody.append(`<tr>
          <td>${product.product_name}</td>
          <td>${product.quantity}</td>
          <td>${product.remark || ''}</td>
          <td>${product.material_info || ''}</td>
          <td>${product.location || ''}</td>
          <td>${product.date_time || ''}</td>
        </tr>`);
      });
      $('#productsModal').modal('show');
    });
  });

  // 提交订单
  $(document).on('click', '.submit-order', function () {
    let id = $(this).data('id');
    $.post('/orders/' + id + '/submit', { _token: '{{ csrf_token() }}' }, function () {
      table.ajax.reload();
    });
  });

  // 导出
  $('#exportExcel').on('click', function () {
    const params = new URLSearchParams({
      order_id: $('#orderIdSearch').val(),
      q: $('#globalSearch').val(),
      @if($isHead)
        salesperson: $('#salespersonFilter').val(),
      @else
        lead_name: $('#leadSearch').val(),
      @endif
      from: $('#dateFrom').val(),
      to: $('#dateTo').val(),
      status: $('#statusFilter').val()
    });
    window.location.href = '{{ route("orders.export") }}?' + params.toString();
  });
});
</script>
@endsection