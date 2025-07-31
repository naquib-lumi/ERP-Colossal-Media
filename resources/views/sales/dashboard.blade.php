@extends('layouts.app')

@section('title', 'Sales Dashboard')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Sales Dashboard</h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5>Leads: {{ count($leads) }}</h5>
                    <h5>Meetings: {{ count($meetings) }}</h5>
                    <h5>Orders: {{ count($orders) }}</h5>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection