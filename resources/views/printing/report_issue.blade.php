@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
  /* ===== Spacing & sizing tuned for airiness ===== */
  .page-wrap{max-width:1100px;margin:0 auto}

  .card.soft{border:0;box-shadow:0 3px 12px rgba(16,24,40,.06);border-radius:16px}
  .card.soft .card-body{padding:28px 28px}
  @media (max-width:576px){ .card.soft .card-body{padding:20px} }

  /* Section header */
  .section-hd{gap:10px;margin-bottom:16px;font-size:16px}

  /* Labels & fields */
  .form-label{font-weight:600;color:#344054;display:flex;align-items:center;gap:8px;font-size:15px;margin-bottom:8px}
  .form-text{color:#98A2B3}
  .req::after{content:" *";color:#DC2626;font-weight:700}

  .field{margin-bottom:22px}

  /* Inputs */
  .form-control{min-height:44px;padding:12px 14px;border-radius:12px}
  .textarea-min{min-height:140px}

  /* Radios — bigger targets + more gap */
  .radio-list{display:flex;flex-direction:column;gap:14px}
  .form-check-input{width:18px;height:18px;margin-top:3px}
  .ms-4#otherWrap{margin-top:6px}
  .hidden{display:none !important;}

  /* Card title row (页面标题一行) */
  .h4.fw-bold{font-size:22px}

  /* Divider spacing */
  hr{margin:24px 0 16px}

  /* Actionbar — larger buttons + gaps */
  .actionbar{position:sticky;bottom:0;background:transparent;padding-top:12px}
  .actionbar .bar{display:flex;justify-content:flex-end;gap:12px}
  .btn-pill{border-radius:12px}
  .actionbar .btn{padding:10px 18px;font-weight:600}

  /* Optional: 让表单区域整体字距更舒适 */
  .card.soft, .card.soft *{letter-spacing:.01em}
</style>

@php
  $orderId = $orderId ?? 'ORD005-P1';
@endphp

<div class="container-fluid py-4 px-4">
  <div class="page-wrap">

    {{-- Header --}}
    <div class="d-flex align-items-center gap-2 mb-3">
      <a href="javascript:history.back()" class="text-decoration-none text-muted"><i class="bi bi-arrow-left"></i></a>
      <h1 class="h4 fw-bold mb-0">Report Issue — <span class="text-muted">#{{ $orderId }}</span></h1>
    </div>

    {{-- Form Card --}}
    <div class="card soft">
      <div class="card-body">

        <form id="issueForm" action="#" method="POST" novalidate>
          @csrf

          {{-- Order ID --}}
          <div class="field">
            <label class="form-label"><i class="bi bi-hash"></i> Order ID</label>
            <input type="text" class="form-control" name="order_id" value="{{ $orderId }}" readonly>
          </div>

          {{-- Reason --}}
          <div class="field">
            <label class="form-label req"><i class="bi bi-exclamation-triangle"></i> Select Reason</label>
            <div class="radio-list">
              <label class="form-check">
                <input class="form-check-input" type="radio" name="reason" value="Low Resolution Artwork">
                <span class="form-check-label">Low Resolution Artwork</span>
              </label>

              <label class="form-check">
                <input class="form-check-input" type="radio" name="reason" value="Missing Bleeds">
                <span class="form-check-label">Missing Bleeds</span>
              </label>

              <label class="form-check">
                <input class="form-check-input" type="radio" name="reason" value="Unsuitable Requested Material">
                <span class="form-check-label">Unsuitable Requested Material</span>
              </label>

              <label class="form-check d-flex align-items-start gap-2">
                <input id="reasonOthers" class="form-check-input mt-1" type="radio" name="reason" value="Others">
                <span class="form-check-label">Others</span>
              </label>

              {{-- Others · specify --}}
              <div id="otherWrap" class="ms-4 w-100 hidden">
                <textarea id="otherText" class="form-control textarea-min" name="reason_other" placeholder="Please specify..."></textarea>
                <div id="otherHelp" class="form-text">Required when choosing “Others”.</div>
              </div>
            </div>
          </div>

          {{-- Notes --}}
          <div class="field">
            <label class="form-label"><i class="bi bi-journal-text"></i> Additional Notes <span class="form-text">(Optional)</span></label>
            <textarea class="form-control textarea-min" name="notes" placeholder="Provide additional details about the issue..."></textarea>
          </div>

          <hr class="mt-4 mb-3">

          {{-- Actions --}}
          <div class="actionbar">
            <div class="bar">
              <a href="javascript:history.back()" class="btn btn-light border btn-pill">Cancel</a>
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
    const form = document.getElementById('issueForm');
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

    // Toggle "Others" textarea
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

    // Final guard on submit (前端校验演示；后端请再校验)
    form.addEventListener('submit', (e)=>{
      validate();
      if (btnSubmit.disabled){
        e.preventDefault();
      }
    });
  })();
</script>
@endsection
