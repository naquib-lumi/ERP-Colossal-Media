@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
  /* Page & cards */
  .page-wrap{max-width:1180px;margin:0 auto;}
  .card.shadow-soft{border:1px solid #ECEFF3;border-radius:14px;box-shadow:0 3px 10px rgba(16,24,40,.06)}
  .table > :not(caption) > * > *{padding:14px 16px}

  /* Toolbar */
  .toolbar{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
  .toolbar .grow{flex:1 1 360px}
  .toolbar .date{width:140px;min-width:140px}
  .toolbar .btn-icon{width:40px;height:40px;display:inline-flex;align-items:center;justify-content:center}

  /* Pills / small buttons */
  .pill{
    border-radius:999px;
    padding:.25rem .85rem;
    border:1px solid #E5E7EB;
    background:#fff;
    color:#111827;
    font-weight:600;
    line-height:1;
  }
  .pill:disabled{opacity:.55;cursor:not-allowed}

  /* Icon-only buttons (for “Product details”) */
  .icon-btn{
    width:36px;height:36px;border:1px solid #E5E7EB;border-radius:10px;
    background:#fff;color:#475467;display:inline-flex;align-items:center;justify-content:center
  }
  .icon-btn:hover{background:#F2F4F7;color:#111827}

  /* Pagination (pill style) */
  .pager .page-link{border-radius:999px;border:1px solid #E5E7EB}
  .pager .active > .page-link{background:#635bff;border-color:#635bff;color:#fff}

  .table thead th{font-size:12px;color:#475467;font-weight:700;background:#F8FAFC}
  .table tbody tr:hover{background:#FAFBFC}
</style>

<div class="container-fluid py-4 px-4">
  <div class="page-wrap">
    <h1 class="fw-bold mb-3" style="font-size:28px;letter-spacing:-.2px;">Order History</h1>

    {{-- Toolbar --}}
    <div class="card shadow-soft mb-3">
      <div class="card-body toolbar">
        <form class="d-flex flex-wrap gap-2 w-100" method="GET" action="{{ route('dispatchcontrol.history') }}">
          <input
            type="text"
            name="q"
            value="{{ $q ?? '' }}"
            class="form-control grow"
            placeholder="Search by Order ID, Product ID, Item ID, or Job Title">

          <input type="text" name="start" value="{{ $start ?? '' }}" class="form-control date" placeholder="mm/dd/yyyy">
          <span class="text-muted d-flex align-items-center">to</span>
          <input type="text" name="end" value="{{ $end ?? '' }}" class="form-control date" placeholder="mm/dd/yyyy">
          <button type="button" class="btn btn-light border btn-icon" title="Calendar">
            <i class="bi bi-calendar2"></i>
          </button>

          <a href="{{ route('dispatchcontrol.history') }}" class="btn btn-light border">
            <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
          </a>
          <button class="btn btn-dark">
            <i class="bi bi-funnel me-1"></i>Apply Filter
          </button>
        </form>
      </div>
    </div>

    {{-- Table --}}
    <div class="card shadow-soft">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
          <div class="fw-semibold">Completed Orders</div>
          <div>{{ number_format($orders->total()) }} total results</div>
        </div>

        <div class="table-responsive">
          <table class="table align-middle">
            <thead>
              <tr>
                <th>PRODUCT ID</th>
                <th>PRODUCT NAME</th>
                <th>COMPLETED DATE</th>
                <th>PROOF FILE</th>
                <th>REMARKS</th>
                <th>PRODUCT DETAILS</th>
              </tr>
            </thead>
            <tbody>
            @forelse ($orders as $row)
              <tr>
                {{-- formatted code like #ORD-12-P0001 --}}
                <td class="fw-semibold">{{ $row->product_code }}</td>
                <td>{{ $row->product_name }}</td>
                <td>{{ $row->completed_date }}</td>

                {{-- === PROOF FILE (pill “View” exactly like your screenshot) === --}}
                <td>
                  @if(!empty($row->proof_url))
                    <a href="{{ $row->proof_url }}" target="_blank" class="pill d-inline-flex align-items-center gap-2" title="View proof">
                      <i class="bi bi-eye"></i><span>View</span>
                    </a>
                  @else
                    <button class="pill d-inline-flex align-items-center gap-2" title="No proof file" disabled>
                      <i class="bi bi-eye"></i><span>View</span>
                    </button>
                  @endif
                </td>
                {{-- === /PROOF FILE === --}}

                <td>{{ $row->remarks }}</td>

                {{-- Square icon button for details --}}
                <td class="text-center">
                  @php
                    // If you have a details route, drop it here:
                    $detailsUrl = $row->details_url ?? '#';
                  @endphp
                  <a href="{{ $detailsUrl }}" class="icon-btn" title="Product details">
                    <i class="bi bi-eye"></i>
                  </a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">No records</td>
              </tr>
            @endforelse
            </tbody>
          </table>
        </div>

        {{-- Pagination (pill style) --}}
        @if ($orders->hasPages())
          <div class="d-flex justify-content-end mt-3">
            <nav>
              <ul class="pagination pager mb-0">
                @if ($orders->onFirstPage())
                  <li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-left"></i></span></li>
                @else
                  <li class="page-item"><a class="page-link" href="{{ $orders->previousPageUrl() }}"><i class="bi bi-chevron-left"></i></a></li>
                @endif

                @php
                  $startPage = max(1, $orders->currentPage() - 1);
                  $endPage   = min($orders->lastPage(), $orders->currentPage() + 1);
                @endphp
                @if ($startPage > 1)
                  <li class="page-item"><a class="page-link" href="{{ $orders->url(1) }}">1</a></li>
                  @if ($startPage > 2)
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                  @endif
                @endif

                @for ($p = $startPage; $p <= $endPage; $p++)
                  @if ($p == $orders->currentPage())
                    <li class="page-item active"><span class="page-link">{{ $p }}</span></li>
                  @else
                    <li class="page-item"><a class="page-link" href="{{ $orders->url($p) }}">{{ $p }}</a></li>
                  @endif
                @endfor

                @if ($endPage < $orders->lastPage())
                  @if ($endPage < $orders->lastPage() - 1)
                    <li class="page-item disabled"><span class="page-link">…</span></li>
                  @endif
                  <li class="page-item"><a class="page-link" href="{{ $orders->url($orders->lastPage()) }}">{{ $orders->lastPage() }}</a></li>
                @endif

                @if ($orders->hasMorePages())
                  <li class="page-item"><a class="page-link" href="{{ $orders->nextPageUrl() }}"><i class="bi bi-chevron-right"></i></a></li>
                @else
                  <li class="page-item disabled"><span class="page-link"><i class="bi bi-chevron-right"></i></span></li>
                @endif
              </ul>
            </nav>
          </div>

          <div class="small text-muted mt-2">
            Showing {{ $orders->firstItem() }} to {{ $orders->lastItem() }} of {{ $orders->total() }} results
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
@endsection
