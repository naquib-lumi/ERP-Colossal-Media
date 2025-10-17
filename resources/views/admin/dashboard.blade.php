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
            <select class="form-select form-select-sm" onchange="location = this.value;">
                <option value="?period=this_month" {{ $period == 'this_month' ? 'selected' : '' }}>This Month</option>
                <option value="?period=this_year" {{ $period == 'this_year' ? 'selected' : '' }}>This Year</option>
                <option value="?period=last_month" {{ $period == 'last_month' ? 'selected' : '' }}>Last Month</option>
                <option value="?period=3_months" {{ $period == '3_months' ? 'selected' : '' }}>Last 3 Months</option>
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
                <div class="bg-light rounded-circle p-2">
                    <i class="fas fa-puzzle-piece text-dark"></i>
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
                <div class="bg-light rounded-circle p-2">
                    <i class="fas fa-clipboard text-dark"></i>
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
                <div class="bg-light rounded-circle p-2">
                    <i class="fas fa-spinner text-dark"></i>
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
                <div class="bg-light rounded-circle p-2">
                    <i class="fas fa-check text-dark"></i>
                </div>
            </div>
        </div>
    </div>
</div>

    <div class="row g-4">
        <div class="col-lg-6">
            <div class="card h-100 border-0 shadow-sm">
                <div class="card-header  text-white">
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
                <div class="card-header  text-white">
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
</div>
@endsection