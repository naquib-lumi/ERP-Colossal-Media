@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
  /* ===== 页面容器 ===== */
  .page-wrap{max-width:1180px;margin:0 auto;}

  /* ===== 卡片阴影（柔和） ===== */
  .card.shadow-soft{box-shadow:0 3px 10px rgba(16,24,40,.06)}

  /* ===== Toolbar 布局（已修正） ===== */
  .toolbar{
    display:flex; align-items:center; gap:12px; flex-wrap:wrap;   /* 自适应换行 */
  }
  .toolbar .grow{ flex: 1 1 360px; }         /* 搜索框最小 360px，能伸展 */
  .toolbar .dates{ display:flex; align-items:center; gap:8px; }
  .toolbar .actions{ display:flex; align-items:center; gap:8px; }

  /* 统一控件高度与宽度 */
  .toolbar .form-control,
  .toolbar .btn,
  .toolbar .btn-icon{ height:40px; }
  .toolbar .btn-icon{
    width:40px; padding:0; display:inline-flex; align-items:center; justify-content:center;
  }
  .toolbar .date-input{ width:140px; min-width:140px; }      /* 日期框不会被压扁 */
  .toolbar .btn span{ white-space:nowrap; }                 /* 文本不折行 */
  .toolbar .btn-apply{ min-width:130px; }                   /* 防止被截断 */
  @media (min-width:992px){
    .toolbar .actions{ margin-left:auto; }                  /* 大屏右对齐 */
  }

  /* ===== 表格样式 ===== */
  .table thead th{font-size:12px;color:#475467;font-weight:700}
  .table td{vertical-align:middle}
  .table > :not(caption) > * > *{padding:14px 16px}

  /* ===== 小图标按钮（产品详情） ===== */
  .icon-btn{
    width:36px;height:36px;border:1px solid #E5E7EB;border-radius:10px;
    display:inline-flex;align-items:center;justify-content:center;color:#475467;background:#fff
  }
  .icon-btn:hover{background:#F2F4F7;color:#344054}

  /* ===== 分页圆角 ===== */
  .pagination .page-link{border-radius:10px}
</style>

<div class="container-fluid py-4 px-4">
  <div class="page-wrap">

    <h1 class="h4 fw-bold mb-4">Order History</h1>

    {{-- ===== Toolbar（整行布局，移动端自动换行） ===== --}}
    <div class="card border-0 shadow-soft mb-3">
      <div class="card-body toolbar">

        <!-- 搜索框（占据剩余宽度） -->
        <input type="text" class="form-control grow" placeholder="Search by Order ID or Job Title">

        <!-- 日期区间 -->
        <div class="dates">
          <input type="text" class="form-control date-input" placeholder="mm/dd/yyyy">
          <span class="text-muted">to</span>
          <input type="text" class="form-control date-input" placeholder="mm/dd/yyyy">
          <button class="btn btn-light border btn-icon" title="Calendar">
            <i class="bi bi-calendar2"></i>
          </button>
        </div>

        <!-- 动作（右对齐） -->
        <div class="actions">
          <button class="btn btn-light border" title="Reset">
            <i class="bi bi-arrow-counterclockwise me-1"></i><span>Reset</span>
          </button>
          <button class="btn btn-dark btn-apply">
            <i class="bi bi-funnel me-1"></i><span>Apply Filter</span>
          </button>
        </div>

      </div>
    </div>

    {{-- ===== Completed Orders 表格（无 Proof File 列） ===== --}}
    <div class="card border-0 shadow-soft">
      <div class="card-body">

        <div class="d-flex justify-content-between align-items-center mb-2 small text-muted">
          <div class="fw-semibold">Completed Orders</div>
          <div>248 total results</div>
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
              <tr>
                <td>ORD005-P1</td>
                <td>Business Cards - Premium</td>
                <td>Jan 15, 2025</td>
                <td>Perfect quality</td>
                <td class="text-center">
                  <button class="icon-btn" title="View details"><i class="bi bi-eye"></i></button>
                </td>
              </tr>
              <tr>
                <td>ORD005-P2</td>
                <td>Flyers A4 - Standard</td>
                <td>Jan 14, 2025</td>
                <td>–</td>
                <td class="text-center">
                  <button class="icon-btn" title="View details"><i class="bi bi-eye"></i></button>
                </td>
              </tr>
              <tr>
                <td>ORD006-P1</td>
                <td>Brochure Tri-fold</td>
                <td>Jan 13, 2025</td>
                <td>Color correction applied</td>
                <td class="text-center">
                  <button class="icon-btn" title="View details"><i class="bi bi-eye"></i></button>
                </td>
              </tr>
              <tr>
                <td>ORD007-P1</td>
                <td>Poster A2 - Glossy</td>
                <td>Jan 12, 2025</td>
                <td>Rush order completed</td>
                <td class="text-center">
                  <button class="icon-btn" title="View details"><i class="bi bi-eye"></i></button>
                </td>
              </tr>
              <tr>
                <td>ORD007-P2</td>
                <td>Letterhead - Corporate</td>
                <td>Jan 11, 2025</td>
                <td>–</td>
                <td class="text-center">
                  <button class="icon-btn" title="View details"><i class="bi bi-eye"></i></button>
                </td>
              </tr>
              <tr>
                <td>ORD007-P5</td>
                <td>Banner 3×6 feet</td>
                <td>Jan 10, 2025</td>
                <td>Weather resistant material</td>
                <td class="text-center">
                  <button class="icon-btn" title="View details"><i class="bi bi-eye"></i></button>
                </td>
              </tr>
              <tr>
                <td>ORD008-P1</td>
                <td>Menu Cards - Restaurant</td>
                <td>Jan 09, 2025</td>
                <td>Laminated finish</td>
                <td class="text-center">
                  <button class="icon-btn" title="View details"><i class="bi bi-eye"></i></button>
                </td>
              </tr>
              <tr>
                <td>ORD008-P3</td>
                <td>Stickers - Custom Shape</td>
                <td>Jan 08, 2025</td>
                <td>Die-cut precision</td>
                <td class="text-center">
                  <button class="icon-btn" title="View details"><i class="bi bi-eye"></i></button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        {{-- ===== 分页 ===== --}}
        <div class="d-flex justify-content-end mt-3">
          <nav>
            <ul class="pagination mb-0">
              <li class="page-item"><a class="page-link" href="#"><i class="bi bi-chevron-left"></i></a></li>
              <li class="page-item active"><span class="page-link">1</span></li>
              <li class="page-item"><a class="page-link" href="#">2</a></li>
              <li class="page-item"><a class="page-link" href="#">3</a></li>
              <li class="page-item disabled"><span class="page-link">…</span></li>
              <li class="page-item"><a class="page-link" href="#">31</a></li>
              <li class="page-item"><a class="page-link" href="#"><i class="bi bi-chevron-right"></i></a></li>
            </ul>
          </nav>
        </div>

        <div class="small text-muted mt-2">Showing 1 to 8 of 248 results</div>
      </div>
    </div>

  </div>
</div>
@endsection
