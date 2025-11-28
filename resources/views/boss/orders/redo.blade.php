@extends('layouts.app')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<div class="container-fluid">
  <h4 class="mb-3">
    <a href="{{ route('boss.orders') }}"
            class="text-decoration-none text-muted me-3"
            style="display: inline-flex; align-items: center; gap: 8px;">
            <i class="bi bi-arrow-left-circle fw-semibold"
                style="font-size: 1.4rem; font-weight: 600; color: #6c757d;"></i>
        </a>
    Report Issue – Redo Job Order {{ $headerOrderNumber }}
  </h4>

  {{-- Latest REDO Reason (if any) --}}
  @if(!empty($latestRedoReason))
    <div class="alert alert-info d-flex align-items-start gap-2">
      <i class="ti ti-info-circle mt-1"></i>
      <div>
        <div class="fw-semibold">Last REDO Reason</div>
        <div class="text-break">{{ $latestRedoReason }}</div>
      </div>
    </div>
  @endif

  {{-- Summary --}}
  <div class="card mb-4">
    <div class="card-body">
      <div class="fw-semibold mb-2">Current Order Summary</div>
      <div class="row g-3">
        <div class="col-md-3">
          <div class="text-muted small">Job Order Title</div>
          <div class="fw-semibold">{{ $order->orderTitle  }}</div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Client</div>
          <div class="fw-semibold">{{ $order->companyName }}</div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Created</div>
          <div class="fw-semibold">{{ optional($order->orderDate ?? $order->created_at)->format('M d, Y') }}</div>
        </div>
        <div class="col-md-3">
          <div class="text-muted small">Total Products</div>
          <div class="fw-semibold">{{ $products->count() }} product(s)</div>
        </div>
      </div>
    </div>
  </div>

  <form method="POST" action="{{ route('boss.orders.redo.store', $order) }}">
    @csrf

    {{-- Reason --}}
    <div class="card mb-3">
      <div class="card-body">
        <div class="fw-semibold mb-2"><i class="ti ti-alert-triangle me-2"></i>Reason for Redo</div>

        @php
          $reasons    = ['Wrong design received','Client changed requirements','Miscommunication on brief','Color error','Others'];
          $oldReason  = old('reason');
          $oldAlt     = old('reason_alt');
          $isOthers   = strcasecmp($oldReason ?? '', 'Others') === 0;
        @endphp

        <div class="row gy-2">
          @foreach($reasons as $r)
            <div class="col-12">
              <label class="d-flex align-items-center gap-2">
                <input
                  type="radio"
                  name="reason"
                  value="{{ $r }}"
                  class="js-reason"
                  {{ $loop->first ? 'required' : '' }}
                  {{ $oldReason===$r ? 'checked' : '' }}
                >
                <span>{{ $r }}</span>
              </label>
            </div>
          @endforeach
        </div>

        {{-- Always visible; required only when “Others” --}}
        <div id="reasonAltWrap" class="mt-3">
          <textarea
            class="form-control @error('reason_alt') is-invalid @enderror"
            rows="4"
            name="reason_alt"
            id="reasonAlt"
            placeholder="Please specify…"
            {{ $isOthers ? 'required' : '' }}
          >{{ $oldAlt }}</textarea>
          @error('reason_alt')
            <div class="invalid-feedback">{{ $message }}</div>
          @enderror
        </div>

        <hr class="my-4">

        {{-- Products to copy --}}
        <div class="fw-semibold mb-2"><i class="ti ti-package-export me-2"></i>Products to copy</div>
        <div class="text-muted small mb-2">Select which products that wish to redo.</div>

        <div class="row g-3">
          @foreach ($products as $p)
            @php $inputId = 'redo-product-'.$p->ProductID; @endphp
            <div class="col-md-6">
              <div class="card h-100 shadow-sm">
                <div class="card-body">
                  <div class="d-flex align-items-start justify-content-between">
                    <div>
                      <div class="fw-medium">{{ $p->productName }}</div>
                      <small class="text-muted">
                        Qty: {{ $p->totalQuantity }} · {{ ucfirst($p->taskType) }}
                      </small>
                    </div>

                    <div class="form-check mt-1">
                      <input
                        type="checkbox"
                        class="form-check-input js-product"
                        name="products[]"
                        id="{{ $inputId }}"
                        value="{{ $p->ProductID }}"
                        {{ in_array($p->ProductID, old('products', [])) ? 'checked' : '' }}>
                      <label for="{{ $inputId }}" class="visually-hidden">Select {{ $p->productName }}</label>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          @endforeach
        </div>

      </div>
    </div>

    {{-- Submit --}}
    <div class="card mb-3">
      <div class="card-body">
        <div class="fw-semibold mb-2"><i class="ti ti-send me-2"></i>Submit Redo Report</div>
        <div class="alert alert-light border">
          <div class="fw-semibold mb-1">What happens next?</div>
          <ul class="mb-0">
            <li>A new Job Order ID will be created with <b>{{ $nextRedoNumber }}</b></li>
            <li>Only the selected products will be editable on the new order</li>
            <li>Status will be auto-set to <b>In Progress</b> for redo</li>
          </ul>
        </div>

        <div class="d-flex gap-2" style="justify-content: right;">
          <a href="{{ route('boss.orders') }}" class="btn btn-link">Cancel</a>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-send me-1"></i> Submit Redo Order
          </button>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
(function () {
  const radios    = Array.from(document.querySelectorAll('.js-reason'));
  const otherText = document.getElementById('reasonAlt');
  const products  = Array.from(document.querySelectorAll('.js-product'));
  const form      = document.querySelector('form[action*="redo"]');

  function currentReason() {
    const r = radios.find(x => x.checked);
    return r ? r.value : '';
  }

  function refreshOthers() {
    const isOthers = currentReason().toLowerCase() === 'others';
    if (isOthers) otherText?.setAttribute('required','required');
    else          otherText?.removeAttribute('required');
  }

  radios.forEach(r => r.addEventListener('change', refreshOthers));
  refreshOthers();

  function ensureOneProductSelected() {
    const any = products.some(p => p.checked);
    if (!any && products.length) {
      products[0].setAttribute('required', 'required');
    } else {
      products.forEach(p => p.removeAttribute('required'));
    }
    return any;
  }
  products.forEach(p => p.addEventListener('change', ensureOneProductSelected));

  form.addEventListener('submit', function (e) {
    refreshOthers();
    if (!ensureOneProductSelected()) {
      products[0].reportValidity?.();
      e.preventDefault();
    }
  });
})();
</script>
@endsection
