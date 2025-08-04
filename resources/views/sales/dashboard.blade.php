@extends('layouts.app')

@section('title', 'Dashboard Overview')
@section('content')
<h1>Dashboard Overview</h1>
    <p>Welcome, {{ auth()->user()->name }}! This is your salesperson dashboard.</p>
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Dashboard Overview</h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5>Leads:  count($leads) </h5>
                    <h5>Meetings:  count($meetings) </h5>
                    <h5>Orders:  count($orders) </h5> 

                </div>
            </div>
        </div>
    </div>
</div>
@endsection