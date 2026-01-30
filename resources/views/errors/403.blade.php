@extends('layouts.app')

@section('title', 'Access Denied')

@section('content')
<div class="container d-flex align-items-center justify-content-center" style="min-height:70vh;">
    <div class="text-center p-5 shadow rounded-4 bg-white" style="max-width:520px;">
        <div class="mb-3">
            <i class="bi bi-shield-lock-fill text-danger" style="font-size:4rem;"></i>
        </div>

        <h1 class="fw-bold text-danger">403</h1>
        <h4 class="mb-3">Access Denied</h4>

        <p class="text-muted mb-4">
            Sorry, you don’t have permission to access this page.<br>
            Please contact your customer support if you think this is a mistake.
        </p>

        <div class="d-flex justify-content-center gap-3">
            <a href="{{ url('/dashboard') }}" class="btn btn-primary px-4">
                <i class="bi bi-house-door"></i> Home
            </a>
        </div>
    </div>
</div>
@endsection
