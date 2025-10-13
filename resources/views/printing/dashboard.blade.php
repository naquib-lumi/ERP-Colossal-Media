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

  .col-actions {
    width: 210px
  }

  .empty {
    padding: 28px;
    text-align: center;
    color: #667085
  }

  /* Action buttons (rounded “pill” icons) */
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

  /* ===== Modal (confirmation) ===== */
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

  /* Pill-style pager */
  .pill-pager .page-link {
    border-radius: 999px;
    border: 1px solid #E6E8F0;
    background: #F6F7FB;
    color: #667085;
    padding: .45rem .9rem;
    line-height: 1;
  }

  .pill-pager .page-item+.page-item {
    margin-left: .5rem
  }

  .pill-pager .page-item.active .page-link {
    background: #635bff;
    border-color: #635bff;
    color: #fff
  }

  .pill-pager .page-item.disabled .page-link {
    opacity: .6;
    cursor: not-allowed;
    background: #F6F7FB
  }

  @media (max-width: 992px) {
    .kpi-grid {
      grid-template-columns: 1fr
    }
  }

  .filter-card {
    border: 1px solid #ECEFF3;
    border-radius: 14px;
  }

  .filter-card .form-label {
    font-size: .8rem;
    color: #6b7280;
    margin-bottom: .25rem;
  }

  .filter-card .input-group-text {
    background: #f8fafc;
    border-color: #e5e7eb;
  }

  .filter-card .form-control {
    border-color: #e5e7eb;
  }

  .filter-card .has-icon .form-control {
    border-left: 0;
  }

  .filter-card .input-group-text i {
    opacity: .75;
  }

  @media (min-width: 1200px) {
    .filter-card form .col-lg-2 {
      min-width: 220px;
    }

    .filter-card form .col-lg-3 {
      min-width: 260px;
    }
  }
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
          <div class="kpi-title mb-1" style="color: #635bff;">In Progress</div>
          <div class="kpi-value" data-kpi="inprogress" style="color: #635bff;">{{ $inProgress }}</div>
        </div>
        <div class="kpi-icon"><i class="bi bi-clock"></i></div>
      </div>
      <div class="kpi-card">
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

      {{-- Filter toolbar --}}
      <div class="card shadow-soft mb-3 filter-card">
        <div class="card-body">
          <div class="d-flex align-items-center mb-3">
            <h6 class="mb-0 fw-semibold">Printing Jobs</h6>
            <span class="text-muted small ms-2">Filter &amp; search</span>
          </div>

          <form class="row g-3 align-items-end" method="GET" action="{{ route('printing.dashboard') }}">
            {{-- Printer --}}
            <div class="col-12 col-md-4 col-lg-2">
              <label class="form-label">Printer</label>
              <div class="input-group input-group-sm has-icon">
                <span class="input-group-text"><i class="bi bi-printer"></i></span>
                <input type="text"
                  name="printer"
                  value="{{ request('printer') }}"
                  class="form-control"
                  placeholder="e.g. Flatbed A2 DTF">
              </div>
            </div>

            {{-- Sq Inch (min) --}}
            <div class="col-6 col-md-4 col-lg-2">
              <label class="form-label">Sq Inch (Min)</label>
              <div class="input-group input-group-sm has-icon">
                <span class="input-group-text"><i class="bi bi-arrow-down-left"></i></span>
                <input type="number"
                  step="1"
                  min="0"
                  name="sq_min"
                  value="{{ request('sq_min') }}"
                  class="form-control"
                  placeholder="0">
              </div>
            </div>

            {{-- Sq Inch (max) --}}
            <div class="col-6 col-md-4 col-lg-2">
              <label class="form-label">Sq Inch (Max)</label>
              <div class="input-group input-group-sm has-icon">
                <span class="input-group-text"><i class="bi bi-arrow-up-right"></i></span>
                <input type="number"
                  step="1"
                  min="0"
                  name="sq_max"
                  value="{{ request('sq_max') }}"
                  class="form-control"
                  placeholder="Any">
              </div>
            </div>

            {{-- Deadline (from) --}}
            <div class="col-6 col-md-4 col-lg-2">
              <label class="form-label">Deadline From</label>
              <div class="input-group input-group-sm has-icon">
                <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                <input type="date"
                  name="deadline_from"
                  value="{{ request('deadline_from') }}"
                  class="form-control">
              </div>
            </div>

            {{-- Deadline (to) --}}
            <div class="col-6 col-md-4 col-lg-2">
              <label class="form-label">Deadline To</label>
              <div class="input-group input-group-sm has-icon">
                <span class="input-group-text"><i class="bi bi-calendar-check"></i></span>
                <input type="date"
                  name="deadline_to"
                  value="{{ request('deadline_to') }}"
                  class="form-control">
              </div>
            </div>

            {{-- Submitted From --}}
            <div class="col-6 col-md-4 col-lg-2">
              <label class="form-label">Submitted From</label>
              <div class="input-group input-group-sm has-icon">
                <span class="input-group-text"><i class="bi bi-upload"></i></span>
                <input type="date"
                  name="submitted_from"
                  value="{{ request('submitted_from') }}"
                  class="form-control">
              </div>
            </div>

            {{-- Submitted To --}}
            <div class="col-6 col-md-4 col-lg-2">
              <label class="form-label">Submitted To</label>
              <div class="input-group input-group-sm has-icon">
                <span class="input-group-text"><i class="bi bi-check2-square"></i></span>
                <input type="date"
                  name="submitted_to"
                  value="{{ request('submitted_to') }}"
                  class="form-control">
              </div>
            </div>

            {{-- Actions --}}
            <div class="col-12 col-lg-4 ms-auto d-flex gap-2 justify-content-end">
              <a href="{{ route('printing.dashboard') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Reset
              </a>
              <button class="btn btn-dark">
                <i class="bi bi-funnel me-1"></i> Apply Filter
              </button>
            </div>
          </form>
        </div>
      </div>


      <div class="table-wrapper">
        <table class="table align-middle mb-0">
          <thead>
            <tr>
              <th>PRODUCT ID</th>
              <th>PRINTER</th>
              <th>SQ INCH</th>
              <th>DEADLINE</th>
              <th>SUBMISSION DATE</th>
              <th class="col-actions">ACTIONS</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($jobs as $row)
            @php
            $deadline = $row->deadline ? \Carbon\Carbon::parse($row->deadline)->format('Y-m-d') : '—';
            $submitted = $row->submission_date ? \Carbon\Carbon::parse($row->submission_date)->format('Y-m-d') : '—';
            $sq = is_numeric($row->sq_inch ?? null) ? number_format((float)$row->sq_inch, 0) . ' sq in' : '0 sq in';
            $code = $row->product_code ?? ('ORD'.($row->order_id ?? $row->ProductID).'-P'.$row->ProductID);

            $isPrinting = strtolower((string)($row->taskType ?? '')) === 'printing';
            $accepted = (int)($row->accepted ?? 0) === 1;
            @endphp
            <tr id="job-{{ $row->ProductID }}">
              <td>{{ $row->product_code }}</td>
              <td>{{ ($row->printer ?? '-') === '-' ? '—' : $row->printer }}</td>
              <td>{{ is_numeric($row->sq_inch ?? null) ? number_format((float)$row->sq_inch, 0).' sq in' : '0 sq in' }}</td>
              <td>{{ $row->deadline ? \Carbon\Carbon::parse($row->deadline)->format('Y-m-d') : '—' }}</td>
              <td>{{ $row->submission_date ? \Carbon\Carbon::parse($row->submission_date)->format('Y-m-d') : '—' }}</td>

              <td class="text-nowrap">
                @if (!$accepted || !$isPrinting)
                <a href="{{ route('printing.orders.show', $row->ProductID) }}" class="icon-pill" title="View">
                  <i class="bi bi-eye"></i>
                </a>
                @endif
                {{-- Optional: printing-only extras (unchanged from before) --}}
                @if ($isPrinting && $accepted)
                <button class="icon-pill js-mark" data-id="{{ $row->ProductID }}" title="Mark Completed">
                  <i class="bi bi-check2"></i>
                </button>
                <a class="icon-pill" title="Report" href="{{ route('printing.report', ['productId' => $row->ProductID]) }}">
                  <i class="bi bi-exclamation-triangle"></i>
                </a>
                <a href="{{ route('printing.orders.show', $row->ProductID) }}" class="icon-pill" title="Edit">
                  <i class="bi bi-pencil"></i>
                </a>
                @endif
              </td>
            </tr>
            @empty
            <tr>
              <td colspan="6" class="empty">No printing jobs found.</td>
            </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination (pill style) --}}
      @if ($jobs instanceof \Illuminate\Pagination\LengthAwarePaginator)
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
      @endif
    </section>
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

    // Open modal
    document.addEventListener('click', (e) => {
      const btn = e.target.closest('.js-mark');
      if (!btn) return;
      currentId = btn.dataset.id;
      mask.classList.add('show');
      mask.setAttribute('aria-hidden', 'false');
    });

    // Close modal
    mask.addEventListener('click', (e) => {
      if (e.target === mask || e.target.hasAttribute('data-close')) {
        mask.classList.remove('show');
        mask.setAttribute('aria-hidden', 'true');
      }
    });

    // Confirm → PATCH to mark complete, then update UI
    yesBtn.addEventListener('click', async () => {
      if (!currentId) return;
      yesBtn.disabled = true;

      try {
        const url = "{{ route('printing.jobs.complete', ['productId' => '__ID__']) }}".replace('__ID__', currentId);
        const res = await fetch(url, {
          method: 'PATCH',
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json'
          }
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
</script>
@endsection