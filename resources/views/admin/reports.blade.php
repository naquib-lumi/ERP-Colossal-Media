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

/* Table (通用) */
.table-wrap{border:1px solid var(--border);border-radius:12px;overflow:hidden}
.table thead th{background:#F8FAFC;color:#475467;font-weight:700}
.table>:not(caption)>*>*{padding:12px 14px;vertical-align:middle}
.badge-dot{display:inline-block;width:8px;height:8px;border-radius:999px;margin-right:6px}

/* 紧凑按钮 */
.btn-sm-compact{min-height:36px;font-size:13px;padding:0 14px;border-radius:6px}
.btn-dark-compact{background:#1E2235;color:#fff;border:0}
.btn-dark-compact:hover{background:#111827}

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

/* 让两张图卡片等高 */
.charts-row .card.soft{height:100%}
</style>

<div class="card soft p-0 mb-3">
  <!-- Tabs（已移除 Machine Usage） -->
  <ul class="nav nav-tabs px-3 pt-3" id="reportTabs" style="border-bottom:1px solid var(--border)">
    <li class="nav-item"><a class="nav-link active" data-target="#salesSec" href="javascript:void(0)">Sales Report</a></li>
    <li class="nav-item"><a class="nav-link" data-target="#orderSec" href="javascript:void(0)">Order Report</a></li>
  </ul>

  <!-- ===================== Sales Report ===================== -->
  <div class="section active" id="salesSec">
    <!-- Filters -->
    <div class="p-3 border-bottom">
      <div class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label small">Select Salesperson</label>
          <select class="form-select" id="salesperson">
            <option>All Salespersons</option><option>Alex</option><option>Brenda</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small">Time Period</label>
          <div class="btn-group w-100" role="group">
            <button class="btn btn-outline-dark btn-sm active">Yearly</button>
            <button class="btn btn-outline-dark btn-sm">Quarterly</button>
            <button class="btn btn-outline-dark btn-sm">Monthly</button>
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label small">Date Range</label>
          <div class="d-flex align-items-center gap-2">
            <input type="date" class="form-control" value="2025-01-01">
            <span class="text-muted small">to</span>
            <input type="date" class="form-control" value="2025-01-31">
          </div>
        </div>
        <div class="col-md-2 text-md-end">
          <button class="btn btn-dark-compact btn-sm-compact w-100">
            <i class="bi bi-download me-1"></i> Export
          </button>
        </div>
      </div>
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
            <div class="num">36</div>
            <span class="delta text-success">+12% from last period</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card soft kpi p-3">
            <div class="d-flex justify-content-between align-items-start">
              <p class="title mb-1">Total Meetings Held</p>
              <span class="icon-pill"><i class="bi bi-calendar3"></i></span>
            </div>
            <div class="num">18</div>
            <span class="delta text-success">+8% from last period</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card soft kpi p-3">
            <div class="d-flex justify-content-between align-items-start">
              <p class="title mb-1">Accepted Meetings</p>
              <span class="icon-pill"><i class="bi bi-check2-square"></i></span>
            </div>
            <div class="num">10</div>
            <span class="text-muted small">55.6% acceptance rate</span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card soft kpi p-3">
            <div class="d-flex justify-content-between align-items-start">
              <p class="title mb-1">Rejected</p>
              <span class="icon-pill"><i class="bi bi-x-square"></i></span>
            </div>
            <div class="num">8</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts -->
    <div class="p-3">
      <div class="row g-3 align-items-stretch charts-row">
        <!-- 左：Monthly Performance -->
        <div class="col-lg-7">
          <div class="card soft p-3 h-100 d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center">
              <h6 class="fw-bold mb-0">Monthly Performance</h6>
              <div class="d-flex align-items-center gap-2">
                <label class="small text-muted mb-0">Month</label>
                <select id="mpMonth" class="form-select form-select-sm" style="width:140px">
                  <option>Jan</option><option>Feb</option><option>Mar</option>
                  <option>Apr</option><option>May</option><option selected>Jun</option>
                </select>
              </div>
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
        </div>

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
                <div class="mt-2 small">
                  <span class="legend-dot" style="background:#22c55e"></span>Accepted
                  <span class="legend-dot" style="background:#ef4444;margin-left:14px"></span>Rejected
                </div>
              </div>

              <!-- 右：竖排筛选 -->
              <aside class="sidebar">
                <div class="filter-stack d-flex flex-column gap-3">
                  <div>
                    <div class="label">Start date</div>
                    <input type="date" class="form-control" value="2025-01-01">
                  </div>
                  <div>
                    <div class="label">End date</div>
                    <input type="date" class="form-control" value="2025-01-31">
                  </div>
                  <div>
                    <div class="label">Salesperson</div>
                    <select class="form-select">
                      <option>All Salespersons</option>
                      <option>Alex</option>
                      <option>Brenda</option>
                    </select>
                  </div>
                  <div>
                    <div class="label">Period</div>
                    <select class="form-select">
                      <option selected>Monthly</option>
                      <option>Quarterly</option>
                      <option>Yearly</option>
                    </select>
                  </div>
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
      <div class="row g-3 align-items-end">
        <div class="col-md-3">
          <label class="form-label small">Time Period</label>
          <select class="form-select" id="ordPeriod">
            <option selected>Monthly</option>
            <option>Quarterly</option>
            <option>Yearly</option>
          </select>
        </div>
        <div class="col-md-5">
          <label class="form-label small">Date Range</label>
          <div class="d-flex gap-2">
            <input type="date" class="form-control" value="2025-01-01">
            <input type="date" class="form-control" value="2025-01-31">
          </div>
        </div>
        <div class="col-md-2 ms-auto text-md-end">
          <button class="btn btn-dark-compact btn-sm-compact w-100">
            <i class="bi bi-download me-1"></i> Export
          </button>
        </div>
      </div>
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
          <span class="badge-dot" style="background:#ef4444;margin-left:14px"></span>Overdue
        </div>
      </div>
    </div>
  </div><!-- /orderSec -->
</div>

<script>
// ===== Tabs（仅两页） =====
document.querySelectorAll('#reportTabs .nav-link').forEach(a=>{
  a.addEventListener('click', ()=>{
    document.querySelectorAll('#reportTabs .nav-link').forEach(x=>x.classList.remove('active'));
    a.classList.add('active');
    document.querySelectorAll('.section').forEach(sec=>sec.classList.remove('active'));
    document.querySelector(a.dataset.target).classList.add('active');
  });
});

// ===== Monthly Performance（单月构成） =====
const mpCtx = document.getElementById('barMonthly');

const mpLabels = ['Leads Added','Accepted','Rejected','50/50','Low Chance'];
const mpColors = ['#60a5fa','#22c55e','#ef4444','#06b6d4','#a78bfa'];

const monthlyData = {
  Jan: [100,30,15,50,5],
  Feb: [80,25,12,35,8],
  Mar: [95,22,14,40,7],
  Apr: [110,21,16,38,6],
  May: [90,34,10,45,4],
  Jun: [100,30,15,50,5],
};

function makeDataset(values){
  return [{
    label: 'This Month',
    data: values,
    backgroundColor: mpColors,
    borderRadius: 6,
    borderSkipped: false
  }];
}

let currentMonth = 'Jun';
let barMonthly = new Chart(mpCtx, {
  type: 'bar',
  data: { labels: mpLabels, datasets: makeDataset(monthlyData[currentMonth]) },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: { callbacks: { label: (ctx) => `${ctx.label}: ${ctx.parsed.y}` } }
    },
    scales: {
      x: { grid: { display: false } },
      y: { beginAtZero: true, ticks: { stepSize: 20 } }
    }
  }
});

// 月份下拉切换
const mpSel = document.getElementById('mpMonth');
mpSel?.addEventListener('change', () => {
  currentMonth = mpSel.value;
  barMonthly.data.datasets = makeDataset(monthlyData[currentMonth]);
  barMonthly.update();
});

// ===== Meeting Outcomes 饼图 =====
const pieCtx = document.getElementById('pieOutcome');
if (pieCtx) {
  new Chart(pieCtx, {
    type: 'pie',
    data: {
      labels: ['Accepted', 'Rejected'],
      datasets: [{
        data: [10, 8],
        backgroundColor: ['#22c55e', '#ef4444'],
        borderWidth: 0
      }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
  });
}

// ===== Order chart =====
new Chart(document.getElementById('orderFulfill'),{
  type:'bar',
  data:{
    labels:['New Order','In Progress','Completed','Overdue'],
    datasets:[{ data:[30,12,17,1], backgroundColor:['#22c55e','#f59e0b','#06b6d4','#ef4444'] }]
  },
  options:{
    responsive:true, maintainAspectRatio:false,
    plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}
  }
});
</script>
@endsection
