@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
  .form-switch-lg .form-check-input{width:3rem;height:1.5rem}
  .form-switch-lg .form-check-input:checked{background-color:#6366f1;border-color:#6366f1}

  /* KPI */
  .kpi-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:18px}
  .kpi-card{border:1px solid #ECEFF3;background:#fff;border-radius:14px;box-shadow:0 2px 6px rgba(16,24,40,.05);padding:20px;display:flex;align-items:center;justify-content:space-between}
  .kpi-title{color:#667085;font-weight:600;font-size:14px}
  .kpi-value{font-size:40px;line-height:1.1;color:#111827;font-weight:800;letter-spacing:-.5px}
  .kpi-icon{width:40px;height:40px;border-radius:12px;background:#F4F6FA;color:#667085;display:flex;align-items:center;justify-content:center;font-size:18px}

  /* Card & Table */
  .card{background:#fff;border:1px solid #ECEFF3;border-radius:14px;box-shadow:0 1px 2px rgba(16,24,40,.05)}
  .table-card .card-hd{padding:12px 16px;font-weight:700;border-bottom:1px solid #EEF2F7}
  .table-card .card-ft{padding:12px 16px;border-top:1px solid #EEF2F7;background:#fff}
  .table-wrapper{overflow:hidden}
  .table{width:100%;border-collapse:separate;border-spacing:0;table-layout:fixed}
  .table thead th{background:#F8FAFC;color:#6B7280;font-weight:600;font-size:12px;letter-spacing:.2px;border-bottom:1px solid #EEF2F7;text-align:left;padding:14px 16px}
  .table td{color:#1F2937;padding:14px 16px;border-top:1px solid #F1F4F8;vertical-align:middle}
  .table tbody tr:hover{background:#FAFBFC}
  .table td:first-child{font-weight:700;color:#111827}
  .col-actions{ width:120px; } 
  .table td.col-actions{
    display:flex;
    align-items:center;
    justify-content:flex-start;   /* 改为靠左对齐 */
    gap:8px;                      /* 两个 icon 间距 */
    padding-left:18px;            /* 左边内距控制视觉距离 */
    padding-right:0;              /* 去掉右边多余空白 */
  }
  .empty{padding:28px;text-align:center;color:#667085}

  /* Action buttons */
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

  /* Modal (confirmation) */
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

  /* Pager */
  .pill-pager .page-link{border-radius:999px;border:1px solid #E6E8F0;background:#F6F7FB;color:#667085;padding:.45rem .9rem;line-height:1}
  .pill-pager .page-item+.page-item{margin-left:.5rem}
  .pill-pager .page-item.active .page-link{background:#635bff;border-color:#635bff;color:#fff}
  .pill-pager .page-item.disabled .page-link{opacity:.6;cursor:not-allowed;background:#F6F7FB}

  @media (max-width:992px){.kpi-grid{grid-template-columns:1fr}}

  /* Filter toolbar */
  .filter-card{border:1px solid #ECEFF3;border-radius:14px}
  .filter-card .form-label{font-size:.8rem;color:#6b7280;margin-bottom:.25rem}
  .filter-card .input-group-text{background:#f8fafc;border-color:#e5e7eb}
  .filter-card .form-control{border-color:#e5e7eb}
  .filter-card .has-icon .form-control{border-left:0}
  .filter-card .input-group-text i{opacity:.75}

  @media (min-width:1200px){
    .filter-card form .col-lg-2{min-width:220px}
    .filter-card form .col-lg-3{min-width:260px}
  }

  .th-sort{text-decoration:none;color:inherit;user-select:none}
  .th-sort:hover{text-decoration:underline}
  .th-sort.is-active{font-weight:700}

  .filter-head{display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;gap:12px}
  .filter-head .title{display:flex;align-items:center;gap:8px}
  .filter-head .actions .btn{border-radius:8px;font-weight:600;font-size:13px;height:34px;line-height:1.1;padding:0 12px}
  .filter-head .actions .btn-dark{background:#1f2544;border-color:#1f2544}
  .filter-head .actions .btn-dark:hover{background:#171c33}
  .filter-head .actions .btn-outline-secondary{color:#1f2544;border-color:#cdd3df}
  .filter-head .actions .btn-outline-secondary:hover{background:#f4f6fa}

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
</style>

<div class="container-fluid py-4 px-4">
  <div class="content-inner" style="max-width:1200px;margin:0 auto;">

    <h1 class="fw-bold mb-3" style="font-size:32px;letter-spacing:-.3px;">Dashboard Overview</h1>

    @if (session('status'))
      <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    {{-- KPIs --}}
    <div class="kpi-grid">
      <div class="kpi-card">
        <div>
          <div class="kpi-title mb-1" style="color:#635bff;">In Progress</div>
          <div class="kpi-value" data-kpi="inprogress" style="color:#635bff;">{{ $inProgress }}</div>
        </div>
        <div class="kpi-icon"><i class="bi bi-clock"></i></div>
      </div>
      <div class="kpi-card cursor-pointer" data-go-status="completed">
        <div>
          <div class="kpi-title mb-1" style="color:seagreen;">Completed</div>
          <div class="kpi-value" data-kpi="completed" style="color:seagreen;">{{ $completed }}</div>
        </div>
        <div class="kpi-icon"><i class="bi bi-check2"></i></div>
      </div>
    </div>

    {{-- Printing Table --}}
    <section class="card table-card">
      <div class="card-hd d-flex align-items-center justify-content-between">
        <span>Printing Jobs</span>
      </div>

      <div class="card shadow-soft filter-card">
        <div class="card-body">
          <form method="GET" action="{{ route('printing.dashboard') }}">
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

                <a href="{{ route('printing.dashboard') }}" class="btn btn-outline-secondary">
                  <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
                </a>
                <button class="btn btn-dark" type="submit">
                  <i class="bi bi-funnel me-1"></i> Apply Filter
                </button>
              </div>
            </div>

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
                  <input type="date" name="deadline_from" value="{{ request('deadline_from', $deadline_from ?? '') }}" class="form-control" placeholder="dd/mm/yyyy">
                </div>
              </div>

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
    </section>

    <div class="table-wrapper card table-card" style="margin-top:20px;">
      <table class="table align-middle mb-0">
        @php
          $q = request()->query();
          $urlWith = function(array $overrides) use ($q) {
            return route('printing.dashboard', array_filter(array_merge($q, $overrides), fn($v)=>$v!==null && $v!==''));
          };

          $sort = request('sort','deadline_nearest');

          $dlNext = $sort === 'deadline_furthest' ? 'deadline_nearest' : 'deadline_furthest';
          $dlLabel = $sort === 'deadline_furthest' ? 'furthest' : ($sort === 'deadline_nearest' ? 'nearest' : '');

          $sbNext = $sort === 'submitted_furthest' ? 'submitted_nearest' : 'submitted_furthest';
          $sbLabel = str_starts_with($sort,'submitted_') ? ($sort==='submitted_furthest'?'furthest':'nearest') : '';
        @endphp
        <thead>
          <tr>
            <th>PRODUCT ID</th>
            <th>ORDER TITLE</th>
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
        <tbody>
          @forelse ($jobs as $row)
            @php
              $deadline  = $row->deadline ? \Carbon\Carbon::parse($row->deadline)->format('Y-m-d') : '—';
              $submitted = $row->submission_date ? \Carbon\Carbon::parse($row->submission_date)->format('Y-m-d') : '—';
              $sq        = is_numeric($row->sq_inch ?? null) ? number_format((float)$row->sq_inch, 0) . ' sq in' : '0 sq in';

              $isPrinting = strtolower((string)($row->taskType ?? '')) === 'printing';
              $accepted   = (int)($row->accepted ?? 0) === 1;

              // deadline flags from controller (is_overdue / is_due_soon)
              $isOverdue   = (int)($row->is_overdue ?? 0) === 1;
              $isDueSoon   = (int)($row->is_due_soon ?? 0) === 1;
              $dlClass     = $isOverdue ? 'text-danger fw-semibold'
                          : ($isDueSoon ? 'text-warning fw-semibold' : '');

              // 目标：铅笔 => 直接打开编辑态
              $showUrl     = route('printing.orders.show', [$row->ProductID, 'from' => 'dashboard']);
              $editUrl     = route('printing.orders.show', [$row->ProductID, 'edit' => 1]);
            @endphp
            <tr id="job-{{ $row->ProductID }}" class="js-row-open" data-code="{{ $row->display_product_id }}" data-href="{{ $showUrl }}" style="cursor:pointer;">
              <td class="whitespace-nowrap font-medium">{{ $row->display_product_id }}</td>
              <td>{{ $row->order_title ?? '—' }}</td>
              <td>{{ number_format((float)($row->sq_inch ?? 0), 4) }} sq in</td>
              <td class="td-deadline {{ $dlClass }}" data-date="{{ $deadlineRaw ?? '' }}">
                {{ $deadline }}
                @if($isOverdue)
                  <span class="badge bg-danger-subtle text-danger ms-2">Expired</span>
                @elseif($isDueSoon)
                  <span class="badge bg-warning-subtle text-warning ms-2">Near</span>
                @endif
              </td>
              <td class="td-submitted" data-date="{{ $row->submission_date ?: '' }}">{{ $submitted }}</td>
              <td class="text-nowrap">
                @if (!$accepted || !$isPrinting)
                <a href="{{ $showUrl }}" class="icon-pill" title="View"><i class="bi bi-eye"></i></a>
                @endif
                {{-- 仅当 printing 且已接受：显示“完成”与“编辑(直入编辑态)” --}}
                @if ($isPrinting && $accepted)
                  <button class="icon-pill js-mark" data-id="{{ $row->ProductID }}" title="Mark as Completed" style="background-color:#4CAF50; color:white; border:none; border-radius:50%; padding:6px 8px; cursor:pointer; transition:0.3s; box-shadow:0 2px 5px rgba(0,0,0,0.15);">
                    <i class="bi bi-check2" style="font-size:16px;"></i>
                  </button>
                  <a href="{{ $editUrl }}" class="icon-pill" title="Edit (jump to edit mode)">
                    <i class="bi bi-pencil"></i>
                  </a>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="empty">No printing jobs found.</td></tr>
          @endforelse
        </tbody>
      </table>
      <div class="card-ft">
        <nav class="d-flex justify-content-end">
          <ul class="pagination pill-pager mb-0">
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

            @for ($p = $from; $p <= $to; $p++)
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

{{-- Confirmation Modal --}}
<div id="printConfirm" class="cx-mask" aria-hidden="true">
  <div class="cx-modal" role="dialog" aria-modal="true" aria-labelledby="cxTitle">
    <div class="cx-header">
      <div id="cxTitle" class="cx-title">Confirmation</div>
      <button type="button" class="cx-close" data-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="cx-body">
      <div class="cx-qicon"><i class="bi bi-question-lg"></i></div>
      <div>
        <div class="cx-q">Do you done the printing?</div>
        <div class="cx-help">This action will save the job order and move it to the furnishing phase.</div>
      </div>
    </div>
    <div class="cx-footer">
      <button type="button" class="cx-btn cx-btn-ghost" data-close>No</button>
      <button type="button" class="cx-btn cx-btn-dark" id="printConfirmYes">Yes</button>
    </div>
  </div>
</div>

<script>
(() => {
  const mask = document.getElementById('printConfirm');
  const yesBtn = document.getElementById('printConfirmYes');
  let currentId = null;
  const csrf = '{{ csrf_token() }}';

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.js-mark');
    if (!btn) return;
    currentId = btn.dataset.id;
    mask.classList.add('show');
    mask.setAttribute('aria-hidden', 'false');
  });

  mask.addEventListener('click', (e) => {
    if (e.target === mask || e.target.hasAttribute('data-close')) {
      mask.classList.remove('show');
      mask.setAttribute('aria-hidden', 'true');
    }
  });

  yesBtn.addEventListener('click', async () => {
    if (!currentId) return;
    yesBtn.disabled = true;

    try {
      const url = "{{ route('printing.jobs.complete', ['productId' => '__ID__']) }}".replace('__ID__', currentId);
      const res = await fetch(url, {
        method: 'PATCH',
        headers: {'X-CSRF-TOKEN': csrf,'Accept': 'application/json'}
      });
      const data = await res.json();

      if (data.ok) {
        const row = document.getElementById('job-' + currentId) ||
          document.querySelector(`button.js-mark[data-id="${currentId}"]`)?.closest('tr');
        if (row) row.remove();

        const bump = (sel, d) => {
          const el = document.querySelector(sel);
          if (!el) return;
          const n = parseInt((el.textContent || '').trim(), 10);
          if (!isNaN(n)) el.textContent = Math.max(0, n + d);
        };
        bump('[data-kpi="inprogress"]', -1);
        bump('[data-kpi="completed"]', +1);
      }
    } catch (err) {
      console.error(err);
    } finally {
      yesBtn.disabled = false;
      mask.classList.remove('show');
      mask.setAttribute('aria-hidden', 'true');
      currentId = null;
    }
  });
})();

document.addEventListener('dblclick', function(e) {
  const tr = e.target.closest('tr.js-row-open');
  if (!tr) return;
  const tag = (e.target.tagName || '').toLowerCase();
  if (['a','button','input','select','textarea','label','svg','path','i'].includes(tag)) return;
  const url = tr.dataset.href;
  if (url) window.location.href = url;
});

(function() {
  // KPI tile to history filter
  const base = "{{ route('printing.history') }}";
  document.querySelectorAll('[data-go-status]').forEach(function(tile) {
    tile.addEventListener('click', function() {
      const status = tile.getAttribute('data-go-status')?.trim();
      if (!status) return;
      const url = new URL(base, window.location.origin);
      url.searchParams.set('status', status);
      window.location.href = url.toString();
    });
  });
})();

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el);
  });
  const mine = document.getElementById('mineCheck');
  if (mine) mine.addEventListener('change', () => mine.form?.submit());
});
</script>
@endsection
