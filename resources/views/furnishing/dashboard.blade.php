@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
/* ===== KPI ===== */
.kpi-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px}
.kpi-card{border:1px solid #ECEFF3;background:#fff;border-radius:14px;box-shadow:0 2px 6px rgba(16,24,40,.05);padding:20px;display:flex;align-items:center;justify-content:space-between}
.kpi-title{color:#667085;font-weight:600;font-size:14px}
.kpi-value{font-size:40px;line-height:1.1;color:#111827;font-weight:800;letter-spacing:-.5px}
.kpi-icon{width:40px;height:40px;border-radius:12px;background:#F4F6FA;color:#667085;display:flex;align-items:center;justify-content:center;font-size:18px}

/* ===== Card & Table ===== */
.card{background:#fff;border:1px solid #ECEFF3;border-radius:14px;box-shadow:0 1px 2px rgba(16,24,40,.05)}
.table-card .card-hd{padding:12px 16px;font-weight:700;border-bottom:1px solid #EEF2F7}
.table-card .card-ft{padding:12px 16px;border-top:1px solid #EEF2F7;background:#fff}

.table-wrapper{overflow:hidden}
.table{width:100%;border-collapse:separate;border-spacing:0;table-layout:fixed}
.table thead th{background:#F8FAFC;color:#6B7280;font-weight:600;font-size:12px;letter-spacing:.2px;border-bottom:1px solid #EEF2F7;text-align:left;padding:14px 16px}
.table td{color:#1F2937;padding:14px 16px;border-top:1px solid #F1F4F8;vertical-align:middle}
.table tbody tr:hover{background:#FAFBFC}
.table td:first-child{font-weight:600;color:#111827}

/* ===== Action 按钮 ===== */
.action-btn{width:28px;height:28px;padding:0;display:inline-flex;align-items:center;justify-content:center;border:1px solid #E3E8EF;border-radius:8px;background:#fff;color:#707780}
.action-btn:hover{background:#F5F8FB;color:#111927;border-color:#D7DFE7}
.action-btn + .action-btn{margin-left:6px}

/* ===== 轻量弹窗 ===== */
.cx-mask{position:fixed;inset:0;background:rgba(15,23,42,.45);display:none;z-index:1080}
.cx-mask.show{display:grid;place-items:center}
.cx-modal{width:560px;max-width:92vw;background:#fff;border:1px solid #E7EAF0;border-radius:14px;box-shadow:0 24px 80px rgba(2,6,23,.28);overflow:hidden}
.cx-header{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #EDF0F3}
.cx-title{font-weight:700;color:#0F172A}
.cx-close{border:0;background:transparent;color:#94A3B8}
.cx-close:hover{color:#6B7280}
.cx-body{display:flex;gap:14px;align-items:flex-start;padding:18px}
.cx-q{font-weight:600;color:#111827;margin-bottom:4px}
.cx-help{color:#667085}
.cx-qicon{width:34px;height:34px;border-radius:10px;background:#F3F4F6;color:#6B7280;display:flex;align-items:center;justify-content:center}
.cx-footer{display:flex;justify-content:flex-end;gap:10px;padding:14px 16px;border-top:1px solid #EDF0F3;background:#FBFBFC}
.cx-btn{border-radius:10px;padding:10px 18px;font-weight:700}
.cx-btn-ghost{background:#EEF2F6;border:1px solid #E5E7EB;color:#0F172A}
.cx-btn-ghost:hover{background:#E2E8F0}
.cx-btn-dark{background:#111827;border:1px solid #111827;color:#fff}
.cx-btn-dark:hover{background:#0B1220;border-color:#0B1220}

/* Furnishing：Submission Date / Actions —— 稍微拉开一点 */
.furnishing .table thead th:nth-child(5),
.furnishing .table tbody td:nth-child(5){
  width: 175px !important;      /* 原 150px → 175px */
  white-space: nowrap;
  padding-right: 12px !important; /* 原 6px → 12px，和 Actions 拉开一点 */
}

.furnishing .table thead th:nth-child(6),
.furnishing .table tbody td:nth-child(6){
  width: 210px !important;        /* 原 200px → 210px，图标不拥挤 */
  white-space: nowrap;
  padding-left: 12px !important;  /* 原 10px → 12px */
  text-align: left !important;
}

</style>

<div class="container-fluid py-4 px-4">
  <div class="content-inner" style="max-width:1200px;margin:0 auto;">
    <h1 class="fw-bold" style="font-size:32px;letter-spacing:-.3px;">Dashboard Overview</h1>

    <!-- KPI -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div>
          <div class="kpi-title mb-1">In Progress</div>
          <div class="kpi-value">24</div>
        </div>
        <div class="kpi-icon"><i class="bi bi-clock"></i></div>
      </div>
      <div class="kpi-card">
        <div>
          <div class="kpi-title mb-1">Completed</div>
          <div class="kpi-value">156</div>
        </div>
        <div class="kpi-icon"><i class="bi bi-check2"></i></div>
      </div>
    </div>

    <!-- Furnishing Table -->
    <section class="card table-card furnishing">
      <div class="card-hd">Furnishing Jobs</div>

      <div class="table-wrapper">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>PRODUCT ID</th>
              <th>CUTTER</th>
              <th>SQ INCH</th>
              <th>DEADLINE</th>
              <th>SUBMISSION DATE</th>
              <th>ACTIONS</th>
            </tr>
          </thead>
          <tbody>
          @php
            $rows = [
              ['ORD005-P1','Jinwei 1 6x10','2500 sq in','2025-09-10','2025-09-08'],
              ['ORD006-P1','AOL1 6x10','1800 sq in','2025-09-12','2025-09-09'],
              ['ORD007-P1','AOL2 1000x700','3200 sq in','2025-09-18','2025-09-08'],
              ['ORD014-P2','Router 1','1500 sq in','2025-09-20','2025-09-10'],
              ['ORD015-P1','Laser 1 300W','2700 sq in','2025-09-14','2025-09-11'],
              ['ORD007-P3','Laser 2 150W','4000 sq in','2025-09-22','2025-09-12'],
              ['ORD006-P5','Laser 3 150W','2100 sq in','2025-09-15','2025-09-12'],
              ['ORD006-P3','AOL1 6x10','3600 sq in','2025-09-25','2025-09-13'],
              ['ORD018-P2','Paper cutter','2900 sq in','2025-09-19','2025-09-13'],
              ['ORD020-P3','AOL1 6x10','3300 sq in','2025-09-28','2025-09-14'],
            ];
          @endphp

          @foreach($rows as $r)
            <tr>
              <td>{{ $r[0] }}</td>
              <td>{{ $r[1] }}</td>
              <td>{{ $r[2] }}</td>
              <td>{{ $r[3] }}</td>
              <td>{{ $r[4] }}</td>
              <td>
                <button class="action-btn" title="View"><i class="bi bi-eye"></i></button>
                <button class="action-btn js-furnish-done" data-id="{{ $r[0] }}" title="Mark as Furnished"><i class="bi bi-check2"></i></button>
                <button class="action-btn" title="Issue"><i class="bi bi-exclamation-triangle"></i></button>
                <button class="action-btn" title="Edit"><i class="bi bi-pencil"></i></button>
              </td>
            </tr>
          @endforeach
          </tbody>
        </table>
      </div>

      <div class="card-ft">
        <nav class="d-flex justify-content-end">
          <ul class="pagination mb-0">
            <li class="page-item disabled"><span class="page-link">Previous</span></li>
            <li class="page-item active"><span class="page-link">1</span></li>
            <li class="page-item"><a class="page-link" href="#">2</a></li>
            <li class="page-item"><a class="page-link" href="#">3</a></li>
            <li class="page-item"><a class="page-link" href="#">Next</a></li>
          </ul>
        </nav>
      </div>
    </section>
  </div>
</div>

<!-- 确认弹窗（Furnishing -> Dispatch Control） -->
<div id="popConfirmFurnish" class="cx-mask" aria-hidden="true">
  <div class="cx-modal" role="dialog" aria-modal="true" aria-labelledby="cxTitleFurnish">
    <div class="cx-header">
      <div id="cxTitleFurnish" class="cx-title">Confirmation</div>
      <button type="button" class="cx-close" data-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="cx-body">
      <div class="cx-qicon"><i class="bi bi-question-lg"></i></div>
      <div>
        <div class="cx-q">Have you completed furnishing?</div>
        <div class="cx-help">This action will save the job order and move it to the dispatch control phase.</div>
      </div>
    </div>
    <div class="cx-footer">
      <button type="button" class="cx-btn cx-btn-ghost" data-close>No</button>
      <button type="button" class="cx-btn cx-btn-dark" id="btnFurnishYes">Yes</button>
    </div>
  </div>
</div>

<script>
(() => {
  const mask = document.getElementById('popConfirmFurnish');
  let currentId = null;

  // 打开弹窗
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.js-furnish-done');
    if (!btn) return;
    currentId = btn.dataset.id || null;
    mask.classList.add('show');
    mask.setAttribute('aria-hidden','false');
  });

  // 关闭弹窗（遮罩或带 data-close 的按钮）
  mask.addEventListener('click', (e) => {
    if (e.target === mask || e.target.hasAttribute('data-close')) {
      mask.classList.remove('show');
      mask.setAttribute('aria-hidden','true');
    }
  });

  // 确认完成（提交到后端）
  document.getElementById('btnFurnishYes').addEventListener('click', () => {
    // TODO: 调用后端接口：把 currentId 从 Furnishing 移到 Dispatch Control
    console.log('Marked as furnished:', currentId);
    mask.classList.remove('show');
    mask.setAttribute('aria-hidden','true');
  });
})();
</script>
@endsection
