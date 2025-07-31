@extends('layouts.app')

@section('title', 'Job Order Status')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Job Order Status</h4>
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Job Description</th>
                                <th>Due Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($jobOrders as $jobOrder)
                            <tr>
                                <td>{{ $jobOrder->job_description }}</td>
                                <td>{{ $jobOrder->due_date }}</td>
                                <td>{{ $jobOrder->status }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection