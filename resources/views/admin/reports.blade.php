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
  <!-- Tabs -->
  <ul class="nav nav-tabs px-3 pt-3" id="reportTabs" style="border-bottom:1px solid var(--border)">
    <li class="nav-item"><a class="nav-link active" data-target="#salesSec" href="javascript:void(0)">Sales Report</a></li>
    <li class="nav-item"><a class="nav-link" data-target="#orderSec" href="javascript:void(0)">Order Report</a></li>
  </ul>

  <!-- Sales Report -->
  <div class="section active" id="salesSec">
    <!-- Filters -->
    <div class="p-3 border-bottom">
      <form id="sales-filters">
        @csrf
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label small">Select Salesperson</label>
            <select class="form-select" name="salesperson" id="salesperson">
              <option value="All Salespersons">All Salespersons</option>
              @foreach($salespeople as $id => $name)
                <option value="{{ $id }}">{{ $name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small">Time Period</label>
            <div class="btn-group w-100" role="group" id="sales-period-group">
              <button type="button" class="btn btn-outline-dark btn-sm" data-period="yearly">Yearly</button>
              <button type="button" class="btn btn-outline-dark btn-sm" data-period="quarterly">Quarterly</button>
              <button type="button" class="btn btn-outline-dark btn-sm active" data-period="monthly">Monthly</button>
            </div>
            <input type="hidden" name="period" id="sales-period" value="monthly">
          </div>
          <div class="col-md-4">
            <label class="form-label small">Date Range</label>
            <div class="d-flex align-items-center gap-2">
              <input type="date" name="start_date" id="sales-start-date" class="form-control" value="{{ now()->startOfMonth()->format('Y-m-d') }}">
              <span class="text-muted small">to</span>
              <input type="date" name="end_date" id="sales-end-date" class="form-control" value="{{ now()->endOfMonth()->format('Y-m-d') }}">
            </div>
          </div>
          <div class="col-md-2 text-md-end d-flex gap-2">
            <button type="button" id="sales-generate" class="btn btn-primary btn-sm-compact">Generate</button>
             <button type="submit" formaction="{{ route('admin.report.export-sales') }}" class="btn btn-dark-compact btn-sm-compact">
              <i class="bi bi-download me-1"></i> Export
            </button>
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
            <div class="num" id="total-leads">0</div>
            <span class="delta" id="leads-delta"></span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card soft kpi p-3">
            <div class="d-flex justify-content-between align-items-start">
              <p class="title mb-1">Total Meetings Held</p>
              <span class="icon-pill"><i class="bi bi-calendar3"></i></span>
            </div>
            <div class="num" id="total-meetings">0</div>
            <span class="delta" id="meetings-delta"></span>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card soft kpi p-3">
            <div class="d-flex justify-content-between align-items-start">
              <p class="title mb-1">Leads Accepted</p>
              <span class="icon-pill"><i class="bi bi-check2-square"></i></span>
            </div>
            <div class="num" id="accepted-meetings">0</div>
          </div>
        </div>
        <div class="col-md-3">
          <div class="card soft kpi p-3">
            <div class="d-flex justify-content-between align-items-start">
              <p class="title mb-1">Leads Rejected</p>
              <span class="icon-pill"><i class="bi bi-x-square"></i></span>
            </div>
            <div class="num" id="rejected-meetings">0</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Charts -->
    <div class="p-3">
      <div class="row g-3 align-items-stretch charts-row">
        <!-- Performance -->
        <div class="col-lg-7">
          <div class="card soft p-3 h-100 d-flex flex-column">
            <h6 class="fw-bold mb-0">Leads Performance</h6>

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

        <!-- Meeting Outcomes -->
        <div class="col-lg-5">
          <div class="card soft p-3 h-100 d-flex flex-column">
            <h6 class="fw-bold mb-0">Sales Outcomes</h6>

            <div class="chart-wrap"><canvas id="pieOutcome"></canvas></div>
            <div class="mt-2 small">
              <span class="legend-dot" style="background:#22c55e"></span>Accepted
              <span class="legend-dot" style="background:#ef4444;margin-left:14px"></span>Rejected
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Order Report -->
  <div class="section" id="orderSec">
    <!-- Filters -->
    <div class="p-3 border-bottom">
      <form id="order-filters">
        @csrf
        <div class="row g-3 align-items-end">
          <div class="col-md-3">
            <label class="form-label small">Time Period</label>
            <div class="btn-group w-100" role="group" id="order-period-group">
              <button type="button" class="btn btn-outline-dark btn-sm" data-period="yearly">Yearly</button>
              <button type="button" class="btn btn-outline-dark btn-sm" data-period="quarterly">Quarterly</button>
              <button type="button" class="btn btn-outline-dark btn-sm active" data-period="monthly">Monthly</button>
            </div>
            <input type="hidden" name="period" id="order-period" value="monthly">
          </div>
          <div class="col-md-5">
            <label class="form-label small">Date Range</label>
            <div class="d-flex gap-2">
              <input type="date" name="start_date" id="order-start-date" class="form-control" value="{{ now()->startOfMonth()->format('Y-m-d') }}">
              <input type="date" name="end_date" id="order-end-date" class="form-control" value="{{ now()->endOfMonth()->format('Y-m-d') }}">
            </div>
          </div>
          <div class="col-md-2 ms-auto text-md-end d-flex gap-2">
            <button type="button" id="order-generate" class="btn btn-primary btn-sm-compact">Generate</button>
            <button type="submit" formaction="{{ route('admin.report.export-orders') }}" class="btn btn-dark-compact btn-sm-compact">
              <i class="bi bi-download me-1"></i> Export
            </button>
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
          <span class="badge-dot" style="background:#ef4444;margin-left:14px"></span>Overdue
        </div>
      </div>
    </div>
  </div>
</div>

<script>
// Initialize charts with zeros
const mpLabels = ['Leads Added', 'Accepted', 'Rejected', '50/50', 'Low Chance'];
const mpColors = ['#60a5fa', '#22c55e', '#ef4444', '#06b6d4', '#a78bfa'];
let barMonthly = new Chart(document.getElementById('barMonthly'), {
  type: 'bar',
  data: { labels: mpLabels, datasets: [{ data: [0, 0, 0, 0, 0], backgroundColor: mpColors, borderRadius: 6, borderSkipped: false }] },
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

let pieOutcome = new Chart(document.getElementById('pieOutcome'), {
  type: 'pie',
  data: { labels: ['Accepted', 'Rejected'], datasets: [{ data: [0, 0], backgroundColor: ['#22c55e', '#ef4444'], borderWidth: 0 }] },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
});

let orderFulfill = new Chart(document.getElementById('orderFulfill'), {
  type: 'bar',
  data: { labels: ['New Order', 'In Progress', 'Completed', 'Overdue'], datasets: [{ data: [0, 0, 0, 0], backgroundColor: ['#22c55e', '#f59e0b', '#06b6d4', '#ef4444'] }] },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true } }
  }
});

// Tabs
document.querySelectorAll('#reportTabs .nav-link').forEach(a => {
  a.addEventListener('click', () => {
    document.querySelectorAll('#reportTabs .nav-link').forEach(x => x.classList.remove('active'));
    a.classList.add('active');
    document.querySelectorAll('.section').forEach(sec => sec.classList.remove('active'));
    document.querySelector(a.dataset.target).classList.add('active');
  });
});

// Function to update date range inputs
function updateDateRange(section, period) {
  const startInput = document.querySelector(`#${section}-start-date`);
  const endInput = document.querySelector(`#${section}-end-date`);
  const today = new Date();
  let startDate, endDate;

  if (period === 'yearly') {
    startDate = new Date(today.getFullYear(), 0, 1);
    endDate = new Date(today.getFullYear(), 11, 31);
  } else if (period === 'quarterly') {
    const quarter = Math.floor(today.getMonth() / 3);
    startDate = new Date(today.getFullYear(), quarter * 3, 1);
    endDate = new Date(today.getFullYear(), (quarter + 1) * 3 - 1, new Date(today.getFullYear(), (quarter + 1) * 3, 0).getDate());
  } else {
    startDate = new Date(today.getFullYear(), today.getMonth(), 1);
    endDate = new Date(today.getFullYear(), today.getMonth() + 1, 0);
  }

  startInput.value = startDate.toISOString().split('T')[0];
  endInput.value = endDate.toISOString().split('T')[0];
}

// Sales Period buttons
document.querySelectorAll('#sales-period-group button').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('#sales-period-group button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelector('#sales-period').value = btn.dataset.period;
    updateDateRange('sales', btn.dataset.period);
  });
});

// Order Period buttons
document.querySelectorAll('#order-period-group button').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('#order-period-group button').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelector('#order-period').value = btn.dataset.period;
    updateDateRange('order', btn.dataset.period);
  });
});

// Sales Generate
document.getElementById('sales-generate').addEventListener('click', () => {
  const formData = new FormData(document.getElementById('sales-filters'));
  fetch('{{ route('admin.report.sales-kpis') }}', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(data => {
      document.getElementById('total-leads').textContent = data.total_leads;
      document.getElementById('leads-delta').className = data.leads_delta >= 0 ? 'delta text-success' : 'delta text-danger';
      document.getElementById('total-meetings').textContent = data.total_meetings;
      document.getElementById('meetings-delta').className = data.meetings_delta >= 0 ? 'delta text-success' : 'delta text-danger';
      document.getElementById('accepted-meetings').textContent = data.accepted;
      document.getElementById('rejected-meetings').textContent = data.rejected;
    })
    .catch(err => console.error('Error fetching sales KPIs:', err));

  fetch('{{ route('admin.report.sales-monthly') }}', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(data => {
      barMonthly.data.datasets[0].data = [data.leads_added, data.accepted, data.rejected, data.fifty_fifty, data.low_chance];
      barMonthly.update();
    })
    .catch(err => console.error('Error fetching performance:', err));

  
  fetch('{{ route('admin.report.sales-outcomes') }}', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(data => {
      pieOutcome.data.datasets[0].data = [data.accepted, data.rejected];
      console.log(pieOutcome.data.datasets[0].data);
      pieOutcome.update();
    })
    .catch(err => console.error('Error fetching meeting outcomes:', err));
});

// Order Generate
document.getElementById('order-generate').addEventListener('click', () => {
  const formData = new FormData(document.getElementById('order-filters'));
  fetch('{{ route('admin.report.order-fulfillment') }}', {
    method: 'POST',
    body: formData
  })
    .then(res => res.json())
    .then(data => {
      orderFulfill.data.datasets[0].data = [data.new_orders, data.in_progress, data.completed, data.overdue];
      orderFulfill.update();
    })
    .catch(err => console.error('Error fetching order fulfillment:', err));
});

document.getElementById('salesperson').addEventListener('change', () => {
  document.getElementById('sales-generate').click();
});
</script>
@endsection