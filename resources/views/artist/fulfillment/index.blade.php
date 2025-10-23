@extends('layouts.app')

@section('title', 'Fulfillment Overview')

@section('content')

@push('styles')
<style>
  .badge-pill {
    border-radius: 999px;
    padding: .28rem .6rem;
    font-weight: 600;
    font-size: .74rem
  }

  .badge-progress {
    background: #eef2ff;
    color: #4338ca
  }

  .badge-completed {
    background: #ecfdf5;
    color: #047857
  }

  .badge-rejected {
    background: #fef2f2;
    color: #b91c1c
  }

  .badge-pending {
    background: #fff7ed;
    color: #b45309
  }

  .toolbar .form-select,
  .toolbar .input-group {
    height: 38px
  }
</style>
@endpush
@php
$filters = [
'q' => request('q', ''),
'task' => request('task', ''),
'status' => request('status', ''),
];
@endphp

@php
$tasks = [
'' => 'All Task Types',
'printing' => 'Printing',
'furnishing' => 'Furnishing',
'delivery' => 'Dispatch Controller',
'installation' => 'Delivery & Installation',
];
@endphp

@php $assignees = $assignees ?? collect(); @endphp
<div class="card mb-4">
  <div class="card-body">
    <form id="ff-filter-form" method="GET" action="{{ route('artist.fulfillment.index') }}" class="row g-2 align-items-center">

    <!-- ===== 顶部标题 + 日期 + 按钮 ===== -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
      <h4 class="mb-0">Fulfillment Overview</h4>

      <!-- 日期 + 按钮组 -->
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <div class="input-group" style="max-width: 320px;">
          <input type="date" name="from" class="form-control" value="{{ $filters['from'] ?? '' }}">
          <span class="input-group-text">~</span>
          <input type="date" name="to" class="form-control" value="{{ $filters['to'] ?? '' }}">
        </div>
        <button type="submit" form="ff-filter-form" class="btn btn-primary px-4">Filter</button>
        <a href="{{ route('artist.fulfillment.index') }}" class="btn btn-outline-secondary">Reset</a>
      </div>
    </div>

    <!-- ===== 主过滤表单 ===== -->

      <!-- Product Code（短） -->
      <div class="col-12 col-lg-2">
        <div class="input-group">
          <span class="input-group-text"><i class="bx bx-hash"></i></span>
          <input type="text" name="code" class="form-control" placeholder="Enter Product ID"
                 value="{{ $filters['code'] ?? '' }}">
        </div>
      </div>

      <!-- Job Title / Company / Product（较长） -->
      <div class="col-12 col-lg-4">
        <div class="input-group">
          <span class="input-group-text"><i class="bx bx-search"></i></span>
          <input id="ff-search" type="text" name="q" class="form-control"
                 placeholder="Search job title, company or product name"
                 value="{{ $filters['q'] ?? '' }}">
        </div>
      </div>

      <!-- All assignees -->
      <div class="col-12 col-lg-2">
        <select name="assignees" class="form-select">
          <option value="">All assignees</option>
          @foreach($assignees as $u)
            <option value="{{ $u->id }}" {{ (string)request('assignees')===(string)$u->id ? 'selected' : '' }}>
              {{ $u->name }} ({{ $u->role }})
            </option>
          @endforeach
        </select>
      </div>

      <!-- Status -->
      <div class="col-6 col-lg-2">
        @php
          $statusOptions = ['' => 'All Statuses'];
          foreach ($statuses as $s) {
            $label = $s === 'in_progress' ? 'In Progress' : \Illuminate\Support\Str::of($s)->replace('_',' ')->title();
            $statusOptions[$s] = $label;
          }
        @endphp
        <select id="ff-status" name="status" class="form-select">
          @foreach($statusOptions as $val => $label)
            <option value="{{ $val }}" {{ request('status')===$val ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
      </div>

      <!-- Task types -->
      <div class="col-6 col-lg-2">
        <select id="ff-task" name="task" class="form-select">
          @foreach($tasks as $val => $label)
            <option value="{{ $val }}" {{ request('task')===$val ? 'selected' : '' }}>{{ $label }}</option>
          @endforeach
        </select>
      </div>

    </form>

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
  (function() {
    const wrap = document.getElementById('ff-table-wrap');
    const search = document.getElementById('ff-search');
    const task = document.getElementById('ff-task');
    const status = document.getElementById('ff-status');

    // init from URL
    const qs = new URLSearchParams(location.search);
    search.value = qs.get('q') || '';
    task.value = qs.get('task') || '';
    status.value = qs.get('status') || '';

    function currentParams() {
      const p = new URLSearchParams();
      if (search.value.trim()) p.set('q', search.value.trim());
      if (task.value) p.set('task', task.value);
      if (status.value) p.set('status', status.value);
      // tell controller to return only the table fragment
      p.set('partial', '1');
      return p;
    }

    function syncUrlAndExport() {
      const u = new URL(location.href);
      const p = new URLSearchParams(currentParams().toString());
      p.delete('partial'); // keep URL clean
      u.search = p.toString();
      history.replaceState(null, '', u.pathname + (u.search ? '?' + u.search : ''));

      const exp = document.getElementById('ff-export');
      if (exp) {
        const eu = new URL(exp.href, location.origin);
        eu.search = p.toString();
        exp.href = eu.toString();
      }
    }

    function buildUrl() {
      const base = @json(route('artist.fulfillment.index'));
      const url = new URL(base, location.origin);
      const p = currentParams();
      url.search = p.toString();
      return url.toString();
    }

    async function load() {
      syncUrlAndExport();
      wrap.style.opacity = .6;
      const res = await fetch(buildUrl(), {
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      });
      const html = await res.text();
      // inject only the partial into the existing card body
      const body = wrap.querySelector('.card-body');
      body.innerHTML = html;
      wrap.style.opacity = 1;
      initDT();
    }

    const debounce = (fn, ms = 300) => {
      let t;
      return (...a) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...a), ms);
      };
    };
    search.addEventListener('input', debounce(load, 300));
    task.addEventListener('change', load);
    status.addEventListener('change', load);

    // DataTables init (id inside the partial)
    function initDT() {
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
        columnDefs: [{
            targets: '_all',
            defaultContent: ''
          },
          {
            targets: -1,
            orderable: false,
            searchable: false,
            className: 'text-end'
          }
        ],
        language: {
          emptyTable: 'No results',
          zeroRecords: 'No results',
          info: 'Showing _START_ to _END_ of _TOTAL_ results',
          paginate: {
            previous: 'Previous',
            next: 'Next'
          }
        },
        drawCallback: function() {
          this.api().columns.adjust().responsive.recalc();
        }
      });
    }
    initDT();
  })();

  (function() {
    // ------- DataTables setup -------
    if (!window.jQuery || !$.fn.DataTable) return;

    const LEN_KEY = 'ff.pageLength.v1';

    function getSavedLen() {
      const raw = localStorage.getItem(LEN_KEY);
      if (raw == null) {
        localStorage.setItem(LEN_KEY, '5');
        return 5;
      }
      const v = parseInt(raw, 10);
      return [5, 10, 20, 30, 50, 100].includes(v) ? v : 5;
    }

    // Build table (no server-side reloading)
    const dt = $('#ffTable').DataTable({
      dom: 'rt<"d-flex justify-content-between align-items-center mt-3"ip>',
      pageLength: getSavedLen(),
      lengthMenu: [
        [5, 10, 20, 30, 50, 100],
        [5, 10, 20, 30, 50, 100]
      ],
      order: [],
      columnDefs: [{
        targets: -1,
        orderable: false,
        searchable: false
      }],
      drawCallback() {
        this.api().columns.adjust().responsive?.recalc?.();
      }
    });

    // Persist user-chosen page length using the native DT control
    // If you have a custom "entries per page" select, bind it the same way as in Orders page.
    $('#ffTable').on('length.dt', function(e, settings, len) {
      localStorage.setItem(LEN_KEY, String(len));
    });

    // ------- ORDER/PRODUCT ID client filter -------
    const idBox = document.getElementById('ffOrderIdSearch');
    const debounce = (fn, ms = 200) => {
      let t;
      return (...a) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...a), ms);
      }
    };

    // Keep value on reload via ?oid=...
    (function preloadOid() {
      const sp = new URLSearchParams(location.search);
      const v = sp.get('oid') || '';
      if (idBox) idBox.value = v;
    })();

    // Filter FIRST column text
    function applyIdFilter() {
      const v = (idBox?.value || '').trim();
      dt.column(0).search(v, false, true).draw();
    }
    if (idBox) {
      idBox.addEventListener('input', debounce(function() {
        // reflect in URL (so Filter button keeps it)
        const u = new URL(location.href);
        const val = this.value.trim();
        if (val) u.searchParams.set('oid', val);
        else u.searchParams.delete('oid');
        history.replaceState(null, '', u);
        applyIdFilter();
      }, 200));
      // initial apply
      applyIdFilter();
    }

    // ------- Double-click row to view -------
    document.querySelectorAll('#ffTable tbody tr.ff-row').forEach(tr => {
      tr.addEventListener('dblclick', () => {
        const url = tr.getAttribute('data-url');
        if (url) window.location.href = url;
      });
      tr.style.cursor = 'pointer';
    });

    // ------- Delivery date sorting helper (nearest/furthest) -------
    // If you want clickable header to toggle, give the Delivery Date <th> an id and attach a click handler;
    // otherwise you can push a hidden input named delivery_sort with 'nearest'/'furthest' and submit form.
  })();
</script>

@endpush