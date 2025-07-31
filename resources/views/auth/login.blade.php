@extends('layouts.app')

  @section('title', 'Login')

  @section('content')
  <div class="container-xxl">
      <div class="authentication-wrapper authentication-basic container-p-y">
          <div class="authentication-inner">
              <div class="card">
                  <div class="card-body">
                      <h4 class="mb-2">Welcome to ERP Colossal Media! 👋</h4>
                      <p class="mb-4">Please sign-in to your account</p>
                      <form method="POST" action="{{ route('login') }}">
                          @csrf
                          <div class="mb-3">
                              <label for="email" class="form-label">Email</label>
                              <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus>
                              @error('email')
                                  <div class="text-danger">{{ $message }}</div>
                              @enderror
                          </div>
                          <div class="mb-3">
                              <label for="password" class="form-label">Password</label>
                              <input type="password" class="form-control" id="password" name="password" required>
                              @error('password')
                                  <div class="text-danger">{{ $message }}</div>
                              @enderror
                          </div>
                          <div class="mb-3">
                              <div class="form-check">
                                  <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                                  <label class="form-check-label" for="remember">Remember me</label>
                              </div>
                          </div>
                          <button type="submit" class="btn btn-primary d-grid w-100">Sign in</button>
                          @if (Route::has('password.request'))
                              <a href="{{ route('password.request') }}" class="text-muted mt-2 d-block">Forgot Password?</a>
                          @endif
                      </form>
                  </div>
              </div>
          </div>
      </div>
  </div>
  @endsection