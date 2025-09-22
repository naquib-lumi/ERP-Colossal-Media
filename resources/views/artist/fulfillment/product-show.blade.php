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

  .remark-pill{
    border:1px solid #edf0f4;
    border-radius:.6rem;
    padding:.6rem .8rem;
    background:#fff;
  }
  .remark-pill .op{ font-weight:600; margin-right:.25rem }
  .remark-pill .txt{ color:#111827 }
</style>
@endpush

@section('content')
<div class="container py-4">

  {{-- Header --}}
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Product Details - {{ $productCode }}</h4>
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
                @elseif($key==='installation')<i class="bx bx-box fs-4 text-muted"></i>
                @else                       <i class="bx bx-car fs-4 text-muted"></i>
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
              <h6 class="mb-3">Attachment from Lead</h6>

              @forelse($leadAttachments as $f)
                <div class="d-flex align-items-center justify-content-between border rounded p-2 mb-2">
                  <div>
                    <i class="bx bx-file me-2"></i>
                    <a href="{{ $f->url }}" target="_blank" download class="text-decoration-none">
                      {{ $f->name }}
                    </a>
                    @if($f->size)
                      <span class="text-muted ms-2">({{ number_format($f->size/1024, 1) }} KB)</span>
                    @endif
                  </div>
                </div>
              @empty
                <div class="text-muted">No lead attachments.</div>
              @endforelse
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
          <div>{{ $productCode }}: {{ $product->productName ?? '-' }}</div>
          <div class="ms-auto key">Qty: <span class="fw-semibold">{{ $product->totalQuantity ?? 0 }}</span></div>
        </div>
        <div class="key mt-2">Material / Remark: <span class="fw-semibold">{{ $product->materialRemark ?? '-' }}</span></div>
      </div>

      {{-- Items --}}
      @foreach($product->items as $it)
        <div class="card mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-semibold">Item {{ $loop->iteration }} — {{ $it->itemName ?? 'Item' }}</div>
                <div class="text-muted small">Qty: {{ (int)($it->quantity ?? 0) }}</div>
              </div>
            </div>

            <div class="row g-3 mt-2">
              {{-- Size + unit --}}
              <div class="col-md-3">
                <small class="text-muted d-block">Size</small>
                <div class="fw-medium">
                  {{ rtrim((string)$it->sizeWidth) }} × {{ rtrim((string)$it->sizeHeight) }}
                  {{ $it->sizeUnit ?? '' }}
                </div>
              </div>

              {{-- Bleed (Top / Right / Bottom / Left) + unit --}}
              <div class="col-md-5">
                <small class="text-muted d-block">Bleed</small>
                @php
                  $bu = $it->bleedUnit ?: ($it->sizeUnit ?? '');
                  $fmt = fn($v) => $v === null ? '—' : rtrim((string)$v);
                @endphp
                <div class="fw-medium">
                  Top: {{ $fmt($it->bleedTop) }} {{ $bu }} &nbsp; |
                  Right: {{ $fmt($it->bleedRight) }} {{ $bu }} &nbsp; |
                  Bottom: {{ $fmt($it->bleedBottom) }} {{ $bu }} &nbsp; |
                  Left: {{ $fmt($it->bleedLeft) }} {{ $bu }}
                </div>
              </div>

              {{-- Prime Centre --}}
              <div class="col-md-6">
                <small class="text-muted d-block">Prime Centre</small>
                <div class="fw-medium">
                  {{ ((int)($it->prime_centre ?? 0) === 1) ? 'Yes' : 'No' }}
                </div>
              </div>

              <div class="col-md-12">
                @php
                  // $it is the current item
                  $mat = $it->material ?? null;

                  // Normalize to a string
                  if ($mat instanceof \Illuminate\Support\Collection) {
                      $mat = $mat->toArray();
                  }

                  if (is_array($mat)) {
                      $materialText = implode(', ', array_filter($mat, fn($v) => $v !== '' && $v !== null));
                  } elseif (is_string($mat)) {
                      // handle JSON-in-string case
                      $decoded = json_decode($mat, true);
                      if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                          $materialText = implode(', ', array_filter($decoded, fn($v) => $v !== '' && $v !== null));
                      } else {
                          $materialText = trim($mat);
                      }
                  } else {
                      $materialText = '';
                  }
                @endphp

                @if($materialText !== '')
                  <small class="text-muted d-block">Material</small>
                  <div class="fw-medium mb-2">{{ $materialText }}</div>
                @elseif($materialText == '')
                  <small class="text-muted d-block">Material</small>
                  <div class="fw-medium mb-2">—</div>
                @endif

                @if($it->spec)
                  <div class="row g-3">
                    <div class="col-md-4">
                      <small class="text-muted d-block">Lamination</small>
                      <div class="fw-medium">{{ $it->spec->lamination ?? '—' }}</div>
                    </div>
                    <div class="col-md-4">
                      <small class="text-muted d-block">Printer</small>
                      <div class="fw-medium">{{ $it->spec->printer ?? '—' }}</div>
                    </div>
                    <div class="col-md-4">
                      <small class="text-muted d-block">Cutter</small>
                      <div class="fw-medium">{{ $it->spec->cutter ?? '—' }}</div>
                    </div>
                  </div>
                @endif
              </div>
            </div>
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

    @php
      $label = ['printing'=>'Printing','furnishing'=>'Furnishing','installation'=>'Installation','delivery'=>'Delivery'];
    @endphp
    @forelse($product->remarks as $r)
      <div class="mb-2 border rounded p-2 bg-white">
        @if(!empty($r->operation))
          <span class="text-muted me-2">{{ $label[$r->operation] ?? ucfirst($r->operation) }}:</span>
        @endif
        {{ $r->remark }}
      </div>
    @empty
      <div class="text-muted">No product remarks.</div>
    @endforelse
  </div>
</div>

  {{-- Attachments --}}
  <div class="card mt-4 mb-4">
    <div class="card-body">
      <h6 class="mb-3">Attachments</h6>

      @forelse($orderFiles as $f)
        <div class="d-flex align-items-center justify-content-between border rounded p-2 mb-2">
          <div>
            <i class="bx bx-file me-2"></i>
            <span class="fw-semibold">{{ $f['name'] }}</span>
            <small class="text-muted ms-2">.{{ $f['ext'] }}</small>
          </div>
          <div class="d-flex gap-2">
            <a href="{{ $f['url'] }}" class="btn btn-sm btn-outline-secondary" target="_blank">Open</a>
            <a href="{{ $f['url'] }}" class="btn btn-sm btn-dark" download>Download</a>
          </div>
        </div>
      @empty
        <div class="text-muted">No attachments uploaded for this order.</div>
      @endforelse
    </div>
  </div>

  <div class="text-end">
    <a href="{{ url()->previous() }}" class="btn btn-secondary">Close</a>
  </div>
</div>
@endsection
