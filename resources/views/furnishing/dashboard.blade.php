@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
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
</style>

<div class="container-fluid py-4 px-4">
  <div class="content-inner" style="max-width:1200px;margin:0 auto;">
    <h1 class="fw-bold" style="font-size:32px;letter-spacing:-.3px;">Dashboard Overview</h1>

    {{-- KPIs --}}
    <div class="kpi-grid">
      <div class="kpi-card">
        <div>
          <div class="kpi-title mb-1">In Progress</div>
          <div class="kpi-value" id="kpiInProgress">{{ $inProgress }}</div>
        </div>
        <div class="kpi-icon"><i class="bi bi-clock"></i></div>
      </div>
      <div class="kpi-card">
        <div>
          <div class="kpi-title mb-1">Completed</div>
          <div class="kpi-value" id="kpiCompleted">{{ $completed }}</div>
        </div>
        <div class="kpi-icon"><i class="bi bi-check2"></i></div>
      </div>
    </div>

    {{-- Furnishing Table --}}
    <section class="card table-card">
      <div class="card-hd d-flex align-items-center justify-content-between">
        <span>Furnishing Jobs</span>
      </div>

      <div class="table-wrapper">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>PRODUCT ID</th>
              <th>CUTTER</th>
              <th>SQ INCH</th>
              <th>DEADLINE</th>
              <th>SUBMISSION DATE</th>
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
              <tr id="job-{{ $j->ProductID }}">
                <td class="fw-semibold">{{ $code }}</td>
                <td>{{ $cutter }}</td>
                <td>{{ number_format($sqIn, 2) }} sq in</td>
                <td>{{ $j->deadline ?: '—' }}</td>
                <td>{{ \Carbon\Carbon::parse($j->submission_date)->format('Y-m-d') }}</td>
                <td>
                  <div class="d-flex align-items-center gap-2">
                    @if (!$accepted || !$isFurnishing)
                      <a class="icon-btn icon-pill" href="{{ $viewUrl }}" title="View">
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
                  </div>
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
</script>
@endsection