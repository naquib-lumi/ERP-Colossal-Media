@extends('layouts.app')

@section('title', 'Product Details')

@push('styles')
<style>
  .mini-card {
    border: 1px solid #edf0f4; border-radius: .75rem; padding: .9rem 1rem; background: #fff;
  }
  .badge-soft { border-radius: 999px; padding: .28rem .6rem; font-weight: 600; font-size: .74rem }
  .badge-completed { background:#ecfdf5; color:#047857 }
  .badge-progress  { background:#eef2ff; color:#4338ca }
  .badge-pending   { background:#fff7ed; color:#b45309 }
  .badge-rejected  { background:#fef2f2; color:#b91c1c }
  .key { color:#6b7280; font-size:.85rem }
  .file-row { border:1px solid #edf0f4; border-radius:.75rem; padding:.75rem 1rem; background:#fff }
</style>
@endpush

@section('content')
<div class="container py-4">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Product Details - #{{ sprintf('ORD%03d-P%d', $order->id ?? 0, $product->ProductID) }}</h4>
    <a href="{{ route('artist.fulfillment.product.export', $product) }}" class="btn btn-dark">
      <i class="bx bx-printer me-1"></i> Export PDF
    </a>
  </div>

  {{-- Progress cards --}}
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-3">
        @php
          $pretty = ['printing'=>'Printing','furnishing'=>'Furnishing','installation'=>'Installation','delivery'=>'Delivery'];
        @endphp
        @foreach($progress as $key => $p)
          <div class="col-sm-6 col-lg-3">
            <div class="mini-card h-100">
              <div class="d-flex align-items-center gap-2 mb-2">
                @if($key==='printing')      <i class="bx bx-printer fs-4 text-muted"></i>
                @elseif($key==='furnishing')<i class="bx bx-wrench fs-4 text-muted"></i>
                @elseif($key==='installation')<i class="bx bx-hammer fs-4 text-muted"></i>
                @else                       <i class="bx bx-truck fs-4 text-muted"></i>
                @endif
                <div class="fw-semibold">{{ $pretty[$key] }}</div>
                <span class="ms-auto badge-soft
                  {{ $p['status']==='completed' ? 'badge-completed' :
                     ($p['status']==='in_progress' ? 'badge-progress' :
                      ($p['status']==='rejected' ? 'badge-rejected' : 'badge-pending')) }}">
                  {{ str_replace('_',' ', $p['status']) }}
                </span>
              </div>
              <div class="small">
                <div><span class="key">Accepted:</span> {{ $p['accepted_at'] ? \Carbon\Carbon::parse($p['accepted_at'])->format('Y-m-d') : '-' }}</div>
                <div><span class="key">Completed:</span> {{ $p['completed_at'] ? \Carbon\Carbon::parse($p['completed_at'])->format('Y-m-d') : '-' }}</div>
                <div><span class="key">Duration:</span> {{ $p['duration'] ?? '-' }}</div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- Job Order Information --}}
  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-start">
        <h6 class="mb-3">Job Order Information</h6>
        <span class="badge bg-light text-muted">{{ $order->order_number ?? $order->id }}</span>
      </div>
      <div class="row g-3">
        <div class="col-md-6">
          <div class="key">Job Title</div>
          <div class="fw-semibold">{{ $order->orderTitle ?? '-' }}</div>
        </div>
        <div class="col-md-6">
          <div class="key">Created By</div>
          <div class="fw-semibold">{{ $order->salesperson->name ?? '-' }}</div>
        </div>

        <div class="col-md-6">
          <div class="key">Company Name</div>
          <div class="fw-semibold">{{ $order->companyName ?? '-' }}</div>
        </div>
        <div class="col-md-6">
          <div class="key">Design Confirmation Required</div>
          <div class="fw-semibold">{{ ($order->approval ?? 0) ? 'Yes' : 'No' }}</div>
        </div>

        <div class="col-md-6">
          <div class="key">Created Date</div>
          <div class="fw-semibold">{{ optional($order->created_at)->format('Y-m-d') ?? '-' }}</div>
        </div>
        <div class="col-md-6">
          <div class="key">Deadline</div>
          <div class="fw-semibold">{{ optional($order->deadline)->format('Y-m-d') ?? '-' }}</div>
        </div>

        <div class="col-md-6">
          @php $leadFirst = $attachments[0] ?? null; @endphp
          @if($leadFirst)
            <div class="mt-2">
              <div class="text-muted small">Attachment from Lead</div>
              <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($leadFirst) }}"
                class="d-inline-flex align-items-center gap-2" target="_blank">
                <i class="bx bxs-file-pdf"></i>
                {{ basename($leadFirst) }}
              </a>
            </div>
          @else
            <div class="mt-2">
              <div class="text-muted small">Attachment from Lead</div>
              <span class="text-muted">-</span>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  {{-- Product & Breakdown Details --}}
  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Product & Breakdown Details</h6>
        <span class="badge bg-light text-muted">Artist {{ $order->artist->name ?? '-' }}</span>
      </div>

      <div class="border rounded p-3 mb-3">
        <div class="d-flex flex-wrap gap-3 align-items-center">
          <div class="fw-semibold">Product</div>
          <div>#ORD{{ $order->id ?? '—' }}-P{{ $product->ProductID }}: {{ $product->productName ?? '-' }}</div>
          <div class="ms-auto key">Qty: <span class="fw-semibold">{{ $product->totalQuantity ?? 0 }}</span></div>
        </div>
        <div class="key mt-2">Material / Remark: <span class="fw-semibold">{{ $product->materialRemark ?? '-' }}</span></div>
      </div>

      {{-- Items --}}
      @foreach($product->items as $i => $item)
        @php
          $spec = $item->spec;   // <-- SPEC FOR THIS ITEM ONLY
        @endphp

        <hr class="my-4">

        <div class="row">
          <div class="col-md-6">
            <div class="key">Item {{ $i+1 }}</div>
            <div class="fw-semibold">{{ $item->itemName ?? '-' }}</div>

            <div class="key mt-3">Quantity per Item</div>
            <div class="fw-semibold">{{ $item->quantity ?? '-' }}</div>

            <div class="key mt-3">Size</div>
            <div class="fw-semibold">
              {{ optional($item)->sizeWidth }} × {{ optional($item)->sizeHeight }}
              @if(!empty($item->sizeLength)) × {{ $item->sizeLength }} @endif
            </div>

            <div class="key mt-3">Bleed Size</div>
            <div class="fw-semibold">
              {{ $item->bleedTop ?? 0 }} × {{ $item->bleedLeft ?? 0 }}
            </div>

            <div class="key mt-3">Material</div>
            <div class="fw-semibold">
              @php
                $materials = is_array($item->material) ? $item->material : (array) $item->material;
              @endphp
              {{ count($materials) ? implode(', ', array_filter($materials)) : '-' }}
            </div>

            <div class="key mt-3">Finishing</div>
            <div class="fw-semibold">{{ $item->finishing ?: '-' }}</div>
          </div>

          <div class="col-md-6">
            <div class="key">Printer</div>
            <div class="fw-semibold">{{ $spec->printer ?? '-' }}</div>

            <div class="key mt-3">Cutter</div>
            <div class="fw-semibold">{{ $spec->cutter ?? '-' }}</div>

            <div class="key mt-3">Lamination</div>
            <div class="fw-semibold">{{ $spec->lamination ?? '-' }}</div>
          </div>
        </div>
      @endforeach
    </div>
  </div>

  {{-- Delivery Method Summary --}}
  <div class="card mb-3">
    <div class="card-body">
      <h6 class="mb-3">Delivery Method Summary</h6>

      @forelse($deliveries as $d)
        <div class="border rounded p-3 mb-3">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="fw-semibold">Delivery Method: {{ $d->method ?: '-' }}</div>
              <div class="key">Quantity:</div>
              <div class="fw-semibold">{{ $d->quantity ?? '-' }}</div>
            </div>
            <div class="col-md-6">
              <div class="key">Date &amp; Time:</div>
              <div class="fw-semibold">
                {{ $d->date ? \Carbon\Carbon::parse($d->date)->format('Y-m-d') : '-' }}
                @if($d->time) @endif
              </div>
            </div>
            <div class="col-12">
              <div class="key">Location:</div>
              <div class="fw-semibold">{{ $d->location ?: 'Not required for pickup' }}</div>
            </div>
          </div>
        </div>
      @empty
        <div class="text-muted">No delivery breakdowns.</div>
      @endforelse
    </div>
  </div>

  {{-- Product Remarks --}}
  <div class="card mb-3">
    <div class="card-body">
      <h6 class="mb-3">Product Remarks</h6>
      <div class="mb-2 border rounded p-2 bg-white">{{ $product->productRemark ?? '-' }}</div>
      @if(!empty($product->remark2))
        <div class="border rounded p-2 bg-white">{{ $product->remark2 }}</div>
      @endif
    </div>
  </div>

  {{-- Attachments --}}
  <div class="card mt-4 mb-4">
    <div class="card-body">
      <h6 class="mb-3">Attachments</h6>

      @forelse($attachments as $path)
        @php
          $name = basename($path);
          $url  = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
        @endphp
        <div class="d-flex align-items-center justify-content-between border rounded px-3 py-2 mb-2">
          <div class="d-flex align-items-center gap-2">
            <i class="bx bxs-file"></i>
            <span>{{ $name }}</span>
          </div>
          <a class="btn btn-sm btn-outline-secondary" href="{{ $url }}" target="_blank">
            <i class="bx bx-download me-1"></i> Download
          </a>
        </div>
      @empty
        <div class="text-muted">No attachments</div>
      @endforelse
    </div>
  </div>

  <div class="text-end">
    <a href="{{ url()->previous() }}" class="btn btn-secondary">Close</a>
  </div>
</div>
@endsection
