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
.table td:first-child{font-weight:700;color:#111827}
.col-actions{width:210px}
.empty{padding:28px;text-align:center;color:#667085}

/* Action buttons – 圆角小图标 */
.icon-pill{
  width:34px;height:34px;border-radius:10px;
  display:inline-flex;align-items:center;justify-content:center;
  border:1px solid #E3E8EF;background:#fff;color:#475467;
}
.icon-pill + .icon-pill{margin-left:8px}
.icon-pill:hover{background:#F4F6FA;color:#111827;border-color:#D7DFE7}

/* ===== Modal (confirmation) ===== */
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
.pill-pager .page-link{
  border-radius:999px;border:1px solid #E6E8F0;background:#F6F7FB;
  color:#667085;padding:.45rem .9rem;line-height:1;
}
.pill-pager .page-item + .page-item{margin-left:.5rem}
.pill-pager .page-item.active .page-link{background:#635bff;border-color:#635bff;color:#fff}
.pill-pager .page-item.disabled .page-link{opacity:.6;cursor:not-allowed;background:#F6F7FB}

@media (max-width: 992px){ .kpi-grid{grid-template-columns:1fr} }
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
          <div class="kpi-title mb-1">In Progress</div>
          <div class="kpi-value" data-kpi="inprogress">{{ $inProgress }}</div>
        </div>
        <div class="kpi-icon"><i class="bi bi-clock"></i></div>
      </div>
      <div class="kpi-card">
        <div>
          <div class="kpi-title mb-1">Completed</div>
          <div class="kpi-value" data-kpi="completed">{{ $completed }}</div>
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
          <tbody>
          @forelse ($jobs as $row)
            @php
              $deadline  = $row->deadline ? \Carbon\Carbon::parse($row->deadline)->format('Y-m-d') : '—';
              $submitted = $row->submission_date ? \Carbon\Carbon::parse($row->submission_date)->format('Y-m-d') : '—';
              $sq = is_numeric($row->sq_inch ?? null) ? number_format((float)$row->sq_inch, 0) . ' sq in' : '0 sq in';
            @endphp
            <tr id="job-{{ $row->ProductID }}">
              <td>{{ $row->product_code }}</td>
              <td>{{ $row->cutter ?: '—' }}</td>
              <td>{{ $sq }}</td>
              <td>{{ $deadline }}</td>
              <td>{{ $submitted }}</td>
              <td class="text-nowrap">
                {{-- 仅 View / Mark / Edit （无 Report） --}}
                <a class="icon-pill" title="View"><i class="bi bi-eye"></i></a>

                <button class="icon-pill js-mark" data-id="{{ $row->ProductID }}" title="Mark Completed">
                  <i class="bi bi-check2"></i>
                </button>

                <a class="icon-pill" title="Edit"><i class="bi bi-pencil"></i></a>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="empty">No furnishing jobs found.</td></tr>
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
                $last    = max(1, $jobs->lastPage());
                $current = $jobs->currentPage();
                $from    = max(1, $current - 1);
                $to      = min($last, $current + 1);
              @endphp

              @if ($from > 1)
                <li class="page-item"><a class="page-link" href="{{ $jobs->url(1) }}">1</a></li>
                @if ($from > 2)
                  <li class="page-item disabled"><span class="page-link">…</span></li>
                @endif
              @endif

              @for ($p = $from; $p <= $to; $p++)
                @if ($p == $current)
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
<div id="furnishConfirm" class="cx-mask" aria-hidden="true">
  <div class="cx-modal" role="dialog" aria-modal="true" aria-labelledby="cxTitle">
    <div class="cx-header">
      <div id="cxTitle" class="cx-title">Confirmation</div>
      <button type="button" class="cx-close" data-close><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="cx-body">
      <div class="cx-qicon"><i class="bi bi-question-lg"></i></div>
      <div>
        <div class="cx-q">Do you done the furnishing?</div>
        <div class="cx-help">This action will mark the job as completed.</div>
      </div>
    </div>
    <div class="cx-footer">
      <button type="button" class="cx-btn cx-btn-ghost" data-close>No</button>
      <button type="button" class="cx-btn cx-btn-dark" id="furnishConfirmYes">Yes</button>
    </div>
  </div>
</div>

<script>
(() => {
  const mask   = document.getElementById('furnishConfirm');
  const yesBtn = document.getElementById('furnishConfirmYes');
  let currentId = null;
  const csrf = '{{ csrf_token() }}';

  const urlPatch = "{{ route('furnishing.jobs.complete', ['productId' => '__ID__']) }}";
  const urlPost  = "{{ route('furnishing.jobs.complete.post', ['productId' => '__ID__']) }}";

  // open modal
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.js-mark');
    if (!btn) return;
    currentId = btn.dataset.id;
    mask.classList.add('show');
    mask.setAttribute('aria-hidden','false');
  });

  // close modal
  mask.addEventListener('click', (e) => {
    if (e.target === mask || e.target.hasAttribute('data-close')) {
      mask.classList.remove('show');
      mask.setAttribute('aria-hidden','true');
    }
  });

  // confirm -> PATCH, fallback POST
  async function complete(id){
    // try PATCH first
    try{
      const res = await fetch(urlPatch.replace('__ID__', id), {
        method: 'PATCH',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
      });
      if(!res.ok) throw new Error('patch failed');
      return await res.json();
    }catch(_){
      // fallback POST
      const res2 = await fetch(urlPost.replace('__ID__', id), {
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
      });
      return await res2.json();
    }
  }

  yesBtn.addEventListener('click', async () => {
    if (!currentId) return;
    yesBtn.disabled = true;

    try {
      const data = await complete(currentId);
      if (data && data.ok){
        const row = document.getElementById('job-'+currentId) ||
                    document.querySelector(`button.js-mark[data-id="${currentId}"]`)?.closest('tr');
        if (row) row.remove();

        // update KPI
        const bump = (sel, d) => {
          const el = document.querySelector(sel);
          if (!el) return;
          const n  = parseInt((el.textContent || '').trim(), 10);
          if (!isNaN(n)) el.textContent = Math.max(0, n + d);
        };
        bump('[data-kpi="inprogress"]', -1);
        bump('[data-kpi="completed"]', +1);
      }
    } catch (err) {
      alert(err?.message || 'Error');
    } finally {
      yesBtn.disabled = false;
      mask.classList.remove('show');
      mask.setAttribute('aria-hidden','true');
      currentId = null;
    }
  });
})();
</script>
@endsection
