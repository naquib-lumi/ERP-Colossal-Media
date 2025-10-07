@extends('layouts.app')

@section('title', 'Dashboard Overview')
@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col">
            <h2>Dashboard Overview</h2>
            <p>Last updated: {{ $lastUpdated }}</p>
        </div>
        <div class="col-auto">
            <select class="form-select" onchange="location = this.value;">
                <option value="?period=this_month" {{ $period == 'this_month' ? 'selected' : '' }}>This Month</option>
                <option value="?period=this_year" {{ $period == 'this_year' ? 'selected' : '' }}>This Year</option>
                <option value="?period=last_month" {{ $period == 'last_month' ? 'selected' : '' }}>Last Month</option>
                <option value="?period=3_months" {{ $period == '3_months' ? 'selected' : '' }}>Last 3 Months</option>
            </select>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h5>Order to Assign</h5>
                    <h3>24</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h5>Total Orders</h5>
                    <h3>156</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h5>In Progress Orders</h5>
                    <h3>{{ $inProgressCount }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="card text-center">
                <div class="card-body">
                    <h5>Completed Orders</h5>
                    <h3>{{ $completedCount }}</h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Orders In Progress</div>
                <div class="card-body">
                    @foreach($inProgressOrders as $order)
                    <div class="mb-3">
                        <strong>#{{ $order->order_number }}</strong><br>
                        {{ $order->orderTitle }}<br>
                        {{ $order->getStatusLabelAttribute() }}<br>
                        Due: {{ $order->deadline ? $order->deadline->format('M d, Y') : 'N/A' }}<br>
                        Assigned to: {{ $order->artist->name ?? 'N/A' }}
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">Recent Completed Orders</div>
                <div class="card-body">
                    @foreach($completedOrders as $order)
                    <div class="mb-3">
                        <strong>#{{ $order->order_number }}</strong><br>
                        {{ $order->orderTitle }}<br>
                        Completed<br>
                        {{ $order->updated_at->format('M d, Y') }}<br>
                        Completed by: {{ $order->artist->name ?? 'N/A' }}
                    </div>
                    @endforeach
                </div>
@endsection