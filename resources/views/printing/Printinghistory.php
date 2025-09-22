@extends('layouts.app')

@section('content')
{{-- 只在本页引入图标 --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
  .page-wrap{max-width:1220px;margin:0 auto;}
  .toolbar .form-control{height:44px}
  .toolbar .btn{height:44px}
  .btn-ghost{border:1px solid #E5E7EB;background:#fff;color:#344054}
  .btn-ghost:hover{background:#F2F4F7}
  .btn-dark{background:#111827;border-color:#111827}
  .btn-dark:hover{background:#0f172a;border-color:#0f172a}

  .card-elev{border:1px solid #EEF0F3;border-radius:14px;box-shadow:0 1px 2px rgba(16,24,40,.06)}
  .table th{color:#475467;font-size:12px;font-weight:700}
  .table td{vertical-align:middle}
  .table > :not(caption) > * > *{padding:16px 14px}

  .pill-icon-btn{
    display:inline-flex;align-items:center;gap:6px;
    border:1px solid #E5E7EB;background:#fff;color:#475467;
    padding:.45rem .9rem;border-radius:10px
  }
  .pill-icon-btn:hover{background:#F2F4F7}

  .view-btn{border:1px solid #D0D5DD;background:#fff;border-radius:10px;padding:.35rem .75rem}
  .view-btn:hover{background:#F2F4F7}

  /* 分页 */
  .pagination .page-link{border:1px solid #E5E7EB;color:#344054;padding:.5rem .8rem;border-radius:10px}
  .pagination .page-item.active .page-link{background:#4F46E5;border-color:#4F46E5;color:#fff}
  .pagination .page-link:focus{box-shadow:none}
</style>

<div class="container-fluid py-4 px-4">
  <div class="page-wrap">
    <h1 class="h4 fw-bold mb-4 d-flex align-items-center gap-2">
      <i class="bi bi-arrow-left-short d-none"></i> Order History
    </h1>

    {{-- Filter / Toolbar --}}
    <div class="card card-elev mb-4">
      <div class="card-body">
        <div class="row g-2 align-items-center toolbar">
          <div class="col-12 col-lg">
            <input type="text" class="form-control" placeholder="Search by Order ID or Job Title">
          </div>
          <div class="col-6 col-md-3 col-lg-2">
            <input type="text" class="form-control" placeholder="mm/dd/yyyy">
          </div>
          <div class="col-auto text-muted">to</div>
          <div class="col-6 col-md-3 col-lg-2">
            <input type="text" class="form-control" placeholder="mm/dd/yyyy">
          </div>
          <div class="col-auto d-none d-md-block">
            <button class="btn btn-ghost" aria-label="calendar"><i class="bi bi-calendar2"></i></button>
          </div>
          <div class="col-auto ms-lg-auto">
            <button class="btn pill-icon-btn"><i class="bi bi-arrow-counterclockwise"></i> Reset</button>
          </div>
          <div class="col-auto">
            <button class="btn btn-dark d-inline-flex align-items-center gap-2">
              <i class="bi bi-funnel"></i> Apply Filter
            </button>
          </div>
        </div>
      </div>
    </div>

    {{-- Completed Orders --}}
    <div class="card card-elev">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="mb-0">Completed Orders</h5>
          <div class="small text-muted">248 total results</div>
        </div>

        <div class="table-responsive">
          <table class="table align-middle">
            <thead class="table-light">
              <tr>
                <th>Product ID</th>
                <th>Product Name</th>
                <th>Completed Date</th>
                <th>Proof File</th>
                <th>Remarks</th>
                <th>Product Details</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td>ORD005-P1</td>
                <td>Business Cards - Premium</td>
                <td>Jan 15, 2025</td>
                <td><button class="view-btn"><i class="bi bi-eye me-1"></i>View</button></td>
                <td>Perfect quality</td>
                <td><button class="pill-icon-btn" aria-label="details"><i class="bi bi-eye"></i></button></td>
              </tr>
              <tr>
                <td>ORD005-P2</td>
                <td>Flyers A4 - Standard</td>
                <td>Jan 14, 2025</td>
                <td><button class="view-btn"><i class="bi bi-eye me-1"></i>View</button></td>
                <td>–</td>
                <td><button class="pill-icon-btn"><i class="bi bi-eye"></i></button></td>
              </tr>
              <tr>
                <td>ORD006-P1</td>
                <td>Brochure Tri-fold</td>
                <td>Jan 13, 2025</td>
                <td><button class="view-btn"><i class="bi bi-eye me-1"></i>View</button></td>
                <td>Color correction applied</td>
                <td><button class="pill-icon-btn"><i class="bi bi-eye"></i></button></td>
              </tr>
              <tr>
                <td>ORD007-P1</td>
                <td>Poster A2 - Glossy</td>
                <td>Jan 12, 2025</td>
                <td><button class="view-btn"><i class="bi bi-eye me-1"></i>View</button></td>
                <td>Rush order completed</td>
                <td><button class="pill-icon-btn"><i class="bi bi-eye"></i></button></td>
              </tr>
              <tr>
                <td>ORD007-P2</td>
                <td>Letterhead - Corporate</td>
                <td>Jan 11, 2025</td>
                <td><button class="view-btn"><i class="bi bi-eye me-1"></i>View</button></td>
                <td>–</td>
                <td><button class="pill-icon-btn"><i class="bi bi-eye"></i></button></td>
              </tr>
              <tr>
                <td>ORD007-P5</td>
                <td>Banner 3×6 feet</td>
                <td>Jan 10, 2025</td>
                <td><button class="view-btn"><i class="bi bi-eye me-1"></i>View</button></td>
                <td>Weather resistant material</td>
                <td><button class="pill-icon-btn"><i class="bi bi-eye"></i></button></td>
              </tr>
              <tr>
                <td>ORD008-P1</td>
                <td>Menu Cards - Restaurant</td>
                <td>Jan 09, 2025</td>
                <td><button class="view-btn"><i class="bi bi-eye me-1"></i>View</button></td>
                <td>Laminated finish</td>
                <td><button class="pill-icon-btn"><i class="bi bi-eye"></i></button></td>
              </tr>
              <tr>
                <td>ORD008-P3</td>
                <td>Stickers - Custom Shape</td>
                <td>Jan 08, 2025</td>
                <td><button class="view-btn"><i class="bi bi-eye me-1"></i>View</button></td>
                <td>Die-cut precision</td>
                <td><button class="pill-icon-btn"><i class="bi bi-eye"></i></button></td>
              </tr>
            </tbody>
          </table>
        </div>

        {{-- footer / pagination --}}
        <div class="d-flex justify-content-between align-items-center mt-2">
          <div class="small text-muted">Showing 1 to 8 of 248 results</div>
          <nav>
            <ul class="pagination mb-0">
              <li class="page-item">
                <a class="page-link" href="#" aria-label="Previous"><i class="bi bi-chevron-left"></i></a>
              </li>
              <li class="page-item active"><span class="page-link">1</span></li>
              <li class="page-item"><a class="page-link" href="#">2</a></li>
              <li class="page-item"><a class="page-link" href="#">3</a></li>
              <li class="page-item disabled"><span class="page-link">…</span></li>
              <li class="page-item"><a class="page-link" href="#">31</a></li>
              <li class="page-item">
                <a class="page-link" href="#" aria-label="Next"><i class="bi bi-chevron-right"></i></a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
    </div>

  </div>
</div>
@endsection
