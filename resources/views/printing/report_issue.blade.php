@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
  .page-wrap{max-width:1100px;margin:0 auto}

  .card.soft{border:0;box-shadow:0 3px 12px rgba(16,24,40,.06);border-radius:16px}
  .card.soft .card-body{padding:28px 28px}
  @media (max-width:576px){ .card.soft .card-body{padding:20px} }

  .form-label{font-weight:600;color:#344054;display:flex;align-items:center;gap:8px;font-size:15px;margin-bottom:8px}
  .form-text{color:#98A2B3}
  .req::after{content:" *";color:#DC2626;font-weight:700}
  .field{margin-bottom:22px}

  .form-control{min-height:44px;padding:12px 14px;border-radius:12px}
  .form-control.is-invalid{border-color:#ef4444}
  .invalid-feedback{display:block}

  .textarea-min{min-height:140px}

  .radio-list{display:flex;flex-direction:column;gap:14px}
  .form-check-input{width:18px;height:18px;margin-top:3px}
  .hidden{display:none !important;}

  .actionbar{position:sticky;bottom:0;background:transparent;padding-top:12px}
  .actionbar .bar{display:flex;justify-content:flex-end;gap:12px}
  .btn-pill{border-radius:12px}
  .actionbar .btn{padding:10px 18px;font-weight:600}
</style>

@php
  // Controller should provide: $productId and $orderCode (e.g. "ORD30-P2")
  $orderCode = $orderCode ?? ($orderId ?? 'ORD005-P1'); // fallback
@endphp

<div class="container-fluid py-4 px-4">
  <div class="page-wrap">

    {{-- Header --}}
    <div class="d-flex align-items-center gap-2 mb-3">
      <a href="{{ route('printing.dashboard') }}" class="text-decoration-none text-muted">
        <i class="bi bi-arrow-left"></i>
      </a>
      <h1 class="h4 fw-bold mb-0">Report Issue — <span class="text-muted">{{ $row->product_code_display }}</span></h1>
    </div>

    {{-- Flash --}}
    @if (session('status'))
      <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    {{-- Form Card --}}
    <div class="card soft">
      <div class="card-body">

        <form id="issueForm"
              method="POST"
              action="{{ route('printing.report.submit', ['productId' => $productId ?? request()->route('productId')]) }}"
              novalidate>
          @csrf

          {{-- Order ID (read only) --}}
          <div class="field">
            <label class="form-label"><i class="bi bi-hash"></i> Order ID</label>
            <input type="text"
                   class="form-control"
                   value="{{ $row->product_code_display }}"
                   readonly>
          </div>

          {{-- Reason --}}
          <div class="field">
            <label class="form-label req"><i class="bi bi-exclamation-triangle"></i> Select Reason</label>

            @error('reason')
              <div class="invalid-feedback mb-2">{{ $message }}</div>
            @enderror

            <div class="radio-list">
              @php $oldReason = old('reason'); @endphp

              <label class="form-check">
                <input class="form-check-input" type="radio" name="reason" value="Low Resolution Artwork"
                       @checked($oldReason === 'Low Resolution Artwork')>
                <span class="form-check-label">Low Resolution Artwork</span>
              </label>

              <label class="form-check">
                <input class="form-check-input" type="radio" name="reason" value="Missing Bleeds"
                       @checked($oldReason === 'Missing Bleeds')>
                <span class="form-check-label">Missing Bleeds</span>
              </label>

              <label class="form-check">
                <input class="form-check-input" type="radio" name="reason" value="Unsuitable Requested Material"
                       @checked($oldReason === 'Unsuitable Requested Material')>
                <span class="form-check-label">Unsuitable Requested Material</span>
              </label>

              <label class="form-check d-flex align-items-start gap-2">
                <input id="reasonOthers" class="form-check-input mt-1" type="radio" name="reason" value="Others"
                       @checked($oldReason === 'Others')>
                <span class="form-check-label">Others</span>
              </label>

              {{-- Others · specify --}}
              <div id="otherWrap" class="ms-4 w-100 {{ $oldReason === 'Others' ? '' : 'hidden' }}">
                <textarea id="otherText"
                          class="form-control textarea-min @error('reason_other') is-invalid @enderror"
                          name="reason_other"
                          placeholder="Please specify...">{{ old('reason_other') }}</textarea>
                <div id="otherHelp" class="form-text">Required when choosing “Others”.</div>
                @error('reason_other')
                  <div class="invalid-feedback">{{ $message }}</div>
                @enderror
              </div>
            </div>
          </div>

          {{-- Notes --}}
          <div class="field">
            <label class="form-label"><i class="bi bi-journal-text"></i> Additional Notes <span class="form-text">(Optional)</span></label>
            <textarea class="form-control textarea-min @error('notes') is-invalid @enderror"
                      name="notes"
                      placeholder="Provide additional details about the issue...">{{ old('notes') }}</textarea>
            @error('notes')
              <div class="invalid-feedback">{{ $message }}</div>
            @enderror
          </div>

          <hr class="mt-4 mb-3">

          {{-- Actions --}}
          <div class="actionbar">
            <div class="bar">
              <a href="{{ route('printing.dashboard') }}" class="btn btn-light border btn-pill">Cancel</a>
              <button id="btnSubmit" type="submit" class="btn btn-dark btn-pill" disabled>
                <i class="bi bi-send me-1"></i>Submit Report
              </button>
            </div>
          </div>
        </form>

      </div>
    </div>

  </div>
</div>

<script>
  (function(){
    const form      = document.getElementById('issueForm');
    const btnSubmit = document.getElementById('btnSubmit');
    const otherWrap = document.getElementById('otherWrap');
    const otherText = document.getElementById('otherText');

    function isOthersSelected(){
      const r = form.querySelector('input[name="reason"]:checked');
      return r && r.value === 'Others';
    }

    function validate(){
      const selected = form.querySelector('input[name="reason"]:checked');
      let ok = !!selected;
      if (ok && isOthersSelected()){
        ok = otherText.value.trim().length > 2;
      }
      btnSubmit.disabled = !ok;
    }

    // Initial state (keep old values)
    validate();

    form.addEventListener('change', (e)=>{
      if (e.target.name === 'reason'){
        if (isOthersSelected()){
          otherWrap.classList.remove('hidden');
          otherText.focus();
        }else{
          otherWrap.classList.add('hidden');
          otherText.value = '';
        }
        validate();
      }
    });

    otherText?.addEventListener('input', validate);

    form.addEventListener('submit', (e)=>{
      validate();
      if (btnSubmit.disabled){ e.preventDefault(); }
    });
  })();
</script>
@endsection
