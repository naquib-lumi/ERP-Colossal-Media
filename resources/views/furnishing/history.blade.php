@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
  .page-wrap {
    max-width: 1180px;
    margin: 0 auto;
  }

  .card.shadow-soft {
    box-shadow: 0 3px 10px rgba(16, 24, 40, .06)
  }

  .toolbar {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap
  }

  .toolbar .grow {
    flex: 1 1 360px
  }

  .toolbar .dates {
    display: flex;
    align-items: center;
    gap: 8px
  }

  .toolbar .actions {
    display: flex;
    align-items: center;
    gap: 8px
  }

  .toolbar .form-control,
  .toolbar .btn,
  .toolbar .btn-icon {
    height: 40px
  }

  .toolbar .btn-icon {
    width: 40px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center
  }

  .toolbar .date-input {
    width: 140px;
    min-width: 140px
  }

  .toolbar .btn span {
    white-space: nowrap
  }

  .toolbar .btn-apply {
    min-width: 130px
  }

  @media (min-width:992px) {
    .toolbar .actions {
      margin-left: auto
    }
  }

  .table thead th {
    font-size: 12px;
    color: #475467;
    font-weight: 700
  }

  .table td {
    vertical-align: middle
  }

  .table>:not(caption)>*>* {
    padding: 14px 16px
  }

  .icon-btn {
    width: 36px;
    height: 36px;
    border: 1px solid #E5E7EB;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: #475467;
    background: #fff
  }

  .icon-btn:hover {
    background: #F2F4F7;
    color: #344054
  }

  .pagination .page-link {
    border-radius: 10px
  }

  .history-filter.card{border:0;border-radius:14px;box-shadow:0 3px 14px rgba(18,23,42,.06)}
  .history-filter .toolbar{display:flex;flex-wrap:wrap;gap:.75rem 1rem;align-items:center;padding:14px 16px}
  .history-filter .form-control{height:42px;border-radius:10px;border-color:#e6e8f0;box-shadow:none}
  .history-filter .form-control:focus{border-color:#bfc6ff;box-shadow:0 0 0 .15rem rgba(99,91,255,.12)}
  .history-filter .grow{flex:1 1 340px;min-width:260px}
  .history-filter .dates{display:flex;align-items:center;gap:.5rem}
  .history-filter .date-input{width:180px}
  .history-filter .actions{margin-left:auto;display:flex;gap:.5rem}
  .history-filter .btn{height:42px;border-radius:10px}
  .history-filter .btn-light{border-color:#e6e8f0;background:#f6f7fb;color:#111827}
  .history-filter .btn-light:hover{background:#eef0f8}
  .history-filter .btn-dark{background:#1f2233;border-color:#1f2233}
  .history-filter .btn-dark:hover{background:#2a2f47}
  .history-filter .with-icon{position:relative}
  .history-filter .with-icon>i{position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:16px;color:#7b8191;pointer-events:none}
  .history-filter .with-icon>.form-control{padding-left:36px}
  .artist-select{flex:0 1 220px}
  .artist-select select{height:42px;border-radius:10px;border-color:#e6e8f0;background:#fff;padding-left:36px}
  .artist-select i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#7b8191;pointer-events:none}
  .table.fixed { table-layout: fixed; }
  .table.fixed .col-id { width: 220px; }
  .table.fixed .col-name { width: 28%; }
  .table.fixed .col-date { width: 170px; }
  .table.fixed .col-remarks { width: 18%; }   /* smaller than before */
  .table.fixed .col-actions { width: 130px; text-align: center; }
  .table thead th{background:#e9ecef;font-weight:700;letter-spacing:.02em;border-right:1px solid #d9d9d9;white-space:nowrap}
  .table thead th:last-child{border-right:0}
  .table tbody tr{transition:background .12s ease}
  .table tbody tr:hover{background:#fafbff}
  .truncate{display:inline-block;max-width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .icon-btn{display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px;border:1px solid #e5e7eb;border-radius:10px;background:#fff;color:#111827}
  .icon-btn:hover{background:#f5f6ff;border-color:#cdd1f8;color:#3742fa}
  .th-sort { text-decoration:none; color:inherit; user-select:none; }
  .th-sort:hover { text-decoration: underline; }
  .th-sort.is-active { font-weight: 700; }
  .sort-caret { opacity:.6; margin-left:.25rem; }
</style>

<div class="container-fluid py-4 px-4">
  <div class="page-wrap">
    <h1 class="h4 fw-bold mb-4">Order History</h1>

    {{-- Toolbar (GET filters) --}}
    <div class="card history-filter shadow-soft mb-3">
      <form method="GET" action="{{ route('furnishing.history') }}">
        <div class="card-body toolbar">
          {{-- Quick Product ID (page filter only) --}}
          <div class="with-icon grow">
            <i class="bi bi-hash"></i>
            <input name="pid" value="{{ request('pid', $pid ?? '') }}" type="text" class="form-control" placeholder="Enter Product ID">
          </div>

          {{-- Main keyword search --}}
          <div class="with-icon grow">
            <i class="bi bi-search"></i>
            <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control"
                  placeholder="Search by Order Title, Company, Product, Remarks">
          </div>

          {{-- Artist --}}
          <div class="with-icon artist-select">
            <i class="bi bi-person-badge"></i>
            <select name="artist" class="form-select">
              <option value="">All artists</option>
              @foreach (($artists ?? []) as $a)
                <option value="{{ $a->id }}" {{ (string)$a->id === (string)request('artist') ? 'selected' : '' }}>
                  {{ $a->name }}
                </option>
              @endforeach
            </select>
          </div>

          {{-- Dates --}}
          <div class="dates">
            <div class="with-icon">
              <i class="bi bi-calendar-event"></i>
              <input type="text" name="start" value="{{ $start ?? '' }}" class="form-control date-input js-date"
                    placeholder="mm/dd/yyyy" autocomplete="off">
            </div>
            <span class="text-muted">to</span>
            <div class="with-icon">
              <i class="bi bi-calendar-check"></i>
              <input type="text" name="end" value="{{ $end ?? '' }}" class="form-control date-input js-date"
                    placeholder="mm/dd/yyyy" autocomplete="off">
            </div>
          </div>

          {{-- Actions --}}
          <div class="actions">
            <a href="{{ route('furnishing.history') }}" class="btn btn-light border">
              <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
            </a>
            <button class="btn btn-dark btn-apply" type="submit">
              <i class="bi bi-funnel me-1"></i> Apply Filter
            </button>
          </div>
        </div>
      </form>
    </div>

    {{-- Completed Orders --}}
    <div class="card border-0 shadow-soft">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
          <div class="fw-semibold">Completed Orders</div>
          <div>{{ number_format($orders->total()) }} total results</div>
        </div>

        @php
          $qAll = request()->query();
          $urlWith = function(array $overrides) use ($qAll) {
            return route('furnishing.history', array_filter(array_merge($qAll, $overrides), fn($v)=>$v!==null && $v!==''));
          };
          $dir = request('dir','desc') === 'asc' ? 'asc' : 'desc';
          $nextDir = $dir === 'asc' ? 'desc' : 'asc';
        @endphp

        <div class="table-responsive">
          <table class="table align-middle fixed">
            <colgroup>
              <col class="col-id">
              <col class="col-name">
              <col class="col-date">
              <col class="col-actions">
            </colgroup>
            <thead class="table-light">
              <tr>
                <th>PRODUCT ID</th>
                <th>PRODUCT NAME</th>
                <th>
                  <a class="th-sort is-active"
                    href="{{ $urlWith(['sort' => 'completed', 'dir' => $nextDir]) }}">
                    COMPLETION DATE
                    <span class="sort-caret">{{ $dir === 'asc' ? '↑' : '↓' }}</span>
                  </a>
                </th>
                <th class="text-center">PRODUCT DETAILS</th>
              </tr>
            </thead>
            <tbody>
              @forelse($orders as $row)
                @php
                  $prodName = $row->product_name ?? '—';
                  $completed = $row->completed_date ? \Carbon\Carbon::parse($row->completed_date)->format('M d, Y') : '—';
                  $remarks = $row->materialRemark ?: '–';
                  $detailsUrl = route('furnishing.history.show', $row->ProductID);
                @endphp
                <tr class="js-row-open" data-href="{{ $detailsUrl }}" style="cursor:pointer;">
                  <td class="fw-semibold">{{ $row->product_code }}</td>
                  <td><span class="truncate" title="{{ $prodName }}">{{ $prodName }}</span></td>
                  <td class="td-completed" data-date="{{ $row->completed_date ?: '' }}">{{ $completed }}</td>
                  <td class="text-center">
                    <a href="{{ $detailsUrl }}" class="icon-btn" title="View details"><i class="bi bi-eye"></i></a>
                  </td>
                </tr>
              @empty
                <tr><td colspan="5" class="text-center text-muted">No records found.</td></tr>
              @endforelse
            </tbody>
          </table>
        </div>

        {{-- Pagination with ellipses --}}
        @php
        $current = $orders->currentPage();
        $last = $orders->lastPage();
        $window = 1;
        $startPg = max(1, $current - $window);
        $endPg = min($last, $current + $window);
        @endphp

        <div class="d-flex justify-content-end mt-3">
          <nav>
            <ul class="pagination mb-0">
              <li class="page-item {{ $orders->onFirstPage() ? 'disabled' : '' }}">
                <a class="page-link" href="{{ $orders->previousPageUrl() ?? '#' }}"><i class="bi bi-chevron-left"></i></a>
              </li>

              @if ($startPg > 1)
              <li class="page-item"><a class="page-link" href="{{ $orders->url(1) }}">1</a></li>
              @if ($startPg > 2)
              <li class="page-item disabled"><span class="page-link">…</span></li>
              @endif
              @endif

              @for ($p = $startPg; $p <= $endPg; $p++)
                @if ($p==$current)
                <li class="page-item active"><span class="page-link">{{ $p }}</span></li>
                @else
                <li class="page-item"><a class="page-link" href="{{ $orders->url($p) }}">{{ $p }}</a></li>
                @endif
                @endfor

                @if ($endPg < $last)
                  @if ($endPg < $last - 1)
                  <li class="page-item disabled"><span class="page-link">…</span></li>
                  @endif
                  <li class="page-item"><a class="page-link" href="{{ $orders->url($last) }}">{{ $last }}</a></li>
                  @endif

                  <li class="page-item {{ $orders->hasMorePages() ? '' : 'disabled' }}">
                    <a class="page-link" href="{{ $orders->nextPageUrl() ?? '#' }}"><i class="bi bi-chevron-right"></i></a>
                  </li>
            </ul>
          </nav>
        </div>

        <div class="small text-muted mt-2">
          Showing {{ $orders->firstItem() ?? 0 }} to {{ $orders->lastItem() ?? 0 }} of {{ $orders->total() }} results
        </div>
      </div>
    </div>

  </div>
</div>

<script>
(() => {
  // Product ID quick filter (client-only, current page)
  const pidInput = document.getElementById('pidFilter');
  const tbody = document.querySelector('.table tbody');
  if (pidInput && tbody) {
    pidInput.addEventListener('input', function () {
      const q = (this.value || '').trim().toLowerCase();
      for (const tr of tbody.querySelectorAll('tr')) {
        const firstCell = tr.querySelector('td');
        const text = (firstCell?.textContent || '').toLowerCase();
        tr.style.display = q && !text.includes(q) ? 'none' : '';
      }
    });
  }

  // Double-click row to open details
  document.addEventListener('dblclick', (e) => {
    const tr = e.target.closest('tr.js-row-open');
    if (!tr) return;
    const tag = (e.target.tagName || '').toLowerCase();
    if (['a','button','input','select','textarea','label','svg','path','i'].includes(tag)) return;
    const url = tr.dataset.href;
    if (url) window.location.href = url;
  });

  // Native date picker shim (mm/dd/yyyy inputs)
  const ymdToUs = v => /^\d{4}-\d{2}-\d{2}$/.test(v) ? (v.slice(5,7)+'/'+v.slice(8,10)+'/'+v.slice(0,4)) : v;
  const usToYmd = v => {
    const m = v.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
    if (!m) return '';
    const [,mm,dd,yy] = m;
    return `${yy}-${mm.padStart(2,'0')}-${dd.padStart(2,'0')}`;
  };
  function attachNativeDate(input){
    const rect = input.getBoundingClientRect();
    input.style.width = rect.width + 'px';
    function openPicker(){
      if (input.type !== 'date') {
        const prev = input.value.trim();
        const ymd = usToYmd(prev);
        input.type = 'date';
        if (ymd) input.value = ymd;
        if (input.showPicker) input.showPicker(); else input.focus();
      }
    }
    function closePicker(){
      if (input.type === 'date') {
        if (input.value) input.value = ymdToUs(input.value);
        input.type = 'text';
      }
    }
    input.addEventListener('focus', openPicker);
    input.addEventListener('click', openPicker);
    input.addEventListener('change', () => {
      if (input.type === 'date' && input.value) {
        const us = ymdToUs(input.value);
        input.type = 'text';
        input.value = us;
        input.dispatchEvent(new Event('change', {bubbles:true}));
      }
    });
    input.addEventListener('blur', closePicker);
  }
  document.querySelectorAll('.js-date').forEach(attachNativeDate);
})();
</script>
@endsection