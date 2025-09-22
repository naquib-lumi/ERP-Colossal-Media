@extends('layouts.app')

@section('content')
<!-- Bootstrap Icons（放在这里就可以用） -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

{{-- 尺寸&对齐微调（不改设计） --}}
<style>
  /* 统一工具条控件尺寸 */
  .toolbar-controls .form-control,
  .toolbar-controls .form-select { height:40px; font-size:14px; }
  .toolbar-controls .form-select { min-width:170px; }
  .toolbar-controls .btn { height:40px; padding:0 16px; }

  /* Actions 按钮（32x32，圆角8px） */
  .action-btn{
    width:32px; height:32px; padding:0;
    display:inline-flex; align-items:center; justify-content:center;
    border:1px solid #D0D5DD; border-radius:8px; background:#fff; color:#475467;
  }
  .action-btn:hover{ background:#F2F4F7; color:#343a40; }
</style>


<div class="container-fluid py-4 px-4">
    <h1 class="h4 fw-bold mb-4">Job Order Table</h1>

    <!-- Stats (保持不变) -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-4 col-lg-2">
            <div class="d-flex align-items-center bg-white rounded shadow-sm p-3">
                <div class="me-3 text-primary fs-4"><i class="bi bi-printer"></i></div>
                <div>
                    <div class="fw-bold">10</div>
                    <small class="text-muted">Printing Overview</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="d-flex align-items-center bg-white rounded shadow-sm p-3">
                <div class="me-3 text-secondary fs-4"><i class="bi bi-lamp"></i></div>
                <div>
                    <div class="fw-bold">7</div>
                    <small class="text-muted">Furnishing Overview</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="d-flex align-items-center bg-white rounded shadow-sm p-3">
                <div class="me-3 text-info fs-4"><i class="bi bi-truck"></i></div>
                <div>
                    <div class="fw-bold">12</div>
                    <small class="text-muted">Delivery Needing Permit</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="d-flex align-items-center bg-white rounded shadow-sm p-3">
                <div class="me-3 text-dark fs-4"><i class="bi bi-wrench"></i></div>
                <div>
                    <div class="fw-bold">5</div>
                    <small class="text-muted">Installation Needing Permit</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="d-flex align-items-center bg-white rounded shadow-sm p-3">
                <div class="me-3 text-muted fs-4"><i class="bi bi-bag"></i></div>
                <div>
                    <div class="fw-bold">6</div>
                    <small class="text-muted">Self Pickup Overview</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-4 col-lg-2">
            <div class="d-flex align-items-center bg-white rounded shadow-sm p-3">
                <div class="me-3 text-success fs-4"><i class="bi bi-bicycle"></i></div>
                <div>
                    <div class="fw-bold">8</div>
                    <small class="text-muted">Courier Overview</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Toolbar (仅加 .toolbar-controls 统一高度) -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 toolbar-controls">
            <input type="text" class="form-control" placeholder="Search by Order ID or Job Title">
            <div class="d-flex gap-2">
                <select class="form-select">
                    <option>All Task Types</option>
                    <option>Printing</option>
                    <option>Self Pickup</option>
                    <option>Installation</option>
                    <option>Courier</option>
                    <option>Furnishing</option>
                </select>
                <select class="form-select">
                    <option>All Statuses</option>
                    <option>In Progress</option>
                    <option>Completed</option>
                </select>
                <button class="btn btn-dark"><i class="bi bi-download me-1"></i> Export CSV</button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light small text-muted">
                    <tr>
                        <th>PRODUCT ID</th>
                        <th>PRODUCT NAME</th>
                        <th>TASK TYPE</th>
                        <th>DEADLINE</th>
                        <th>STATUS</th>
                        <th>DELIVERY DATE</th>
                        <th>DELIVERY LOCATION</th>
                        <th class="text-end">ACTIONS</th>
                    </tr>
                </thead>
                <tbody class="small">
                    <tr>
                        <td>#ORD003-P1</td>
                        <td>Business Cards</td>
                        <td><span class="badge bg-primary-subtle text-primary">Printing</span></td>
                        <td>2025-07-25</td>
                        <td><span class="badge bg-warning-subtle text-warning">In Progress</span></td>
                        <td><i class="bi bi-exclamation-triangle text-warning"></i></td>
                        <td>Be confirm</td>
                        <td class="text-end">
                            <button class="action-btn me-1" title="View"><i class="bi bi-eye"></i></button>
                            <button class="action-btn" title="Edit"><i class="bi bi-pencil"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td>#ORD003-P1</td>
                        <td>Business Cards</td>
                        <td><span class="badge bg-secondary-subtle text-secondary">Self Pickup</span></td>
                        <td>2025-07-25</td>
                        <td><span class="badge bg-warning-subtle text-warning">In Progress</span></td>
                        <td>—</td>
                        <td>Not required for pickup</td>
                        <td class="text-end">
                            <button class="action-btn me-1" title="View"><i class="bi bi-eye"></i></button>
                            <button class="action-btn" title="Edit"><i class="bi bi-pencil"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td>#ORD005-P2</td>
                        <td>Flyers A4 - Standard</td>
                        <td><span class="badge bg-info-subtle text-info">Installation</span></td>
                        <td>2025-07-27</td>
                        <td><span class="badge bg-success">Completed</span></td>
                        <td>2025-07-26</td>
                        <td>123 Main St, City, State 12345</td>
                        <td class="text-end">
                            <button class="action-btn me-1" title="View"><i class="bi bi-eye"></i></button>
                            <button class="action-btn" title="Edit"><i class="bi bi-pencil"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td>#ORD002-P1</td>
                        <td>Banners</td>
                        <td><span class="badge bg-dark-subtle text-dark">Courier</span></td>
                        <td>2025-07-20</td>
                        <td><span class="badge bg-success">Completed</span></td>
                        <td>2025-07-20</td>
                        <td>77 Highway View, Media Hub District, State 99999</td>
                        <td class="text-end">
                            <button class="action-btn me-1" title="View"><i class="bi bi-eye"></i></button>
                            <button class="action-btn" title="Edit"><i class="bi bi-pencil"></i></button>
                        </td>
                    </tr>
                    <tr>
                        <td>#ORD006-P3</td>
                        <td>Posters</td>
                        <td><span class="badge bg-secondary-subtle text-secondary">Furnishing</span></td>
                        <td>2025-07-30</td>
                        <td><span class="badge bg-warning-subtle text-warning">In Progress</span></td>
                        <td><i class="bi bi-exclamation-triangle text-warning"></i></td>
                        <td>—</td>
                        <td class="text-end">
                            <button class="action-btn me-1" title="View"><i class="bi bi-eye"></i></button>
                            <button class="action-btn" title="Edit"><i class="bi bi-pencil"></i></button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination (原样保留) -->
        <div class="d-flex justify-content-between align-items-center px-4 py-3 small text-muted">
            <div>Showing 1 to 5 of 24 results</div>
            <nav>
                <ul class="pagination mb-0">
                    <li class="page-item"><a class="page-link" href="#">Previous</a></li>
                    <li class="page-item active"><span class="page-link">1</span></li>
                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                    <li class="page-item"><a class="page-link" href="#">3</a></li>
                    <li class="page-item"><a class="page-link" href="#">Next</a></li>
                </ul>
            </nav>
        </div>
    </div>
</div>
@endsection
