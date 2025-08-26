@extends('layouts.app')

@section('title', 'Fulfillment Overview')

@section('content')

@push('styles')
<style>
  .badge-pill {border-radius:999px;padding:.28rem .6rem;font-weight:600;font-size:.74rem}
  .badge-progress{background:#eef2ff;color:#4338ca}
  .badge-completed{background:#ecfdf5;color:#047857}
  .badge-rejected{background:#fef2f2;color:#b91c1c}
  .badge-pending{background:#fff7ed;color:#b45309}
  .toolbar .form-select, .toolbar .input-group{height:38px}
</style>
@endpush
@php
  $filters = [
    'q'      => request('q', ''),
    'task'   => request('task', ''),
    'status' => request('status', ''),
  ];
@endphp
<div class="container py-4">
  <h3 class="mb-3">Fulfillment Overview</h3>

  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex flex-wrap toolbar gap-2 align-items-center">
        <div class="input-group" style="max-width:320px">
          <span class="input-group-text bg-white border-end-0"><i class="bx bx-search"></i></span>
          <input id="ff-search" type="text" class="form-control border-start-0"
                 placeholder="Search by Product ID or Job Title">
        </div>

        <select id="ff-task" class="form-select w-auto">
            <option value="">All Task Types</option>
            @foreach($taskTypes as $t)
                <option value="{{ $t }}" {{ ($filters['task'] ?? '') === $t ? 'selected' : '' }}>
                {{ $t }}
                </option>
            @endforeach
        </select>

        <select id="ff-status" class="form-select w-auto">
            <option value="">All Statuses</option>
            @foreach($statuses as $s)
                <option value="{{ $s }}" {{ ($filters['status'] ?? '') === $s ? 'selected' : '' }}>
                {{ $s }}
                </option>
            @endforeach
        </select>
      </div>
    </div>
  </div>

  <div id="ff-table-wrap" class="card">
    <div class="card-body">
      @include('artist.fulfillment._table', ['rows' => $rows])
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const wrap   = document.getElementById('ff-table-wrap');
  const search = document.getElementById('ff-search');
  const task   = document.getElementById('ff-task');
  const status = document.getElementById('ff-status');

  // init from URL
  const qs = new URLSearchParams(location.search);
  search.value = qs.get('q')      || '';
  task.value   = qs.get('task')   || '';
  status.value = qs.get('status') || '';

  function currentParams(){
    const p = new URLSearchParams();
    if (search.value.trim()) p.set('q', search.value.trim());
    if (task.value)          p.set('task', task.value);
    if (status.value)        p.set('status', status.value);
    // tell controller to return only the table fragment
    p.set('partial', '1');
    return p;
  }

  function syncUrlAndExport(){
    const u = new URL(location.href);
    const p = new URLSearchParams(currentParams().toString());
    p.delete('partial'); // keep URL clean
    u.search = p.toString();
    history.replaceState(null,'', u.pathname + (u.search ? '?' + u.search : ''));

    const exp = document.getElementById('ff-export');
    if (exp){
      const eu = new URL(exp.href, location.origin);
      eu.search = p.toString();
      exp.href = eu.toString();
    }
  }

  function buildUrl(){
    const base = @json(route('artist.fulfillment.index'));
    const url  = new URL(base, location.origin);
    const p    = currentParams();
    url.search = p.toString();
    return url.toString();
  }

  async function load(){
    syncUrlAndExport();
    wrap.style.opacity = .6;
    const res  = await fetch(buildUrl(), { headers: { 'X-Requested-With':'XMLHttpRequest' } });
    const html = await res.text();
    // inject only the partial into the existing card body
    const body = wrap.querySelector('.card-body');
    body.innerHTML = html;
    wrap.style.opacity = 1;
    initDT();
  }

  const debounce = (fn,ms=300)=>{ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; };
  search.addEventListener('input', debounce(load, 300));
  task  .addEventListener('change', load);
  status.addEventListener('change', load);

  // DataTables init (id inside the partial)
function initDT(){
  if (!window.jQuery || !$.fn.DataTable) return;
  const $t = $('#ff-table');

  if ($.fn.dataTable.isDataTable($t)) {
    $t.DataTable().destroy();
  }

  $t.DataTable({
    dom: '<"d-flex justify-content-between align-items-center"lB>rt<"d-flex justify-content-between align-items-center"ip>',
    paging: true,
    pageLength: 5,
    order: [],
    autoWidth: false,
    responsive: true,
    buttons: [
      // your buttons if any
    ],
    // IMPORTANT: fill missing cells automatically so one short row won't explode
    columnDefs: [
      { targets: '_all', defaultContent: '' },
      { targets: -1, orderable: false, searchable: false, className: 'text-end' }
    ],
    language: {
      emptyTable: 'No results',
      zeroRecords: 'No results',
      info: 'Showing _START_ to _END_ of _TOTAL_ results',
      paginate: { previous: 'Previous', next: 'Next' }
    },
    drawCallback: function(){ this.api().columns.adjust().responsive.recalc(); }
  });
}

  initDT();
})();
</script>

@endpush
