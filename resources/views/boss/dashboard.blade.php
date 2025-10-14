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
  --blue:#60a5fa; --green:#22c55e; --red:#ef4444; --cyan:#06b6d4; --violet:#a78bfa; --amber:#f59e0b; --teal:#10b981; --pink:#f472b6;
}
body{background:var(--bg);}
.page-wrap{max-width:1240px;margin:0 auto}
.card.soft{border:0;background:var(--card);box-shadow:var(--shadow);border-radius:16px}
.form-control,.form-select,.btn{min-height:38px;font-size:14px}

/* Tabs（如果后续要复用） */
.nav-tabs .nav-link{border:0;color:#475467;padding:14px 18px}
.nav-tabs .nav-link.active{color:#111827;border-bottom:3px solid var(--accent);border-radius:0}

/* KPI */
.kpi .title{font-size:12px;color:var(--muted)}
.kpi .num{font-weight:700;font-size:22px;color:var(--text)}
.kpi .delta{font-size:12px}
.kpi .icon-pill{background:#F2F4F7;color:#667085;border-radius:10px;padding:6px 8px;line-height:1}

/* 图例/图表 */
.chart-wrap{height:260px}
.legend-dot{display:inline-block;width:10px;height:10px;border-radius:50%;margin-right:6px;vertical-align:middle}
.legend-row{color:#667085;font-size:13px}

/* 表格 */
.table-wrap{border:1px solid var(--border);border-radius:12px;overflow:hidden}
.table thead th{background:#F8FAFC;color:#475467;font-weight:700}
.table>:not(caption)>*>*{padding:12px 14px;vertical-align:middle}
.tag{display:inline-block;background:#F2F4F7;color:#344054;border:1px solid #E5E7EB;border-radius:999px;padding:2px 8px;font-size:12px;margin:2px}
.kebab{border:0;background:transparent}
.badge-dot{display:inline-block;width:8px;height:8px;border-radius:999px;margin-right:6px}

/* 工具条（并排） */
.toolbar{gap:10px}
.toolbar .form-select,.toolbar .form-control{min-height:34px;font-size:13px;border-radius:8px}
.short-select{min-width:140px;max-width:180px}

/* 右侧筛选栏（Meeting Outcomes） */
.outcomes-grid{display:grid;grid-template-columns: 1.7fr 1fr;gap:16px;align-items:start}
.sidebar{border-left:1px solid var(--border);padding-left:12px}
.filter-stack .label{font-size:12px;color:#667085;margin-bottom:4px}
.filter-stack .form-control,.filter-stack .form-select{min-height:36px;font-size:13px;border-radius:8px}

/* 小屏处理 */
@media (max-width: 992px){
  .outcomes-grid{grid-template-columns:1fr}
  .sidebar{border-left:0;border-top:1px solid var(--border);padding-left:0;padding-top:12px}
}

/* Redo 卡片 & Costing 卡片 */
.redo-card .chart-wrap{height:260px}
.costing-card .table-wrap{border:1px solid var(--border);border-radius:12px;overflow:hidden}
.costing-card .table.table-sm>:not(caption)>*>*{padding:10px 12px}
.costing-card .table thead th{background:#F8FAFC;color:#475467;font-weight:700}
.costing-card .table tbody td{vertical-align:middle}
.costing-card .table td .tag{margin:2px}
.costing-tools .form-select,.costing-tools .form-control{min-height:34px;font-size:13px;border-radius:8px}

/* 统一紧凑按钮（与你之前页一致） */
.btn-sm-compact{min-height:36px;font-size:13px;padding:0 14px;border-radius:6px}
.btn-dark-compact{background:#1E2235;color:#fff;border:0}
.btn-dark-compact:hover{background:#111827}
.btn-gray-compact{background:#94A3B8;color:#fff;border:0}
.btn-gray-compact:hover{background:#64748B}

/* 按钮里的图标微距 */
.btn i{margin-right:.4rem}

.w-160{width:180px}
.w-180{width:180px}
.w-200{width:550px}

</style>

<div class="py-4">

  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-3 px-2">
    <h5 class="fw-bold mb-0">Dashboard Overview</h5>
  </div>

  <div class="card soft p-3 mb-3">
    <!-- KPI -->
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

    <!-- Charts row -->
    <div class="row g-3 mt-1">
      <!-- Monthly Performance（单月构成） -->
      <div class="col-lg-7">
        <div class="card soft p-3 h-100">
          <div class="d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0">Monthly Performance</h6>
            <div class="d-flex align-items-center gap-2">
              <label class="small text-muted mb-0">Month</label>
              <select id="mpMonth" class="form-select form-select-sm short-select">
                @php $m = $monthlyPerformance['month_short']; @endphp
                @foreach(['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] as $mon)
                  <option value="{{ $mon }}" {{ $mon === $m ? 'selected' : '' }}>{{ $mon }}</option>
                @endforeach
              </select>
            </div>
          </div>
          <div class="d-flex flex-wrap gap-3 legend-row mb-2 mt-2">
            <span><i class="legend-dot" style="background:var(--blue)"></i>Leads Added</span>
            <span><i class="legend-dot" style="background:var(--green)"></i>Accepted</span>
            <span><i class="legend-dot" style="background:var(--red)"></i>Rejected</span>
            <span><i class="legend-dot" style="background:var(--cyan)"></i>50/50</span>
            <span><i class="legend-dot" style="background:var(--violet)"></i>Low Chance</span>
          </div>
          <div class="chart-wrap"><canvas id="barMonthly"></canvas></div>
        </div>
      </div>

      <!-- Meeting Outcomes（左图右筛） -->
      <div class="col-lg-5">
        <div class="card soft p-3 h-100">
          <h6 class="fw-bold mb-2">Meeting Outcomes</h6>
          <div class="outcomes-grid">
            <div>
              <div class="chart-wrap"><canvas id="pieOutcome"></canvas></div>
              <div class="mt-2 small">
                <span class="legend-dot" style="background:var(--green)"></span>Accepted
                <span class="legend-dot" style="background:var(--red);margin-left:14px"></span>Rejected
              </div>
            </div>
            <aside class="sidebar">
              <form id="outcomeFilter" method="GET" class="filter-stack d-flex flex-column gap-3">
                <div>
                  <div class="label">Start date</div>
                  <input type="date" class="form-control" name="start_date"
                        value="{{ $meetingOutcomes['filters']['start_date'] }}">
                </div>
                <div>
                  <div class="label">End date</div>
                  <input type="date" class="form-control" name="end_date"
                        value="{{ $meetingOutcomes['filters']['end_date'] }}">
                </div>
                <div>
                  <div class="label">Salesperson</div>
                  <select class="form-select" name="salesperson">
                    <option value="all" {{ $meetingOutcomes['filters']['salesperson']==='all' ? 'selected' : '' }}>
                      All Salesperson
                    </option>
                    @foreach($salespeople as $sp)
                      <option value="{{ $sp->id }}" {{ (string)$sp->id === (string)$meetingOutcomes['filters']['salesperson'] ? 'selected' : '' }}>
                        {{ $sp->name }}
                      </option>
                    @endforeach
                  </select>
                </div>
                <div>
                  <div class="label">Period</div>
                  <select class="form-select" name="period" id="piePeriod">
                    @php $p = $meetingOutcomes['filters']['period'] ?? 'monthly'; @endphp
                    <option value="monthly"  {{ $p==='monthly'  ? 'selected' : '' }}>Monthly</option>
                    <option value="quarterly"{{ $p==='quarterly'? 'selected' : '' }}>Quarterly</option>
                    <option value="yearly"   {{ $p==='yearly'   ? 'selected' : '' }}>Yearly</option>
                    <option value="custom"   {{ $p==='custom'   ? 'selected' : '' }}>Custom</option>
                  </select>
                </div>
                {{-- No submit button needed; we auto-submit on change --}}
              </form>
            </aside>
          </div>
        </div>
      </div>
    </div>
  </div><!-- /top card -->

  <!-- Job Orders -->
  <div class="card soft p-3 mb-3" id="jobOrdersCard">
    <h6 class="fw-bold mb-2">Job Orders</h6>
    <div class="d-flex align-items-center toolbar mb-2">
      <form method="GET" class="d-flex align-items-center toolbar mb-2" id="jobFilterForm" style="width: 100%;">
        <input type="text"
              name="order_search"
              value="{{ $filters['order_search'] ?? '' }}"
              class="form-control"
              placeholder="Search by Job Title, Company…">

        <select name="artist" class="form-select short-select">
          <option value="">All Artists</option>
          @foreach($artists as $ar)
            <option value="{{ $ar->id }}" {{ (string)($filters['artist'] ?? '') === (string)$ar->id ? 'selected' : '' }}>
              {{ $ar->name }}
            </option>
          @endforeach
        </select>

        <input type="date"
              name="order_date"
              value="{{ $filters['order_date'] ?? '' }}"
              class="form-control short-select">

        <select name="order_status" class="form-select short-select">
          @php $st = $filters['order_status'] ?? ''; @endphp
          <option value="" {{ $st==='' ? 'selected' : '' }}>All Status</option>
          <option {{ $st==='In Progress' ? 'selected' : '' }}>In Progress</option>
          <option {{ $st==='Completed'   ? 'selected' : '' }}>Completed</option>
          <option {{ $st==='Rejected'    ? 'selected' : '' }}>Rejected</option>
          <option {{ $st==='Assigned'    ? 'selected' : '' }}>Assigned</option>
          <option {{ $st==='To Assign'   ? 'selected' : '' }}>To Assign</option>
        </select>

        <div class="ms-auto d-flex gap-2">
          <button class="btn btn-dark-compact btn-sm-compact" type="submit">
            <i class="bi bi-download"></i> Export
          </button>
        </div>
      </form>
    </div>

    <div class="table-wrap">
      <table class="table mb-0" id="jobTable">
        <thead>
          <tr>
            <th>Order ID</th><th>Job Title</th><th>Company</th><th>Artist</th><th>Status</th><th>Deadline</th><th class="text-end">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse($jobOrders as $o)
            @php
              $isTopRedo = in_array($o->id, $redoTop3Ids ?? []);
              $rawNumber = $isTopRedo && $o->orig_no ? $o->orig_no : $o->order_number;
              $orderIdDisplay = rtrim($rawNumber ?? '', 'R') . ($isTopRedo ? 'R' : '');
              $statusLabel = ucwords(str_replace('_',' ', $o->orderStatus ?? ''));
            @endphp
            <tr data-artist="{{ $o->artist_name ?? '' }}" data-status="{{ $statusLabel }}">
              <td>{{ $orderIdDisplay }}</td>
              <td>{{ $o->orderTitle }}</td>
              <td>{{ $o->companyName }}</td>
              <td>{{ $o->artist_name ?? '—' }}</td>
              <td><span class="tag">{{ $statusLabel ?: '—' }}</span></td>
              <td>{{ optional(\Carbon\Carbon::parse($o->deadline ?? null))->toDateString() }}</td>
              <td class="text-end"><button class="kebab"><i class="bi bi-three-dots-vertical"></i></button></td>
            </tr>
          @empty
            <tr><td colspan="7" class="text-center text-muted">No orders to display.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <!-- Job Order Fulfillment -->
  <div class="card soft p-3 mb-3">
    <h6 class="fw-bold mb-2">Job Order Fulfillment</h6>
    <div class="chart-wrap"><canvas id="orderFulfill"></canvas></div>
    <div class="small mt-2">
      <span class="badge-dot" style="background:var(--green)"></span>Total Orders
      <span class="badge-dot" style="background:var(--amber);margin-left:14px"></span>In Progress
      <span class="badge-dot" style="background:var(--cyan);margin-left:14px"></span>Completed
      <span class="badge-dot" style="background:var(--red);margin-left:14px"></span>Rejected
    </div>
  </div>

  <!-- Machine usage summary -->
  <div class="card soft p-3 mb-3">
    <h6 class="fw-bold mb-2">Machine Usage Summary</h6>
    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
      <form method="GET" class="d-flex align-items-center gap-2 mb-2 flex-wrap" id="machineFilterForm" style="width: 100%;">
        <input type="text" name="machine_q" class="form-control form-control-sm w-180"
              placeholder="Search machine..." value="{{ $machineFilters['machine_q'] ?? '' }}">

        <select name="machine_type" class="form-select form-select-sm w-160">
          @php $mt = $machineFilters['machine_type'] ?? ''; @endphp
          <option value="" {{ $mt==='' ? 'selected' : '' }}>All Machine Types</option>
          <option value="Printer" {{ $mt==='Printer' ? 'selected' : '' }}>Printer</option>
          <option value="Cutter"  {{ $mt==='Cutter'  ? 'selected' : '' }}>Cutter</option>
        </select>

        <select name="machine_range" class="form-select form-select-sm w-160">
          @php $mr = $machineFilters['machine_range'] ?? 'last30'; @endphp
          <option value="last30" {{ $mr==='last30' ? 'selected' : '' }}>Last 30 Days</option>
          <option value="last90" {{ $mr==='last90' ? 'selected' : '' }}>Last 90 Days</option>
          <option value="year"   {{ $mr==='year'   ? 'selected' : '' }}>This Year</option>
        </select>

        <div class="ms-auto d-flex gap-2">
          <button class="btn btn-dark-compact btn-sm-compact" type="submit">
            <i class="bi bi-download"></i> Export
          </button>
          <button class="btn btn-gray-compact btn-sm-compact" type="button">
            <i class="bi bi-plus-lg"></i> Add Machine Type
          </button>
        </div>
      </form>
    </div>


    <div class="table-wrap">
      <table class="table mb-0" id="machineTable">
        <thead>
          <tr>
            <th>MACHINE NAME</th>
            <th>MACHINE TYPE</th>
            <th>USED BY ITEMS</th>
            <th>TOTAL QUANTITY</th>
            <th class="text-end">ACTIONS</th>
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
  </div>

  <!-- Cost charts row -->
  <div class="row g-3 mb-3">
    <div class="col-lg-6">
      <div class="card soft p-3 h-100">
        <h6 class="fw-bold mb-2">Total Cost Distribution</h6>
        <div class="chart-wrap"><canvas id="costDonut"></canvas></div>
        <div class="small mt-2">
          <span class="legend-dot" style="background:var(--blue)"></span>Material Cost
          <span class="legend-dot" style="background:var(--amber);margin-left:14px"></span>Labor Cost
          <span class="legend-dot" style="background:var(--pink);margin-left:14px"></span>Overhead
          <span class="legend-dot" style="background:var(--violet);margin-left:14px"></span>Other
        </div>
      </div>
    </div>
    <div class="col-lg-6">
      <div class="card soft p-3 h-100 redo-card">
        <h6 class="fw-bold mb-2">Redo Count by Product & Order</h6>
        <div class="chart-wrap"><canvas id="redoChart"></canvas></div>
      </div>
    </div>
  </div>

  <!-- Costing Data Management -->
  <div class="card soft p-3 mb-3 costing-card" id="costingSection">
    <h6 class="fw-bold mb-2">Costing Data Management</h6>
    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
  <!-- Search 在前 -->
  <input type="text" class="form-control form-control-sm w-200" placeholder="Search product id, product name...">

  <!-- 下拉：Machine Type -->
  <select class="form-select form-select-sm w-160">
    <option>All Machine Type</option>
    <option>Printer</option>
    <option>Cutter</option>
  </select>

  <!-- 下拉：时间范围 -->
  <select class="form-select form-select-sm w-160">
    <option>Last 30 Days</option>
    <option>Last 90 Days</option>
    <option>This Year</option>
  </select>

  <!-- 右侧按钮 -->
  <div class="ms-auto d-flex gap-2">
    <button class="btn btn-dark-compact btn-sm-compact">
      <i class="bi bi-download"></i> Export
    </button>
    <button class="btn btn-gray-compact btn-sm-compact">
      <i class="bi bi-plus-lg"></i> Add Machine Type
    </button>
  </div>
</div>


    <div class="table-wrap">
      <table class="table table-sm mb-0" id="costTable">
        <thead>
        <tr>
          <th>Product ID</th><th>Product Quantity</th><th>Used by Items</th><th>Total Cost</th><th>Past Usage</th><th class="text-end">Actions</th>
        </tr>
        </thead>
        <tbody>
        <tr data-type="Printer">
          <td>ORD-2025-001</td><td>8 Products</td><td>4000</td><td>RM 400.00</td>
          <td><span class="tag">#ORD005-P1</span><span class="tag">#ORD006-P1</span></td>
          <td class="text-end"><button class="kebab"><i class="bi bi-three-dots-vertical"></i></button></td>
        </tr>
        <tr data-type="Cutter">
          <td>ORD-2025-002</td><td>4 Products</td><td>2000</td><td>RM 200.00</td>
          <td><span class="tag">#ORD010-C2</span><span class="tag">#ORD012-C3</span><span class="tag">#ORD013-C3</span></td>
          <td class="text-end"><button class="kebab"><i class="bi bi-three-dots-vertical"></i></button></td>
        </tr>
        <tr data-type="Printer">
          <td>ORD-2025-003</td><td>6 Products</td><td>1650</td><td>RM 315.00</td>
          <td><span class="tag">#ORD001-P2</span><span class="tag">#ORD004-P2</span><span class="tag">#ORD009-P2</span></td>
          <td class="text-end"><button class="kebab"><i class="bi bi-three-dots-vertical"></i></button></td>
        </tr>
        </tbody>
      </table>
    </div>
  </div>

</div><!-- /page-wrap -->

<script>
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
  const mpSel = document.getElementById('mpMonth');
  mpSel && mpSel.addEventListener('change', e => {
    const params = new URLSearchParams(window.location.search);
    params.set('mpMonth', e.target.value);
    params.set('year', {{ (int)$monthlyPerformance['year'] }});
    window.location.search = params.toString();
  });
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

// ===== Cost Donut =====
const donutCtx = document.getElementById('costDonut');
if (donutCtx) {
  new Chart(donutCtx,{
    type:'doughnut',
    data:{ labels:['Material Cost','Labor Cost','Overhead','Other'],
      datasets:[{ data:[500,400,250,150], backgroundColor:['#60a5fa','#f59e0b','#f472b6','#a78bfa'], borderWidth:0, cutout:'65%' }]},
    options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false} } }
  });
}

// ===== Redo 多色柱图（有颜色）=====
const redoCtx = document.getElementById('redoChart');
if (redoCtx) {
  new Chart(redoCtx,{
    type:'bar',
    data:{
      labels:['ORD005-P1','ORD007-P2','ORD010-P2','ORD011-A1','ORD014-B1','ORD018-P3','ORD020-C2'],
      datasets:[{ data:[3,1,2,4,2,3,1],
        backgroundColor:['#60a5fa','#22c55e','#f59e0b','#ef4444','#a78bfa','#10b981','#f472b6'],
        borderRadius:6, borderSkipped:false }]
    },
    options:{
      responsive:true, maintainAspectRatio:false, plugins:{ legend:{display:false}, tooltip:{callbacks:{label:c=>`Redo: ${c.parsed.y}`}} },
      scales:{ y:{beginAtZero:true, ticks:{stepSize:1}}, x:{grid:{display:false}} }
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
</script>

@endsection
