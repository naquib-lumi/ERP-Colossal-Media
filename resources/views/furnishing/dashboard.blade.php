@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
  .form-switch-lg .form-check-input {
    width: 3rem; height: 1.5rem;
  }
  .form-switch-lg .form-check-input:checked {
    background-color: #6366f1; border-color: #6366f1;
  }
  /* ===== KPI ===== */
  .kpi-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 18px
  }

  .kpi-card {
    border: 1px solid #ECEFF3;
    background: #fff;
    border-radius: 14px;
    box-shadow: 0 2px 6px rgba(16, 24, 40, .05);
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between
  }

  .kpi-title {
    color: #667085;
    font-weight: 600;
    font-size: 14px
  }

  .kpi-value {
    font-size: 40px;
    line-height: 1.1;
    color: #111827;
    font-weight: 800;
    letter-spacing: -.5px
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

  /* ===== Card & Table ===== */
  .card {
    background: #fff;
    border: 1px solid #ECEFF3;
    border-radius: 14px;
    box-shadow: 0 1px 2px rgba(16, 24, 40, .05)
  }

  .table-card .card-hd {
    padding: 12px 16px;
    font-weight: 700;
    border-bottom: 1px solid #EEF2F7
  }

  .table-card .card-ft {
    padding: 12px 16px;
    border-top: 1px solid #EEF2F7;
    background: #fff
  }

  .table-wrapper {
    overflow: hidden
  }

  .table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    table-layout: fixed
  }

  .table thead th {
    background: #F8FAFC;
    color: #6B7280;
    font-weight: 600;
    font-size: 12px;
    letter-spacing: .2px;
    border-bottom: 1px solid #EEF2F7;
    text-align: left;
    padding: 14px 16px
  }

  .table td {
    color: #1F2937;
    padding: 14px 16px;
    border-top: 1px solid #F1F4F8;
    vertical-align: middle
  }

  .table tbody tr:hover {
    background: #FAFBFC
  }

  .table td:first-child {
    font-weight: 700;
    color: #111827
  }

  /* action col a bit narrower now that Report is gone */
  .col-actions {
    width: 170px
  }

  /* Action buttons – circular vibe like screenshot */
  .icon-pill {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border: 1px solid #E3E8EF;
    background: #fff;
    color: #475467;
  }

  .icon-pill+.icon-pill {
    margin-left: 8px
  }

  .icon-pill:hover {
    background: #F4F6FA;
    color: #111827;
    border-color: #D7DFE7
  }

  /* Empty state */
  .empty {
    padding: 28px;
    text-align: center;
    color: #667085
  }

  /* ===== Modal (matches your picture) ===== */
  .cx-mask {
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, .45);
    display: none;
    z-index: 1080
  }

  .cx-mask.show {
    display: grid;
    place-items: center
  }

  .cx-modal {
    width: 560px;
    max-width: 92vw;
    background: #fff;
    border: 1px solid #E7EAF0;
    border-radius: 14px;
    box-shadow: 0 24px 80px rgba(2, 6, 23, .28);
    overflow: hidden
  }

  .cx-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 18px;
    border-bottom: 1px solid #EDF0F3
  }

  .cx-title {
    font-weight: 700;
    color: #0F172A
  }

  .cx-close {
    border: 0;
    background: transparent;
    color: #94A3B8
  }

  .cx-close:hover {
    color: #6B7280
  }

  .cx-body {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    padding: 18px
  }

  .cx-qicon {
    width: 34px;
    height: 34px;
    border-radius: 10px;
    background: #F3F4F6;
    color: #6B7280;
    display: flex;
    align-items: center;
    justify-content: center
  }

  .cx-q {
    font-weight: 600;
    color: #111827;
    margin-bottom: 4px
  }

  .cx-help {
    color: #667085
  }

  .cx-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    padding: 14px 16px;
    border-top: 1px solid #EDF0F3;
    background: #FBFBFC
  }

  .cx-btn {
    border-radius: 10px;
    padding: 10px 18px;
    font-weight: 700
  }

  .cx-btn-ghost {
    background: #EEF2F6;
    border: 1px solid #E5E7EB;
    color: #0F172A
  }

  .cx-btn-ghost:hover {
    background: #E2E8F0
  }

  .cx-btn-dark {
    background: #111827;
    border: 1px solid #111827;
    color: #fff
  }

  .cx-btn-dark:hover {
    background: #0B1220;
    border-color: #0B1220
  }

  @media (max-width: 992px) {
    .kpi-grid {
      grid-template-columns: 1fr
    }
  }

  /* === Pill Pager (keeps pager visible) === */
  .pill-pager .page-link {
    border-radius: 999px;
    border: 1px solid #E6E8F0;
    background: #F6F7FB;
    color: #667085;
    padding: .45rem .9rem;
    line-height: 1;
  }

  .pill-pager .page-item+.page-item {
    margin-left: .5rem;
  }

  .pill-pager .page-item.active .page-link {
    background: #635bff;
    border-color: #635bff;
    color: #fff;
  }

  .pill-pager .page-item.disabled .page-link {
    opacity: .6;
    cursor: not-allowed;
    background: #F6F7FB;
  }

  .filter-card{ border:1px solid #ECEFF3; border-radius:14px; }
  .filter-card .form-label{ font-size:.8rem; color:#6b7280; margin-bottom:.25rem; }
  .filter-card .input-group-text{ background:#f8fafc; border-color:#e5e7eb; }
  .filter-card .form-control{ border-color:#e5e7eb; }
  .filter-card .has-icon .form-control{ border-left:0; }
  .filter-card .input-group-text i{ opacity:.75; }
  @media (min-width: 1200px){
    .filter-card form .col-lg-2 { min-width: 220px; }
    .filter-card form .col-lg-3 { min-width: 260px; }
  }

  .js-sort-deadline { cursor: pointer; user-select: none; }
  .js-sort-deadline::after { content: " ↕"; opacity: .5; font-size: .9em; }
  .js-sort-deadline[data-order="asc"]::after  { content: " ↑"; }
  .js-sort-deadline[data-order="desc"]::after { content: " ↓"; }

  .th-sort { text-decoration:none; color:inherit; user-select:none; }
  .th-sort:hover { text-decoration:underline; }
  .th-sort.is-active { font-weight:700; }
</style>

<div class="container-fluid py-4 px-4">
  <div class="content-inner" style="max-width:1200px;margin:0 auto;">
    <h1 class="fw-bold" style="font-size:32px;letter-spacing:-.3px;">Dashboard Overview</h1>

    {{-- KPIs --}}
    <div class="kpi-grid">
      <div class="kpi-card">
        <div>
          <div class="kpi-title mb-1" style="color: #635bff;">In Progress</div>
          <div class="kpi-value" id="kpiInProgress" style="color: #635bff;">{{ $inProgress }}</div>
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

      <div class="card shadow-soft mb-3 filter-card">
        <div class="card-body">
          <div class="d-flex align-items-center mb-3">
            <h6 class="mb-0 fw-semibold">Furnishing Jobs</h6>
            <span class="text-muted small ms-2">Filter &amp; search</span>
          </div>

          <form class="row g-3 align-items-end" method="GET" action="{{ route('furnishing.dashboard') }}">
            {{-- Client-side Product ID (page only) --}}
            <div class="col-12 col-md-6 col-lg-3">
              <label class="form-label">Search Product ID</label>
              <div class="input-group input-group-sm has-icon">
                <span class="input-group-text"><i class="bi bi-hash"></i></span>
                <input type="text" name="pid" value="{{ request('pid', $pid ?? '') }}" class="form-control" placeholder="Enter product ID">
              </div>
            </div>

            {{-- Unified keyword: cutter / order title / company / product name --}}
            <div class="col-12 col-md-6 col-lg-4">
              <label class="form-label">Search</label>
              <div class="input-group input-group-sm has-icon">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" name="q" value="{{ request('q') }}" class="form-control"
                      placeholder="Cutter, Order title, Company name, or Product name">
              </div>
            </div>

            {{-- Artist --}}
            <div class="col-12 col-md-6 col-lg-3">
              <label class="form-label">Artist</label>
              <div class="input-group input-group-sm has-icon">
                <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                <select name="artist" class="form-select">
                  <option value="">All artists</option>
                  @foreach (($artists ?? []) as $a)
                    <option value="{{ $a->id }}" {{ (string)$a->id === (string)request('artist') ? 'selected' : '' }}>
                      {{ $a->name }}
                    </option>
                  @endforeach
                </select>
              </div>
            </div>

            {{-- Deadline --}}
            <div class="col-6 col-md-4 col-lg-2">
              <label class="form-label">Deadline From</label>
              <div class="input-group input-group-sm has-icon">
                <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                <input type="date" name="deadline_from" value="{{ request('deadline_from') }}" class="form-control">
              </div>
            </div>
            <div class="col-6 col-md-4 col-lg-2">
              <label class="form-label">Deadline To</label>
              <div class="input-group input-group-sm has-icon">
                <span class="input-group-text"><i class="bi bi-calendar-check"></i></span>
                <input type="date" name="deadline_to" value="{{ request('deadline_to') }}" class="form-control">
              </div>
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label d-flex align-items-center gap-1">
                Only my tasks
                <i class="bi bi-info-circle text-muted"
                  data-bs-toggle="tooltip"
                  title="Show products currently in a stage that matches your role"></i>
              </label>

              <div class="form-switch form-switch-lg">
                <input class="form-check-input mine-switch" type="checkbox" role="switch"
                      id="mineCheck" name="mine" value="1"
                      {{ request('mine') ? 'checked' : '' }}>
                <label class="form-check-label ms-2" for="mineCheck">Filter Me</label>
              </div>
            </div>

            {{-- Actions --}}
            <div class="col-12 col-lg-4 ms-auto d-flex gap-2 justify-content-end">
              <a href="{{ route('furnishing.dashboard') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
              </a>
              <button class="btn btn-dark"><i class="bi bi-funnel me-1"></i> Apply Filter</button>
            </div>
          </form>
        </div>
      </div>

      <div class="table-wrapper">
        <table class="table align-middle mb-0">
          @php
            $q = request()->query();
            $urlWith = function(array $overrides) use ($q) {
              return route('furnishing.dashboard', array_filter(array_merge($q, $overrides), fn($v)=>$v!==null && $v!==''));
            };

            $sort = request('sort','deadline_nearest');

            $dlNext   = $sort === 'deadline_furthest' ? 'deadline_nearest' : 'deadline_furthest';
            $dlLabel  = $sort === 'deadline_furthest' ? 'furthest' : ($sort === 'deadline_nearest' ? 'nearest' : '');

            $sbNext   = $sort === 'submitted_furthest' ? 'submitted_nearest' : 'submitted_furthest';
            $sbLabel  = str_starts_with($sort,'submitted_') ? ($sort==='submitted_furthest'?'furthest':'nearest') : '';
          @endphp
          <thead>
            <tr>
              <th>PRODUCT ID</th>
              <th>CUTTER</th>
              <th>SQ INCH</th>

              {{-- DEADLINE: toggle nearest <-> furthest --}}
              <th>
                <a class="th-sort {{ str_starts_with($sort,'deadline_') ? 'is-active' : '' }}"
                  href="{{ $urlWith(['sort' => $dlNext]) }}">
                  DEADLINE
                  @if($dlLabel)
                    <span class="badge bg-light text-dark ms-1">{{ $dlLabel }}</span>
                  @endif
                </a>
              </th>

              {{-- SUBMISSION DATE: toggle nearest <-> furthest --}}
              <th>
                <a class="th-sort {{ str_starts_with($sort,'submitted_') ? 'is-active' : '' }}"
                  href="{{ $urlWith(['sort' => $sbNext]) }}">
                  SUBMISSION DATE
                  @if($sbLabel)
                    <span class="badge bg-light text-dark ms-1">{{ $sbLabel }}</span>
                  @endif
                </a>
              </th>

              <th class="col-actions">ACTIONS</th>
            </tr>
          </thead>
            <tbody id="jobsTbody">
            @forelse ($jobs as $j)
              @php
                $code = $j->order_number
                  ? $j->order_number.'-P'.$j->ProductID
                  : 'ORD'.$j->OrderID.'-P'.$j->ProductID;

                $cutter = $j->cutter ?: '—';
                $sqIn   = is_null($j->sq_inch) ? 0 : $j->sq_inch;
                $accepted = (int)($j->accepted ?? 0) === 1;

                $viewUrl  = route('furnishing.job.show', $j->ProductID);              // always available
                $editUrl  = route('furnishing.job.show', [$j->ProductID, 'edit' => 1]); // same page; edit visible after accepted
                $doneUrl  = route('furnishing.jobs.complete', $j->ProductID);         // PATCH

                $isFurnishing = strtolower((string)($j->taskType ?? '')) === 'furnishing';
                $accepted = (int)($j->accepted ?? 0) === 1;
              @endphp
              <tr id="job-{{ $j->ProductID }}"
    class="js-row-open"
    data-href="{{ route('furnishing.job.show', $j->ProductID) }}"
    style="cursor:pointer;">
  <td class="whitespace-nowrap font-medium">
                {{ $j->display_product_id }}
              </td>
  <td>{{ ($j->cutter ?? '-') === '-' ? '—' : $j->cutter }}</td>
  <td>{{ is_numeric($j->sq_inch ?? null) ? number_format((float)$j->sq_inch, 0).' sq in' : '0 sq in' }}</td>

  {{-- sortable date cells with raw ISO in data-date --}}
  <td class="td-deadline" data-date="{{ $j->deadline ?: '' }}">
    {{ $j->deadline ? \Carbon\Carbon::parse($j->deadline)->format('Y-m-d') : '—' }}
  </td>
  <td class="td-submitted" data-date="{{ $j->submission_date ?: '' }}">
    {{ $j->submission_date ? \Carbon\Carbon::parse($j->submission_date)->format('Y-m-d') : '—' }}
  </td>

  <td class="text-nowrap">
    @php
      $isFurnishing = strtolower((string)($j->taskType ?? '')) === 'furnishing';
      $accepted = (int)($j->accepted ?? 0) === 1;
    @endphp
    @if (!$accepted || !$isFurnishing)
      <a class="icon-pill" href="{{ route('furnishing.job.show', $j->ProductID) }}" title="View">
        <i class="bi bi-eye"></i>
      </a>
    @endif
    @if ($isFurnishing && $accepted)
      <button class="icon-pill js-mark" data-id="{{ $j->ProductID }}" title="Mark Completed">
        <i class="bi bi-check2"></i>
      </button>
      <a href="{{ route('furnishing.orders.show', $j->ProductID) }}" class="icon-pill" title="Edit">
        <i class="bi bi-pencil"></i>
      </a>
    @endif
  </td>
</tr>
            @empty
              <tr>
                <td colspan="7" class="text-center text-muted">No jobs found.</td>
              </tr>
            @endforelse
            </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      <div class="card-ft">
        <nav class="d-flex justify-content-end">
          <ul class="pagination pill-pager mb-0">
            {{-- Previous --}}
            @if ($jobs->onFirstPage())
            <li class="page-item disabled"><span class="page-link">Previous</span></li>
            @else
            <li class="page-item"><a class="page-link" href="{{ $jobs->previousPageUrl() }}">Previous</a></li>
            @endif

            {{-- Compact page window with ellipses --}}
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

                {{-- Next --}}
                @if ($jobs->hasMorePages())
                <li class="page-item"><a class="page-link" href="{{ $jobs->nextPageUrl() }}">Next</a></li>
                @else
                <li class="page-item disabled"><span class="page-link">Next</span></li>
                @endif
          </ul>
        </nav>
      </div>
    </section>
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
    const mask   = document.getElementById('confirmModal');
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

    function toInt(el) {
      if (!el) return 0;
      const n = parseInt((el.textContent || '0').replace(/[^\d-]/g, ''), 10);
      return isNaN(n) ? 0 : n;
    }

    btnYes.addEventListener('click', async () => {
      if (!currentId) return;

      btnYes.disabled = true;

      try {
        const url = "{{ route('furnishing.jobs.complete', ['productId' => '__ID__']) }}"
                      .replace('__ID__', currentId);

        const res = await fetch(url, {
          method: 'PATCH',
          headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
        });

        const data = await res.json();

        if (data && data.ok) {
          // 1) remove the row immediately
          const row = document.getElementById('job-' + currentId);
          if (row) row.remove();

          // 2) update empty state if needed
          const tbody = document.getElementById('jobsTbody');
          if (tbody && !tbody.querySelector('tr')) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No jobs found.</td></tr>';
          }

          // 3) live-update KPI numbers
          const inProgEl = document.getElementById('kpiInProgress');
          const compEl   = document.getElementById('kpiCompleted');

          if (inProgEl) {
            const v = Math.max(0, toInt(inProgEl) - 1);
            inProgEl.textContent = v;
          }
          if (compEl) {
            compEl.textContent = toInt(compEl) + 1;
          }
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
    input.addEventListener('input', function () {
      const q = (this.value || '').toLowerCase().trim();
      tbody.querySelectorAll('tr').forEach(tr => {
        const text = (tr.querySelector('td')?.textContent || '').toLowerCase();
        tr.style.display = q && !text.includes(q) ? 'none' : '';
      });
    });
  })();

  // Row double-click to open
  document.addEventListener('dblclick', (e) => {
    const tr = e.target.closest('tr.js-row-open');
    if (!tr) return;
    const tag = (e.target.tagName || '').toLowerCase();
    if (['a','button','input','select','textarea','label','svg','path','i'].includes(tag)) return;
    const url = tr.dataset.href;
    if (url) window.location.href = url;
  });

(function () {
  // base route to history page (Laravel route)
  const base = "{{ route('furnishing.history') }}";

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
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el);
  });

  const mine = document.getElementById('mineCheck');
  if (mine) mine.addEventListener('change', () => mine.form?.submit());
});
</script>
@endsection