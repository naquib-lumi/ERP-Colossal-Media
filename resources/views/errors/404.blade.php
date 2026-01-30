@extends('layouts.app')

@section('title', '404 — Page Not Found')

@section('content')
<style>
  .nf-wrap{
    min-height: calc(100vh - 140px);
    display:flex;
    align-items:center;
    justify-content:center;
    padding: 28px 12px;
  }
  .nf-card{
    width: 100%;
    max-width: 860px;
    border: 1px solid rgba(0,0,0,.06);
    border-radius: 18px;
    overflow:hidden;
    background:#fff;
    box-shadow: 0 14px 40px rgba(0,0,0,.10);
  }
  .nf-top{
    padding: 22px 22px 14px 22px;
    background: linear-gradient(135deg, rgba(108,92,231,.14), rgba(0,174,239,.10));
    border-bottom: 1px solid rgba(0,0,0,.06);
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap: 12px;
    flex-wrap: wrap;
  }
  .nf-badge{
    display:inline-flex;
    align-items:center;
    gap:.5rem;
    font-weight:800;
    font-size:.85rem;
    padding:.35rem .7rem;
    border-radius: 999px;
    background:#fff;
    border: 1px solid rgba(0,0,0,.08);
    box-shadow: 0 8px 18px rgba(0,0,0,.06);
  }
  .nf-dot{
    width:10px;height:10px;border-radius:999px;
    background:#dc3545;
    box-shadow: 0 0 0 4px rgba(220,53,69,.14);
  }
  .nf-body{
    padding: 22px;
    display:grid;
    grid-template-columns: 1.2fr .8fr;
    gap: 18px;
  }
  @media (max-width: 768px){
    .nf-body{ grid-template-columns: 1fr; }
  }
  .nf-title{
    font-size: 1.6rem;
    font-weight: 900;
    margin: 0 0 .4rem 0;
    letter-spacing: -.02em;
  }
  .nf-sub{
    margin:0;
    color:#6c757d;
    font-weight:600;
    line-height:1.5;
  }
  .nf-code{
    font-size: 4rem;
    font-weight: 900;
    letter-spacing: .08em;
    color: rgba(108,92,231,.20);
    text-align:right;
    user-select:none;
  }
  @media (max-width: 768px){
    .nf-code{ text-align:left; }
  }
  .nf-actions{
    display:flex;
    align-items:center;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 14px;
  }
  .btn-purple{
    background:#6C5CE7 !important;
    border:none !important;
    color:#fff !important;
    border-radius: 10px !important;
    padding: .55rem 1rem !important;
    font-weight: 800 !important;
    box-shadow: 0 10px 22px rgba(108,92,231,.22);
  }
  .btn-purple:hover{
    background:#5a4cd9 !important;
    transform: translateY(-1px);
  }
  .btn-soft{
    background:#f8f9fa !important;
    border: 1px solid rgba(0,0,0,.08) !important;
    border-radius: 10px !important;
    padding: .55rem 1rem !important;
    font-weight: 800 !important;
  }
  .nf-hint{
    margin-top: 12px;
    padding: 12px 14px;
    border-radius: 12px;
    background:#f8f9fa;
    border: 1px dashed rgba(0,0,0,.14);
    color:#6c757d;
    font-weight: 600;
    font-size: .9rem;
  }
</style>

<div class="nf-wrap">
  <div class="nf-card">

    <div class="nf-top">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <span class="nf-badge">
          <span class="nf-dot"></span>
          404 • Page Not Found
        </span>
        <span class="text-muted fw-semibold small">
          The page you’re looking for doesn’t exist or has been moved.
        </span>
      </div>

      <div class="text-muted small fw-semibold">
        {{ request()->path() }}
      </div>
    </div>

    <div class="nf-body">
      <div>
        <h1 class="nf-title">Oops — we can’t find that page.</h1>
        <p class="nf-sub">
          It might be a broken link, a typo, or you don’t have access to this resource.
          Try going back, or return to the dashboard.
        </p>

        <div class="nf-actions">
          <a href="{{ url('/dashboard') }}" class="btn btn-purple">
            <i class="bi bi-house-door me-2"></i> Back to Home
          </a>

          <button type="button" class="btn btn-soft" onclick="history.back()">
            <i class="bi bi-arrow-left me-2"></i> Go Back
          </button>
        </div>

        <div class="nf-hint">
          Tip: If you believe this is an error, please share the URL with admin/support.
        </div>
      </div>

      <div class="nf-code">404</div>
    </div>

  </div>
</div>
@endsection
