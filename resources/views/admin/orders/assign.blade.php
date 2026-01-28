@extends('layouts.app') {{-- or your layout --}}

@section('content')
<div class="container-xxl">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0">Job Order Status – #ORD-{{ str_pad($order->id, 4, '0', STR_PAD_LEFT) }}</h3>
    <a href="{{ route('admin.orders') }}" class="btn btn-light">
      <i class="bx bx-chevron-left"></i> Back to Orders
    </a>
  </div>

  {{-- Lead / company / headline info --}}
  <div class="card mb-4">
    <div class="card-header">Lead Information</div>
    <div class="card-body row g-3">
      <div class="col-md-6">
        <div class="small text-muted">Company Name</div>
        <div class="fw-medium">{{ $order->companyName ?? '-' }}</div>
      </div>
      <div class="col-md-3">
        <div class="small text-muted">Phone</div>
        <div class="fw-medium">{{ $order->leadPhone ?? '-' }}</div>
      </div>
      <div class="col-md-3">
        <div class="small text-muted">Email</div>
        <div class="fw-medium">{{ $order->leadEmail ?? '-' }}</div>
      </div>
    </div>
  </div>

  {{-- Job details --}}
  <div class="card mb-4">
    <div class="card-header">Job Order Details</div>
    <div class="card-body row g-3">
      <div class="col-md-4">
        <div class="small text-muted">Job Title</div>
        <div class="fw-medium">{{ $order->orderTitle ?? '-' }}</div>
      </div>
      <div class="col-md-4">
        <div class="small text-muted">Created Date</div>
        <div class="fw-medium">{{ optional($order->created_at)->format('d/m/Y') }}</div>
      </div>
      <div class="col-md-4">
        <div class="small text-muted">Deadline</div>
        <div class="fw-medium">{{ \Carbon\Carbon::parse($order->deadline)->format('d/m/Y') }}</div>
      </div>

      <div class="col-md-4">
        <div class="small text-muted">Created By (Salesperson)</div>
        <div class="fw-medium">{{ optional($order->salesperson)->name ?? '-' }}</div>
      </div>
      <div class="col-md-4">
        <div class="small text-muted">Current Status</div>
        <span class="badge bg-secondary">{{ \Illuminate\Support\Str::of($order->orderStatus)->replace('_',' ')->title() }}</span>
      </div>
      <div class="col-md-4">
        <div class="small text-muted">Artist</div>
        <div class="fw-medium">{{ optional($order->artist)->name ?? '— (not assigned)' }}</div>
      </div>
    </div>
  </div>

  {{-- Product details (Accordion for multiple products) --}}
  <div class="card mb-4">
    <div class="card-header">Product Details</div>
    <div class="card-body">
      @php $products = $order->products ?? collect(); @endphp

      @if($products->isEmpty())
        <div class="text-muted">No product items.</div>
      @else
        <div class="accordion" id="prodAcc">
          @foreach($order->products as $idx => $p)
            <div class="accordion-item">
              <h2 class="accordion-header" id="ph{{ $idx }}">
                <button class="accordion-button {{ $idx ? 'collapsed' : '' }}" type="button"
                        data-bs-toggle="collapse" data-bs-target="#pc{{ $idx }}"
                        aria-expanded="{{ $idx ? 'false' : 'true' }}" aria-controls="pc{{ $idx }}">
                  Product #{{ $p->ProductID }} — {{ $p->productName ?? '-' }}
                </button>
              </h2>
              <div id="pc{{ $idx }}" class="accordion-collapse collapse {{ $idx ? '' : 'show' }}"
                  aria-labelledby="ph{{ $idx }}" data-bs-parent="#prodAcc">
                <div class="accordion-body">
                  <div class="mb-2">
                    <strong>Total Quantity:</strong> {{ $p->totalQuantity ?? '-' }}<br>
                    <strong>Material:</strong> {{ $p->materialRemark ?? '-' }}<br>
                    <strong>Remarks:</strong> {{ $p->productRemark ?? '-' }}
                  </div>

                  @php $breaks = $p->deliveryBreakdowns; @endphp
                  @if($breaks->count())
                    <div class="table-responsive">
                      <table class="table table-striped table-sm">
                        <thead>
                          <tr>
                            <th>Delivery Method</th>
                            <th>Quantity</th>
                            <th>Location</th>
                            <th>Date & Time</th>
                          </tr>
                        </thead>
                        <tbody>
                          @foreach($breaks as $d)
                            <tr>
                              <td>{{ $d->method }}</td>
                              <td>{{ $d->quantity }}</td>
                              <td>{{ $d->location }}</td>
                              <td>{{ optional($d->when)->format('M d, Y h:i A') }}</td>
                            </tr>
                          @endforeach
                        </tbody>
                      </table>
                    </div>
                  @else
                    <em>No delivery breakdowns for this product.</em>
                  @endif
                </div>
              </div>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  {{-- Assign artist --}}
  <div class="card">
    <div class="card-header">Assign Artist</div>
    <div class="card-body">
      <form method="POST" action="{{ route('admin.orders.assign.store', $order->id) }}">
        @csrf
        <div class="row g-3 align-items-end">
          <div class="col-md-6">
            <label class="form-label">Artist</label>
            <select name="artist_id" id="artistSelect" class="form-select" required>
              <option value="">Search or select artist…</option>
              @foreach($artists as $a)
                <option value="{{ $a->id }}">{{ $a->name }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-3">
            <button class="btn btn-primary">Assign Artist</button>
          </div>
        </div>
      </form>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
  // If you want live search with Select2 (optional):
  $('#artistSelect').select2({ placeholder:'Search artist...', width:'100%' });
</script>
@endpush
