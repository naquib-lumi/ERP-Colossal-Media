@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
  .form-switch-lg .form-check-input{width:3rem;height:1.5rem}
  .form-switch-lg .form-check-input:checked{background-color:#6366f1;border-color:#6366f1}

  /* ===== Cards ===== */
  .stat-card{border:1px solid #E7EAEE;border-radius:14px;background:#fff;padding:18px 20px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 1px 2px rgba(16,24,40,.05)}
  .stat-card .num{font-size:34px;font-weight:800;letter-spacing:-.3px}
  .stat-card .label{color:#6B7280;font-weight:600}

  /* ===== Filter card ===== */
  .filter-card .form-label{font-weight:600;font-size:.85rem}
  .filter-card .input-group.has-icon .input-group-text{background:#f8f9fb;border-right:0}
  .filter-card .input-group.has-icon .form-control,
  .filter-card .input-group.has-icon .form-select{border-left:0}
  .filter-card .btn.btn-dark{background:#1f2232;border-color:#1f2232}

  /* 顶部：标题 + (Filter Me / Reset / Apply) 同一排 */
  .filter-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:14px}
  .filter-head .title{display:flex;align-items:center;gap:8px}
  .filter-head .actions{display:flex;align-items:center;gap:12px}
  .filter-head .btn{border-radius:10px;height:36px;padding:0 14px;font-weight:600;font-size:13px}
  .filter-head .btn-outline-secondary{border-color:#D0D5DD}
  .filter-head .btn-outline-secondary:hover{background:#F2F4F7}

  /* Filter Me 对齐微调（让开关与文字齐平） */
  .filter-head .actions .form-check{margin:0}
  .filter-head .actions .form-check-input{
    width:2.35rem;height:1.2rem;border-radius:1rem;margin-top:0;margin-right:.35rem;vertical-align:middle
  }
  .filter-head .actions label[for="mineTop"]{margin:0;line-height:1;position:relative;top:1px}

  /* 输入控件：大屏一整排，小屏自动换行（跟参考图一样） */
  .filters-row{
    display:grid;
    grid-template-columns: minmax(180px,220px) minmax(280px,1fr) minmax(220px,280px) minmax(180px,220px) minmax(180px,220px);
    gap:12px;
  }
  @media (max-width: 1200px){
    .filters-row{ grid-template-columns: 1fr 1fr; }
  }
  @media (max-width: 576px){
    .filters-row{ grid-template-columns: 1fr; }
  }

  /* ===== Table ===== */
  .table-progress {
    table-layout: fixed
  }

  .table-progress col.col-id { width: 140px; } 

  .table-progress col.col-stage {
    width: 18%
  }

  .table-progress col.col-date {
    width: 110px
  }

  .table-progress col.col-deadline {
    width: 120px
  }

  .table-progress col.col-packaging { width: 110px; }

  .table-progress col.col-actions {
    width: 95px
  }

  .table-progress thead th {
    position: sticky;
    top: 0;
    z-index: 2;
    background: #F8FAFC;
    font-size: 12px;
    color: #475467;
    font-weight: 700;
    border-bottom: 1px solid #EDF1F6
  }

  .table-progress tbody tr:nth-child(odd) {
    background: #FCFCFD
  }

  .table-progress td,
  .table-progress th {
    padding: 14px 14px;
    vertical-align: middle
  }

  .table-progress tbody tr:hover {
    background: #FAFBFF
  }

  /* ===== Pipeline ===== */
  .pipeline {
    position: relative;
    height: 18px;
  }

  .pipeline .track {
    position: absolute;
    left: 0;
    right: 0;
    top: 50%;
    height: 6px;
    transform: translateY(-50%);
    border-radius: 999px;
    background: #E5E7EB;
  }

  .pipeline .fill {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    height: 6px;
    border-radius: 999px;
    background: #12B76A;
    left: var(--start, 0%);
    /* NEW: where the green segment begins */
    width: calc(var(--end, 0%) - var(--start, 0%));
    /* NEW: segment length */
  }

  .dot {
    position: absolute;
    top: 50%;
    transform: translate(-50%, -50%);
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #12B76A;
    box-shadow: 0 0 0 2px #fff;
  }

  .dot.gray {
    background: #98A2B3;
  }

  .dot.red {
    background: #F04438;
  }

  .dot.p1 {
    left: 6%
  }

  .dot.p2 {
    left: 35%
  }

  .dot.p3 {
    left: 60%
  }

  .dot.p4 {
    left: 87.5%
  }

  /* Node positions */
  .dot.p1 {
    left: 6%
  }

  .dot.p2 {
    left: 35%
  }

  .dot.p3 {
    left: 60%
  }

  .dot.p4 {
    left: 87.5%
  }

  /* Actions (compact, consistent) */
  .action-btn {
    width: 34px;
    height: 34px;
    border: 1px solid #D6DAE1;
    border-radius: 10px;
    background: #fff;
    color: #475467;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: .15s
  }

  .action-btn:hover {
    background: #EEF2F7;
    color: #1F2937
  }

  /* ===== Pagination ===== */
  .card-ft {
    padding: 12px 16px;
    border-top: 1px solid #EDF1F6;
    background: #fff
  }

  .pagination {
    display: flex;
    justify-content: center;
    gap: 6px;
    margin: 0
  }

  .pagination li {
    display: inline-block
  }

  .pagination .page-link {
    color: #475467;
    border: 1px solid #D0D5DD;
    border-radius: 10px;
    padding: 8px 12px;
    background: #fff;
    font-weight: 600;
    font-size: 13px;
    line-height: 1;
    transition: .15s
  }

  .pagination .page-link:hover {
    background: #F2F4F7;
    border-color: #CBD3DD
  }

  .pagination .active .page-link {
    background: #12B76A;
    border-color: #12B76A;
    color: #fff
  }

  .pagination .disabled .page-link {
    color: #A3AAB5;
    background: #F8FAFB;
    border-color: #E5E7EB;
    cursor: not-allowed
  }

  .table-responsive+nav[role="navigation"] {
    display: none !important;
  }

  /* tighten any paginator margins globally */
  nav[role="navigation"] {
    margin: 0 !important;
  }

  /* ===== Modal ===== */
  .cx-mask {position:fixed;inset:0;background:#0005;display:none;align-items:center;justify-content:center;z-index:1000}
  .cx-mask.show{display:flex}
  .cx-wrap{width:100%;padding:16px}
  .cx-modal{background:#fff;border-radius:12px;box-shadow:0 10px 30px #0003;margin:0 auto;max-width:640px}
  .cx-header,.cx-footer{padding:16px 20px;display:flex;gap:12px;align-items:center}
  .cx-body{padding:0 20px 16px 20px}
  .cx-title{font-weight:600}
  .cx-close{margin-left:auto;background:none;border:0}

  .cx-footer .btn {
    min-width: 140px;
    border-radius: 10px;
    padding: 10px 14px;
    font-weight: 600;
    transition: transform .04s ease, box-shadow .15s ease;
  }

  .cx-footer .btn:active { transform: translateY(1px); }

  .cx-footer .btn.btn-back {
    background: #f3f4f6;      /* light gray */
    border: 1px solid #e5e7eb;
    color: #374151;
  }
  .cx-footer .btn.btn-back:hover { background: #edeef1; }

  .cx-footer .btn.btn-accept {
    background: #16a34a;      /* emerald-600 */
    border: 1px solid #15803d;
    color: #fff;
    box-shadow: 0 6px 18px rgba(22,163,74,.22);
  }
  .cx-footer .btn.btn-accept:hover { background: #15803d; }
  @media (max-width: 768px){

  /* 顶部统计卡：更紧凑可换行 */
  .stat-card{
    padding:14px 16px;
    gap:10px;
  }
  .stat-card .num{font-size:28px}
  .stat-card .label{font-size:.8rem}

  /* 过滤头部：标题与动作分两行，按钮略缩小 */
  .filter-head{
    flex-direction: column;
    align-items: stretch;
    gap:10px;
  }
  .filter-head .title{
    justify-content: space-between;
  }
  .filter-head .actions{
    justify-content: flex-start;
    flex-wrap: wrap;
    gap:8px;
  }
  .filter-head .btn{
    height:34px;
    padding:0 12px;
    font-size:12px;
    border-radius:8px;
  }
  .filter-head .actions .form-check-input{
    width:2.1rem;height:1.1rem;
  }

  /* 输入区：两列 → 已有；进一步压缩间距与控件高度 */
  .filters-row{ gap:10px; }
  .filter-card .form-label{ font-size:.8rem; }
  .filter-card .input-group .form-control,
  .filter-card .input-group .form-select{
    height:38px;
    font-size:.9rem;
  }

  /* 表格：略缩字距与内边距；动作按钮更小 */
  .table-progress thead th{
    font-size:11px;
    padding:12px;
  }
  .table-progress td,
  .table-progress th{
    padding:12px 12px;
    font-size:.95rem;
  }
  .action-btn{
    width:32px;height:32px;border-radius:8px;
  }

  /* Pipeline 更细、更小的点 */
  .pipeline{ height:16px; }
  .pipeline .track{ height:5px; }
  .pipeline .fill{ height:5px; }
  .dot{
    width:10px;height:10px;
    box-shadow:0 0 0 1.5px #fff;
  }

  /* 分页更紧凑 */
  .card-ft{ padding:10px 12px; }
  .pagination{ gap:4px; }
  .pagination .page-link{
    padding:7px 10px;
    font-size:12px;
    border-radius:8px;
  }

  /* Modal 更贴边、更顺手 */
  .cx-wrap{ padding:12px; }
  .cx-modal{ border-radius:10px; }
  .cx-header,.cx-footer{ padding:12px 14px; }
  .cx-body{ padding:0 14px 12px 14px; }
  .cx-footer .btn{
    min-width:120px;
    padding:9px 12px;
    border-radius:8px;
  }
}

/* ----------- 小屏手机 (≤576px) ---------- */
@media (max-width: 576px){

  /* 统计卡：竖排布局，数字在上，标签在下 */
  .stat-card{
    padding:12px 14px;
    flex-direction: column;
    align-items: flex-start;
    gap:4px;
  }
  .stat-card .num{ font-size:24px; }
  .stat-card .label{ font-size:.78rem; }

  /* Filter 头：按钮全宽堆叠，开关与文字更紧 */
  .filter-head .actions{
    width:100%;
    gap:8px;
  }
  .filter-head .actions .btn{
    width:100%;
  }
  .filter-head .actions .form-check{
    width:100%;
    display:flex;
    align-items:center;
    gap:8px;
  }

  /* 输入区：单列（你原本已设），再缩进一丢丢 */
  .filters-row{
    grid-template-columns: 1fr;
    gap:8px;
  }

  /* 表格：允许横向滚动 + 缩窄列宽 + 减少内边距 */
  .table-responsive{
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
  }
  .table-progress{
    /* min-width: 720px;  */
    font-size:.92rem;
  }
  .table-progress col.col-id{ width:140px; }
  /* .table-progress col.col-stage{ width: 28%; } */
  .table-progress col.col-date,
  .table-progress col.col-deadline{ width:120px; }
  .table-progress col.col-actions{ width:96px; }

  .table-progress thead th{ padding:10px; }
  .table-progress td{ padding:10px; }

  /* 动作按钮与 icon 区更紧凑 */
  .action-btn{
    width:30px;height:30px;border-radius:8px;
  }

  /* Pipeline 再小一点 */
  .pipeline{ height:14px; }
  .pipeline .track{ height:4px; }
  .pipeline .fill{ height:4px; }
  .dot{ width:8px;height:8px; }

  /* 分页按钮满宽两端贴合（更容易点） */
  .pagination{
    justify-content: space-between;
    gap:6px;
  }
  .pagination .page-link{
    padding:7px 9px;
    font-size:11.5px;
    border-radius:8px;
  }

  /* Modal：小屏全屏化体验 */
  .cx-wrap{ padding:0; }
  .cx-modal{
    max-width:none;
    width:100%;
    height:100vh;
    border-radius:0;
    display:flex;flex-direction:column;
  }
  .cx-header,.cx-footer{ padding:12px; }
  .cx-body{
    padding:0 12px 12px 12px;
    overflow:auto;
    flex:1;
  }
  .cx-footer .btn{
    width:100%;
    min-width:0;
  }

  /* 隐藏 Laravel 默认分页外边距（你已有规则再强化一下） */
  nav[role="navigation"]{ margin:0 !important; }
}

@media (max-width:576px){
   /* DEADLINE cell: stack date + badge */
  .table-progress td:nth-child(7){
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
    white-space: normal !important;   /* allow wrapping inside */
  }

  /* PACKAGING cell: keep status separated, no overlap */
  .table-progress td:nth-child(8){
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
    white-space: normal !important;
  }

  /* make badges behave nicely (no collision) */
  .table-progress td:nth-child(7) .badge,
  .table-progress td:nth-child(7) .status-badge,
  .table-progress td:nth-child(8) .badge{
    display: inline-block;
    max-width: 100%;
    white-space: nowrap;
  }

  /* packaging button/text should not sit on same baseline as badge */
  .js-packaging-pending,
  .text-success{
    line-height: 1.2;
  }

  /* Give table more room → scroll instead of stacking text */
  .table-progress{
    min-width: 1100px !important;   /* KEY: more space */
  }

  /* Product ID: slightly smaller */
  .table-progress col.col-id{
    width: 130px !important;
  }

  /* STAGES: force proper readable width */
  .table-progress col.col-stage{
    width: 180px !important;        /* KEY: fixes vertical letters */
  }

  /* Date In / Deadline: tighter */
  .table-progress col.col-date{
    width: 100px !important;
  }
  .table-progress col.col-deadline{
    width: 110px !important;
  }

  /* Packaging: compact */
  .table-progress col.col-packaging{
    width: 95px !important;
  }

  /* Actions: smallest usable */
  .table-progress col.col-actions{
    width: 85px !important;
  }

  /* Keep headers in one line */
  .table-progress thead th{
    white-space: nowrap !important;
    word-break: normal !important;
  }

  .sm-hide{ display:none !important; }
  .sm-shrink{ font-size:.85em !important; }
}
</style>

<div class="container-fluid py-4 px-4" style="max-width:1200px;margin:0 auto">
  <h1 class="h4 fw-bold mb-3" style="letter-spacing:-.2px">Fullfilment Overview</h1>

  {{-- KPIs --}}
  <div class="row g-3 mb-4">
    <div class="col-12 col-lg-6 cursor-pointer" data-go-status="in_progress">
      <div class="stat-card">
        <div>
          <div class="label mb-1" style="color:#635bff;">In Progress</div>
          <div class="num" style="color:#635bff;">{{ $inProgress }}</div>
        </div>
      </div>
    </div>
    <div class="col-12 col-lg-6 cursor-pointer" data-go-status="completed">
      <div class="stat-card">
        <div>
          <div class="label mb-1" style="color:seagreen;">Completed</div>
          <div class="num" style="color:seagreen;">{{ $completed }}</div>
        </div>
      </div>
    </div>
  </div>

  {{-- ===== Filter Card (Printing) ===== --}}
  <div class="card shadow-soft mb-3 filter-card">
    <div class="card-body">
      <form method="GET" action="{{ route('sales.progress') }}">

        {{-- 顶部：标题 + Filter Me + Reset + Apply（同一排） --}}
        <div class="filter-head">
          <div class="title">
            <h6 class="mb-0 fw-semibold">Fulfillment</h6>
            <span class="text-muted small">Filter &amp; search</span>
          </div>

          <div class="actions">
            <a href="{{ route('sales.progress') }}" class="btn btn-outline-secondary">
              <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
            </a>
            <button class="btn btn-dark" type="submit">
              <i class="bi bi-funnel me-1"></i>Apply Filter
            </button>
          </div>
        </div>

        {{-- 输入控件：一整排 --}}
        <div class="filters-row">
          {{-- Product ID --}}
          <div>
            <label class="form-label">Search Product ID</label>
            <div class="input-group input-group-sm has-icon">
              <span class="input-group-text"><i class="bi bi-hash"></i></span>
              <input type="text" name="pid" value="{{ request('pid', $pid ?? '') }}" class="form-control" placeholder="Enter Product ID">
            </div>
          </div>

          {{-- Search --}}
          <div>
            <label class="form-label">Search</label>
            <div class="input-group input-group-sm has-icon">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="text" name="q" value="{{ request('q', $q ?? '') }}" class="form-control"
                     placeholder="Order title, Company name, or Product name">
            </div>
          </div>

          {{-- Artist --}}
          <div>
            <label class="form-label">Artist</label>
            <div class="input-group input-group-sm has-icon">
              <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
              <select name="artist" class="form-select">
                <option value="">All artists</option>
                @foreach (($artists ?? []) as $a)
                  <option value="{{ $a->id }}" {{ (string)$a->id === (string)request('artist', $artist ?? '') ? 'selected' : '' }}>
                    {{ $a->name }}
                  </option>
                @endforeach
              </select>
            </div>
          </div>

          {{-- Deadline From --}}
          <div>
            <label class="form-label">Deadline From</label>
            <div class="input-group input-group-sm has-icon">
              <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
              <input type="date" name="deadline_from" value="{{ request('deadline_from', $deadline_from ?? '') }}" class="form-control">
            </div>
          </div>

          {{-- Deadline To --}}
          <div>
            <label class="form-label">Deadline To</label>
            <div class="input-group input-group-sm has-icon">
              <span class="input-group-text"><i class="bi bi-calendar-check"></i></span>
              <input type="date" name="deadline_to" value="{{ request('deadline_to', $deadline_to ?? '') }}" class="form-control">
            </div>
          </div>
        </div>

        {{-- 原来的 Filter Me（隐藏保留，用来提交值） --}}
        <div class="d-none">
          <input class="form-check-input mine-switch" type="checkbox" role="switch"
                 id="mineCheck" name="mine" value="1" {{ request('mine') ? 'checked' : '' }}>
        </div>

      </form>
    </div>
  </div>

  {{-- ===== Production Status 表格（原样保留） ===== --}}
  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <h5 class="mb-0 fw-semibold">Production Status</h5>
        <small class="text-muted">Sorted by least progress</small>
      </div>

      <div class="table-responsive">
        <table class="table table-progress align-middle mb-0">
          <colgroup>
            <col class="col-id">
            <col class="col-stage"><col class="col-stage"><col class="col-stage"><col class="col-stage">
            <col class="col-date"><col class="col-deadline"><col class="col-packaging">

          </colgroup>

          <thead>
          <tr>
            <th>PRODUCT ID</th>
            <th>PRINTING</th>
            <th>FURNISHING</th>
            <th>DISPATCH CONTROL</th>
            <th>DELIVERY & INSTALLATION</th>
            <th>
              @php
                $isDateIn = request('sort_by')==='date_in';
                $nextModeDI = $isDateIn && request('sort_mode')==='near' ? 'far' : 'near';
              @endphp
              <a class="text-decoration-none text-dark"
                 href="{{ request()->fullUrlWithQuery(['sort_by'=>'date_in','sort_mode'=>$nextModeDI,'page'=>1]) }}">
                DATE IN
                @if($isDateIn)
                  <span class="badge bg-light text-muted ms-1">{{ strtoupper(request('sort_mode','near')) }}</span>
                @endif
              </a>
            </th>
            <th>
              @php
                $isDeadline = request('sort_by')==='deadline';
                $nextModeDL = $isDeadline && request('sort_mode')==='near' ? 'far' : 'near';
              @endphp
              <a class="text-decoration-none text-dark"
                 href="{{ request()->fullUrlWithQuery(['sort_by'=>'deadline','sort_mode'=>$nextModeDL,'page'=>1]) }}">
                DEADLINE
                @if($isDeadline)
                  <span class="badge bg-light text-muted ms-1">{{ strtoupper(request('sort_mode','near')) }}</span>
                @endif
              </a>
            </th>
            @php
              $q = request()->query();
              $urlWith = function(array $overrides) use ($q) {
                return route('sales.progress', array_filter(array_merge($q, $overrides), fn($v)=>$v!==null && $v!==''));
              };
              // for actions sort
              $acNext  = $sort === 'accepted_last' ? 'accepted_first' : 'accepted_last';
              $acLabel = str_starts_with($sort,'accepted_')
                  ? ($sort === 'accepted_last' ? 'last' : 'first')
                  : '';
            @endphp
            <th>PACKAGING</th>
          </tr>
          </thead>

          <tbody>
          @php
            $STAGES=['printing','furnishing','delivery','installation'];
            $POS=['printing'=>6,'furnishing'=>35,'delivery'=>60,'installation'=>87.5];
            $DOT=['printing'=>'p1','furnishing'=>'p2','delivery'=>'p3','installation'=>'p4'];
            // helper to render a dot class or skip entirely if stage missing
            $dotClass = function(array $p, string $stage) use ($DOT) {
            if (!isset($p['stages'][$stage])) return null; // skip dot for missing stage
            $s = $p['stages'][$stage]['status'] ?? null;
            $posClass = $DOT[$stage];
            if ($s === 'completed') return "dot {$posClass}";
            if ($s === 'rejected') return "dot red {$posClass}";
            return "dot gray {$posClass}";
            };
          @endphp

          @forelse($rows as $r)
            @php
            $STAGES = ['printing','furnishing','delivery','installation'];
                $POS = ['printing'=>6,'furnishing'=>35,'delivery'=>60,'installation'=>87.5];
                $DOT = ['printing'=>'p1','furnishing'=>'p2','delivery'=>'p3','installation'=>'p4'];
                
              $currentStage = $r['current_stage'] ?? null;
              $currentStatus = $r['current_status'] ?? null;

              $hasStage = function(string $s) use ($r,$currentStage){ return isset($r['stages'][$s]) || $currentStage===$s; };
              $visible = array_values(array_filter($STAGES,$hasStage));
              $first = $visible[0] ?? null; $start = $first ? $POS[$first] : 0;

              $rej=null; foreach($visible as $s){ if(($r['stages'][$s]['status']??null)==='rejected'){ $rej=$s; break; } }
              if($rej){ $end=$POS[$rej]; }
              else{
                $lastCompleted=null; foreach($visible as $s){ if(($r['stages'][$s]['status']??null)==='completed'){ $lastCompleted=$s; } }
                $end = $lastCompleted ? ($lastCompleted==='delivery' ? 100 : $POS[$lastCompleted]) : $start;
              }

              $dotClass=function(array $row,string $stage)use($DOT,$currentStage,$currentStatus){
                // Extra: if installation task is active and in_progress, show gray dot on INSTALLATION column (p4)
                if ($stage==='installation'
                    && (int)($row['installation_task_type'] ?? 0) === 1
                    && strtolower((string)($row['installation_status'] ?? '')) === 'in_progress') {
                  return 'dot '.$DOT[$stage].' gray';
                }

                if ($stage===$currentStage && $currentStatus==='in_progress') { return 'dot '.$DOT[$stage].' gray'; }
                if (!isset($row['stages'][$stage]) && $stage!==$currentStage) return null;
                $s = $row['stages'][$stage]['status']??null;
                if ($s==='completed') return 'dot '.$DOT[$stage];
                elseif ($s==='rejected') return 'dot red '.$DOT[$stage];
                else return 'dot gray '.$DOT[$stage];
              };

              $dateIn = $r['orderDate'] ? \Carbon\Carbon::parse($r['orderDate'])->format('Y-m-d') : '—';
              $deadline = $r['deadline'] ? \Carbon\Carbon::parse($r['deadline'])->format('Y-m-d') : '—';

              $isdelivery = strtolower((string)($r['current_stage'] ?? '')) === 'delivery';
              $accepted = (int)($r['accepted'] ?? 0) === 1;

              $pid = $r['OrderID'] ?? ($r->OrderID ?? null);
              $isdeliveryCompleted = (int)($r['delivery_completed'] ?? 0) === 1
                                      || strtolower((string)($r['current_status'] ?? '')) === 'completed';
            @endphp

            <tr data-id="{{ $r['OrderID'] }}" class="clickable-row" style="cursor:pointer;">
              <td>{{ $r['product_code'] }}</td>
              <td colspan="4">
                <div class="pipeline" style="--start:{{ $start }}%; --end:{{ $end }}%;">
                  <div class="track"></div>
                  <div class="fill"></div>

                  @php $d=$dotClass($r,'printing'); @endphp @if($d)<span class="{{ $d }}"></span>@endif
                  @php $d=$dotClass($r,'furnishing'); @endphp @if($d)<span class="{{ $d }}"></span>@endif
                  @php $d=$dotClass($r,'delivery'); @endphp @if($d)<span class="{{ $d }}"></span>@endif
                  @php $d=$dotClass($r,'installation'); @endphp @if($d)<span class="{{ $d }}"></span>@endif
                </div>
              </td>
              <td>{{ $dateIn }}</td>
              @php
                $deadline     = data_get($r, 'deadline');
                $isOverdue    = (bool) data_get($r, 'is_overdue', false);
                $isDueSoon    = (bool) data_get($r, 'is_due_soon', false);
                $deadlineStr  = $deadline ? \Carbon\Carbon::parse($deadline)->format('Y-m-d') : '—';
                $cls          = $isOverdue ? 'text-danger fw-semibold'
                              : ($isDueSoon ? 'text-warning fw-semibold' : 'text-body');
              @endphp

              <td>
                <span class="deadline-date {{ $cls }}">{{ $deadlineStr }}</span>
                @if($isOverdue)
                  <span class="badge bg-danger-subtle text-danger ms-2">Expired</span>
                @elseif($isDueSoon)
                  <span class="badge bg-warning-subtle text-warning ms-2">Near</span>
                @endif
              </td>
              @php
                $packagingVal  = data_get($r, 'packaging');         // may be null/0/1
                $packagingDone = (int)($packagingVal ?? 0) === 1;   // ✅ null/0 -> Pending, 1 -> Completed
              @endphp
              <td class="text-center">
                @if($packagingDone)
                  <span class="fw-semibold text-success">Completed</span>
                @else
                  <span class="fw-semibold text-primary">
                      Pending
                    </span>
                @endif
              </td>
              
            </tr>
          @empty
            <tr><td colspan="9" class="text-center text-muted py-4">No data.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>

    @if ($rows instanceof \Illuminate\Pagination\LengthAwarePaginator)
      <div class="card-ft">
        {{ $rows->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
      </div>
    @endif
  </div>
</div>

<form id="proofForm" method="POST" enctype="multipart/form-data" action="" style="display:none">@csrf @method('PATCH')</form>

<div id="proofModal" class="cx-mask" aria-hidden="true">
  <div class="cx-wrap">
    <div class="cx-modal" role="dialog" aria-modal="true" aria-labelledby="proofTitle" style="max-width:720px">
      <div class="cx-header">
        <i class="bi bi-images text-success"></i>
        <div id="proofTitle" class="cx-title">Upload Installation Proof</div>
        <button type="button" class="cx-close" data-close="proofModal"><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="cx-body">
        <div class="mb-2 small text-muted">Add at least one image (JPG/PNG, up to 12 MB each).</div>
        <input id="proofFiles" type="file" name="photos[]" accept="image/*" multiple class="form-control mb-3">
        <div id="proofPreview" class="d-flex flex-wrap gap-2"></div>
      </div>
      <div class="cx-footer">
        <button type="button" class="btn btn-back" data-close="proofModal">Cancel</button>
        <button type="button" id="confirmProof" class="btn btn-accept">Confirm & Complete</button>
      </div>
    </div>
  </div>
</div>

<form id="packagingForm" method="POST" action="" style="display:none;">
  @csrf
  @method('PATCH')
</form>

<div id="packagingModal" class="cx-mask" aria-hidden="true">
  <div class="cx-wrap">
    <div class="cx-modal" role="dialog" aria-modal="true" style="max-width:520px">
      <div class="cx-header">
        <i class="bi bi-box-seam text-primary"></i>
        <div class="cx-title">Complete Packaging</div>
        <button type="button" class="cx-close" data-close="packagingModal"><i class="bi bi-x-lg"></i></button>
      </div>

      <div class="cx-body">
        <div class="text-muted">Are you sure you want to mark <b>Packaging</b> as completed?</div>
      </div>

      <div class="cx-footer">
        <button type="button" class="btn btn-back" data-close="packagingModal">Cancel</button>
        <button type="button" class="btn btn-accept" id="btnPackagingConfirm">Yes, Complete</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // tooltips
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));

  // 顶部 Filter Me 与隐藏 mineCheck 同步
  const mineHidden = document.getElementById('mineCheck'); // 隐藏的真正提交字段
  const mineTop    = document.getElementById('mineTop');   // 顶部可见开关
  if (mineHidden && mineTop) {
    mineTop.checked = !!mineHidden.checked;
    mineTop.addEventListener('change', () => { mineHidden.checked = mineTop.checked; mineHidden.form?.submit(); });
    mineHidden.addEventListener('change', () => { mineTop.checked = mineHidden.checked; });
  }

  // 双击行跳详情
  document.querySelectorAll('.clickable-row').forEach(row => {
    row.addEventListener('dblclick', () => {
      const id = row.dataset.id;
      if (id) window.location.href = `/orders/${id}`;
    });
  });
});



document.addEventListener('DOMContentLoaded', () => {
  const modal   = document.getElementById('proofModal');
  const files   = document.getElementById('proofFiles');
  const preview = document.getElementById('proofPreview');
  const form    = document.getElementById('proofForm');


  function closeProof() { modal.classList.remove('show'); }

  document.querySelectorAll('[data-close="proofModal"]').forEach(btn => {
    btn.addEventListener('click', closeProof);
  });
  modal?.addEventListener('click', e => { if (e.target === modal) closeProof(); });

  // live preview
  files?.addEventListener('change', () => {
    preview.innerHTML = '';
    const list = Array.from(files.files || []);
    list.slice(0, 12).forEach(f => {
      const url = URL.createObjectURL(f);
      const img = document.createElement('img');
      img.src = url;
      img.style.maxWidth = '120px';
      img.style.maxHeight = '90px';
      img.style.objectFit = 'cover';
      img.className = 'border rounded';
      preview.appendChild(img);
    });
  });

  document.getElementById('confirmProof')?.addEventListener('click', () => {
    if (!files?.files?.length) {
      alert('Please upload at least one photo.');
      return;
    }
    // Move input into form (it already is), submit
    form.appendChild(files);
    form.submit();
  });
});

(function () {
  const modal = document.getElementById('packagingModal');
  const btnConfirm = document.getElementById('btnPackagingConfirm');
  const form = document.getElementById('packagingForm'); // ✅ use hidden form token
  let actionUrl = null;

  function openModal() {
    if (!modal) return;
    modal.classList.add('show');
    modal.setAttribute('aria-hidden', 'false');
  }

  function closeModal() {
    if (!modal) return;
    modal.classList.remove('show');
    modal.setAttribute('aria-hidden', 'true');
  }

  function getCsrfToken() {
    // A) try meta first (if exists)
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) return meta.getAttribute('content');

    // B) fallback: hidden form _token
    const tokenInput = form ? form.querySelector('input[name="_token"]') : null;
    return tokenInput ? tokenInput.value : null;
  }

  document.addEventListener('click', (e) => {
    // open modal
    const btn = e.target.closest('.js-packaging-pending');
    if (btn) {
      actionUrl = btn.getAttribute('data-url');
      openModal();
      e.preventDefault();
      return;
    }

    // close modal
    const closeBtn = e.target.closest('[data-close="packagingModal"]');
    if (closeBtn) {
      closeModal();
      e.preventDefault();
      return;
    }
  });

  if (btnConfirm) {
    btnConfirm.addEventListener('click', async () => {
      if (!actionUrl) return;

      const csrf = getCsrfToken();
      if (!csrf) {
        alert('CSRF token not found. Please ensure #packagingForm has @csrf.');
        return;
      }

      try {
        btnConfirm.disabled = true;

        const res = await fetch(actionUrl, {
          method: 'PATCH',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': csrf, // ✅ fixed
            'Accept': 'application/json',
          },
          credentials: 'same-origin', // ✅ helps session/cookie
        });

        const json = await res.json().catch(() => ({}));
        if (!res.ok || json.ok === false) {
          throw new Error(json.message || 'Failed to complete packaging.');
        }

        window.location.reload();

      } catch (err) {
        console.error(err);
        alert('Unable to update packaging. Please try again.');
      } finally {
        btnConfirm.disabled = false;
        closeModal();
      }
    });
  }
})();
</script>
@endsection
