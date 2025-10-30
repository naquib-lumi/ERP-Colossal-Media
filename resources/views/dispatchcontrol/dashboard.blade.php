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
</style>

<div class="container-fluid py-4 px-4" style="max-width:1200px;margin:0 auto">
  <h1 class="h4 fw-bold mb-3" style="letter-spacing:-.2px">Dashboard Overview</h1>

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

  {{-- ===== Filter Card (Dispatch Control) ===== --}}
  <div class="card shadow-soft mb-3 filter-card">
    <div class="card-body">
      <form method="GET" action="{{ route('dispatchcontrol.dashboard') }}">

        {{-- 顶部：标题 + Filter Me + Reset + Apply（同一排） --}}
        <div class="filter-head">
          <div class="title">
            <h6 class="mb-0 fw-semibold">Dispatch Control</h6>
            <span class="text-muted small">Filter &amp; search</span>
          </div>

          <div class="actions">
            {{-- 顶部 Filter Me（与隐藏原件同步） --}}
            <div class="d-flex align-items-center me-1">
              <div class="form-check form-switch me-1">
                <input class="form-check-input" type="checkbox" id="mineTop">
              </div>
              <label class="small mb-0" for="mineTop">Filter Me</label>
              <i class="bi bi-info-circle text-muted ms-1" data-bs-toggle="tooltip"
                 title="Show products currently in a stage that matches your role"></i>
            </div>

            <a href="{{ route('dispatchcontrol.dashboard') }}" class="btn btn-outline-secondary">
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
            <col class="col-date"><col class="col-deadline"><col class="col-actions">
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
            <th class="text-center">ACTIONS</th>
          </tr>
          </thead>

          <tbody>
          @php
            $STAGES=['printing','furnishing','delivery','installation'];
            $POS=['printing'=>8,'furnishing'=>35,'delivery'=>60,'installation'=>87.5];
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
                $POS = ['printing'=>8,'furnishing'=>35,'delivery'=>60,'installation'=>87.5];
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
                if($stage===$currentStage && $currentStatus==='in_progress'){ return 'dot '.$DOT[$stage].' gray'; }
                if(!isset($row['stages'][$stage]) && $stage!==$currentStage) return null;
                $s=$row['stages'][$stage]['status']??null;
                if($s==='completed') return 'dot '.$DOT[$stage];
                elseif($s==='rejected') return 'dot red '.$DOT[$stage];
                else return 'dot gray '.$DOT[$stage];
              };

              $dateIn = $r['orderDate'] ? \Carbon\Carbon::parse($r['orderDate'])->format('Y-m-d') : '—';
              $deadline = $r['deadline'] ? \Carbon\Carbon::parse($r['deadline'])->format('Y-m-d') : '—';

              $isdelivery = strtolower((string)($r['current_stage'] ?? '')) === 'delivery';
              $accepted = (int)($r['accepted'] ?? 0) === 1;

              $pid = $r['ProductID'] ?? ($r->ProductID ?? null);
              $isdeliveryCompleted = (int)($r['delivery_completed'] ?? 0) === 1
                                      || strtolower((string)($r['current_status'] ?? '')) === 'completed';
            @endphp

            <tr data-id="{{ $r['ProductID'] }}" class="clickable-row" style="cursor:pointer;">
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
              <td>{{ $deadline }}</td>
              <td class="text-center">
                <div class="d-inline-flex gap-1">
                  @if (!$accepted || $isdeliveryCompleted || !$isdelivery)
                    <a href="{{ route('dispatchcontrol.job.show', $pid) }}" class="action-btn" title="View">
                      <i class="bi bi-eye"></i>
                    </a>
                  @endif
                  @unless ($isdeliveryCompleted)
                    @if ($accepted && $isdelivery)
                      <a href="{{ route('dispatchcontrol.job.show', $pid) }}" class="action-btn" title="Edit">
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
            <tr><td colspan="8" class="text-center text-muted py-4">No data.</td></tr>
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
      if (id) window.location.href = `/dispatchcontrol/job/${id}`;
    });
  });
});

// KPI tiles click -> 跳到带 status 的页面
(function () {
  const base = "{{ route('dispatchcontrol.job-order') }}";
  document.querySelectorAll('[data-go-status]').forEach(tile=>{
    tile.addEventListener('click',()=>{
      const s = tile.getAttribute('data-go-status');
      const url = new URL(base, window.location.origin);
      if (s) url.searchParams.set('status', s);
      window.location.href = url.toString();
    });
  });
})();

document.addEventListener('DOMContentLoaded', () => {
  const modal   = document.getElementById('proofModal');
  const files   = document.getElementById('proofFiles');
  const preview = document.getElementById('proofPreview');
  const form    = document.getElementById('proofForm');

  function openProof(productId) {
    // set action to PATCH /installation/jobs/{product}/complete
    const urlTmpl = "{{ route('dispatchcontrol.jobs.complete', ['product' => '___ID___']) }}";
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
</script>
@endsection
