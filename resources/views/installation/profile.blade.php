@extends('layouts.app')

@section('content')
@push('styles')
<style>
  /* Slide-fade dropdown panel */
  .edit-panel{
      overflow:hidden;
      max-height:0;
      opacity:0;
      transform:translateY(-6px);
      transition:
          max-height .55s cubic-bezier(.2,.8,.2,1),
          opacity .35s ease,
          transform .35s ease;
      will-change:max-height,opacity,transform;
  }
  .edit-panel.open{
      opacity:1;
      transform:translateY(0);
      max-height:520px; /* adjust if your form is taller */
      animation:popShadow .45s ease both;
  }
  @keyframes popShadow{
      from{ box-shadow:0 0 0 rgba(0,0,0,0); }
      to{ box-shadow:0 .125rem .75rem rgba(0,0,0,.06); }
  }

  .collapse-area{
      overflow:hidden;
      max-height:0;
      opacity:0;
      transition:max-height .45s cubic-bezier(.2,.8,.2,1), opacity .25s ease;
  }
  .collapse-area.open{ opacity:1; }

  /* Rotate chevron when open */
  #toggleEdit[aria-expanded="true"] #toggleIcon{
      transform:rotate(180deg);
      transition:transform .25s ease;
  }
</style>
@endpush

<div class="container-fluid">
  <!-- Header -->
  <div class="d-flex align-items-center justify-content-between mb-3">
      <h3 class="mb-0">User Profile</h3>
      <button id="toggleEdit" class="btn btn-outline-primary" aria-expanded="{{ $errors->any() ? 'true' : 'false' }}">
          <i class="ti ti-edit me-1"></i> Edit Profile
          <i class="ti ti-chevron-down ms-1 small" id="toggleIcon"></i>
      </button>
  </div>

  {{-- Flash messages --}}
  @if (session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
  @endif
  @if ($errors->any())
    <div class="alert alert-danger">
      <div class="fw-bold mb-1">Please fix the following:</div>
      <ul class="mb-0">
        @foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach
      </ul>
    </div>
  @endif

  <!-- Info Card -->
  <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
          <span class="fw-semibold">User Info</span>
          <span class="badge bg-success">Active</span>
      </div>

      <div class="card-body">
          <div class="row align-items-center">
              {{-- Avatar (use your asset/initials if you prefer) --}}
              <div class="col-auto">
                  <img
                      src="{{ asset('assets/img/avatars/1.png') }}"
                      alt="Avatar"
                      class="rounded-circle border"
                      width="72" height="72">
              </div>

              {{-- Fields grid --}}
              <div class="col">
                  <div class="row gy-3 gx-5">
                      <div class="col-md-3">
                          <div class="text-muted small">Full Name</div>
                          <div class="fw-semibold">{{ $user->name ?? '—' }}</div>
                      </div>
                      <div class="col-md-3">
                          <div class="text-muted small">Email</div>
                          <div class="fw-semibold">{{ $user->email ?? '—' }}</div>
                      </div>
                      <div class="col-md-3">
                          <div class="text-muted small">Role</div>
                          <div class="fw-semibold">{{ \Illuminate\Support\Str::of($user->role ?? '')->replace('_','-')->title() ?: '—' }}</div>
                      </div>
                      <div class="col-md-3">
                          <div class="text-muted small">Contact Number</div>
                          <div class="fw-semibold">{{ $user->contact_number ?: '—' }}</div>
                      </div>
                  </div>
              </div>
          </div>
      </div>
  </div>

  {{-- Slide-down edit panel --}}
  <div id="profileEditWrap" class="mt-3">
      <div
        id="editPanel"
        class="card edit-panel @if($errors->any()) open @endif"
        aria-hidden="@if($errors->any()) false @else true @endif"
      >
          <div class="card-body">
              <form method="POST" action="{{ route('installation.profile.update') }}" novalidate>
                  @csrf
                  @method('PUT')

                  <div class="row g-4">
                      <div class="col-md-4">
                          <label class="form-label">Full Name</label>
                          <input
                            type="text"
                            name="name"
                            class="form-control @error('name') is-invalid @enderror"
                            value="{{ old('name', $user->name) }}"
                            required>
                          @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                      </div>

                      <div class="col-md-4">
                          <label class="form-label">Email</label>
                          <input
                            type="email"
                            name="email"
                            class="form-control @error('email') is-invalid @enderror"
                            value="{{ old('email', $user->email) }}"
                            required>
                          @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                      </div>

                      <div class="col-md-4">
                          <label class="form-label">Contact Number</label>
                          <input
                            type="text"
                            name="contact_number"
                            class="form-control @error('contact_number') is-invalid @enderror"
                            value="{{ old('contact_number', $user->contact_number) }}"
                            placeholder="+60 123-456-789">
                          @error('contact_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                      </div>

                      <hr class="my-4">

                      {{-- Toggle row --}}
                      <div class="d-flex align-items-center justify-content-between">
                          <div>
                              <div class="fw-semibold">Change Password</div>
                              <div class="text-muted small">Update your password by providing your current password.</div>
                          </div>
                          <div class="form-check form-switch ms-3">
                              <input class="form-check-input" type="checkbox" id="togglePwd" name="change_password" value="1"
                                     @checked(old('change_password'))>
                          </div>
                      </div>

                      {{-- Password fields (collapsed by default) --}}
                      <div id="passwordFields" class="mt-3 collapse-area" aria-hidden="true">
                          <div class="row g-4">
                              <div class="col-md-4">
                                  <label class="form-label">Current Password</label>
                                  <input
                                    type="password"
                                    name="current_password"
                                    class="form-control @error('current_password') is-invalid @enderror"
                                    autocomplete="current-password">
                                  @error('current_password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                              </div>

                              <div class="col-md-4">
                                  <label class="form-label">New Password</label>
                                  <input
                                    type="password"
                                    name="password"
                                    class="form-control @error('password') is-invalid @enderror"
                                    autocomplete="new-password"
                                    placeholder="Min 8, mix case, number, symbol">
                                  @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
                              </div>

                              <div class="col-md-4">
                                  <label class="form-label">Confirm New Password</label>
                                  <input
                                    type="password"
                                    name="password_confirmation"
                                    class="form-control"
                                    autocomplete="new-password">
                              </div>
                          </div>
                      </div>
                  </div>

                  <div class="mt-4 d-flex gap-2">
                      <button type="submit" class="btn btn-primary">Save changes</button>
                      <button type="button" id="cancelEdit" class="btn btn-light">Cancel</button>
                  </div>
              </form>
          </div>
      </div>
  </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
  const btn     = document.getElementById('toggleEdit');
  const panel   = document.getElementById('editPanel');
  const cancel  = document.getElementById('cancelEdit');

  const pwdWrap = document.getElementById('passwordFields');
  const pwdTgl  = document.getElementById('togglePwd');

  // --- helpers -------------------------------------------------
  const syncPanelHeight = () => {
    panel.style.maxHeight = panel.scrollHeight + 'px';
  };

  const openPanel = (open) => {
    if (open) {
      panel.classList.add('open');
      requestAnimationFrame(syncPanelHeight);
      panel.setAttribute('aria-hidden', 'false');
      btn?.setAttribute('aria-expanded', 'true');
      panel.scrollIntoView({ behavior:'smooth', block:'start' });
    } else {
      panel.style.maxHeight = panel.scrollHeight + 'px';
      requestAnimationFrame(() => {
        panel.classList.remove('open');
        panel.style.maxHeight = '0px';
        panel.setAttribute('aria-hidden', 'true');
        btn?.setAttribute('aria-expanded', 'false');
      });
    }
  };

  const openPwd = (open) => {
    if (!pwdWrap) return;
    if (open) {
      pwdWrap.classList.add('open');
      pwdWrap.style.maxHeight = pwdWrap.scrollHeight + 'px';
    } else {
      pwdWrap.style.maxHeight = pwdWrap.scrollHeight + 'px';
      requestAnimationFrame(() => {
        pwdWrap.classList.remove('open');
        pwdWrap.style.maxHeight = '0px';
      });
    }
    // keep parent height synced while inner section animates
    let ticks = 0;
    const id = setInterval(() => {
      syncPanelHeight();
      if (++ticks > 14) clearInterval(id);
    }, 30);
  };

  // --- initial state -------------------------------------------
  if (panel.classList.contains('open')) {
    syncPanelHeight();
  }

  // Auto-open password on validation errors / old inputs
  const shouldOpenPwd = @json(
    old('change_password') ||
    $errors->has('current_password') ||
    $errors->has('password') ||
    old('password') ||
    old('password_confirmation')
  );
  if (shouldOpenPwd && pwdTgl) {
    pwdTgl.checked = true;
    openPwd(true);
  }

  // --- events --------------------------------------------------
  btn?.addEventListener('click', (e) => {
    e.preventDefault();
    openPanel(!panel.classList.contains('open'));
  });

  cancel?.addEventListener('click', () => openPanel(false));

  pwdTgl?.addEventListener('change', (e) => openPwd(e.target.checked));

  // keep heights correct on resize
  window.addEventListener('resize', () => {
    if (panel.classList.contains('open')) syncPanelHeight();
    if (pwdWrap?.classList.contains('open')) {
      pwdWrap.style.maxHeight = pwdWrap.scrollHeight + 'px';
    }
  });
});
</script>
@endpush
@endsection
