@extends('layouts.app')

@section('content')
<div class="container-fluid">

  <h4 class="mb-3">Report Issue – Redo Job Order <span class="text-muted">{{ $order->order_number }}</span></h4>
  <div class="card mb-6">
      <div class="card-body">
        <div class="fw-semibold mb-2">Current Order Summary</div>
        <div class="row g-3">
          <div class="col-md-3">
            <div class="text-muted small">Order ID</div>
            <div class="fw-semibold">{{ $order->order_number }}</div>
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
  <form method="POST" action="{{ route('artist.orders.redo.store', $order) }}">
    @csrf

    <div class="card mb-3">
      <div class="card-body">
        <div class="fw-semibold mb-2"><i class="ti ti-alert-triangle me-2"></i>Reason for Redo</div>

        <div class="row gy-2">
          @php
          $reasons = ['Wrong design received','Client changed requirements','Miscommunication on brief','Color error','Others'];
          $oldReason = old('reason');
          @endphp

          @foreach($reasons as $r)
            <div class="col-12">
              <label class="d-flex align-items-center gap-2">
                <input type="radio" name="reason" value="{{ $r }}" {{ $oldReason===$r ? 'checked' : '' }}>
                <span>{{ $r }}</span>
              </label>
            </div>
          @endforeach
        </div>

        <textarea class="form-control mt-3" rows="4" name="reason_alt" placeholder="Please specify…">{{ old('reason_alt') }}</textarea>

        <hr class="my-4">

        <div class="fw-semibold mb-2"><i class="ti ti-package-export me-2"></i>Products to copy</div>
        <div class="text-muted small mb-2">Select which products should be copied into the redo order. If you leave all unchecked, all products will be copied.</div>

        <div class="row g-2">
          @foreach($products as $p)
            <div class="col-md-6">
              <label class="card p-2 d-flex align-items-center justify-content-between">
                <div>
                  <div class="fw-semibold">{{ $p->productName }}</div>
                  <div class="text-muted small">Qty: {{ $p->totalQuantity }} · {{ ucfirst($p->taskType) }}</div>
                </div>
                <input type="checkbox" class="form-check-input" name="products[]" value="{{ $p->ProductID }}">
              </label>
            </div>
          @endforeach
        </div>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-body">
        <div class="fw-semibold mb-2"><i class="ti ti-send me-2"></i>Submit Redo Report</div>
        <div class="alert alert-light border">
          <div class="fw-semibold mb-1">What happens next?</div>
          <ul class="mb-0">
            <li>New Job Order will be auto-created with ID like: <code>{{ $order->order_number }}R</code></li>
            <li>Only selected products will be copied to the new order</li>
            <li>Status will be auto-set to <b>In Progress</b> for reassignment</li>
          </ul>
        </div>

        <div class="d-flex gap-2" style="justify-content: right;">
          <a href="{{ route('artist.orders') }}" class="btn btn-link">Cancel</a>
          <button type="submit" class="btn btn-primary">
            <i class="ti ti-send me-1"></i> Submit Redo Order
          </button>
        </div>
      </div>
    </div>
  </form>
</div>
@endsection
