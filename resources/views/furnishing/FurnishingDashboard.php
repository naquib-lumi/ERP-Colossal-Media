@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
/* ===== KPI 卡片 ===== */
.kpi-card {
  border: 1px solid #ECEFF3;
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 2px 6px rgba(16,24,40,.05);
  padding: 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.kpi-card .kpi-title {
  color: #667085;
  font-weight: 600;
  font-size: 14px;
}
.kpi-card .kpi-value {
  font-size: 40px;
  line-height: 1.1;
  color: #111827;
  font-weight: 700;
  letter-spacing: -0.5px;
}
.kpi-card .kpi-icon {
  width: 40px; height: 40px;
  border-radius: 12px;
  background: #F4F6FA;
  color: #667085;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 18px;
}

/* ===== 表格整体 ===== */
.table-wrapper {
  border: 1px solid #ECEFF3;
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
}
.table thead th {
  background: #F8FAFC;
  color: #6B7280;
  font-weight: 600;
  font-size: 12px;
  letter-spacing: .2px;
  border-bottom: 1px solid #EEF2F7;
}
.table td {
  color: #1F2937;
  padding: 14px 16px;
  border-top: 1px solid #F1F4F8;
  vertical-align: middle;
}
.table tbody tr:hover {
  background: #FAFBFC;
}
.table td:first-child {
  font-weight: 600;
  color: #111827;
}

/* ===== Action 按钮 ===== */
.action-btn {
  width: 28px; height: 28px;
  padding: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #E3E8EF;
  border-radius: 8px;
  background: #fff;
  color: #707780;
}
.action-btn:hover {
  background: #F5F8FB;
  color: #111927;
  border-color: #D7DFE7;
}
.action-btn + .action-btn { margin-left: 6px; }

/* ===== 分页 ===== */
.pagination .page-link {
  border: 1px solid #E5EAF0;
  background: #fff;
  color: #475467;
  padding: .4rem .7rem;
  border-radius: 10px;
}
.pagination .page-item.active .page-link {
  background: #3B82F6;
  border-color: #3B82F6;
  color: #fff;
  box-shadow: 0 2px 6px rgba(59,130,246,.25);
}
.pagination .page-link:hover {
  background: #F6F8FB;
  color: #111827;
}
</style>

<div class="container-fluid py-4 px-4">
  <h1 class="h4 fw-bold mb-4">Dashboard Overview</h1>

  <!-- KPI 卡片 -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
      <div class="kpi-card">
        <div>
          <div class="kpi-title mb-1">In Progress</div>
          <div class="kpi-value">24</div>
        </div>
        <div class="kpi-icon"><i class="bi bi-clock"></i></div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="kpi-card">
        <div>
          <div class="kpi-title mb-1">Completed</div>
          <div class="kpi-value">156</div>
        </div>
        <div class="kpi-icon"><i class="bi bi-check2"></i></div>
      </div>
    </div>
  </div>

  <!-- 表格 -->
  <div class="table-wrapper">
    <table class="table align-middle mb-0">
      <thead>
        <tr>
          <th>PRODUCT ID</th>
          <th>CUTTER</th>
          <th>SQ INCH</th>
          <th>DEADLINE</th>
          <th>SUBMISSION DATE</th>
          <th class="text-center">ACTIONS</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>ORD005-P1</td>
          <td>Jinwei 1 6x10</td>
          <td>2500 sq in</td>
          <td>2025-09-10</td>
          <td>2025-09-08</td>
          <td class="text-center">
            <button class="action-btn"><i class="bi bi-eye"></i></button>
            <button class="action-btn"><i class="bi bi-check2"></i></button>
            <button class="action-btn"><i class="bi bi-exclamation-triangle"></i></button>
            <button class="action-btn"><i class="bi bi-pencil"></i></button>
          </td>
        </tr>
        <tr>
          <td>ORD006-P1</td>
          <td>AOL1 6x10</td>
          <td>1800 sq in</td>
          <td>2025-09-12</td>
          <td>2025-09-09</td>
          <td class="text-center">
            <button class="action-btn"><i class="bi bi-eye"></i></button>
            <button class="action-btn"><i class="bi bi-check2"></i></button>
            <button class="action-btn"><i class="bi bi-exclamation-triangle"></i></button>
            <button class="action-btn"><i class="bi bi-pencil"></i></button>
          </td>
        </tr>
        <tr>
          <td>ORD007-P1</td>
          <td>AOL2 1000x700</td>
          <td>3200 sq in</td>
          <td>2025-09-18</td>
          <td>2025-09-08</td>
          <td class="text-center">
            <button class="action-btn"><i class="bi bi-eye"></i></button>
            <button class="action-btn"><i class="bi bi-check2"></i></button>
            <button class="action-btn"><i class="bi bi-exclamation-triangle"></i></button>
            <button class="action-btn"><i class="bi bi-pencil"></i></button>
          </td>
        </tr>
        <tr>
          <td>ORD014-P2</td>
          <td>Router 1</td>
          <td>1500 sq in</td>
          <td>2025-09-20</td>
          <td>2025-09-10</td>
          <td class="text-center">
            <button class="action-btn"><i class="bi bi-eye"></i></button>
            <button class="action-btn"><i class="bi bi-check2"></i></button>
            <button class="action-btn"><i class="bi bi-exclamation-triangle"></i></button>
            <button class="action-btn"><i class="bi bi-pencil"></i></button>
          </td>
        </tr>
        <tr>
          <td>ORD015-P1</td>
          <td>Laser 1 300W</td>
          <td>2700 sq in</td>
          <td>2025-09-14</td>
          <td>2025-09-11</td>
          <td class="text-center">
            <button class="action-btn"><i class="bi bi-eye"></i></button>
            <button class="action-btn"><i class="bi bi-check2"></i></button>
            <button class="action-btn"><i class="bi bi-exclamation-triangle"></i></button>
            <button class="action-btn"><i class="bi bi-pencil"></i></button>
          </td>
        </tr>
        <tr>
          <td>ORD007-P3</td>
          <td>Laser 2 150W</td>
          <td>4000 sq in</td>
          <td>2025-09-22</td>
          <td>2025-09-12</td>
          <td class="text-center">
            <button class="action-btn"><i class="bi bi-eye"></i></button>
            <button class="action-btn"><i class="bi bi-check2"></i></button>
            <button class="action-btn"><i class="bi bi-exclamation-triangle"></i></button>
            <button class="action-btn"><i class="bi bi-pencil"></i></button>
          </td>
        </tr>
        <tr>
          <td>ORD006-P5</td>
          <td>Laser 3 150W</td>
          <td>2100 sq in</td>
          <td>2025-09-15</td>
          <td>2025-09-12</td>
          <td class="text-center">
            <button class="action-btn"><i class="bi bi-eye"></i></button>
            <button class="action-btn"><i class="bi bi-check2"></i></button>
            <button class="action-btn"><i class="bi bi-exclamation-triangle"></i></button>
            <button class="action-btn"><i class="bi bi-pencil"></i></button>
          </td>
        </tr>
        <tr>
          <td>ORD006-P3</td>
          <td>AOL1 6x10</td>
          <td>3600 sq in</td>
          <td>2025-09-25</td>
          <td>2025-09-13</td>
          <td class="text-center">
            <button class="action-btn"><i class="bi bi-eye"></i></button>
            <button class="action-btn"><i class="bi bi-check2"></i></button>
            <button class="action-btn"><i class="bi bi-exclamation-triangle"></i></button>
            <button class="action-btn"><i class="bi bi-pencil"></i></button>
          </td>
        </tr>
        <tr>
          <td>ORD018-P2</td>
          <td>Paper cutter</td>
          <td>2900 sq in</td>
          <td>2025-09-19</td>
          <td>2025-09-13</td>
          <td class="text-center">
            <button class="action-btn"><i class="bi bi-eye"></i></button>
            <button class="action-btn"><i class="bi bi-check2"></i></button>
            <button class="action-btn"><i class="bi bi-exclamation-triangle"></i></button>
            <button class="action-btn"><i class="bi bi-pencil"></i></button>
          </td>
        </tr>
        <tr>
          <td>ORD020-P3</td>
          <td>AOL1 6x10</td>
          <td>3300 sq in</td>
          <td>2025-09-28</td>
          <td>2025-09-14</td>
          <td class="text-center">
            <button class="action-btn"><i class="bi bi-eye"></i></button>
            <button class="action-btn"><i class="bi bi-check2"></i></button>
            <button class="action-btn"><i class="bi bi-exclamation-triangle"></i></button>
            <button class="action-btn"><i class="bi bi-pencil"></i></button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>

  <!-- 分页 -->
  <nav class="mt-3">
    <ul class="pagination justify-content-end">
      <li class="page-item disabled"><span class="page-link">Previous</span></li>
      <li class="page-item active"><span class="page-link">1</span></li>
      <li class="page-item"><a class="page-link" href="#">2</a></li>
      <li class="page-item"><a class="page-link" href="#">3</a></li>
      <li class="page-item"><a class="page-link" href="#">Next</a></li>
    </ul>
  </nav>
</div>
@endsection
