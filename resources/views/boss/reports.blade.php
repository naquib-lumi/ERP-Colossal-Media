@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<style>
  :root{
    --bg:#F9FAFB; --card:#FFFFFF; --border:#E5E7EB;
    --text:#101828; --muted:#667085;
    --shadow:0 2px 6px rgba(16,24,40,.05);
    --success:#16A34A; --danger:#DC2626; --primary:#111827;
    --accent:#2E3A8C;
  }
  body{background:var(--bg);}
  .page-wrap{max-width:1240px;margin:0 auto}
  .card.soft{border:0;background:var(--card);box-shadow:var(--shadow);border-radius:16px}
  .form-control,.form-select,.btn{min-height:38px;font-size:14px}

  /* Tabs */
  .nav-tabs .nav-link{border:0;color:#475467;padding:14px 18px}
  .nav-tabs .nav-link.active{color:#111827;border-bottom:3px solid var(--accent);border-radius:0}

  /* Section switch */
  .section{display:none}
  .section.active{display:block}

  /* KPI */
  .kpi .title{font-size:12px;color:var(--muted)}
  .kpi .num{font-weight:700;font-size:22px;color:var(--text)}
  .kpi .delta{font-size:12px}
  .kpi .icon-pill{
    background:#F2F4F7;color:#667085;border-radius:10px;padding:6px 8px;line-height:1
  }

  /* Charts & legends */
  .chart-wrap{height:260px}
  .legend-dot{display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:6px;vertical-align:middle}
  .legend-row{color:#667085;font-size:13px}

  /* Table */
  .table-wrap{border:1px solid var(--border);border-radius:12px;overflow:hidden}
  .table thead th{background:#F8FAFC;color:#475467;font-weight:700}
  .table>:not(caption)>*>*{padding:12px 14px;vertical-align:middle}
  .tag{display:inline-block;background:#F2F4F7;color:#344054;border:1px solid #E5E7EB;border-radius:999px;padding:2px 8px;font-size:12px;margin:2px}
  .kebab{border:0;background:transparent}
  .badge-dot{display:inline-block;width:8px;height:8px;border-radius:999px;margin-right:6px}

  /* Compact buttons */
  .btn-sm-compact{min-height:36px;font-size:13px;padding:0 14px;border-radius:6px}
  .btn-dark-compact{background:#1E2235;color:#fff;border:0}
  .btn-dark-compact:hover{background:#111827}
  .btn-gray-compact{background:#94A3B8;color:#fff;border:0}
  .btn-gray-compact:hover{background:#64748B}

  /* 备用：横向紧凑工具条 */
  .toolbar-compact{gap:8px}
  .toolbar-compact .form-control,
  .toolbar-compact .form-select{min-height:34px;font-size:13px;padding:0 10px;border-radius:6px;}
  .w-140{width:140px}
  .w-150{width:150px}
  .separator{color:#98A2B3;font-size:12px}

  /* Meeting Outcomes：左图表 / 右筛选 */
  .outcomes-grid{
    display:grid;
    grid-template-columns: 1.7fr 1fr;
    gap:16px;
    align-items:start;
  }
  .sidebar{
    border-left:1px solid var(--border);
    padding-left:12px;
  }
  .filter-stack .form-control,
  .filter-stack .form-select{
    min-height:36px; font-size:13px; border-radius:8px;
  }
  .filter-stack .label{
    font-size:12px; color:#667085; margin-bottom:4px;
  }
  /* 小屏改为上下排 */
  @media (max-width: 992px){
    .outcomes-grid{ grid-template-columns: 1fr; }
    .sidebar{ border-left:0; border-top:1px solid var(--border); padding-left:0; padding-top:12px; }
  }

  /* 缩短 Machine Usage 的下拉 */
  .short-select{min-width:140px;max-width:160px}

  /* 让两张图卡片等高 */
  .charts-row .card.soft{height:100%}
</style>

<div class="card soft p-0 mb-3">
  <!-- Tabs -->
  <ul class="nav nav-tabs px-3 pt-3" id="reportTabs" style="border-bottom:1px solid var(--border)">
    <li class="nav-item"><a class="nav-link active" data-target="#salesSec" href="javascript:void(0)">Sales Report</a></li>
    <li class="nav-item"><a class="nav-link" data-target="#orderSec" href="javascript:void(0)">Order Report</a></li>
    <li class="nav-item"><a class="nav-link" data-target="#machineSec" href="javascript:void(0)">Machine Usage</a></li>
  </ul>

  <!-- ===================== Sales Report ===================== -->
  <div class="section active" id="salesSec">
    <!-- Filters -->
    <div class="p-3 border-bottom">
      <form id="salesFilterForm" method="GET" action="{{ url()->current() }}#salesSec">
        @php
          $sf = $salesFilters ?? [
            'salesperson' => 'all',
            'period'      => 'monthly',
            'start_date'  => now()->startOfMonth()->toDateString(),
            'end_date'    => now()->endOfMonth()->toDateString(),
          ];
        @endphp
        <div class="row g-3 align-items-end">

          <!-- Salesperson -->
          <div class="col-md-3">
            <label class="form-label small">Select Salesperson</label>
            <select class="form-select" id="salesperson" name="salesperson">
              <option value="all" {{ ($sf['salesperson'] ?? 'all') === 'all' ? 'selected' : '' }}>All Salespersons</option>
              @foreach($salespeople as $sp)
                <option value="{{ $sp->id }}" {{ (string)($sf['salesperson'] ?? 'all') === (string)$sp->id ? 'selected' : '' }}>
                  {{ $sp->name }}
                </option>
              @endforeach
            </select>
          </div>

          <!-- Period segmented buttons (writes to hidden input) -->
          <!-- <div class="col-md-3">
            <label class="form-label small">Time Period</label>
            <div class="btn-group w-100" role="group" id="periodGroup">
              @php $pSel = $sf['period'] ?? 'monthly'; @endphp
              <button type="button" class="btn btn-outline-dark btn-sm {{ $pSel==='yearly'?'active':'' }}" data-value="yearly">Yearly</button>
              <button type="button" class="btn btn-outline-dark btn-sm {{ $pSel==='quarterly'?'active':'' }}" data-value="quarterly">Quarterly</button>
              <button type="button" class="btn btn-outline-dark btn-sm {{ $pSel==='monthly'?'active':'' }}" data-value="monthly">Monthly</button>
            </div>
            <input type="hidden" name="period" id="periodInput" value="{{ $pSel }}">
          </div> -->

          <!-- Date Range -->
          <div class="col-md-4">
            <label class="form-label small">Date Range</label>
            <div class="d-flex align-items-center gap-2">
              <input type="date" class="form-control" name="start_date" value="{{ $sf['start_date'] ?? '' }}">
              <span class="text-muted small">to</span>
              <input type="date" class="form-control" name="end_date" value="{{ $sf['end_date'] ?? '' }}">
            </div>
          </div>

          <!-- Actions -->
          <div class="col-md-4">
            <div class="d-flex flex-wrap gap-2">
              <button type="submit" name="action" value="filter" class="btn btn-dark">
                <i class="bi bi-funnel"></i> Filter
              </button>

              <a id="salesResetBtn" href="{{ url()->current() }}#salesSec" class="btn btn-secondary">Reset</a>

              {{-- carry current filters when exporting --}}
              <!-- <a href="#" class="btn btn-outline-dark">
                <i class="bi bi-download"></i> Export
              </a> -->
            </div>
          </div>

        </div>
      </form>
    </div>

    <!-- KPI -->
    <div class="p-3">
      <div class="row g-3">
        <div class="col-md-3">
          <div class="card soft kpi p-3">
            <div class="d-flex justify-content-between align-items-start">
              <p class="title mb-1">Total Leads Added</p>
              <span class="icon-pill"><i class="bi bi-magnet"></i></span>
            </div>
            <div class="num">{{ number_format($kpis['total_leads']) }}</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card soft kpi p-3">
            <div class="d-flex justify-content-between align-items-start">
              <p class="title mb-1">Total Meetings Held</p>
              <span class="icon-pill"><i class="bi bi-calendar3"></i></span>
            </div>
            <div class="num">{{ number_format($kpis['total_meetings']) }}</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card soft kpi p-3">
            <div class="d-flex justify-content-between align-items-start">
              <p class="title mb-1">Scheduled Meetings</p>
              <span class="icon-pill"><i class="bi bi-check2-square"></i></span>
            </div>
            <div class="num">{{ number_format($kpis['accepted_meets']) }}</div>
            <span class="text-muted small" style="color: #10b981;">
              {{ $kpis['total_meetings'] ? number_format($kpis['accepted_meets'] / max($kpis['total_meetings'],1) * 100, 1) : 0 }}% acceptance rate
            </span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card soft kpi p-3">
            <div class="d-flex justify-content-between align-items-start">
              <p class="title mb-1">Canceled Meeting</p>
              <span class="icon-pill"><i class="bi bi-x-square"></i></span>
            </div>
            <div class="num">{{ number_format($kpis['rejected_meets']) }}</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts （左右并排） -->
    <div class="p-3">
      <div class="row g-3 align-items-stretch charts-row">
        <!-- 左：Monthly Performance -->
        <div class="col-lg-7">
          <div class="card soft p-3 h-100 d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center">
              <h6 class="fw-bold mb-0">Monthly Performance</h6>
            </div>

            <div class="d-flex flex-wrap gap-3 legend-row mb-2 mt-2">
              <span><i class="legend-dot" style="background:#60a5fa"></i>Leads Added</span>
              <span><i class="legend-dot" style="background:#22c55e"></i>Accepted</span>
              <span><i class="legend-dot" style="background:#ef4444"></i>Rejected</span>
              <span><i class="legend-dot" style="background:#06b6d4"></i>50/50</span>
              <span><i class="legend-dot" style="background:#a78bfa"></i>Low Chance</span>
            </div>

            <div class="chart-wrap flex-grow-1"><canvas id="barMonthly"></canvas></div>
          </div>
        </div><!-- ✅ 必须的闭合，防止右侧被包进去 -->

        <!-- 右：Meeting Outcomes -->
        <div class="col-lg-5">
          <div class="card soft p-3 h-100 d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="fw-bold mb-0">Meeting Outcomes</h6>
            </div>

            <div class="outcomes-grid">
              <!-- 左：饼图 -->
              <div>
                <div class="chart-wrap"><canvas id="pieOutcome"></canvas></div>
                
              </div>

              <!-- 右：竖排筛选 -->
              <aside class="sidebar">
                <div class="mt-2 small">
                  <span class="legend-dot" style="background:#22c55e"></span>Accepted
                  <span class="legend-dot" style="background:#ef4444;margin-left:14px"></span>Rejected
                </div>
              </aside>
            </div>
          </div>
        </div>
      </div>
    </div> <!-- /p-3 -->
  </div><!-- /salesSec -->

  <!-- ===================== Order Report ===================== -->
  <div class="section" id="orderSec">
    <!-- Filters -->
    <div class="p-3 border-bottom">
      <form id="orderFilterForm" method="GET" action="{{ url()->current() }}#orderSec">
        @php
          $of = $orderFilters ?? [
            'ord_artist' => 'all',
            'ord_start'  => now()->startOfMonth()->toDateString(),
            'ord_end'    => now()->endOfMonth()->toDateString(),
          ];
        @endphp

        <div class="row g-3 align-items-end">
          <!-- Artist -->
          <div class="col-md-3">
            <label class="form-label small">Select Artist</label>
            <select name="ord_artist" class="form-select">
              <option value="all" {{ ($of['ord_artist'] ?? 'all') === 'all' ? 'selected' : '' }}>
                All Artists
              </option>
              @foreach($artists as $a)
                <option value="{{ $a->id }}" {{ (string)($of['ord_artist'] ?? 'all') === (string)$a->id ? 'selected' : '' }}>
                  {{ $a->name }}
                </option>
              @endforeach
            </select>
          </div>

          <!-- Date Range -->
          <div class="col-md-5">
            <label class="form-label small">Date Range</label>
            <div class="d-flex align-items-center gap-2">
              <input type="date" name="ord_start" class="form-control" value="{{ $of['ord_start'] ?? '' }}">
              <span class="text-muted small">to</span>
              <input type="date" name="ord_end" class="form-control" value="{{ $of['ord_end'] ?? '' }}">
            </div>
          </div>

          <!-- Actions (same row) -->
          <div class="col-md-4">
            <div class="d-flex flex-wrap gap-2 justify-content-md-end">
              <button type="submit" class="btn btn-dark">
                <i class="bi bi-funnel"></i> Filter
              </button>

              <a id="orderResetBtn" href="{{ url()->current() }}#orderSec" class="btn btn-secondary">Reset</a>

              {{-- Carry current filters to export (wire to your route) --}}
              <!-- <a href="#"
                class="btn btn-outline-dark">
                <i class="bi bi-download"></i> Export
              </a> -->
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- Chart -->
    <div class="p-3">
      <div class="card soft p-3">
        <h6 class="fw-bold mb-2">Job Order Fulfillment</h6>
        <div class="chart-wrap"><canvas id="orderFulfill"></canvas></div>
        <div class="small mt-2">
          <span class="badge-dot" style="background:#22c55e"></span>New Order
          <span class="badge-dot" style="background:#f59e0b;margin-left:14px"></span>In Progress
          <span class="badge-dot" style="background:#06b6d4;margin-left:14px"></span>Completed
          <span class="badge-dot" style="background:#ef4444;margin-left:14px"></span>Rejected
        </div>
      </div>
    </div>
  </div><!-- /orderSec -->

  <!-- ===================== Machine Usage ===================== -->
  @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mx-3 mt-3" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif
  <div class="section" id="machineSec">
    <!-- Tools -->
    <div class="p-3 border-bottom">
      
      <form method="GET" id="machineFilterForm" action="{{ url()->current() }}#machineSec">
        @php $mf = $machineFilters ?? ['machine_q'=>'','machine_type'=>'','machine_range'=>'last30']; @endphp
        <div class="p-3 d-flex justify-content-end gap-2">
          {{-- Export (route optional; keep your current one if different) --}}
          <!-- <a class="btn btn-dark"
            href="#machineSec">
            <i class="bi bi-download me-1"></i> Export
          </a> -->

          {{-- Add Machine Type (frontend modal) --}}
          <button type="button" class="btn btn-secondary"
                  data-bs-toggle="modal" data-bs-target="#addMachineModal">
            Add Machine Type
          </button>
        </div>
        <div class="row g-2 align-items-end">
          <div class="col-lg-5 col-md-6">
            <input type="text" name="machine_q" class="form-control"
                  placeholder="Search machine..." value="{{ $mf['machine_q'] ?? '' }}">
          </div>

          <div class="col-lg-2 col-md-3">
            <select name="machine_type" class="form-select">
              <option value="" {{ ($mf['machine_type'] ?? '')==='' ? 'selected' : '' }}>All Machine Types</option>
              <option value="Printer" {{ ($mf['machine_type'] ?? '')==='Printer' ? 'selected' : '' }}>Printer</option>
              <option value="Cutter"  {{ ($mf['machine_type'] ?? '')==='Cutter'  ? 'selected' : '' }}>Cutter</option>
            </select>
          </div>

          <div class="col-lg-2 col-md-3">
            @php $mr = $mf['machine_range'] ?? 'last30'; @endphp
            <select name="machine_range" class="form-select">
              <option value="last30" {{ $mr==='last30' ? 'selected' : '' }}>Last 30 Days</option>
              <option value="last90" {{ $mr==='last90' ? 'selected' : '' }}>Last 90 Days</option>
              <option value="year"   {{ $mr==='year'   ? 'selected' : '' }}>This Year</option>
            </select>
          </div>

          <div class="col-lg-3 col-md-12 d-flex justify-content-lg-end gap-2">
            <button class="btn btn-dark" type="submit">
              <i class="bi bi-funnel"></i> Filter
            </button>
            <a class="btn btn-secondary" href="{{ url()->current() }}#machineSec" id="machineResetBtn">
              Reset
            </a>
            
          </div>
        </div>
      </form>
    </div>

    <!-- Table -->
    <div class="p-3">
      <div class="table-wrap">
        <table class="table mb-0" id="machineTable">
          <thead>
            <tr>
              <th>Machine Name</th>
              <th>Machine Type</th>
              <th>Used by Items</th>
              <th>Total Quantity</th>
              <th class="text-end">Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($machineUsage as $m)
              <tr>
                <td>{{ $m->machine_name }}</td>
                <td>{{ $m->machine_type }}</td>
                <td>{{ number_format($m->used_items) }} items</td>
                <td>{{ number_format($m->total_qty) }}</td>
                <td class="text-end">
                  <button class="kebab"><i class="bi bi-three-dots-vertical"></i></button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="5" class="text-center text-muted">No machine usage found for current filters.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
  </div>

  <!-- Pagination -->
  <div class="d-flex justify-content-between align-items-center mt-3">
    <small class="text-muted">
      Showing {{ $machineUsage->firstItem() ?? 0 }} to {{ $machineUsage->lastItem() ?? 0 }}
      of {{ $machineUsage->total() }} results
    </small>
    {{ $machineUsage->onEachSide(1)->withQueryString()->links('pagination::bootstrap-5') }}
  </div>
      </div>
    </div><!-- /machineSec -->
  </div>

<!-- ===== Add Machine Type Modal ===== -->
<div class="modal fade" id="addMachineModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" style="border-radius:16px"
          method="POST" action="{{ route('boss.machines.store') }}#machineSec">
      @csrf
      <div class="modal-header">
        <h6 class="modal-title"><i class="bi bi-plus-lg me-2"></i>Add Machine</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        @if ($errors->any())
          <div class="alert alert-danger small">
            {{ $errors->first() }}
          </div>
        @endif

        <div class="mb-3">
          <label class="form-label small">Machine Name</label>
          <input type="text" name="machine_name" class="form-control"
                 value="{{ old('machine_name') }}" placeholder="e.g. Handtop Hybrid">
        </div>

        <div>
          <label class="form-label small">Machine Type</label>
          <select name="machine_type" class="form-select">
            <option value="" disabled {{ old('machine_type') ? '' : 'selected' }}>Select type</option>
            <option value="printer"    {{ old('machine_type')==='printer'    ? 'selected' : '' }}>Printer</option>
            <option value="cutter"     {{ old('machine_type')==='cutter'     ? 'selected' : '' }}>Cutter</option>
            <option value="lamination" {{ old('machine_type')==='lamination' ? 'selected' : '' }}>Lamination</option>
          </select>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-light" type="button" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-dark" type="submit">
          <i class="bi bi-save me-1"></i> Save
        </button>
      </div>
    </form>
  </div>
</div>

<script>
// ===== Tabs =====
const links = document.querySelectorAll('#reportTabs .nav-link');
const sections = document.querySelectorAll('.section');

function activateTab(target){
  links.forEach(l=>l.classList.remove('active'));
  sections.forEach(s=>s.classList.remove('active'));
  const btn = document.querySelector(`#reportTabs .nav-link[data-target="${target}"]`);
  const sec = document.querySelector(target);
  if(btn && sec){
    btn.classList.add('active');
    sec.classList.add('active');
  }
}

// click -> switch tab and update hash
links.forEach(a=>{
  a.addEventListener('click', ()=>{
    const target = a.dataset.target;
    activateTab(target);
    // persist tab in URL without reloading
    if(history.replaceState){
      history.replaceState(null, '', `${location.pathname}${location.search}${target}`);
    }else{
      location.hash = target; // fallback
    }
  });
});

// on load -> open tab from hash (default sales)
window.addEventListener('DOMContentLoaded', ()=>{
  const target = location.hash && document.querySelector(location.hash) ? location.hash : '#salesSec';
  activateTab(target);
});

// ===== Monthly Performance（单月构成） =====
const mpCtx = document.getElementById('barMonthly');
const mpLabels = ['Leads Added','Accepted','Rejected','50/50','Low Chance'];
const mpColors = ['#60a5fa','#22c55e','#ef4444','#06b6d4','#a78bfa'];

// Values from server (per your rules)
const monthlyServerValues = {!! json_encode([
  $monthlyPerformance['bars']['leads_added'],
  $monthlyPerformance['bars']['accepted'],
  $monthlyPerformance['bars']['rejected'],
  $monthlyPerformance['bars']['fifty_fifty'],
  $monthlyPerformance['bars']['low_chance'],
]) !!};


if (mpCtx) {
  const barMonthly = new Chart(mpCtx, {
    type:'bar',
    data:{ labels: mpLabels, datasets:[{ label:'This Month', data: monthlyServerValues, backgroundColor: mpColors, borderRadius:6, borderSkipped:false }]},
    options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false} }, scales:{ x:{grid:{display:false}}, y:{beginAtZero:true, ticks:{stepSize:20}} } }
  });

  // Keep the same visual control; on change, reload with new month (no UI change)

}

// ===== Meeting Outcomes 饼图 =====
const pieCtx = document.getElementById('pieOutcome');
if (pieCtx) {
  const accepted = {{ $meetingOutcomes['accepted'] }};
  const rejected = {{ $meetingOutcomes['rejected'] }};

  // If both are zero, show a message instead of an empty chart
  if (accepted === 0 && rejected === 0) {
    pieCtx.parentElement.innerHTML =
      `<div class="d-flex justify-content-center align-items-center text-muted" style="height:220px; font-size:12px;">
         No meeting data found for selected filters
       </div>`;
  } else {
    new Chart(pieCtx, {
      type: 'pie',
      data: {
        labels: ['Accepted','Rejected'],
        datasets: [{
          data: [accepted, rejected],
          backgroundColor: ['#22c55e','#ef4444'],
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } }
      }
    });
  }
}

// ===== Job Order Fulfillment =====
const fulfillCtx = document.getElementById('orderFulfill');
if (fulfillCtx) {
  const fulfillData = {!! json_encode([
    $jobFulfillment['total'],
    $jobFulfillment['in_progress'],
    $jobFulfillment['completed'],
    $jobFulfillment['rejected'],
  ]) !!};

  new Chart(fulfillCtx, {
    type: 'bar',
    data: {
      labels: ['Total Orders','In Progress','Completed','Rejected'],
      datasets: [{
        data: fulfillData,
        backgroundColor: ['#22c55e','#f59e0b','#06b6d4','#ef4444'],
        borderRadius: 6,
        borderSkipped: false
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { stepSize: 5 } }, x: { grid: { display: false } } }
    }
  });
}

(function(){
  const form = document.getElementById('outcomeFilter');
  if (!form) return;

  // autosubmit on any change
  form.addEventListener('change', function(){
    // if period changed to a preset range, set dates then submit
    if (document.activeElement && document.activeElement.name === 'period') {
      const period = document.activeElement.value;
      const sd = form.querySelector('input[name="start_date"]');
      const ed = form.querySelector('input[name="end_date"]');
      const today = new Date();

      const pad = n => String(n).padStart(2,'0');
      const iso = d => `${d.getFullYear()}-${pad(d.getMonth()+1)}-${pad(d.getDate())}`;

      if (period === 'monthly') {
        const s = new Date(today.getFullYear(), today.getMonth(), 1);
        const e = new Date(today.getFullYear(), today.getMonth()+1, 0);
        sd.value = iso(s); ed.value = iso(e);
      } else if (period === 'quarterly') {
        const q = Math.floor(today.getMonth()/3);                 // 0..3
        const s = new Date(today.getFullYear(), q*3, 1);
        const e = new Date(today.getFullYear(), q*3 + 3, 0);
        sd.value = iso(s); ed.value = iso(e);
      } else if (period === 'yearly') {
        const s = new Date(today.getFullYear(), 0, 1);
        const e = new Date(today.getFullYear(), 11, 31);
        sd.value = iso(s); ed.value = iso(e);
      }
    }
    form.submit();
  });

  const f = document.getElementById('jobFilterForm');
  if (!f) return;

  // submit on change of selects/date
  ['artist','order_date','order_status'].forEach(n => {
    const el = f.querySelector(`[name="${n}"]`);
    if (el) el.addEventListener('change', () => f.submit());
  });

  // submit on Enter in search
  const q = f.querySelector('[name="order_search"]');
  if (q) q.addEventListener('keydown', e => {
    if (e.key === 'Enter') { e.preventDefault(); f.submit(); }
  });

  const machinefilter = document.getElementById('machineFilterForm');
  if (!machinefilter) return;
  ['machine_type','machine_range'].forEach(n=>{
    const el = machinefilter.querySelector(`[name="${n}"]`);
    if (el) el.addEventListener('change', () => machinefilter.submit());
  });
  const result = machinefilter.querySelector('[name="machine_q"]');
  if (result) result.addEventListener('keydown', e => { if (e.key==='Enter'){ e.preventDefault(); machinefilter.submit(); }});
})();

// sales report filter
(function () {
  const form = document.getElementById('salesFilterForm');
  if (!form) return;

  const group = document.getElementById('periodGroup');
  const hidden = document.getElementById('periodInput');

  if (group && hidden) {
    group.querySelectorAll('button[data-value]').forEach(btn => {
      btn.addEventListener('click', () => {
        group.querySelectorAll('button').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        hidden.value = btn.dataset.value;
      });
    });
  }

  // Reset just navigates to the page without query string
  const reset = document.getElementById('salesResetBtn');
  if (reset) {
    reset.addEventListener('click', (e) => {
      e.preventDefault();
      window.location = form.getAttribute('action');
    });
  }
})();

document.addEventListener('DOMContentLoaded', () => {
  const addBtn = document.querySelector('#addMachineModal .btn-dark-compact');
  if (addBtn) {
    addBtn.addEventListener('click', () => {
      const name  = document.querySelector('#addMachineModal input[type="text"]').value.trim();
      const type  = document.querySelector('#addMachineModal select').value;
      if (!name || !type) { alert('Please enter machine name and type.'); return; }
      // Frontend only: close modal and show a toast/alert
      const modal = bootstrap.Modal.getInstance(document.getElementById('addMachineModal'));
      modal.hide();
      // Optional: add your toast here
    });
  }
});
</script>
@endsection
