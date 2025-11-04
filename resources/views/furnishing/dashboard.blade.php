@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
  .form-switch-lg .form-check-input { width: 3rem; height: 1.5rem; }
  .form-switch-lg .form-check-input:checked { background-color: #6366f1; border-color: #6366f1; }

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
  .table td:first-child{font-weight:700;color:#111827}

  .col-actions{width:170px}
/* ===== Actions column (slightly left aligned) ===== */
.col-actions{ width:120px; } 
.table td.col-actions{
  display:flex;
  align-items:center;
  justify-content:flex-start;   /* 改为靠左对齐 */
  gap:8px;                      /* 两个 icon 间距 */
  padding-left:18px;            /* 左边内距控制视觉距离 */
  padding-right:0;              /* 去掉右边多余空白 */
}

/* Action buttons – clean and compact */
.icon-pill{
  width:32px;
  height:32px;
  border-radius:10px;
  display:inline-flex;
  align-items:center;
  justify-content:center;
  border:1px solid #E3E8EF;
  background:#fff;
  color:#475467;
  transition:background .15s ease, border-color .15s ease, color .15s ease, transform .12s ease;
}
.icon-pill i{ font-size:14px; line-height:1; }
.icon-pill:hover{ background:#F4F6FA; color:#111827; border-color:#D7DFE7; transform:translateY(-1px); }
.icon-pill:active{ transform:translateY(0); }
.icon-pill:focus-visible{ outline:2px solid #C7D2FE; outline-offset:2px; border-color:#A5B4FC; }

/* 更小屏幕稍微收紧 */
@media (max-width:1200px){
  .col-actions{ width:100px; }
  .table td.col-actions{ padding-left:12px; }
  .icon-pill{ width:28px; height:28px; }
}


  /* ===== Modal ===== */
  .cx-mask{position:fixed;inset:0;background:rgba(15,23,42,.45);display:none;z-index:1080}
  .cx-mask.show{display:grid;place-items:center}
  .cx-modal{width:560px;max-width:92vw;background:#fff;border:1px solid #E7EAF0;border-radius:14px;box-shadow:0 24px 80px rgba(2,6,23,.28);overflow:hidden}
  .cx-header{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #EDF0F3}
  .cx-title{font-weight:700;color:#0F172A}
  .cx-close{border:0;background:transparent;color:#94A3B8}
  .cx-close:hover{color:#6B7280}
  .cx-body{display:flex;gap:14px;align-items:flex-start;padding:18px}
  .cx-qicon{width:34px;height:34px;border-radius:10px;background:#F3F4F6;color:#6B7280;display:flex;align-items:center;justify-content:center}
  .cx-q{font-weight:600;color:#111827;margin-bottom:4px}
  .cx-help{color:#667085}
  .cx-footer{display:flex;justify-content:flex-end;gap:10px;padding:14px 16px;border-top:1px solid #EDF0F3;background:#FBFBFC}
  .cx-btn{border-radius:10px;padding:10px 18px;font-weight:700}
  .cx-btn-ghost{background:#EEF2F6;border:1px solid #E5E7EB;color:#0F172A}
  .cx-btn-ghost:hover{background:#E2E8F0}
  .cx-btn-dark{background:#111827;border:1px solid #111827;color:#fff}
  .cx-btn-dark:hover{background:#0B1220;border-color:#0B1220}

  @media (max-width:992px){.kpi-grid{grid-template-columns:1fr}}

  /* === Pill Pager === */
  .pill-pager .page-link{border-radius:999px;border:1px solid #E6E8F0;background:#F6F7FB;color:#667085;padding:.45rem .9rem;line-height:1}
  .pill-pager .page-item+.page-item{margin-left:.5rem}
  .pill-pager .page-item.active .page-link{background:#635bff;border-color:#635bff;color:#fff}
  .pill-pager .page-item.disabled .page-link{opacity:.6;cursor:not-allowed;background:#F6F7FB}

  /* filter card */
  .filter-card{border:1px solid #ECEFF3;border-radius:14px}
  .filter-card .form-label{font-size:.8rem;color:#6b7280;margin-bottom:.25rem}
  .filter-card .input-group-text{background:#f8fafc;border-color:#e5e7eb}
  .filter-card .form-control{border-color:#e5e7eb}
  .filter-card .has-icon .form-control{border-left:0}
  .filter-card .input-group-text i{opacity:.75}
  @media (min-width:1200px){.filter-card form .col-lg-2{min-width:220px}.filter-card form .col-lg-3{min-width:260px}}

  .js-sort-deadline{cursor:pointer;user-select:none}
  .js-sort-deadline::after{content:" ↕";opacity:.5;font-size:.9em}
  .js-sort-deadline[data-order="asc"]::after{content:" ↑"}
  .js-sort-deadline[data-order="desc"]::after{content:" ↓"}
  .th-sort{text-decoration:none;color:inherit;user-select:none}
  .th-sort:hover{text-decoration:underline}
  .th-sort.is-active{font-weight:700}

  /* 顶部按钮 + 下方输入区 */
  .filter-actions-top{display:flex;justify-content:flex-end;gap:8px;margin-bottom:12px}
  .filter-actions-top .btn{border-radius:8px;font-weight:600;font-size:13px;height:34px;line-height:1.1;padding:0 12px}
  .filter-actions-top .btn-dark{background:#1f2544;border-color:#1f2544}
  .filter-actions-top .btn-dark:hover{background:#171c33}
  .filter-actions-top .btn-outline-secondary{color:#1f2544;border-color:#cdd3df}
  .filter-actions-top .btn-outline-secondary:hover{background:#f4f6fa}

  .filters-row{display:flex;flex-wrap:wrap;align-items:end;gap:12px}
  .filters-row .form-label{font-size:13px;font-weight:600;color:#475467;margin-bottom:6px}
  .filters-row .input-group-text{background:#fff;border-right:0}
  .filters-row .input-group.input-group-sm .form-control,
  .filters-row .input-group.input-group-sm .form-select{height:38px;font-size:13px}
  .filters-row .form-control,.filters-row .form-select{border-radius:10px}
  .fx-id{max-width:190px;flex:1 1 160px}
  .fx-search{min-width:260px;flex:2 1 320px}
  .fx-artist{max-width:220px;flex:1 1 200px}
  .fx-date{max-width:180px;flex:1 1 160px}

  @media (max-width:992px){.filter-actions-top{justify-content:flex-end}}

  .filter-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:12px}
  .filter-head .title{display:flex;align-items:center;gap:8px}
  .filter-head .actions .btn{border-radius:8px;font-weight:600;font-size:13px;height:34px;line-height:1.1;padding:0 12px}
  .filter-head .actions .btn-dark{background:#1f2544;border-color:#1f2544}
  .filter-head .actions .btn-dark:hover{background:#171c33}
  .filter-head .actions .btn-outline-secondary{color:#1f2544;border-color:#cdd3df}
  .filter-head .actions .btn-outline-secondary:hover{background:#f4f6fa}
</style>

<div class="container-fluid py-4 px-4">
  <div class="content-inner" style="max-width:1200px;margin:0 auto;">
    <h1 class="fw-bold" style="font-size:32px;letter-spacing:-.3px;">Dashboard Overview</h1>

    {{-- KPIs --}}
    <div class="kpi-grid">
      <div class="kpi-card">
        <div>
          <div class="kpi-title mb-1" style="color:#635bff;">In Progress</div>
          <div class="kpi-value" id="kpiInProgress" style="color:#635bff;">{{ $inProgress }}</div>
        </div>
        <div class="kpi-icon"><i class="bi bi-clock"></i></div>
      </div>
      <div class="kpi-card cursor-pointer" data-go-status="completed">
        <div>
          <div class="kpi-title mb-1" style="color:seagreen;">Completed</div>
          <div class="kpi-value" id="kpiCompleted" style="color:seagreen;">{{ $completed }}</div>
        </div>
        <div class="kpi-icon"><i class="bi bi-check2"></i></div>
      </div>
    </div>

    {{-- Furnishing Table --}}
    <section class="card table-card">
      <div class="card-hd d-flex align-items-center justify-content-between">
        <span>Furnishing Jobs</span>
      </div>

      <div class="card shadow-soft filter-card">
        <div class="card-body">
          <form method="GET" action="{{ route('furnishing.dashboard') }}">
            {{-- 顶部：标题在左、按钮在右 --}}
            <div class="filter-head">
              <div class="title">
                <span class="text-muted small">Filter &amp; search</span>
              </div>
              <div class="actions d-flex align-items-center gap-3">
                <div class="form-check form-switch m-0 d-flex align-items-center">
                  <input class="form-check-input me-1" type="checkbox" role="switch" id="mineCheck" name="mine" value="1"
                         {{ request('mine') ? 'checked' : '' }} style="cursor:pointer;">
                  <label class="form-check-label small fw-semibold text-muted" for="mineCheck" style="user-select:none;cursor:pointer;">
                    Filter Me
                  </label>
                  <i class="bi bi-info-circle ms-1 text-secondary small" data-bs-toggle="tooltip"
                     title="Show only tasks assigned to your role."></i>
                </div>

                <a href="{{ route('furnishing.dashboard') }}" class="btn btn-outline-secondary">
                  <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </a>
                <button class="btn btn-dark" type="submit">
                  <i class="bi bi-funnel me-1"></i> Apply Filter
                </button>
              </div>
            </div>

            {{-- 下方：一排输入控件 --}}
            <div class="filters-row">
              <div class="fx-id">
                <label class="form-label">Search Product ID</label>
                <div class="input-group input-group-sm has-icon">
                  <span class="input-group-text"><i class="bi bi-hash"></i></span>
                  <input type="text" name="pid" value="{{ request('pid', $pid ?? '') }}" class="form-control" placeholder="Enter Product ID">
                </div>
              </div>

              <div class="fx-search">
                <label class="form-label">Search</label>
                <div class="input-group input-group-sm has-icon">
                  <span class="input-group-text"><i class="bi bi-search"></i></span>
                  <input type="text" name="q" value="{{ request('q', $q ?? '') }}" class="form-control"
                         placeholder="Order title, Company name, or Product name">
                </div>
              </div>

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

              <div class="fx-date">
                <label class="form-label">Deadline From</label>
                <div class="input-group input-group-sm has-icon">
                  <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                  <input type="date" name="deadline_from" value="{{ request('deadline_from', $deadline_from ?? '') }}" class="form-control">
                </div>
              </div>

              <div class="fx-date">
                <label class="form-label">Deadline To</label>
                <div class="input-group input-group-sm has-icon">
                  <span class="input-group-text"><i class="bi bi-calendar-check"></i></span>
                  <input type="date" name="deadline_to" value="{{ request('deadline_to', $deadline_to ?? '') }}" class="form-control">
                </div>
              </div>
            </div>
          </form>

        </div>
      </div>
    </section>

    <div class="table-wrapper card table-card" style="margin-top:20px;">
      <table class="table align-middle mb-0">
        @php
          $q = request()->query();
          $urlWith = function(array $overrides) use ($q) {
            return route('furnishing.dashboard', array_filter(array_merge($q, $overrides), fn($v)=>$v!==null && $v!==''));
          };

          $sort = request('sort','deadline_nearest');

          $dlNext  = $sort === 'deadline_furthest' ? 'deadline_nearest' : 'deadline_furthest';
          $dlLabel = $sort === 'deadline_furthest' ? 'furthest' : ($sort === 'deadline_nearest' ? 'nearest' : '');

          $sbNext  = $sort === 'submitted_furthest' ? 'submitted_nearest' : 'submitted_furthest';
          $sbLabel = str_starts_with($sort,'submitted_') ? ($sort==='submitted_furthest'?'furthest':'nearest') : '';
        @endphp
        <thead>
        <tr>
          <th>PRODUCT ID</th>
          <th>CUTTER</th>
          <th>SQ INCH</th>
          <th>
            <a class="th-sort {{ str_starts_with($sort,'deadline_') ? 'is-active' : '' }}"
               href="{{ $urlWith(['sort' => $dlNext]) }}">
              DEADLINE
              @if($dlLabel)<span class="badge bg-light text-dark ms-1">{{ $dlLabel }}</span>@endif
            </a>
          </th>
          <th>
            <a class="th-sort {{ str_starts_with($sort,'submitted_') ? 'is-active' : '' }}"
               href="{{ $urlWith(['sort' => $sbNext]) }}">
              SUBMISSION DATE
              @if($sbLabel)<span class="badge bg-light text-dark ms-1">{{ $sbLabel }}</span>@endif
            </a>
          </th>
          @php
            // for actions sort
            $acNext  = $sort === 'accepted_last' ? 'accepted_first' : 'accepted_last';
            $acLabel = str_starts_with($sort,'accepted_')
                ? ($sort === 'accepted_last' ? 'last' : 'first')
                : '';
          @endphp
          <th class="col-actions">
            <a class="th-sort {{ str_starts_with($sort,'accepted_') ? 'is-active' : '' }}"
              href="{{ $urlWith(['sort' => $acNext]) }}">
              ACTIONS
              @if($acLabel)
                <span class="badge bg-light text-dark ms-1">{{ $acLabel }}</span>
              @endif
            </a>
          </th>
        </tr>
        </thead>

        <tbody id="jobsTbody">
        @forelse ($jobs as $j)
          @php
            $isFurnishing = strtolower((string)($j->taskType ?? '')) === 'furnishing';
            $accepted     = (int)($j->accepted ?? 0) === 1;

            // 👇 定义查看/编辑链接：铅笔 -> 直接进入编辑态
            $viewUrl = route('furnishing.job.show', $j->ProductID);
            $editUrl = route('furnishing.job.show', [$j->ProductID, 'edit' => 1]);
          @endphp

          <tr id="job-{{ $j->ProductID }}"
              class="js-row-open"
              data-href="{{ $viewUrl }}"
              style="cursor:pointer;">
            <td class="whitespace-nowrap font-medium">{{ $j->display_product_id }}</td>
            <td>{{ ($j->cutter ?? '-') === '-' ? '—' : $j->cutter }}</td>
            <td>{{ is_numeric($j->sq_inch ?? null) ? number_format((float)$j->sq_inch, 0).' sq in' : '0 sq in' }}</td>

            <td class="td-deadline" data-date="{{ $j->deadline ?: '' }}">
              {{ $j->deadline ? \Carbon\Carbon::parse($j->deadline)->format('Y-m-d') : '—' }}
            </td>
            <td class="td-submitted" data-date="{{ $j->submission_date ?: '' }}">
              {{ $j->submission_date ? \Carbon\Carbon::parse($j->submission_date)->format('Y-m-d') : '—' }}
            </td>

            <td class="text-nowrap col-actions text-end">
              @if (!$accepted || !$isFurnishing)
                {{-- 未接受或非 Furnishing：只可查看 --}}
                <a class="icon-pill" href="{{ $viewUrl }}" title="View"><i class="bi bi-eye"></i></a>
              @else
                {{-- 已接受且是 Furnishing：✔ 完成 + ✏ 直接编辑 --}}
                <button class="icon-pill js-mark" 
                        data-id="{{ $j->ProductID }}" 
                        title="Marked as Completed"
                        style="background-color:#4CAF50; color:white; border:none; border-radius:50%; padding:6px 8px; cursor:pointer; transition:0.3s; box-shadow:0 2px 5px rgba(0,0,0,0.15);">
                  <i class="bi bi-check2" style="font-size:16px;"></i>
                </button>
                <a class="icon-pill" href="{{ $editUrl }}" title="Edit">
                  <i class="bi bi-pencil"></i>
                </a>
              @endif
            </td>
          </tr>
        @empty
          <tr><td colspan="7" class="text-center text-muted">No jobs found.</td></tr>
        @endforelse
        </tbody>
      </table>

      <div class="card-ft">
        <nav class="d-flex justify-content-end">
          <ul class="pagination pill-pager mb-0">
            {{-- Previous --}}
            @if ($jobs->onFirstPage())
              <li class="page-item disabled"><span class="page-link">Previous</span></li>
            @else
              <li class="page-item"><a class="page-link" href="{{ $jobs->previousPageUrl() }}">Previous</a></li>
            @endif

            @php
              $last = max(1, $jobs->lastPage());
              $current = $jobs->currentPage();
              $from = max(1, $current - 1);
              $to = min($last, $current + 1);
            @endphp

            @if ($from > 1)
              <li class="page-item"><a class="page-link" href="{{ $jobs->url(1) }}">1</a></li>
              @if ($from > 2)
                <li class="page-item disabled"><span class="page-link">…</span></li>
              @endif
            @endif

            @for ($p=$from; $p<=$to; $p++)
              @if ($p==$current)
                <li class="page-item active"><span class="page-link">{{ $p }}</span></li>
              @else
                <li class="page-item"><a class="page-link" href="{{ $jobs->url($p) }}">{{ $p }}</a></li>
              @endif
            @endfor

            @if ($to < $last)
              @if ($to < $last - 1)
                <li class="page-item disabled"><span class="page-link">…</span></li>
              @endif
              <li class="page-item"><a class="page-link" href="{{ $jobs->url($last) }}">{{ $last }}</a></li>
            @endif

            {{-- Next --}}
            @if ($jobs->hasMorePages())
              <li class="page-item"><a class="page-link" href="{{ $jobs->nextPageUrl() }}">Next</a></li>
            @else
              <li class="page-item disabled"><span class="page-link">Next</span></li>
            @endif
          </ul>
        </nav>
      </div>
    </div>
  </div>
</div>

{{-- Confirm Modal --}}
<div id="confirmModal" class="cx-mask" aria-hidden="true">
  <div class="cx-modal" role="dialog" aria-modal="true" aria-labelledby="cxTitle">
    <div class="cx-header">
      <div id="cxTitle" class="cx-title">Confirmation</div>
      <button type="button" class="cx-close" data-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="cx-body">
      <div class="cx-qicon"><i class="bi bi-question-lg"></i></div>
      <div>
        <div class="cx-q">Do you done the furnishing?</div>
        <div class="cx-help">This action will save the job order and move it to the completed phase.</div>
      </div>
    </div>
    <div class="cx-footer">
      <button type="button" class="cx-btn cx-btn-ghost" data-close>No</button>
      <button type="button" class="cx-btn cx-btn-dark" id="confirmYes">Yes</button>
    </div>
  </div>
</div>

<script>
(() => {
  const mask = document.getElementById('confirmModal');
  const btnYes = document.getElementById('confirmYes');
  let currentId = null;
  const csrf = '{{ csrf_token() }}';

  // open modal
  document.addEventListener('click', (e) => {
    const markBtn = e.target.closest('.js-mark');
    if (!markBtn) return;
    currentId = markBtn.dataset.id;
    mask.classList.add('show');
    mask.setAttribute('aria-hidden', 'false');
  });

  // close modal
  mask.addEventListener('click', (e) => {
    if (e.target === mask || e.target.hasAttribute('data-close')) {
      mask.classList.remove('show');
      mask.setAttribute('aria-hidden', 'true');
    }
  });

  function toInt(el){
    if (!el) return 0;
    const n = parseInt((el.textContent || '0').replace(/[^\d-]/g,''),10);
    return isNaN(n) ? 0 : n;
  }

  btnYes.addEventListener('click', async () => {
    if (!currentId) return;
    btnYes.disabled = true;

    try {
      const url = "{{ route('furnishing.jobs.complete', ['productId' => '__ID__']) }}".replace('__ID__', currentId);

      const res = await fetch(url, {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
      });
      const data = await res.json();

      if (data && data.ok) {
        const row = document.getElementById('job-' + currentId);
        if (row) row.remove();

        const tbody = document.getElementById('jobsTbody');
        if (tbody && !tbody.querySelector('tr')) {
          tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No jobs found.</td></tr>';
        }

        const inProgEl = document.getElementById('kpiInProgress');
        const compEl   = document.getElementById('kpiCompleted');
        if (inProgEl) inProgEl.textContent = Math.max(0, toInt(inProgEl) - 1);
        if (compEl)   compEl.textContent   = toInt(compEl) + 1;
      } else {
        alert((data && data.message) || 'Failed to mark complete.');
      }
    } catch (err) {
      console.error(err);
      alert('Failed to mark complete.');
    } finally {
      btnYes.disabled = false;
      mask.classList.remove('show');
      mask.setAttribute('aria-hidden', 'true');
      currentId = null;
    }
  });
})();

(() => {
  const input = document.getElementById('pidFilter');
  const tbody = document.querySelector('.table tbody');
  if (!input || !tbody) return;
  input.addEventListener('input', function() {
    const q = (this.value || '').toLowerCase().trim();
    tbody.querySelectorAll('tr').forEach(tr => {
      const text = (tr.querySelector('td')?.textContent || '').toLowerCase();
      tr.style.display = q && !text.includes(q) ? 'none' : '';
    });
  });
})();

// 行双击：打开「查看」(非编辑)
document.addEventListener('dblclick', (e) => {
  const tr = e.target.closest('tr.js-row-open');
  if (!tr) return;
  const tag = (e.target.tagName || '').toLowerCase();
  if (['a','button','input','select','textarea','label','svg','path','i'].includes(tag)) return;
  const url = tr.dataset.href;
  if (url) window.location.href = url;
});

// KPI 卡片点击跳转到历史筛选
(function(){
  const base = "{{ route('furnishing.history') }}";
  document.querySelectorAll('[data-go-status]').forEach(function(tile){
    tile.addEventListener('click', function(){
      const status = tile.getAttribute('data-go-status')?.trim();
      if (!status) return;
      const url = new URL(base, window.location.origin);
      url.searchParams.set('status', status);
      window.location.href = url.toString();
    });
  });
})();

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
  const mine = document.getElementById('mineCheck');
  if (mine) mine.addEventListener('change', () => mine.form?.submit());
});
</script>
@endsection
