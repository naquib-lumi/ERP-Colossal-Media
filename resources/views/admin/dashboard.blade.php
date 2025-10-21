@extends('layouts.app')

@section('title', 'Dashboard Overview')
@section('content')
<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col">
            <h2>Dashboard Overview</h2>
            <p class="text-muted">Last updated: {{ $lastUpdated }}</p>
        </div>
        <div class="col-auto">
            <select class="form-select form-select-sm" onchange="location = '?period=' + this.value;">
                <option value="this_month" {{ $period == 'this_month' ? 'selected' : '' }}>This Month</option>
                <option value="this_year" {{ $period == 'this_year' ? 'selected' : '' }}>This Year</option>
                <option value="last_month" {{ $period == 'last_month' ? 'selected' : '' }}>Last Month</option>
                <option value="3_months" {{ $period == '3_months' ? 'selected' : '' }}>Last 3 Months</option>
            </select>
        </div>
    </div>

<div class="row mb-4 g-3">
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm text-center bg-white rounded-3">
            <div class="card-body d-flex justify-content-between align-items-center p-3">
                <div class="text-start">
                    <h6 class="mb-1 text-muted">Orders to Assign</h6>
                    <h3 class="mb-0 text-dark">{{ $ordersToAssign }}</h3>
                </div>
                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bx bx-puzzle text-dark"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm text-center bg-white rounded-3">
            <div class="card-body d-flex justify-content-between align-items-center p-3">
                <div class="text-start">
                    <h6 class="mb-1 text-muted">Total Orders</h6>
                    <h3 class="mb-0 text-dark">{{ $totalOrders }}</h3>
                </div>
                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bx bx-clipboard text-dark"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm text-center bg-white rounded-3">
            <div class="card-body d-flex justify-content-between align-items-center p-3">
                <div class="text-start">
                    <h6 class="mb-1 text-muted">In Progress Orders</h6>
                    <h3 class="mb-0 text-dark">{{ $inProgressCount }}</h3>
                </div>
                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bx bx-loader-alt text-dark"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card h-100 border-0 shadow-sm text-center bg-white rounded-3">
            <div class="card-body d-flex justify-content-between align-items-center p-3">
                <div class="text-start">
                    <h6 class="mb-1 text-muted">Completed Orders</h6>
                    <h3 class="mb-0 text-dark">{{ $completedCount }}</h3>
                </div>
                <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bx bx-check text-dark"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header text-white">
                    <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Orders In Progress</h5>
                </div>
                <div class="card-body">
                    @forelse($inProgressOrders as $order)
                        <div class="card mb-2 border-0 bg-light">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ $order->order_number }}</h6>
                                        <p class="mb-1 small text-muted">{{ $order->orderTitle }}</p>
                                        <p class="mb-0 small">Assigned To: {{ $order->artist?->name ?? 'N/A' }}</p>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-secondary">{{ $order->getStatusLabelAttribute() }}</span>
                                        <p class="mb-1 small text-dark fw-bold">Due: {{ $order->deadline ? $order->deadline->format('M d, Y') : 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-muted">No orders in progress.</p>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header text-white">
                    <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Recent Completed Order</h5>
                </div>
                <div class="card-body">
                    @forelse($completedOrders as $order)
                        <div class="card mb-2 border-0 bg-light">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ $order->order_number }}</h6>
                                        <p class="mb-1 small text-muted">{{ $order->orderTitle }}</p>
                                        <p class="mb-0 small">Completed By: {{ $order->artist?->name ?? 'N/A' }}</p>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-secondary">{{ $order->getStatusLabelAttribute() }}</span>
                                        <p class="mb-1 small text-dark fw-bold">{{ $order->updated_at->format('M d, Y') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-center text-muted">No completed orders.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    
  <div class="row g-4 mb-4">
    <div class="col-md-6 d-flex">
        <div class="card flex-fill h-100">
            <div class="card-body">
                <h6>Monthly Performance</h6>
                <div id="leadsChart"></div>
            </div>
        </div>
    </div>
    <div class="col-md-6 d-flex">
        <div class="card flex-fill h-100">
            <div class="card-body">
                <h6>Job Order Fulfillment</h6>
                <div id="fulfillmentChart"></div>
            </div>
        </div>
    </div>
</div>


</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const period = new URLSearchParams(window.location.search).get('period') || 'this_month';

    // Monthly Leads Performance Chart
    const wrapLeads = document.getElementById('leadsChart');
    if (wrapLeads) {
        wrapLeads.innerHTML = `
            <div id="leadsChartWrap" class="position-relative" style="height:280px;">
            <canvas id="leadsBar" class="chartjs" style="width:100%;height:100%;"></canvas>
            </div>
        `;

        fetch(`{{ route('admin.leadsBreakdown') }}?period=${period}`)
            .then(r => r.json())
            .then(res => {
                const labels = ['Added', 'Accepted', 'Rejected', '50/50', 'Low Chance'];
                const counts = [res.added, res.accepted, res.rejected, res.fifty_fifty, res.low_chance];
                const bg = ['rgba(52, 152, 219, 0.65)', 'rgba(46, 204, 113, 0.65)', 'rgba(231, 76, 60, 0.65)', 'rgba(243, 156, 18, 0.65)', 'rgba(155, 89, 182, 0.65)'];
                const border = ['#3498db', '#27ae60', '#e74c3c', '#f39c12', '#9b59b6'];

                new Chart(document.getElementById('leadsBar').getContext('2d'), {
                    type: 'bar',
                    data: { labels, datasets: [{
                        label: 'Leads Breakdown',
                        data: counts,
                        backgroundColor: bg,
                        borderColor: border,
                        borderWidth: 1,
                        borderRadius: 8,
                        barPercentage: 0.8
                    }]},
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: { callbacks: { label: c => `${c.parsed.y} leads` } }
                        },
                        scales: {
                            x: { grid: { display: false } },
                            y: { beginAtZero: true, ticks: { stepSize: 5 } }
                        }
                    }
                });

                const total = counts.reduce((a, b) => a + b, 0);
                const cap = document.createElement('div');
                cap.className = 'text-center pt-3';
                cap.innerHTML = `<div class="fw-semibold">Total: ${total} leads</div>`;
                wrapLeads.appendChild(cap);
            }).catch(e => console.error('Leads chart error:', e));
    }

    // Job Order Fulfillment Report Chart
    const wrapFulfillment = document.getElementById('fulfillmentChart');
    if (wrapFulfillment) {
        wrapFulfillment.innerHTML = `
            <div id="fulfillmentChartWrap" class="position-relative" style="height:280px;">
            <canvas id="fulfillmentBar" class="chartjs" style="width:100%;height:100%;"></canvas>
            </div>
        `;

        fetch(`{{ route('admin.fulfillmentCounts') }}?period=${period}`)
            .then(r => r.json())
            .then(res => {
            const labels = ['New Orders', 'In Progress', 'Completed', 'Overdue'];
            const data = [res.new_orders || 0, res.in_progress || 0, res.completed || 0, res.overdue || 0];
            const bg = ['rgba(39, 174, 96, 0.65)', 'rgba(243, 156, 18, 0.65)', 'rgba(0, 188, 212, 0.65)', 'rgba(231, 76, 60, 0.65)'];
            const border = ['#27ae60', '#f39c12', '#00bcd4', '#e74c3c'];

            new Chart(document.getElementById('fulfillmentBar').getContext('2d'), {
                type: 'bar',
                data: { labels, datasets: [{
                    label: 'Orders',
                    data: data,
                    backgroundColor: bg,
                    borderColor: border,
                    borderWidth: 1,
                    borderRadius: 8,
                    barPercentage: 0.8
                }]},
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: c => `${c.parsed.y} orders` } }
                    },
                    scales: {
                        x: { grid: { display: false } },
                        y: { beginAtZero: true, ticks: { stepSize: 5 } }
                    }
                }
            });
        }).catch(e => console.error('Fulfillment chart error:', e));
    }
});
</script>
@endsection