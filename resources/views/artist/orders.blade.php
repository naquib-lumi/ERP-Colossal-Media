@extends('layouts.app')

@section('content')
@push('styles')
<style>
  .bg-light .input-group-text { border-color:#e9ecef; }
  .bg-light .form-control, .bg-light .form-select { border-color:#e9ecef; }
  .bg-light.border { border-color:#e9ecef !important; }

  #ordersFilterForm .form-control,
  #ordersFilterForm .form-select,
  #ordersFilterForm .input-group-text { height: 42px; }

  /* Compact select for entries-per-page */
  #pageLength { min-width: 72px; }

  /* Table look like Figma */
  .table-modern thead th {
    background: #fbfbfc;
    text-transform: uppercase;
    letter-spacing: .04em;
    font-size: .74rem;
    color: #6c7680;
    border-bottom: 1px solid #eceff3 !important;
  }
  .table-modern tbody td { vertical-align: middle; border-color: #f1f3f6 !important; }
  .table-modern tbody tr:hover td { background: #fafbfc; }

  /* Status pills */
  .badge-status { padding: .35rem .6rem; font-weight: 600; border-radius: 999px; font-size: .95rem; }

  /* Tiny icon buttons */
  .btn-icon { --size: 32px; width: var(--size); height: var(--size); display: inline-flex; align-items: center; justify-content: center; padding: 0; border-radius: .5rem; }
  .btn-outline-secondary.btn-icon { border-color: #e5e7eb; }

  /* Datatables */
  div.dataTables_wrapper .dataTables_paginate { float: right; }
  div.dataTables_wrapper .dataTables_info { padding-top: .75rem; color: #6c7680; }
  /* 只隐藏默认 filter（我们用自定义搜索） */
  div.dataTables_wrapper .dataTables_filter { display: none; }

  .d-flex.flex-nowrap>* { flex-shrink: 0; }

  table.dataTable { width: 100% !important; }
  .card-datatable .dataTables_wrapper,
  .card-datatable .dataTables_scroll,
  .card-datatable .dataTables_scrollHead,
  .card-datatable .dataTables_scrollBody { width: 100% !important; }
  .card-datatable .dataTables_wrapper .row { margin: 0; }

  /* 显示并美化 length */
  .dataTables_length { display:flex !important; align-items:center; gap:.5rem; }

  /* 顶部日期控件与表单控件等高 */
  #orders-date-range .form-control,
  #orders-date-range .input-group-text { height:42px; }
</style>
@endpush

<div class="container py-4">
  <h3 class="mb-4">Job Order Overview</h3>

  <!-- Content wrapper -->
  <div class="content-wrapper">
    <div class="container-p-y">

      <!-- Product List Widget -->
      <div class="card mb-6">
        <div class="card-widget-separator-wrapper">
          <div class="card-body card-widget-separator">
            @if(!empty($isHead) && $isHead)
              <div class="row gy-4 gy-sm-1">
                {{-- To assign --}}
                <div class="col-sm-6 col-lg cursor-pointer" data-go-status="to_assign">
                  <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-4 pb-sm-0">
                    <div>
                      <p class="mb-1">To assign</p>
                      <h4 class="mb-1">{{ $metrics['to_assign'] ?? 0 }}</h4>
                    </div>
                    <span class="avatar me-sm-6"><span class="avatar-initial rounded w-px-44 h-px-44"><i class="icon-base bx bx-share-alt icon-lg text-heading"></i></span></span>
                  </div>
                  <hr class="d-none d-sm-block d-lg-none me-6" />
                </div>
                {{-- Assigned --}}
                <div class="col-sm-6 col-lg cursor-pointer" data-go-status="assigned">
                  <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-4 pb-sm-0">
                    <div>
                      <p class="mb-1">Assigned</p>
                      <h4 class="mb-1">{{ $metrics['assigned'] ?? 0 }}</h4>
                    </div>
                    <span class="avatar me-sm-6"><span class="avatar-initial rounded w-px-44 h-px-44"><i class="icon-base bx bx-user-check icon-lg text-heading"></i></span></span>
                  </div>
                  <hr class="d-none d-sm-block d-lg-none me-6" />
                </div>
                {{-- In progress --}}
                <div class="col-sm-6 col-lg cursor-pointer" data-go-status="in_progress">
                  <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0 card-widget-3">
                    <div>
                      <p class="mb-1">In Progress</p>
                      <h4 class="mb-1">{{ $metrics['in_progress'] ?? 0 }}</h4>
                    </div>
                    <span class="avatar p-2 me-sm-6"><span class="avatar-initial rounded w-px-44 h-px-44"><i class="icon-base bx bx-pencil icon-lg text-heading"></i></span></span>
                  </div>
                  <hr class="d-none d-sm-block d-lg-none me-6" />
                </div>
                {{-- Completed --}}
                <div class="col-sm-6 col-lg cursor-pointer" data-go-status="completed">
                  <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0">
                    <div>
                      <p class="mb-1">Completed</p>
                      <h4 class="mb-1">{{ $metrics['completed'] ?? 0 }}</h4>
                    </div>
                    <span class="avatar p-2 me-sm-6"><span class="avatar-initial rounded w-px-44 h-px-44"><i class="icon-base bx bx-check icon-lg text-heading"></i></span></span>
                  </div>
                  <hr class="d-none d-sm-block d-lg-none me-6" />
                </div>
                {{-- Rejected --}}
                <div class="col-sm-6 col-lg cursor-pointer" data-go-status="rejected">
                  <div class="d-flex justify-content-between align-items-start pb-4 pb-sm-0">
                    <div>
                      <p class="mb-1">Rejected</p>
                      <h4 class="mb-1">{{ $metrics['rejected'] ?? 0 }}</h4>
                    </div>
                    <span class="avatar p-2 me-sm-6"><span class="avatar-initial rounded w-px-44 h-px-44"><i class="icon-base bx bx-x-circle icon-lg text-heading"></i></span></span>
                  </div>
                </div>
              </div>
            @else
              {{-- Normal artist --}}
              <div class="row gy-4 gy-sm-1">
                <div class="col-sm-6 col-lg">
                  <div class="d-flex justify-content-between align-items-start card-widget-1 border-end pb-4 pb-sm-0">
                    <div>
                      <p class="mb-1">Total Order</p>
                      <h4 class="mb-1">{{ $metrics['total'] ?? 0 }}</h4>
                    </div>
                    <span class="avatar me-sm-6"><span class="avatar-initial rounded w-px-44 h-px-44"><i class="icon-base bx bx-store-alt icon-lg text-heading"></i></span></span>
                  </div>
                  <hr class="d-none d-sm-block d-lg-none me-6" />
                </div>
                <div class="col-sm-6 col-lg cursor-pointer" data-go-status="pending">
                  <div class="d-flex justify-content-between align-items-start card-widget-2 border-end pb-4 pb-sm-0">
                    <div><p class="mb-1">Pending</p><h4 class="mb-1">{{ $metrics['pending'] ?? 0 }}</h4></div>
                    <span class="avatar me-sm-6"><span class="avatar-initial rounded w-px-44 h-px-44"><i class="icon-base bx bx-laptop icon-lg text-heading"></i></span></span>
                  </div>
                  <hr class="d-none d-sm-block d-lg-none me-6" />
                </div>
                <div class="col-sm-6 col-lg cursor-pointer" data-go-status="in_progress">
                  <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0 card-widget-3">
                    <div><p class="mb-1">In Progress</p><h4 class="mb-1">{{ $metrics['in_progress'] ?? 0 }}</h4></div>
                    <span class="avatar p-2 me-sm-6"><span class="avatar-initial rounded w-px-44 h-px-44"><i class="icon-base bx bx-pencil icon-lg text-heading"></i></span></span>
                  </div>
                  <hr class="d-none d-sm-block d-lg-none me-6" />
                </div>
                <div class="col-sm-6 col-lg cursor-pointer" data-go-status="completed">
                  <div class="d-flex justify-content-between align-items-start border-end pb-4 pb-sm-0">
                    <div><p class="mb-1">Completed</p><h4 class="mb-1">{{ $metrics['completed'] ?? 0 }}</h4></div>
                    <span class="avatar p-2 me-sm-6"><span class="avatar-initial rounded w-px-44 h-px-44"><i class="icon-base bx bx-check icon-lg text-heading"></i></span></span>
                  </div>
                  <hr class="d-none d-sm-block d-lg-none me-6" />
                </div>
                <div class="col-sm-6 col-lg cursor-pointer" data-go-status="rejected">
                  <div class="d-flex justify-content-between align-items-start pb-4 pb-sm-0">
                    <div><p class="mb-1">Rejected</p><h4 class="mb-1">{{ $metrics['rejected'] ?? 0 }}</h4></div>
                    <span class="avatar p-2 me-sm-6"><span class="avatar-initial rounded w-px-44 h-px-44"><i class="icon-base bx bx-x-circle icon-lg text-heading"></i></span></span>
                  </div>
                </div>
              </div>
            @endif
          </div>
        </div>
      </div>

      <!-- Job Orders Table -->
      <div class="card mb-4">
        <div class="card-body">
          <!-- 标题 + 右侧按钮（把日期放到这里的最前面） -->
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <h5 class="mb-0">Job Orders</h5>

            @php $statusVal = request('status', $statusRaw ?? ''); @endphp

            <!-- 右侧按钮容器：新增 id="ordersToolbar" -->
            <div id="ordersToolbar" class="d-flex align-items-center gap-2 flex-wrap">
              <button type="submit" class="btn btn-primary px-4" form="ordersFilterForm">Filter</button>
              <a href="{{ route('artist.orders') }}" class="btn btn-outline-secondary">Reset</a>
              <button id="exportExcel" type="button" class="btn btn-dark">
                <i class="bx bx-export me-1"></i> Export
              </button>
              <a href="{{ route('artist.orders.create') }}" class="btn btn-light text-primary">Add Order</a>
            </div>
          </div>

          <div class="w-100 border rounded-3 px-3 py-3">
            <!-- 第一排：Order ID + Salesperson + Search + Status -->
            <form id="ordersFilterForm" method="GET" action="{{ route('artist.orders') }}">
              <div class="row g-2 align-items-center">
                {{-- Order ID (client-side, column 0 only) --}}
                <div class="col-12 col-lg-2">
                  <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bx bx-hash"></i></span>
                    <input id="orderIdSearch" type="text" class="form-control" placeholder="Search Order ID">
                  </div>
                </div>

                {{-- Salesperson / Artist --}}
                <div class="col-12 col-lg-3">
                  <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bx bx-user"></i></span>
                    <input type="text" name="artist" class="form-control"
                      placeholder="{{ ($isHead ?? false) ? 'Search artist name…' : 'Search salesperson name…' }}"
                      value="{{ request('artist') }}">
                  </div>
                </div>

                {{-- Global order/product search (server) --}}
                <div class="col-12 col-lg-5">
                  <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bx bx-search"></i></span>
                    <input name="q" type="text" class="form-control"
                      placeholder="Search orders or product details" value="{{ request('q') }}">
                  </div>
                </div>

                {{-- All statuses --}}
                <div class="col-12 col-lg-2">
                  <select name="status" class="form-select">
                    <option value="">All statuses</option>
                    @if(!($isHead ?? false))
                      <option value="pending"     {{ $statusVal==='pending' ? 'selected' : '' }}>Pending</option>
                    @endif
                    <option value="in_progress" {{ $statusVal==='in_progress' ? 'selected' : '' }}>In progress</option>
                    <option value="completed"   {{ $statusVal==='completed' ? 'selected' : '' }}>Completed</option>
                    <option value="rejected"    {{ $statusVal==='rejected' ? 'selected' : '' }}>Rejected</option>
                    @if($isHead ?? false)
                      <option value="to_assign" {{ $statusVal==='to_assign' ? 'selected' : '' }}>To assign</option>
                      <option value="assigned"  {{ $statusVal==='assigned'  ? 'selected' : '' }}>Assigned</option>
                    @endif
                  </select>
                </div>

                {{-- Keep deadline sort if set --}}
                @if(request('deadline_sort'))
                  <input type="hidden" name="deadline_sort" value="{{ request('deadline_sort') }}">
                @endif
              </div>
            </form>
          </div>

          <!-- 日期区间（初始隐藏，脚本会移动到按钮区最前面） -->
          <div id="orders-date-range" class="mt-2" style="display:none;">
            <div class="input-group" style="min-width:360px;max-width:520px;">
              <span class="input-group-text bg-white"><i class="bx bx-calendar"></i></span>
              <input type="date" name="from" class="form-control" value="{{ request('from') }}" form="ordersFilterForm">
              <span class="input-group-text">~</span>
              <input type="date" name="to" class="form-control" value="{{ request('to') }}" form="ordersFilterForm">
            </div>
          </div>

          <div class="table-responsive" id="orders-table-wrapper">
            @include('artist.partials.orders-table', ['orders' => $orders])
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

{{-- 把日期块移动到按钮容器里（Filter 前） --}}
<script>
(function () {
  const toolbar   = document.getElementById('ordersToolbar');
  const dateBlock = document.getElementById('orders-date-range');
  if (!toolbar || !dateBlock) return;

  dateBlock.style.display = '';
  dateBlock.classList.add('me-2'); // 与右侧按钮留点间距
  toolbar.insertBefore(dateBlock, toolbar.firstElementChild);
})();
</script>

@push('scripts')
<script>
  const LEN_KEY = 'orders.pageLength.v2';

  function getSavedLen() {
    const raw = localStorage.getItem(LEN_KEY);
    if (raw == null) { localStorage.setItem(LEN_KEY, '5'); return 5; }
    const v = parseInt(raw, 10);
    return [5,10,20,30].includes(v) ? v : 5;
  }

  function bindPageLengthControl(dt) {
    const sel = document.getElementById('pageLength');
    if (!sel || !dt) return;
    sel.value = String(dt.page.len());
    sel.onchange = function () {
      const v = parseInt(sel.value, 10) || 5;
      localStorage.setItem(LEN_KEY, String(v));
      dt.page.len(v).draw();
    };
  }

  (function preloadOrderIdFromUrl(){
    const box = document.getElementById('orderIdSearch');
    if (!box) return;
    const oid = new URLSearchParams(location.search).get('oid') || '';
    box.value = oid;
  })();

  (function persistOidOnSubmit(){
    const form = document.getElementById('ordersFilterForm');
    const box  = document.getElementById('orderIdSearch');
    if (!form || !box) return;
    form.addEventListener('submit', () => {
      let hid = form.querySelector('input[name="oid"]');
      if (!hid) { hid = document.createElement('input'); hid.type='hidden'; hid.name='oid'; form.appendChild(hid); }
      hid.value = box.value.trim();
    });
  })();

  (function syncOidWhileTyping(){
    const box = document.getElementById('orderIdSearch');
    if (!box) return;
    const debounce = (fn, ms=200) => { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; };
    const setQuery = (k,v)=>{
      const u = new URL(location.href);
      if (v) u.searchParams.set(k, v); else u.searchParams.delete(k);
      history.replaceState(null, '', u);
    };
    box.addEventListener('input', debounce(() => { setQuery('oid', box.value.trim()); }, 200));
  })();

  function bindOrderIdFilter(dt) {
    const input = document.getElementById('orderIdSearch');
    if (!input || !dt || !$.fn || !$.fn.dataTable) return;
    let term = (input.value || '').trim().toLowerCase();

    const orderIdFilterFn = function(settings, data) {
      if (!term) return true;
      const col0 = (data[0] || '').toString().toLowerCase();
      return col0.includes(term);
    };
    orderIdFilterFn._orderIdFilter = true;
    $.fn.dataTable.ext.search = $.fn.dataTable.ext.search
      .filter(fn => !fn._orderIdFilter)
      .concat(orderIdFilterFn);

    const debounce = (fn, ms=200) => { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), ms); }; };
    input.addEventListener('input', debounce(function(){
      term = (this.value || '').trim().toLowerCase();
      dt.draw();
    }, 200));

    if (term) dt.draw();
  }

  (function() {
    let dt = null;

    function clearDtFilters() {
      if (!$.fn || !$.fn.dataTable) return;
      $.fn.dataTable.ext.search = $.fn.dataTable.ext.search.filter(fn =>
        !fn._ordersStatusFilter && !fn._orderIdFilter
      );
    }

    if (!window.jQuery) { console.error('jQuery not loaded → DataTables will not init'); return; }

    $(function() {
      try {
        clearDtFilters();

        // 底部工具条包含 l（length）、i（info）、p（paginate）
        dt = $('#jobOrdersTable').DataTable({
          dom: 'Brt<"d-flex justify-content-between align-items-center mt-3"lip>',
          paging: true,
          pageLength: getSavedLen(),
          lengthMenu: [[5,10,20,30],[5,10,20,30]],
          autoWidth: false,
          responsive: true,
          order: [],
          buttons: [{
            extend: 'excel',
            title: 'Job Orders',
            className: 'd-none',
            exportOptions: { columns: [0,1,2,3,4,5] }
          }],
          columnDefs: [
            { targets: -1, orderable: false, searchable: false, className: 'text-end' },
            { targets: '_all', defaultContent: '' }
          ],
          language: {
            lengthMenu: 'Show _MENU_',
            emptyTable: 'No matching records found',
            zeroRecords: 'No matching records found',
            info: 'Showing _START_ to _END_ of _TOTAL_ results',
            infoEmpty: 'Showing 0 to 0 of 0 results',
            paginate: { previous: 'Previous', next: 'Next' }
          },
          drawCallback: function() { this.api().columns.adjust().responsive.recalc(); }
        });

        bindPageLengthControl(dt);
        bindOrderIdFilter(dt);

        // 按 data-status-code 的自定义过滤
        const filterFn = function(settings, data, dataIndex) {
          const desired = (document.getElementById('statusFilter')?.value || '').toLowerCase();
          if (!desired) return true;
          const node = dt.row(dataIndex).node();
          const code = (node.querySelector('td[data-status-code]')?.dataset.statusCode || '').toLowerCase();
          return code === desired;
        };
        filterFn._ordersStatusFilter = true;
        $.fn.dataTable.ext.search.push(filterFn);

        $('#jobSearch').off('keyup.dt').on('keyup.dt', function(){ dt.search(this.value).draw(); });
        $('#statusFilter').off('change.dt').on('change.dt', function(){ dt.draw(); });
        $('#exportExcel').off('click.dt').on('click.dt', function(){ dt.button(0).trigger(); });

        const s = document.getElementById('jobSearch'); if (s && s.value) dt.search(s.value).draw();
        const f = document.getElementById('statusFilter'); if (f && f.value) dt.draw();

        window.addEventListener('resize', () => setTimeout(() => dt.columns.adjust().responsive.recalc(), 100));

      } catch (e) { console.error('Failed to initialize DataTables:', e); }
    });
  })();

  (function() {
    const form = document.getElementById('ordersFilterForm');
    if (!form) return;

    // change 即时提交（文本输入除外）
    form.addEventListener('change', function(e) {
      const el = e.target;
      if (['SELECT', 'INPUT'].includes(el.tagName)) {
        if (el.tagName === 'INPUT' && el.type === 'text') return;
        form.submit();
      }
    });

    // 输入框回车提交（Order ID 输入框除外）
    form.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && e.target.tagName === 'INPUT' && e.target.id !== 'orderIdSearch') {
        form.submit();
      }
    });
  })();

  (function() {
    // 归一化日期到 YYYY-MM-DD
    function toISODateString(val) {
      if (!val) return '';
      if (/^\d{4}-\d{2}-\d{2}$/.test(val)) return val;
      const d = new Date(val); if (isNaN(d.getTime())) return '';
      const yyyy = d.getFullYear();
      const mm = String(d.getMonth()+1).padStart(2,'0');
      const dd = String(d.getDate()).padStart(2,'0');
      return `${yyyy}-${mm}-${dd}`;
    }

    const form = document.getElementById('ordersFilterForm') || document.querySelector('form[action*="artist/orders"]');
    if (!form) return;

    function normalizeDates() {
      const from = form.querySelector('input[name="from"]');
      const to   = form.querySelector('input[name="to"]');
      if (from) { const iso = toISODateString(from.value); if (iso) from.value = iso; }
      if (to)   { const iso = toISODateString(to.value);   if (iso) to.value = iso; }
    }

    form.addEventListener('submit', normalizeDates);
    form.querySelectorAll('input[name="from"], input[name="to"]').forEach(inp => {
      inp.addEventListener('change', function(){ const iso = toISODateString(inp.value); if (iso) inp.value = iso; });
    });
  })();

  (function () {
    const base = "{{ route('artist.orders') }}";
    document.querySelectorAll('[data-go-status]').forEach(function (tile) {
      tile.addEventListener('click', function () {
        const s = tile.getAttribute('data-go-status');
        const url = new URL(base, window.location.origin);
        if (s) url.searchParams.set('status', s);
        window.location.href = url.toString();
      });
    });
  })();
</script>
@endpush
@endsection
