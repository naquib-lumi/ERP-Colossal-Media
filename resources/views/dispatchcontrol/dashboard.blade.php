@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
/* ===== 基础表格：固定列宽，确保四个阶段列等宽 ===== */
.table-progress { table-layout: fixed; }
.table-progress col.col-id        { width: 160px; }
.table-progress col.col-stage     { width: 18%; }   /* 4 列阶段，每列等宽 */
.table-progress col.col-date      { width: 140px; }
.table-progress col.col-deadline  { width: 140px; }
.table-progress col.col-actions   { width: 110px; }
.table-progress thead th { font-size:12px; color:#475467; font-weight:700; }
.table-progress td, .table-progress th { vertical-align: middle; padding:16px 14px; }

/* ===== 统一轨道（跨四列） ===== */
.pipeline {
  position: relative;
  height: 18px;            /* 行高留一点空间好看 */
}
.pipeline .track {
  position: absolute; left:0; right:0; top:50%;
  height:6px; transform: translateY(-50%);
  border-radius: 999px; background:#E5E7EB;    /* 整条浅灰底 */
}
.pipeline .fill {
  position:absolute; left:0; top:50%;
  transform: translateY(-50%);
  height:6px; border-radius:999px;
  background:#12B76A;      /* 绿色完成段 */
  width: var(--progress, 0%);   /* 关键：控制到哪一节点 */
}

/* 四个节点（正中对齐四列中心） */
.dot {
  position:absolute; top:50%; transform:translate(-50%,-50%);
  width:12px; height:12px; border-radius:50%;
  background:#12B76A; box-shadow:0 0 0 2px #fff;
}
.dot.gray { background:#98A2B3; }
.dot.red  { background:#F04438; }

/* 节点位置：四列中心（12.5%、37.5%、62.5%、87.5%） */
.dot.p1 { left:12.5%; }
.dot.p2 { left:37.5%; }
.dot.p3 { left:62.5%; }
.dot.p4 { left:87.5%; }

/* Actions */
.action-btn{
  width:32px; height:32px; border:1px solid #D0D5DD; border-radius:8px; background:#fff; color:#475467;
  display:inline-flex; align-items:center; justify-content:center;
}
.action-btn:hover{ background:#F2F4F7; color:#344054; }

/* 统计卡片 */
.stat-card {
  border:1px solid #E5E7EB;
  border-radius:12px;
  padding:20px;
  display:flex; align-items:center; justify-content:space-between;
  background:#fff;
  box-shadow:0 1px 2px rgba(16,24,40,.06);
}
.stat-card .num { font-size:32px; font-weight:700; }
.stat-card small { color:#667085; }

</style>
<div class="container-fluid py-4 px-4">
  <h1 class="h4 fw-bold mb-4">Dashboard Overview</h1>

  <!-- 顶部统计卡片 -->
  <div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
      <div class="stat-card">
        <div>
          <div class="text-muted mb-1">In Progress</div>
          <div class="num">24</div>
        </div>
        <i class="bi bi-clock fs-3 text-secondary"></i>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="stat-card">
        <div>
          <div class="text-muted mb-1">Completed</div>
          <div class="num">156</div>
        </div>
        <i class="bi bi-check2 fs-3 text-success"></i>
      </div>
    </div>
  </div>

<div class="container-fluid py-4 px-4">
  <h1 class="h4 fw-bold mb-4">Dashboard Overview</h1>

  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <h5 class="mb-3">Production Status</h5>

      <div class="table-responsive">
        <table class="table table-progress align-middle mb-0">
          <!-- 固定列宽：四个阶段列等宽，下面的 colspan=4 就能拿到一整条轨道宽度 -->
          <colgroup>
            <col class="col-id">
            <col class="col-stage">
            <col class="col-stage">
            <col class="col-stage">
            <col class="col-stage">
            <col class="col-date">
            <col class="col-deadline">
            <col class="col-actions">
          </colgroup>

          <thead class="table-light">
            <tr>
              <th>PRODUCT ID</th>
              <th>PRINTING</th>
              <th>FURNISHING</th>
              <th>DISPATCH CONTROL</th>
              <th>DELIVERY & INSTALLATION</th>
              <th>DATE IN</th>
              <th>DEADLINE</th>
              <th class="text-center">ACTIONS</th>
            </tr>
          </thead>

          <tbody>
            <!-- Row 1：只有 Printing 灰点，进度 0%（和你的图一致） -->
            <tr>
              <td>#ORD005-P1</td>
              <td colspan="4">
                <div class="pipeline" style="--progress:0%;">
                  <div class="track"></div>
                  <div class="fill"></div>
                  <span class="dot gray p1"></span>
                </div>
              </td>
              <td>2025-07-25</td>
              <td>2025-07-15</td>
              <td class="text-center">
                <button class="action-btn" title="View"><i class="bi bi-eye"></i></button>
              </td>
            </tr>

            <!-- Row 2：绿线从 Printing 到 Delivery 中间，最后 Delivery 灰点 -->
            <tr>
              <td>#ORD003-P4</td>
              <td colspan="4">
                <div class="pipeline" style="--progress:62.5%;">
                  <div class="track"></div>
                  <div class="fill"></div>
                  <span class="dot p1"></span>
                  <span class="dot p2"></span>
                  <span class="dot p3"></span>
                  <span class="dot gray p4"></span>
                </div>
              </td>
              <td>2025-07-28</td>
              <td>2025-07-18</td>
              <td class="text-center">
                <button class="action-btn" title="View"><i class="bi bi-eye"></i></button>
              </td>
            </tr>

            <!-- Row 3：绿线到 Dispatch 前（约 42%），Dispatch 处红点 -->
            <tr>
              <td>#ORD005-P2</td>
              <td colspan="4">
                <div class="pipeline" style="--progress:42%;">
                  <div class="track"></div>
                  <div class="fill"></div>
                  <span class="dot p1"></span>
                  <span class="dot p2"></span>
                  <span class="dot red p3"></span>
                  <span class="dot gray p4" style="opacity:.35"></span>
                </div>
              </td>
              <td>2025-07-20</td>
              <td>2025-07-10</td>
              <td class="text-center">
                <div class="d-inline-flex gap-1">
                  <button class="action-btn" title="View"><i class="bi bi-eye"></i></button>
                  <button class="action-btn" title="Edit"><i class="bi bi-pencil"></i></button>
                  <button class="action-btn" title="Done"><i class="bi bi-check2"></i></button>
                </div>
              </td>
            </tr>

            <!-- Row 4：到 Dispatch 完成（62.5%），Delivery 端出现问题（p4 红点） -->
            <tr>
              <td>#ORD001-P3</td>
              <td colspan="4">
                <div class="pipeline" style="--progress:62.5%;">
                  <div class="track"></div>
                  <div class="fill"></div>
                  <span class="dot p1"></span>
                  <span class="dot p2"></span>
                  <span class="dot p3"></span>
                  <span class="dot red p4"></span>
                </div>
              </td>
              <td>2025-07-22</td>
              <td>2025-07-11</td>
              <td class="text-center">
                <button class="action-btn" title="View"><i class="bi bi-eye"></i></button>
              </td>
            </tr>

            <!-- Row 5：全流程完成（到 p4） -->
            <tr>
              <td>#ORD007-P6</td>
              <td colspan="4">
                <div class="pipeline" style="--progress:87.5%;">
                  <div class="track"></div>
                  <div class="fill"></div>
                  <span class="dot p1"></span>
                  <span class="dot p2"></span>
                  <span class="dot p3"></span>
                  <span class="dot p4"></span>
                </div>
              </td>
              <td>2025-07-30</td>
              <td>2025-07-16</td>
              <td class="text-center">
                <div class="d-inline-flex gap-1">
                  <button class="action-btn" title="View"><i class="bi bi-eye"></i></button>
                  <button class="action-btn" title="Edit"><i class="bi bi-pencil"></i></button>
                  <button class="action-btn" title="Done"><i class="bi bi-check2"></i></button>
                </div>
              </td>
            </tr>

            <!-- Row 6：到 p2（37.5%）完成，后续待开始 -->
            <tr>
              <td>#ORD008-P1</td>
              <td colspan="4">
                <div class="pipeline" style="--progress:37.5%;">
                  <div class="track"></div>
                  <div class="fill"></div>
                  <span class="dot p1"></span>
                  <span class="dot p2"></span>
                  <span class="dot gray p3" style="opacity:.35"></span>
                  <span class="dot gray p4" style="opacity:.35"></span>
                </div>
              </td>
              <td>2025-07-18</td>
              <td>2025-07-09</td>
              <td class="text-center">
                <button class="action-btn" title="View"><i class="bi bi-eye"></i></button>
              </td>
            </tr>

            <!-- Row 7：只完成 p1（12.5%），其余待开始 -->
            <tr>
              <td>#ORD009-P2</td>
              <td colspan="4">
                <div class="pipeline" style="--progress:12.5%;">
                  <div class="track"></div>
                  <div class="fill"></div>
                  <span class="dot p1"></span>
                  <span class="dot gray p2" style="opacity:.35"></span>
                  <span class="dot gray p3" style="opacity:.35"></span>
                  <span class="dot gray p4" style="opacity:.35"></span>
                </div>
              </td>
              <td>2025-07-21</td>
              <td>2025-07-12</td>
              <td class="text-center">
                <button class="action-btn" title="View"><i class="bi bi-eye"></i></button>
              </td>
            </tr>

  <!-- 全部完成：整条绿色，p1~p4 全部绿色 dot -->
  <tr>
    <td>#ORD010-P4</td>
    <td colspan="4">
      <div class="pipeline" style="--progress:100%;">
        <div class="track"></div>
        <div class="fill"></div>
        <span class="dot p1"></span>
        <span class="dot p2"></span>
        <span class="dot p3"></span>
        <span class="dot p4"></span>
      </div>
    </td>
    <td>2025-07-26</td>
    <td>2025-07-17</td>
    <td class="text-center">
      <button class="action-btn" title="View"><i class="bi bi-eye"></i></button>
    </td>
  </tr>

          </tbody>
        </table>
      </div>

    </div>
  </div>
</div>
@endsection
