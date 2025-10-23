@extends('layouts.app')

@section('title', 'Dashboard Overview')
@section('content')
<style>
  :root{
    --bg:#F5F7FB;
    --card:#FFFFFF;
    --muted:#6B7280;
    --text:#111827;
    --border:#E5E7EB;
    --shadow:0 3px 12px rgba(16,24,40,.06);
    --radius:16px;

    /* brand colors */
    --blue-50:#EEF2FF;   --blue-400:#60A5FA; --blue-600:#2563EB;
    --cyan-50:#ECFEFF;   --cyan-400:#22D3EE; --cyan-600:#0891B2;
    --amber-50:#FFFBEB;  --amber-400:#F59E0B; --amber-600:#B45309;
    --green-50:#ECFDF5;  --green-400:#34D399; --green-600:#047857;
    --rose-50:#FFF1F2;   --rose-400:#FB7185; --rose-600:#E11D48;
  }

  body{background:var(--bg);}
  h2{color:var(--text);}
  .dash-muted{color:var(--muted);}
  .card.soft{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow)}

  /* ===== top metrics ===== */
  .metric-card .card-body{padding:16px}
  .metric-title{font-size:.8rem;color:var(--muted);margin-bottom:6px}
  .metric-value{font-size:1.8rem;font-weight:700;color:var(--text)}
  .icon-pill{width:44px;height:44px;border-radius:999px;display:flex;align-items:center;justify-content:center;box-shadow:var(--shadow)}
  .pill-blue{background:var(--blue-50);color:var(--blue-600)}
  .pill-cyan{background:var(--cyan-50);color:var(--cyan-600)}
  .pill-amber{background:var(--amber-50);color:var(--amber-600)}
  .pill-green{background:var(--green-50);color:var(--green-600)}

  /* ===== section headers ===== */
  .section-hd{display:flex;align-items:center;gap:10px;padding:12px 16px;border-bottom:1px solid var(--border);background:#F9FAFB;border-radius:12px 12px 0 0}
  .hd-bar{width:6px;height:18px;border-radius:4px;background:var(--blue-400)}
  .hd-bar.green{background:var(--green-400)}

  /* ===== list cards ===== */
  .list-card{background:#F9FAFB;border:1px solid var(--border);border-radius:12px;padding:14px 16px;margin-bottom:10px}
  .order-no{font-weight:700;color:var(--text)}
  .order-meta{font-size:.85rem;color:var(--muted);margin-top:2px}
  .order-meta span{color:var(--text)}
  .text-end .badge{margin-bottom:6px}

  /* soft badges */
  .badge-soft{font-weight:600;border-radius:10px;padding:6px 10px;font-size:.75rem}
  .badge-soft-info{background:var(--blue-50);color:var(--blue-600)}
  .badge-soft-success{background:var(--green-50);color:var(--green-600)}
  .badge-soft-warning{background:var(--amber-50);color:var(--amber-600)}
  .badge-soft-danger{background:var(--rose-50);color:var(--rose-600)}
  .badge-soft-secondary{background:#E5E7EB;color:#374151}
</style>

<div class="container-fluid">
  <div class="row mb-4 align-items-center">
    <div class="col">
      <h2>Dashboard Overview</h2>
      <p class="dash-muted">Last updated: {{ $lastUpdated }}</p>
    </div>
    <div class="col-auto">
      <select class="form-select form-select-sm" onchange="location='?period='+this.value;">
        <option value="this_month" {{ $period == 'this_month' ? 'selected' : '' }}>This Month</option>
        <option value="this_year" {{ $period == 'this_year' ? 'selected' : '' }}>This Year</option>
        <option value="last_month" {{ $period == 'last_month' ? 'selected' : '' }}>Last Month</option>
        <option value="3_months" {{ $period == '3_months' ? 'selected' : '' }}>Last 3 Months</option>
      </select>
    </div>
  </div>

  {{-- ===== top metric cards ===== --}}
  <div class="row mb-4 g-3">
    <div class="col-md-3">
      <div class="card soft metric-card h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="metric-title">Orders to Assign</div>
            <div class="metric-value">{{ $ordersToAssign }}</div>
          </div>
          <div class="icon-pill pill-blue"><i class="bx bx-puzzle"></i></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card soft metric-card h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="metric-title">Total Orders</div>
            <div class="metric-value">{{ $totalOrders }}</div>
          </div>
          <div class="icon-pill pill-cyan"><i class="bx bx-clipboard"></i></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card soft metric-card h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="metric-title">In Progress Orders</div>
            <div class="metric-value">{{ $inProgressCount }}</div>
          </div>
          <div class="icon-pill pill-amber"><i class="bx bx-loader-alt"></i></div>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card soft metric-card h-100">
        <div class="card-body d-flex justify-content-between align-items-center">
          <div>
            <div class="metric-title">Completed Orders</div>
            <div class="metric-value">{{ $completedCount }}</div>
          </div>
          <div class="icon-pill pill-green"><i class="bx bx-check"></i></div>
        </div>
      </div>
    </div>
  </div>

  @php
    $badgeClass = function($label){
      $map = [
        'Completed'   => 'badge-soft-success',
        'In Progress' => 'badge-soft-info',
        'Pending'     => 'badge-soft-warning',
        'Overdue'     => 'badge-soft-danger',
      ];
      return 'badge-soft '.($map[$label] ?? 'badge-soft-secondary');
    };
  @endphp

  {{-- ===== order lists ===== --}}
  <div class="row g-4">
    <div class="col-lg-6">
      <div class="card soft h-100">
        <div class="section-hd">
          <span class="hd-bar"></span>
          <h5 class="m-0">Orders In Progress</h5>
        </div>
        <div class="card-body">
          @forelse($inProgressOrders as $order)
            @php $label=$order->getStatusLabelAttribute(); @endphp
            <div class="list-card">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="order-no">{{ $order->order_number }}</div>
                  <div class="order-meta">{{ $order->orderTitle }}</div>
                  <div class="order-meta">Assigned To: <span>{{ $order->artist?->name ?? 'N/A' }}</span></div>
                </div>
                <div class="text-end">
                  <span class="{{ $badgeClass($label) }}">{{ $label }}</span>
                  <div class="order-meta fw-semibold mt-1">Due: {{ $order->deadline ? $order->deadline->format('M d, Y') : 'N/A' }}</div>
                </div>
              </div>
            </div>
          @empty
            <p class="text-center dash-muted">No orders in progress.</p>
          @endforelse
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card soft h-100">
        <div class="section-hd">
          <span class="hd-bar green"></span>
          <h5 class="m-0">Recent Completed Order</h5>
        </div>
        <div class="card-body">
          @forelse($completedOrders as $order)
            @php $label=$order->getStatusLabelAttribute(); @endphp
            <div class="list-card">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="order-no">{{ $order->order_number }}</div>
                  <div class="order-meta">{{ $order->orderTitle }}</div>
                  <div class="order-meta">Completed By: <span>{{ $order->artist?->name ?? 'N/A' }}</span></div>
                </div>
                <div class="text-end">
                  <span class="{{ $badgeClass($label) }}">{{ $label }}</span>
                  <div class="order-meta fw-semibold mt-1">{{ $order->updated_at->format('M d, Y') }}</div>
                </div>
              </div>
            </div>
          @empty
            <p class="text-center dash-muted">No completed orders.</p>
          @endforelse
        </div>
      </div>
    </div>
  </div>

  {{-- ===== charts ===== --}}
  <div class="row g-4 mb-4 mt-1">
    <div class="col-md-6 d-flex">
      <div class="card soft flex-fill h-100">
        <div class="section-hd">
          <span class="hd-bar"></span>
          <h6 class="m-0">Monthly Performance</h6>
        </div>
        <div class="card-body"><div id="leadsChart"></div></div>
      </div>
    </div>
    <div class="col-md-6 d-flex">
      <div class="card soft flex-fill h-100">
        <div class="section-hd">
          <span class="hd-bar green"></span>
          <h6 class="m-0">Job Order Fulfillment</h6>
        </div>
        <div class="card-body"><div id="fulfillmentChart"></div></div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const period = new URLSearchParams(window.location.search).get('period') || 'this_month';

  // Monthly chart
  const wrapLeads = document.getElementById('leadsChart');
  if (wrapLeads) {
    wrapLeads.innerHTML = `<div style="height:280px;"><canvas id="leadsBar"></canvas></div>`;
    fetch(`{{ route('admin.leadsBreakdown') }}?period=${period}`)
      .then(r => r.json())
      .then(res => {
        const labels=['Added','Accepted','Rejected','50/50','Low Chance'];
        const data=[res.added,res.accepted,res.rejected,res.fifty_fifty,res.low_chance];
        const bg=['rgba(96,165,250,.4)','rgba(34,197,94,.4)','rgba(251,113,133,.4)','rgba(245,158,11,.4)','rgba(165,180,252,.4)'];
        const border=['#60A5FA','#22C55E','#FB7185','#F59E0B','#A5B4FC'];
        new Chart(document.getElementById('leadsBar').getContext('2d'), {
          type:'bar', data:{labels,datasets:[{data,backgroundColor:bg,borderColor:border,borderWidth:1,borderRadius:8}]},
          options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false}},y:{beginAtZero:true,ticks:{stepSize:5}}}}
        });
      });
  }

  // Fulfillment chart
  const wrapF = document.getElementById('fulfillmentChart');
  if (wrapF) {
    wrapF.innerHTML=`<div style="height:280px;"><canvas id="fulfillmentBar"></canvas></div>`;
    fetch(`{{ route('admin.fulfillmentCounts') }}?period=${period}`)
      .then(r=>r.json()).then(res=>{
        const labels=['New Orders','In Progress','Completed','Overdue'];
        const data=[res.new_orders||0,res.in_progress||0,res.completed||0,res.overdue||0];
        const bg=['rgba(96,165,250,.4)','rgba(245,158,11,.4)','rgba(34,197,94,.4)','rgba(251,113,133,.4)'];
        const border=['#60A5FA','#F59E0B','#22C55E','#FB7185'];
        new Chart(document.getElementById('fulfillmentBar').getContext('2d'), {
          type:'bar',data:{labels,datasets:[{data,backgroundColor:bg,borderColor:border,borderWidth:1,borderRadius:8}]},
          options:{responsive:true,maintainAspectRatio:false,plugins:{legend:{display:false}},scales:{x:{grid:{display:false}},y:{beginAtZero:true,ticks:{stepSize:5}}}}
        });
      });
  }
});
</script>
@endsection
