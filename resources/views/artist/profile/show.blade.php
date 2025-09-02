@extends('layouts.app') {{-- or your artist layout --}}

@section('content')
<div class="container-fluid">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <h3 class="mb-0">User Profile</h3>
    <a href="{{ route('artist.profile.edit') }}" class="btn btn-outline-primary">
      <i class="ti ti-edit me-1"></i> Edit Profile
    </a>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <span class="fw-semibold">User Info</span>
      <span class="badge {{ $user->status_badge_class ?? 'bg-success' }}">
        {{ $user->status_label ?? 'Active' }}
      </span>
    </div>

    <div class="card-body">
      <div class="row align-items-center">
        {{-- Avatar --}}
        <div class="col-auto">
          <img src="{{ $user->avatar_url ?? asset('assets/img/avatars/1.png') }}"
               alt="Avatar"
               class="rounded-circle border"
               width="72" height="72">
        </div>

        {{-- Fields grid --}}
        <div class="col">
          <div class="row gy-3 gx-5">
            <div class="col-md-3">
              <div class="text-muted small">Full Name</div>
              <div class="fw-semibold">{{ $user->name }}</div>
            </div>
            <div class="col-md-3">
              <div class="text-muted small">Email</div>
              <div class="fw-semibold">{{ $user->email }}</div>
            </div>
            <div class="col-md-3">
              <div class="text-muted small">Role</div>
              <div class="fw-semibold">{{ $user->display_role ?? $user->role }}</div>
            </div>
            <div class="col-md-3">
              <div class="text-muted small">Contact Number</div>
              <div class="fw-semibold">
                {{ $user->contact_number ?? '—' }}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif
</div>
@endsection
