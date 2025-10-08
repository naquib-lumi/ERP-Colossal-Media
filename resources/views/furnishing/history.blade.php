@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
  .page-wrap{max-width:1180px;margin:0 auto;}
  .card.shadow-soft{box-shadow:0 3px 10px rgba(16,24,40,.06)}
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
  .table thead th{font-size:12px;color:#475467;font-weight:700}
  .table td{vertical-align:middle}
  .table>:not(caption)>*>*{padding:14px 16px}
  .icon-btn{width:36px;height:36px;border:1px solid #E5E7EB;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;color:#475467;background:#fff}
  .icon-btn:hover{background:#F2F4F7;color:#344054}
  .pagination .page-link{border-radius:10px}
</style>

<div class="container-fluid py-4 px-4">
  <div class="page-wrap">
    <h1 class="h4 fw-bold mb-4">Order History</h1>

    {{-- Toolbar (GET filters) --}}
    <div class="card border-0 shadow-soft mb-3">
      <form method="GET" action="{{ route('furnishing.history') }}">
        <div class="card-body toolbar">
          <input type="text" name="q" value="{{ $q ?? '' }}" class="form-control grow"
                 placeholder="Search by Order ID or Job Title">

          <div class="dates">
            <input type="text" name="start" value="{{ $start ?? '' }}" class="form-control date-input" placeholder="mm/dd/yyyy">
            <span class="text-muted">to</span>
            <input type="text" name="end" value="{{ $end ?? '' }}" class="form-control date-input" placeholder="mm/dd/yyyy">
            <button type="button" class="btn btn-light border btn-icon" title="Calendar">
              <i class="bi bi-calendar2"></i>
            </button>
          </div>

          <div class="actions">
            <a href="{{ route('furnishing.history') }}" class="btn btn-light border" title="Reset">
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
          <table class="table align-middle">
            <thead class="table-light">
              <tr>
                <th style="width:160px;">PRODUCT ID</th>
                <th>PRODUCT NAME</th>
                <th style="width:160px;">COMPLETED DATE</th>
                <th>REMARKS</th>
                <th style="width:140px;" class="text-center">PRODUCT DETAILS</th>
              </tr>
            </thead>
            <tbody>
              @forelse($orders as $row)
                <tr>
                  <td>
                    {{ $row->order_number ?: ('ORD'.($row->order_id ?? $row->ProductID)) }}
                    -P{{ $row->ItemID ?? $row->ProductID }}
                  </td>
                  <td>{{ $row->product_name }}</td>
                  <td>
                    @if(!empty($row->completed_date))
                      {{ \Carbon\Carbon::parse($row->completed_date)->format('M d, Y') }}
                    @else
                      -
                    @endif
                  </td>
                  {{-- Use materialRemark from DB; fallback to dash --}}
                  <td>{{ $row->materialRemark ?? '–' }}</td>
                  <td class="text-center">
                    <button class="icon-btn" title="View details"><i class="bi bi-eye"></i></button>
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

        {{-- Pagination with ellipses --}}
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
@endsection
