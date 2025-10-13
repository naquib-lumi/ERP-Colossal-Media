@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
  /* Layout */
  .page-wrap{max-width:1100px;margin:0 auto;}
  .card.shadow-soft{box-shadow:0 3px 10px rgba(16,24,40,.06)}

  /* Toolbar */
  .toolbar{display:flex;align-items:center;gap:12px;flex-wrap:wrap}
  .toolbar .grow{flex:1 1 360px}
  .toolbar .dates{display:flex;align-items:center;gap:8px}
  .toolbar .actions{display:flex;align-items:center;gap:8px}
  .toolbar .form-control,.toolbar .btn,.toolbar .btn-icon{height:40px}
  .toolbar .btn-icon{width:40px;padding:0;display:inline-flex;align-items:center;justify-content:center}
  .toolbar .date-input{width:140px;min-width:140px}
  .toolbar .btn span{white-space:nowrap}
  .toolbar .btn-apply{min-width:130px}
  @media (min-width:992px){.toolbar .actions{margin-left:auto}}

  /* Table */
  .table.fixed{table-layout:fixed;width:100%;}
  .table thead th{font-size:12px;color:#475467;font-weight:700}
  .table td{vertical-align:middle}
  .table>:not(caption)>*>*{padding:14px 16px}

  /* Percent widths */
  .col-id{width:14%}
  .col-name{width:32%}
  .col-date{width:16%}
  .col-remarks{width:28%}
  .col-actions{width:10%}
  @media (max-width:1200px){
    .col-name{width:30%}
    .col-remarks{width:28%}
  }
  @media (max-width:992px){
    .col-name,.col-remarks{width:auto}
  }

  .truncate{overflow:hidden;text-overflow:ellipsis;white-space:nowrap;display:block}

  /* Action icon */
  .icon-btn{
    width:36px;height:36px;border:1px solid #E5E7EB;border-radius:10px;
    display:inline-flex;align-items:center;justify-content:center;
    color:#475467;background:#fff
  }
  .icon-btn:hover{background:#F2F4F7;color:#344054}
  .pagination .page-link{border-radius:10px}
</style>

<div class="container-fluid py-4 px-4">
  <div class="page-wrap">
    <h1 class="h4 fw-bold mb-4">Order History</h1>

    {{-- Toolbar --}}
    <div class="card border-0 shadow-soft mb-3">
      <form method="GET" action="{{ route('printing.history') }}">
        <div class="card-body toolbar">
          <input
            type="text"
            name="q"
            value="{{ $q ?? '' }}"
            class="form-control grow"
            placeholder="Search by Product Name or Remarks">

          <div class="dates">
            <input type="text" name="start" value="{{ $start ?? '' }}" class="form-control date-input js-date" placeholder="mm/dd/yyyy" autocomplete="off">
            <span class="text-muted">to</span>
            <input type="text" name="end" value="{{ $end ?? '' }}" class="form-control date-input js-date" placeholder="mm/dd/yyyy" autocomplete="off">
          </div>

          <div class="actions">
            <a href="{{ route('printing.history') }}" class="btn btn-light border" title="Reset">
              <i class="bi bi-arrow-counterclockwise me-1"></i><span>Reset</span>
            </a>
            <button class="btn btn-dark btn-apply" type="submit">
              <i class="bi bi-funnel me-1"></i><span>Apply Filter</span>
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

        <div class="table-responsive">
          <table class="table align-middle fixed">
            <colgroup>
              <col class="col-id">
              <col class="col-name">
              <col class="col-date">
              <col class="col-remarks">
              <col class="col-actions">
            </colgroup>

            <thead class="table-light">
              <tr>
                <th>PRODUCT ID</th>
                <th>PRODUCT NAME</th>
                <th>COMPLETED DATE</th>
                <th>REMARKS</th>
                <th class="text-center">PRODUCT DETAILS</th>
              </tr>
            </thead>

            <tbody>
              @forelse($orders as $row)
                @php
                  $prodName = $row->product_name ?? '—';
                  $completed = $row->completed_date ? \Carbon\Carbon::parse($row->completed_date)->format('M d, Y') : '—';
                  $remarks = $row->materialRemark ?: '–';
                @endphp
                <tr>
                  <td class="fw-semibold">{{ $row->order_number ?: ('ORD'.($row->order_id ?? $row->ProductID)) }}-P{{ $row->ItemID ?? $row->ProductID }}</td>
                  <td><span class="truncate" title="{{ $prodName }}">{{ $prodName }}</span></td>
                  <td>{{ $completed }}</td>
                  <td><span class="truncate" title="{{ $remarks }}">{{ $remarks }}</span></td>
                  <td class="text-center">
                    <a href="{{ route('printing.history.show', $row->ProductID) }}" class="icon-btn" title="View details">
                      <i class="bi bi-eye"></i>
                    </a>
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="5" class="text-center text-muted">No records found.</td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>

        {{-- Pagination --}}
        @php
          $current = $orders->currentPage();
          $last    = $orders->lastPage();
          $window  = 1;
          $startPg = max(1, $current - $window);
          $endPg   = min($last, $current + $window);
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
                @if ($p == $current)
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

@push('scripts')
<script>
(() => {
  // mm/dd/yyyy ↔ yyyy-mm-dd 互转
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
        if (input.showPicker) input.showPicker();
        else input.focus();
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
@endpush
@endsection
