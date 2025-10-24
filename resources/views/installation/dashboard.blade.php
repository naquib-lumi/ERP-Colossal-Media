@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
  /* ===== Cards (subtle) ===== */
  .stat-card {
    border: 1px solid #E7EAEE;
    border-radius: 14px;
    background: #fff;
    padding: 18px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 1px 2px rgba(16, 24, 40, .05)
  }

  .kpi-icon {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    background: #F4F6FA;
    color: #667085;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px
  }

  .stat-card .num {
    font-size: 34px;
    font-weight: 800;
    letter-spacing: -.3px
  }

  .stat-card .label {
    color: #6B7280;
    font-weight: 600
  }

  /* ===== Table sizing + zebra + sticky head ===== */
  .table-progress {
    table-layout: fixed
  }

  .table-progress col.col-id {
    width: 170px
  }

  .table-progress col.col-stage {
    width: 18%
  }

  .table-progress col.col-date {
    width: 140px
  }

  .table-progress col.col-deadline {
    width: 140px
  }

  .table-progress col.col-actions {
    width: 120px
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

  /* ===== Unified pipeline ===== */
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
    left: 12.5%
  }

  .dot.p2 {
    left: 37.5%
  }

  .dot.p3 {
    left: 62.5%
  }

  .dot.p4 {
    left: 87.5%
  }

  /* Node positions */
  .dot.p1 {
    left: 8%
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

  /* ===== Pagination: footed + centered ===== */
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

  .filter-card .has-icon .input-group-text{ background:#fff }
  .filter-card .input-group-text i{ color:#7b8191 }
  .filter-card .form-control,
  .filter-card .form-select{
    border-color:#e6e8f0; border-radius:10px;
  }
  .filter-card .form-control:focus,
  .filter-card .form-select:focus{
    border-color:#bfc6ff; box-shadow:0 0 0 .15rem rgba(99,91,255,.12);
  }

  /* ====== ADD-ON: 顶部按钮 + 下方输入区（保留原有样式不改动） ====== */
  .filter-actions-top{
    display:flex; justify-content:flex-end; gap:8px; margin-bottom:12px;
  }
  .filter-actions-top .btn{ border-radius:8px; font-weight:600; font-size:13px; height:34px; line-height:1.1; padding:0 12px; }
  .filter-actions-top .btn-dark{ background:#1f2544; border-color:#1f2544; }
  .filter-actions-top .btn-dark:hover{ background:#171c33; }
  .filter-actions-top .btn-outline-secondary{ color:#1f2544; border-color:#cdd3df; }
  .filter-actions-top .btn-outline-secondary:hover{ background:#f4f6fa; }

  /* 下方输入区保持一排紧凑 */
  .filters-row{ display:flex; flex-wrap:wrap; align-items:end; gap:12px; }
  .filters-row .form-label{ font-size:13px; font-weight:600; color:#475467; margin-bottom:6px; }
  .filters-row .input-group-text{ background:#fff; border-right:0; }
  .filters-row .input-group.input-group-sm .form-control,
  .filters-row .input-group.input-group-sm .form-select{ height:38px; font-size:13px; }
  .filters-row .form-control,.filters-row .form-select{ border-radius:10px; }

  .fx-id{ max-width:190px; flex:1 1 160px; }
  .fx-search{ min-width:260px; flex:2 1 320px; }
  .fx-artist{ max-width:220px; flex:1 1 200px; }
  .fx-date{ max-width:180px; flex:1 1 160px; }

  @media (max-width: 992px){
    .filter-actions-top{ justify-content:flex-end; }
  }

  /* 标题与按钮同一排 */
.filter-head{
  display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:12px;
}
.filter-head .title{display:flex;align-items:center;gap:8px;}
.filter-head .actions .btn{
  border-radius:8px;font-weight:600;font-size:13px;height:34px;line-height:1.1;padding:0 12px;
}
.filter-head .actions .btn-dark{background:#1f2544;border-color:#1f2544;}
.filter-head .actions .btn-dark:hover{background:#171c33;}
.filter-head .actions .btn-outline-secondary{color:#1f2544;border-color:#cdd3df;}
.filter-head .actions .btn-outline-secondary:hover{background:#f4f6fa;}

/* 下方输入区：一排紧凑 */
.filters-row{display:flex;flex-wrap:wrap;align-items:end;gap:12px;}
.filters-row .form-label{font-size:13px;font-weight:600;color:#475467;margin-bottom:6px;}
.filters-row .input-group-text{background:#fff;border-right:0;}
.filters-row .input-group.input-group-sm .form-control,
.filters-row .input-group.input-group-sm .form-select{height:38px;font-size:13px;}
.filters-row .form-control,.filters-row .form-select{border-radius:10px;}

.fx-id{max-width:190px;flex:1 1 160px;}
.fx-search{min-width:260px;flex:2 1 320px;}
.fx-artist{max-width:220px;flex:1 1 200px;}
.fx-date{max-width:180px;flex:1 1 160px;}

</style>

<div class="container-fluid py-4 px-4" style="max-width:1200px;margin:0 auto">
  <h1 class="h4 fw-bold mb-3" style="letter-spacing:-.2px">Dashboard Overview</h1>

  {{-- KPIs --}}
  <div class="row g-3 mb-4">
    <div class="col-12 col-lg-6">
      <div class="stat-card">
        <div>
          <div class="label mb-1" style="color: #635bff;">In Progress</div>
          <div class="num" style="color: #635bff;">{{ $inProgress }}</div>
        </div>
        <div class="kpi-icon"><i class="bi bi-clock"></i></div>
      </div>
    </div>
    <div class="col-12 col-lg-6">
      <div class="stat-card cursor-pointer" data-go-status="completed">
        <div>
          <div class="label mb-1" style="color:seagreen;">Completed</div>
          <div class="num" style="color:seagreen;">{{ $completed }}</div>
        </div>
        <div class="kpi-icon"><i class="bi bi-check2"></i></div>
      </div>
    </div>
  </div>

{{-- Filter & search toolbar --}}
<div class="card shadow-soft mb-3 filter-card">
  <div class="card-body">

    <form method="GET" action="{{ route('installation.dashboard') }}">
      {{-- 顶部：标题在左、按钮在右（同一排） --}}
      <div class="filter-head">
        <div class="title">
          <h6 class="mb-0 fw-semibold">Installation Jobs</h6>
          <span class="text-muted small">Filter &amp; search</span>
        </div>
        <div class="actions d-flex align-items-center gap-3">
          {{-- 新增：Filter Me switch（不改你其它代码） --}}
          <div class="form-check form-switch m-0 d-flex align-items-center">
            <input class="form-check-input me-1" type="checkbox" role="switch" id="mineCheck" name="mine" value="1"
              {{ request('mine') ? 'checked' : '' }} style="cursor:pointer;">
            <label class="form-check-label small fw-semibold text-muted" for="mineCheck" style="user-select:none;cursor:pointer;">
              Filter Me
            </label>
            <i class="bi bi-info-circle ms-1 text-secondary small" data-bs-toggle="tooltip"
               title="Show only tasks assigned to your role."></i>
          </div>

          <a href="{{ route('installation.dashboard') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
          </a>
          <button class="btn btn-dark" type="submit">
            <i class="bi bi-funnel me-1"></i> Apply Filter
          </button>
        </div>
      </div>

      {{-- 下方：一排输入控件 --}}
      <div class="filters-row">
        {{-- Product ID --}}
        <div class="fx-id">
          <label class="form-label">Search Product ID</label>
          <div class="input-group input-group-sm has-icon">
            <span class="input-group-text"><i class="bi bi-hash"></i></span>
            <input type="text" name="pid" value="{{ request('pid', $pid ?? '') }}" class="form-control" placeholder="Enter Product ID">
          </div>
        </div>

        {{-- Keyword --}}
        <div class="fx-search">
          <label class="form-label">Search</label>
          <div class="input-group input-group-sm has-icon">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" name="q" value="{{ request('q', $q ?? '') }}" class="form-control"
              placeholder="Order title, Company name, or Product name">
          </div>
        </div>

        {{-- Artist --}}
        <div class="fx-artist">
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
        <div class="fx-date">
          <label class="form-label">Deadline From</label>
          <div class="input-group input-group-sm has-icon">
            <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
            <input type="date" name="deadline_from" value="{{ request('deadline_from', $deadline_from ?? '') }}" class="form-control" placeholder="dd/mm/yyyy">
          </div>
        </div>

        {{-- Deadline To --}}
        <div class="fx-date">
          <label class="form-label">Deadline To</label>
          <div class="input-group input-group-sm has-icon">
            <span class="input-group-text"><i class="bi bi-calendar-check"></i></span>
            <input type="date" name="deadline_to" value="{{ request('deadline_to', $deadline_to ?? '') }}" class="form-control" placeholder="dd/mm/yyyy">
          </div>
        </div>
      </div>
    </form>

  </div>
</div>


  {{-- Production Status --}}
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
            <col class="col-stage">
            <col class="col-stage">
            <col class="col-stage">
            <col class="col-stage">
            <col class="col-date">
            <col class="col-deadline">
            <col class="col-actions">
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
                  $isDateIn   = request('sort_by') === 'date_in';
                  $nextModeDI = $isDateIn && request('sort_mode') === 'near' ? 'far' : 'near';
                @endphp
                <a class="text-decoration-none text-dark"
                  href="{{ request()->fullUrlWithQuery(['sort_by' => 'date_in', 'sort_mode' => $nextModeDI, 'page' => 1]) }}">
                  DATE IN
                  @if($isDateIn)
                    <span class="badge bg-light text-muted ms-1">{{ strtoupper(request('sort_mode','near')) }}</span>
                  @endif
                </a>
              </th>

              <th>
                @php
                  $isDeadline   = request('sort_by') === 'deadline';
                  $nextModeDL   = $isDeadline && request('sort_mode') === 'near' ? 'far' : 'near';
                @endphp
                <a class="text-decoration-none text-dark"
                  href="{{ request()->fullUrlWithQuery(['sort_by' => 'deadline', 'sort_mode' => $nextModeDL, 'page' => 1]) }}">
                  DEADLINE
                  @if($isDeadline)
                    <span class="badge bg-light text-muted ms-1">{{ strtoupper(request('sort_mode','near')) }}</span>
                  @endif
                </a>
              </th>
              <th class="text-center">ACTIONS</th>
            </tr>
          </thead>

          <tbody>
            @php
            // constant maps
            $STAGES = ['printing','furnishing','delivery','installation'];
            $POS = ['printing'=>8,'furnishing'=>35,'delivery'=>60,'installation'=>87.5];
            $DOT = ['printing'=>'p1','furnishing'=>'p2','delivery'=>'p3','installation'=>'p4'];
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

            @forelse ($rows as $r)
              @php
                $STAGES = ['printing','furnishing','delivery','installation'];
                $POS = ['printing'=>8,'furnishing'=>35,'delivery'=>60,'installation'=>87.5];
                $DOT = ['printing'=>'p1','furnishing'=>'p2','delivery'=>'p3','installation'=>'p4'];

                $currentStage = $r['current_stage'] ?? null; // from products.taskType
                $currentStatus = $r['current_status'] ?? null; // from products.status

                $hasStage = function(string $s) use ($r, $currentStage) {
                return isset($r['stages'][$s]) || $currentStage === $s;
                };

                $visible = array_values(array_filter($STAGES, $hasStage));

                $first = $visible[0] ?? null;
                $start = $first ? $POS[$first] : 0;

                $rej = null;
                foreach ($visible as $s) {
                if (($r['stages'][$s]['status'] ?? null) === 'rejected') { $rej = $s; break; }
                }
                if ($rej) {
                $end = $POS[$rej];
                } else {
                $lastCompleted = null;
                foreach ($visible as $s) {
                if (($r['stages'][$s]['status'] ?? null) === 'completed') { $lastCompleted = $s; }
                }
                $end = $lastCompleted
                ? ($lastCompleted === 'installation' ? 100 : $POS[$lastCompleted])
                : $start;
                }

                $dotClass = function(array $row, string $stage) use ($DOT, $currentStage, $currentStatus) {
                if ($stage === $currentStage && $currentStatus === 'in_progress') {
                return 'dot '.$DOT[$stage].' gray';
                }
                if (!isset($row['stages'][$stage]) && $stage !== $currentStage) return null;

                $s = $row['stages'][$stage]['status'] ?? null;
                if ($s === 'completed') return 'dot '.$DOT[$stage];
                elseif ($s === 'rejected') return 'dot red '.$DOT[$stage];
                else return 'dot gray '.$DOT[$stage]; // includes pending/unknown
                };

                $dateIn = $r['orderDate'] ? \Carbon\Carbon::parse($r['orderDate'])->format('Y-m-d') : '—';
                $deadline = $r['deadline'] ? \Carbon\Carbon::parse($r['deadline'])->format('Y-m-d') : '—';

                $isInstallation = strtolower((string)($r['current_stage'] ?? '')) === 'installation';
                $accepted = (int)($r['accepted'] ?? 0) === 1;

                
              @endphp

              <tr data-id="{{ $r['ProductID'] }}" data-code="{{ $r['product_code'] }}" class="clickable-row" style="cursor: pointer;">
                <td>{{ $r['product_code'] }}</td>
                <td colspan="4">
                  <div class="pipeline" style="--start:{{ $start }}%; --end:{{ $end }}%;">
                    <div class="track"></div>
                    <div class="fill"></div>

                    @php $d = $dotClass($r,'printing'); @endphp @if($d)<span class="{{ $d }}"></span>@endif
                    @php $d = $dotClass($r,'furnishing'); @endphp @if($d)<span class="{{ $d }}"></span>@endif
                    @php $d = $dotClass($r,'delivery'); @endphp @if($d)<span class="{{ $d }}"></span>@endif
                    @php $d = $dotClass($r,'installation'); @endphp @if($d)<span class="{{ $d }}"></span>@endif
                  </div>
                </td>
                <td>{{ $dateIn }}</td>
                <td>{{ $deadline }}</td>
                <td class="text-center">
                  @php
                    $pid = $r['ProductID'] ?? ($r->ProductID ?? null);

                    $isInstallCompleted = (int)($r['installation_completed'] ?? 0) === 1
                                          || strtolower((string)($r['current_status'] ?? '')) === 'completed';
                  @endphp

                  <div class="d-inline-flex gap-1">
                    {{-- Always show View --}}
                    @if (!$accepted || $isInstallCompleted || !$isInstallation)
                    <a href="{{ route('installation.job.show', $pid) }}" class="action-btn" title="View">
                      <i class="bi bi-eye"></i>
                    </a>
                    @endif
                    {{-- If installation NOT completed yet, show Edit / Mark Completed --}}
                    @unless ($isInstallCompleted)
                      @if ($accepted && $isInstallation)
                        <a href="{{ route('installation.job.show', $pid) }}" class="action-btn" title="Edit">
                          <i class="bi bi-pencil"></i>
                        </a>
                        <button class="action-btn js-open-proof" data-id="{{ $pid }}" title="Mark Completed">
                          <i class="bi bi-check2"></i>
                        </button>
                      @endif
                    @endunless
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-muted py-4">No data.</td>
              </tr>
            @endforelse
          </tbody>

        </table>
      </div>
    </div>

    {{-- Pagination pinned to card foot --}}
    @if ($rows instanceof \Illuminate\Pagination\LengthAwarePaginator)
    <div class="card-ft">
      {{-- Bootstrap-5 numeric pager, 1 neighbor on each side --}}
      {{ $rows->withQueryString()->onEachSide(1)->links('pagination::bootstrap-5') }}
    </div>
    @endif

  </div>
</div>

<form id="proofForm" method="POST" enctype="multipart/form-data"
      action="" style="display:none">@csrf @method('PATCH')</form>

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
        <button type="button" id="confirmProof" class="btn btn-accept">
          Confirm & Complete
        </button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const modal   = document.getElementById('proofModal');
  const files   = document.getElementById('proofFiles');
  const preview = document.getElementById('proofPreview');
  const form    = document.getElementById('proofForm');

  function openProof(productId) {
    // set action to PATCH /installation/jobs/{product}/complete
    const urlTmpl = "{{ route('installation.jobs.complete', ['product' => '___ID___']) }}";
    form.action = urlTmpl.replace('___ID___', productId);
    form.style.display = 'block'; // needed so the FormData sees inputs

    // reset inputs & preview
    files.value = '';
    preview.innerHTML = '';

    modal.classList.add('show');
  }
  function closeProof() { modal.classList.remove('show'); }

  document.querySelectorAll('.js-open-proof').forEach(btn => {
    btn.addEventListener('click', () => openProof(btn.dataset.id));
  });

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

(() => {
  const input = document.getElementById('pidFilter');
  const tbody = document.querySelector('.table-progress tbody');
  if (!input || !tbody) return;
  input.addEventListener('input', function(){
    const q = (this.value || '').trim().toLowerCase();
    tbody.querySelectorAll('tr').forEach(tr => {
      const txt = (tr.querySelector('td')?.textContent || '').toLowerCase();
      tr.style.display = q && !txt.includes(q) ? 'none' : '';
    });
  });
})();

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.clickable-row').forEach(row => {
    row.addEventListener('dblclick', () => {
      const id = row.dataset.id;
      if (id) {
        window.location.href = `/installation/job/${id}`;
      }
    });
  });
});
(function () {
  // base route to history page (Laravel route)
  const base = "{{ route('installation.history') }}";

  document.querySelectorAll('[data-go-status]').forEach(function (tile) {
    tile.addEventListener('click', function () {
      const status = tile.getAttribute('data-go-status')?.trim();
      if (!status) return;

      // Build target URL with query param
      const url = new URL(base, window.location.origin);
      url.searchParams.set('status', status);

      // Redirect to ?status=completed
      window.location.href = url.toString();
    });
  });
})();

// ===== Filter Me tooltip + auto submit（新增，不影响其它逻辑）=====
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    if (window.bootstrap?.Tooltip) new bootstrap.Tooltip(el);
  });
  const mine = document.getElementById('mineCheck');
  if (mine) mine.addEventListener('change', () => mine.form?.submit());
});
</script>
@endsection
