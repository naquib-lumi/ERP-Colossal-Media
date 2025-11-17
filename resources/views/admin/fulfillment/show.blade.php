{{-- resources/views/admin/fulfillment/show.blade.php --}}
@extends('layouts.app')

@section('title', 'Product Details')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
  /* Card look in the screenshot */
  .soft-card{border:1px solid #edf0f4;border-radius:14px;background:#fff}
  .soft-card .card-body{padding:20px}
  .muted{color:#6b7280}

  /* Progress cards */
  .progress-grid{row-gap:18px}
  .progress-card{border:1px solid #eef1f4;border-radius:14px;padding:18px 16px;background:#fff}
  .progress-head{display:flex;align-items:center;gap:10px;margin-bottom:6px}
  .progress-title{font-weight:600}
  .progress-badge{margin-left:auto;border-radius:999px;font-weight:600;font-size:.75rem;padding:.26rem .6rem}
  .progress-badge.in_progress{background:#eef2ff;color:#4338ca}
  .progress-badge.pending{background:#fff7ed;color:#b45309}
  .progress-badge.completed{background:#ecfdf5;color:#047857}
  .progress-badge.rejected{background:#fef2f2;color:#b91c1c}
  .progress-rows{font-size:.925rem}
  .progress-rows div{line-height:1.45}

  /* Order summary (two columns like screenshot) */
  .summary-grid{row-gap:16px}
  .summary-key{color:#6b7280;font-size:.95rem;margin-bottom:2px}
  .summary-val{font-weight:600}

  .mini-card{border:1px solid #edf0f4;border-radius:.75rem;padding:.9rem 1rem;background:#fff}
  .badge-soft{border-radius:999px;padding:.28rem .6rem;font-weight:600;font-size:.74rem}
  .badge-completed{background:#ecfdf5;color:#047857}
  .badge-progress{background:#eef2ff;color:#4338ca}
  .badge-pending{background:#fff7ed;color:#b45309}
  .badge-rejected{background:#fef2f2;color:#b91c1c}
  .key{color:#6b7280;font-size:.85rem}
  .remarks-list .list-group-item{border:1px solid #eef0f3;border-radius:12px!important;padding:.85rem 1rem;margin-bottom:.5rem}
  .remarks-op{min-width:210px;text-align:center;font-weight:600;border-radius:999px;padding:.4rem .75rem}
  .remarks-op.printing{background:#EEF2FF;color:#4F46E5}
  .remarks-op.furnishing{background:#FFF7ED;color:#C2410C}
  .remarks-op.installation{background:#ECFEFF;color:#0E7490}
  .remarks-op.courier{background:#ECFDF5;color:#047857}
  .remarks-op.self_pickup{background:#F3F4F6;color:#111827}
  .remarks-op.artist{background:#eaecf9;color:#8295fd}
</style>
@endpush

@section('content')
@php
  // Small helpers
  $fmtDate = fn($d) => $d ? \Carbon\Carbon::parse($d)->format('Y-m-d') : '—';

  $stageMeta = [
    'printing'     => ['title' => 'Printing',                'icon' => 'bi-printer'],
    'furnishing'   => ['title' => 'Furnishing',              'icon' => 'bi-tools'],
    'delivery'     => ['title' => 'Delivery',                'icon' => 'bi-truck'],
    'installation' => ['title' => 'Delivery & Installation', 'icon' => 'bi-wrench'],
  ];
  $badgeFor = function ($status) {
    return match ($status) {
      'completed'   => 'badge-completed',
      'in_progress' => 'badge-progress',
      'rejected'    => 'badge-rejected',
      ''            => 'd-none',     // blank pill when stage is intentionally hidden
      default       => 'badge-pending',
    };
  };
@endphp

<div class="container py-4">
  {{-- Header --}}
  <div class="d-flex align-items-center mb-3">
    <a href="{{ route('admin.fulfillment') }}"
       class="text-decoration-none text-muted me-3"
       style="display:inline-flex;align-items:center;gap:8px;">
      <i class="bi bi-arrow-left-circle fw-semibold"
         style="font-size:1.4rem;font-weight:600;color:#6c757d;"></i>
    </a>
    <h4 class="mb-0">
      Product Details — <span class="text-muted">{{ $productCode }}</span>
    </h4>
  </div>

  {{-- Fulfillment Progress (mirrors artist view: fork + blanking already computed in controller) --}}
  <div class="soft-card mb-3">
    <div class="card-body">
      <div class="row progress-grid">
        @foreach (['printing','furnishing','delivery','installation'] as $stage)
          @php
            $row   = $progress[$stage] ?? ['status'=>'pending','accepted_at'=>null,'completed_at'=>null,'duration'=>null];
            $icon  = ['printing'=>'bi-printer','furnishing'=>'bi-tools','delivery'=>'bi-truck','installation'=>'bi-wrench'][$stage];
            $title = ['printing'=>'Printing','furnishing'=>'Furnishing','delivery'=>'Dispatch Control','installation'=>'Delivery & Installation'][$stage];
            $pill  = $row['status'] ?: 'pending';
          @endphp
          <div class="col-12 col-md-6 col-xl-3">
            <div class="progress-card">
              <div class="progress-head">
                <i class="bi {{ $icon }} text-muted fs-5"></i>
                <div class="progress-title">{{ $title }}</div>
                <span class="progress-badge {{ strtolower($pill) }}">{{ str_replace('_',' ', $pill) }}</span>
              </div>
              <div class="progress-rows muted">
                <div>Accepted: {{ $row['accepted_at'] ? \Carbon\Carbon::parse($row['accepted_at'])->format('Y-m-d') : '—' }}</div>
                <div>Completed: {{ $row['completed_at'] ? \Carbon\Carbon::parse($row['completed_at'])->format('Y-m-d') : '—' }}</div>
                <div>Duration: {{ $row['duration'] ?? '—' }}</div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>

  {{-- Order Summary --}}
  <div class="soft-card mb-3">
  <div class="card-body">
    <div class="fw-semibold mb-2">Job Order Information</div>
    <div class="row summary-grid">
      {{-- LEFT COLUMN --}}
      <div class="col-md-6">
        <div class="summary-key">Job Title</div>
        <div class="summary-val mb-3">{{ $order->orderTitle ?? '-' }}</div>

        <div class="summary-key">Company Name</div>
        <div class="summary-val mb-3">{{ $order->companyName ?? '-' }}</div>

        <div class="summary-key">Created Date</div>
        <div class="summary-val mb-3">
          {{ ($order->created_at ?? null) ? \Carbon\Carbon::parse($order->created_at)->format('Y-m-d') : '—' }}
        </div>

        <div class="summary-key">Attachment from Lead</div>
        @if(($leadAttachments ?? collect())->isNotEmpty())
          <div class="summary-val">
            @foreach($leadAttachments as $f)
              <div class="mb-1"><a href="{{ $f->url }}" target="_blank">{{ $f->name }}</a></div>
            @endforeach
          </div>
        @else
          <div class="muted">No lead attachments.</div>
        @endif
      </div>

      {{-- RIGHT COLUMN --}}
      <div class="col-md-6">
        <div class="summary-key">Created By</div>
        <div class="summary-val mb-3">{{ optional($order->salesperson)->name ?? '-' }}</div>

        <div class="summary-key">Design Confirmation Required</div>
        <div class="summary-val mb-3">{{ ($order->approval ?? 0) ? 'Yes' : 'No' }}</div>

        <div class="summary-key">Deadline</div>
        <div class="summary-val mb-3">
          {{ ($order->deadline ?? null) ? \Carbon\Carbon::parse($order->deadline)->format('Y-m-d') : '—' }}
        </div>
      </div>
    </div>
  </div>
</div>

  {{-- Product & Breakdown Details --}}
  <div class="card mb-4">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="mb-0">Product & Breakdown Details</h6>
        <div class="d-flex gap-2">
          @if(optional($order->artist)->name)
            <span class="badge bg-light text-muted fw-semibold px-3 py-2">
              {{ $order->artist->name }}
            </span>
          @endif
          @if(!empty($order->dataEntry) && !empty($order->dataEntry->name))
            <span class="badge fw-semibold px-3 py-2" style="background:#E0F7FF;color:#00AEEF;">
              {{ $order->dataEntry->name }}
            </span>
          @endif
        </div>
      </div>

      <div class="border rounded p-3 mb-3">
        <div class="d-flex flex-wrap gap-3 align-items-center">
          <div class="fw-semibold">Product</div>
          <div>{{ $productCode }}: {{ $product->productName ?? '-' }}</div>
          <div class="ms-auto key">Qty:
            <span class="fw-semibold">{{ (int)($product->totalQuantity ?? 0) }}</span>
          </div>
        </div>
        <div class="key mt-2">Material / Remark:
          <span class="fw-semibold">{{ $product->materialRemark ?? '-' }}</span>
        </div>
      </div>

      {{-- Items list --}}
      @forelse($product->items as $it)
        @php
          // size text
          $size = null;
          if ($it->sizeWidth !== null || $it->sizeHeight !== null) {
            $fmtn = fn($n)=> rtrim(rtrim(number_format((float)$n,2,'.',''), '0'),'.');
            $size = $fmtn($it->sizeWidth).' × '.$fmtn($it->sizeHeight).' '.($it->sizeUnit ?? '');
          }
          // bleed text
          $bleed = null;
          if ($it->bleedTop !== null || $it->bleedRight !== null || $it->bleedBottom !== null || $it->bleedLeft !== null) {
            $fmtn = fn($n)=> rtrim(rtrim(number_format((float)$n,2,'.',''), '0'),'.');
            $bleed = $fmtn($it->bleedTop).'/'.$fmtn($it->bleedBottom).'/'.$fmtn($it->bleedLeft).'/'.$fmtn($it->bleedRight)
                   .' '.($it->bleedUnit ?? $it->sizeUnit ?? '');
          }
          // material (json/array/plain)
          $materialText = '';
          $mat = $it->material ?? null;
          if ($mat instanceof \Illuminate\Support\Collection) $mat = $mat->toArray();
          if (is_array($mat)) {
            $materialText = implode(', ', array_filter($mat, fn($v)=>$v!=='' && $v!==null));
          } elseif (is_string($mat)) {
            $decoded = json_decode($mat, true);
            $materialText = (json_last_error()===JSON_ERROR_NONE && is_array($decoded))
              ? implode(', ', array_filter($decoded, fn($v)=>$v!=='' && $v!==null))
              : trim($mat);
          }
        @endphp

        <div class="card mb-3">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-semibold">Item {{ $loop->iteration }} — {{ $it->itemName ?? 'Item' }}</div>
                <div class="text-muted small">Qty: {{ (int)($it->quantity ?? 0) }}</div>
              </div>
            </div>

            <div class="row g-3 mt-2">
              <div class="col-md-2">
                <small class="text-muted d-block">Size</small>
                <div class="fw-medium">{{ $size ?: '—' }}</div>
              </div>
              <div class="col-md-2">
                <small class="text-muted d-block">Bleed (T/B/L/R)</small>
                <div class="fw-medium">{{ $bleed ?: '—' }}</div>
              </div>
              <div class="col-md-2">
                <small class="text-muted d-block">Prime Centre</small>
                <div class="fw-medium">{{ ((int)($it->prime_centre ?? 0) === 1) ? 'Yes' : 'No' }}</div>
              </div>
              <div class="col-md-2">
                <small class="text-muted d-block">Assemble</small>
                <div class="fw-medium">
                  {{ filled($it->finishing ?? null) ? $it->finishing : '—' }}
                </div>
              </div>

              <div class="col-md-2">
                <small class="text-muted d-block">Material</small>
                <div class="fw-medium mb-2">{{ $materialText !== '' ? $materialText : '—' }}</div>
              </div>

              <div class="col-md-12">
                

                <div class="row g-3">
                  <div class="col-md-4">
                    <small class="text-muted d-block">Lamination</small>
                    <div class="fw-medium">{{ optional($it->spec)->lamination ?: '—' }}</div>
                  </div>
                  <div class="col-md-4">
                    <small class="text-muted d-block">Printer</small>
                    <div class="fw-medium">{{ optional($it->spec)->printer ?: '—' }}</div>
                  </div>
                  <div class="col-md-4">
                    <small class="text-muted d-block">Cutter</small>
                    <div class="fw-medium">{{ optional($it->spec)->cutter ?: '—' }}</div>
                  </div>
                </div>
              </div>
            </div>

          </div>
        </div>
      @empty
        <p class="text-muted mb-0">No items.</p>
      @endforelse
    </div>
  </div>

  {{-- Deliveries --}}
  @php
    $methodLabel = function ($m) {
      $m = strtolower(trim((string)$m));
      if ($m === 'courier') return 'Courier';
      if (in_array($m, ['self pickup','self_pickup','pickup'], true)) return 'Self Pickup';
      if ($m === 'delivery_installation' || $m === 'installation' || str_contains($m,'install')) {
        return 'Delivery & Installation';
      }
      return '—';
    };
  @endphp
  <div class="card mb-4">
  <div class="card-body">
    <div class="d-flex align-items-center mb-2">
      {{-- Left: title --}}
      <div class="fw-semibold me-auto">Deliveries</div>

      {{-- Right: qty summary + Edit button --}}
      <div class="d-flex align-items-center text-muted small">
        {{-- Qty summary --}}
        <div>
          <strong>Total:</strong>
          <span id="qtyTotal">{{ (int)($product->totalQuantity ?? 0) }}</span>
        </div>
        <div class="ms-3">
          <strong>Delivery Plan:</strong>
          <span id="qtyPlanned">0</span>
        </div>
        <div class="ms-3">
          <strong>Remaining:</strong>
          <span id="qtyRemaining">{{ (int)($product->totalQuantity ?? 0) }}</span>
        </div>
      </div>
      @php
          $isCompleted = strtolower((string) $product->status) === 'completed';
      @endphp
      {{-- Edit + Add Row buttons --}}
      <div class="ms-3 d-flex align-items-center gap-2">
        @if(!$isCompleted)
        <button type="button" class="btn btn-sm btn-primary" id="btnEdit">
          <i class="bi bi-pencil-square me-1"></i> Edit
        </button>

        {{-- Shown only in edit mode via JS --}}
        <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="btnAddRow">
          <i class="bi bi-plus-circle me-1"></i> Add Delivery
        </button>
        @endif
      </div>

    </div>

    <form action="{{ route('admin.fulfillment.deliveries.update', $product->ProductID ?? $productId ?? $order->id) }}"
          method="POST" id="deliveriesForm" class="d-none">
      @csrf
      @method('PUT')

      <div class="table-responsive delivery-row">
        
        <table class="table table-sm align-middle">
          <thead>
          <tr class="text-muted">
            <th style="width:130px;">Date</th>
            <th style="width:90px;">Time</th>
            <th>Location</th>
            <th style="width:220px;">Method</th>
            <th style="width:170px;">Qty</th>
            <th style="width:160px;">Install Type</th>
            <th style="width:140px;">Outsource Cost (RM)</th>
            <!-- <th style="width:60px;"></th> -->
          </tr>
          </thead>
          
          <tbody id="editTbody">
          @foreach($deliveries as $d)
          <tr>
            <td>
              <input type="date"
                    name="rows[{{ $d->BreakdownID }}][date]"
                    class="form-control form-control-sm js-date"
                    value="{{ $d->date ? \Carbon\Carbon::parse($d->date)->format('Y-m-d') : '' }}">
              <input type="hidden" name="rows[{{ $d->BreakdownID }}][id]" value="{{ $d->BreakdownID }}">
            </td>
            <td>
              <input type="time" name="rows[{{ $d->BreakdownID }}][time]"
                    class="form-control form-control-sm"
                    value="{{ $d->time ? \Carbon\Carbon::parse($d->time)->format('H:i') : '' }}">
            </td>
            <td>
              <input type="text" name="rows[{{ $d->BreakdownID }}][location]"
                    class="form-control form-control-sm" value="{{ $d->location }}">
            </td>

            {{-- NEW: METHOD SELECT --}}
            @php
              $canon = strtolower(trim((string)($d->method ?? '')));
              if (in_array($canon, ['self_pickup','pickup'])) $canon = 'self pickup';
              elseif ($canon === 'installation') $canon = 'delivery_installation';
            @endphp
            <td>
              <select name="rows[{{ $d->BreakdownID }}][method]"
                      class="form-select form-select-sm js-method delivery-method" required>
                <option value="">—</option>
                <option value="delivery_installation" {{ $canon==='delivery_installation' ? 'selected' : '' }}>Delivery &amp; Installation</option>
                <option value="courier" {{ $canon==='courier' ? 'selected' : '' }}>Courier</option>
                <option value="self pickup" {{ $canon==='self pickup' ? 'selected' : '' }}>Self Pickup</option>
              </select>
            </td>

            <td style="max-width:100px;">
              <input type="number"
                    name="rows[{{ $d->BreakdownID }}][quantity]"
                    class="form-control form-control-sm qty-input delivery-qty"
                    value="{{ (int)($d->quantity ?? 0) }}"
                    min="1"
                    step="1"
                    inputmode="numeric"
                    onkeydown="return !['e','E','+','-','.'].includes(event.key)" required>
            </td>

            {{-- Install fields (will be disabled unless method == delivery_installation) --}}
            <td>
              @php
                // normalize DB value to our three-option set
                $type = strtolower(trim((string)($d->deliver_install_type ?? '')));
                if (!in_array($type, ['in-house','outsource','both'], true)) {
                    $type = ''; // unrecognized -> empty
                }
              @endphp
              <select name="rows[{{ $d->BreakdownID }}][deliver_install_type]"
                      class="form-select form-select-sm js-install-type">
                <option value="">—</option>
                <option value="in-house"  {{ $type==='in-house'  ? 'selected' : '' }}>In House</option>
                <option value="outsource" {{ $type==='outsource' ? 'selected' : '' }}>Outsource</option>
                <option value="both"      {{ $type==='both'      ? 'selected' : '' }}>Both</option>
              </select>
            </td>

            {{-- Outsource Cost (enabled only if method=DI and type is outsource/both) --}}
            <td>
              <input type="number" min="0" step="0.01"
                    name="rows[{{ $d->BreakdownID }}][outsource_cost]"
                    class="form-control form-control-sm js-install-cost"
                    value="{{ !is_null($d->outsource_cost) ? (float)$d->outsource_cost : '' }}">
            </td>

            <!-- <td class="text-center">
              <button type="button" class="btn btn-sm btn-link text-danger btnDeleteRow">
                <i class="bi bi-trash"></i>
              </button>
              <input type="hidden" name="rows[{{ $d->BreakdownID }}][_delete]" value="0" />
            </td> -->
          </tr>
          @endforeach
          </tbody>
        </table>

        {{-- client-side total qty error --}}
        <div id="qtyError" class="text-danger small d-none mb-2" style="margin-top: 10px;">
          Delivery Plan cannot exceed product total quantity.
        </div>

        {{-- server-side error from controller --}}
        @error('rows')
          <div class="alert alert-danger py-2 px-3 mb-2">{{ $message }}</div>
        @enderror

        {{-- client-side total qty error --}}
        <div id="qtyError" class="text-danger small d-none mb-2">
          Total delivery quantity (<span class="js-qty-cur">0</span>)
          cannot exceed product quantity (<span class="js-qty-max">{{ (int)($product->totalQuantity ?? 0) }}</span>).
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-2">
        <button type="button" class="btn btn-light" id="btnCancel">Cancel</button>
        <button type="submit" id="btnSaveDeliveries" class="btn btn-success btn-sm">
          <i class="bi bi-check-lg me-1"></i> Save
        </button>
      </div>
    </form>

    {{-- Read-only table (default view) --}}
    <div id="deliveriesReadonly">
      @if($deliveries->isEmpty())
        <div class="text-muted">No delivery breakdowns.</div>
      @else
        <div class="table-responsive">
          <table class="table table-sm table-striped align-middle">
            <thead>
              <tr>
                <th style="width:130px;">DATE</th>
                <th style="width:90px;">TIME</th>
                <th>LOCATION</th>
                <th style="width:220px;">METHOD</th>   
                <th style="width:170px;">QTY</th>
                <th style="width:160px;">INSTALL TYPE</th>
                <th style="width:140px;">OUTSOURCE COST (RM)</th>
              </tr>
            </thead>
            <tbody>
            @foreach($deliveries as $d)
              <tr>
                <td>{{ $d->date ? \Carbon\Carbon::parse($d->date)->format('Y-m-d') : '—' }}</td>
                <td>{{ $d->time ? \Carbon\Carbon::parse($d->time)->format('H:i') : '—' }}</td>
                <td>{{ $d->location ?: '—' }}</td>
                <td>{{ $methodLabel($d->method ?? '') }}</td>
                <td>{{ (int)($d->quantity ?? 0) }}</td>
                @php
                  $type = strtolower((string)($d->deliver_install_type ?? ''));
                  $typeLabel = match ($type) {
                    'in-house'  => 'In House',
                    'outsource' => 'Outsource',
                    'both'      => 'Both',
                    default     => '—',
                  };
                @endphp
                <td>{{ $typeLabel }}</td>
                <td>{{ !is_null($d->outsource_cost) ? number_format((float)$d->outsource_cost,2) : '—' }}</td>
              </tr>
            @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>
  </div>
</div>

  {{-- Product Remarks (with author) --}}
  <div class="card mb-4">
    <div class="card-body pb-2">
      <h6 class="mb-3">Product Remarks</h6>
      @php
        $label = [
          'printing'     => 'To Printing',
          'furnishing'   => 'To Furnishing',
          'installation' => 'To Delivery & Installation',
          'courier'      => 'To Courier',
          'self_pickup'  => 'To Self Pickup',
          'artist'       => 'To Artist',
        ];
      @endphp

      <ul class="list-group list-group-flush remarks-list">
        @forelse ($product->remarks as $r)
          @php
            $op        = strtolower((string)($r->operation ?? ''));
            $author    = optional($r->user)->name ?? '—';
            $timestamp = $r->created_at ? \Carbon\Carbon::parse($r->created_at)->format('Y-m-d') : '';
          @endphp
          <li class="list-group-item">
            <div class="d-flex align-items-start gap-3">
              <span class="remarks-op {{ $op }}">{{ $label[$op] ?? ucfirst($op ?: 'Note') }}</span>
              <div class="flex-grow-1">
                <div>{{ $r->remark ?? '—' }}</div>
                <div class="text-muted small mt-1">
                  by <span class="fw-semibold">{{ $author }}</span>
                  @if($timestamp) • <span>{{ $timestamp }}</span>@endif
                </div>
              </div>
            </div>
          </li>
        @empty
          <li class="list-group-item text-muted">No product remarks.</li>
        @endforelse
      </ul>
    </div>
  </div>

  {{-- Attachments --}}
  <div class="row">
    
      <div class="card mb-4">
        <div class="card-body">
          <div class="fw-semibold mb-2">Order Files</div>
          @forelse($orderFiles as $f)
            <div class="d-flex align-items-center justify-content-between border rounded p-2 mb-2">
              <div>
                <i class="bi bi-paperclip me-2"></i>
                <a href="{{ $f['url'] }}" target="_blank">{{ $f['name'] }}</a>
              </div>
              <a href="{{ $f['url'] }}" class="btn btn-sm btn-outline-secondary" download>Download</a>
            </div>
          @empty
            <div class="text-muted">No files.</div>
          @endforelse
        </div>
      </div>
  </div>

  <div class="text-end">
    <a href="{{ route('admin.fulfillment') }}" class="btn btn-secondary">Close</a>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function(){
  const btnEdit    = document.getElementById('btnEdit');
  const btnCancel  = document.getElementById('btnCancel');
  const form       = document.getElementById('deliveriesForm');
  const ro         = document.getElementById('deliveriesReadonly');
  const btnAddRow  = document.getElementById('btnAddRow');
  const tbody      = document.getElementById('editTbody');

  let newIndex = 0;

  function toggleEdit(on){
    form.classList.toggle('d-none', !on);
    ro.classList.toggle('d-none', on);
    btnAddRow.classList.toggle('d-none', !on);
    btnEdit.classList.toggle('d-none', on);
  }

  btnEdit?.addEventListener('click', () => toggleEdit(true));
  btnCancel?.addEventListener('click', () => toggleEdit(false));

  btnAddRow?.addEventListener('click', () => {
    const key = 'new_' + (++newIndex);
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>
        <input type="date" class="form-control form-control-sm js-date" name="rows[${key}][date]">
      </td>
      <td>
        <input type="time" class="form-control form-control-sm" name="rows[${key}][time]">
      </td>
      <td>
        <input type="text" class="form-control form-control-sm" name="rows[${key}][location]">
      </td>
      <td>
        <select name="rows[${key}][method]" class="form-select form-select-sm js-method" required>
          <option value="">—</option>
          <option value="delivery_installation">Delivery &amp; Installation</option>
          <option value="courier">Courier</option>
          <option value="self pickup">Self Pickup</option>
        </select>
      </td>
      <td style="max-width:100px;">
        <input type="number"
              class="form-control form-control-sm qty-input"
              name="rows[${key}][quantity]"
              min="1"
              step="1"
              inputmode="numeric"
              onkeydown="return !['e','E','+','-','.'].includes(event.key)" required>
      </td>
      <td>
        <select name="rows[${key}][deliver_install_type]" class="form-select form-select-sm js-install-type">
          <option value="">—</option>
          <option value="in-house">In House</option>
          <option value="outsource">Outsource</option>
          <option value="both">Both</option>
        </select>
      </td>
      <td>
        <input type="number" min="0" step="0.01"
              name="rows[${key}][outsource_cost]"
              class="form-control form-control-sm js-install-cost">
      </td>
      <td class="text-center">
        <button type="button" class="btn btn-sm btn-link text-danger btnDeleteRow">
          <i class="bi bi-trash"></i>
        </button>
        <input type="hidden" name="rows[${key}][_delete]" value="0" />
      </td>
    `;
    tbody.appendChild(tr);

    // ====== NEW LOGIC (reuse your existing behaviour, just extended) ======
    const methodSelect = tr.querySelector('.js-method');
    if (!methodSelect) return;

    // read current methods from existing rows (excluding this new one if empty)
    const methods = Array.from(document.querySelectorAll('#editTbody .js-method'))
      .map(s => s.value)
      .filter(v => v); // remove empty

    const hasCourierOrPickup = methods.some(v => v === 'courier' || v === 'self pickup');
    const hasDI              = methods.some(v => v === 'delivery_installation');

    // 1) Only Courier / Self Pickup so far -> block Delivery & Installation
    if (hasCourierOrPickup && !hasDI) {
      const optDI = methodSelect.querySelector('option[value="delivery_installation"]');
      if (optDI) {
        optDI.disabled = true;
        optDI.hidden   = true;
      }
    }
    // 2) Only Delivery & Installation so far -> block Courier & Self Pickup
    else if (!hasCourierOrPickup && hasDI) {
      ['courier', 'self pickup'].forEach(val => {
        const opt = methodSelect.querySelector(`option[value="${val}"]`);
        if (opt) {
          opt.disabled = true;
          opt.hidden   = true;
        }
      });
    }
    // case 3: mix of both -> nothing blocked (do nothing)

    // keep your existing install toggle behaviour
    toggleInstallFields(tr);
  });

  tbody?.addEventListener('click', (e) => {
    const btn = e.target.closest('.btnDeleteRow');
    if (!btn) return;
    const tr = btn.closest('tr');
    const delInput = tr.querySelector('input[name$="[_delete]"]');
    // existing row -> mark delete + hide; new row -> just remove
    if (delInput) {
      delInput.value = '1';
      tr.style.display = 'none';
    } else {
      tr.remove();
    }
  });

  function toggleInstallFields(tr) {
    const methodSel = tr.querySelector('.js-method');
    const typeInput = tr.querySelector('.js-install-type');
    const costInput = tr.querySelector('.js-install-cost');
    if (!methodSel || !typeInput || !costInput) return;

    const isDI = (methodSel.value === 'delivery_installation');
    typeInput.disabled = !isDI;
    costInput.disabled = !isDI;

    if (!isDI) { // clear if not Delivery & Installation
      typeInput.value = '';
      costInput.value = '';
    }
  }

  function applyMethodRules() {
  const rows = document.querySelectorAll('#editTbody tr');

  let hasCourierSelf = false;
  let hasDI = false;

  // detect what we already have (ignoring deleted rows)
  rows.forEach(tr => {
    const delFlag = tr.querySelector('input[name$="[_delete]"]');
    if (delFlag && delFlag.value === '1') return;

    const sel = tr.querySelector('.js-method');
    if (!sel) return;

    const val = (sel.value || '').toLowerCase();

    if (val === 'courier' || val === 'self pickup') {
      hasCourierSelf = true;
    }
    if (val === 'delivery_installation') {
      hasDI = true;
    }
  });

  // apply rules to every select
  rows.forEach(tr => {
    const sel = tr.querySelector('.js-method');
    if (!sel) return;

    const optDI      = sel.querySelector('option[value="delivery_installation"]');
    const optCourier = sel.querySelector('option[value="courier"]');
    const optPickup  = sel.querySelector('option[value="self pickup"]');

    // reset (so we don't permanently lock options when pattern changes)
    [optDI, optCourier, optPickup].forEach(opt => {
      if (!opt) return;
      opt.disabled = false;
      opt.hidden   = false;
      opt.title    = '';
    });

    // CASE 1: only Courier/Self-pickup so far -> block DI
    if (hasCourierSelf && !hasDI) {
      if (optDI) {
        optDI.disabled = true;
        optDI.hidden   = true;
        optDI.title    = 'Disabled: existing deliveries are Courier / Self Pickup only';

        if (sel.value === 'delivery_installation') {
          sel.value = '';
          sel.dispatchEvent(new Event('change', { bubbles: true }));
        }
      }
    }
    // CASE 2: only Delivery & Installation so far -> block Courier + Self Pickup
    else if (hasDI && !hasCourierSelf) {
      [optCourier, optPickup].forEach(opt => {
        if (!opt) return;
        opt.disabled = true;
        opt.hidden   = true;
        opt.title    = 'Disabled: existing deliveries are Delivery & Installation only';

        if (sel.value === opt.value) {
          sel.value = '';
          sel.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });
    }
    // CASE 3: mix of both -> nothing blocked
  });
}

  // expose for other script block
  window.applyDeliveryMethodRules = applyMethodRules;

  // initialize states for existing rows
  document.querySelectorAll('#editTbody tr').forEach(toggleInstallFields);
  applyMethodRules();

  // react to method changes
  document.getElementById('editTbody')?.addEventListener('change', (e) => {
    if (e.target.classList.contains('js-method')) {
      toggleInstallFields(e.target.closest('tr'));
      applyMethodRules();
    }
  });

  // when adding new row, initialize its install fields too
  btnAddRow?.addEventListener('click', () => {
    setTimeout(() => {
      const trs = document.querySelectorAll('#editTbody tr');
      if (trs.length) toggleInstallFields(trs[trs.length - 1]);
    }, 0);
  });
})();

(function(){
  const tbody   = document.getElementById('editTbody');
  const form    = document.getElementById('deliveriesForm'); // your edit form
  const maxQty  = {{ (int)($product->totalQuantity ?? 0) }};
  const errBox  = document.getElementById('qtyError');
  // const curSpan = document.querySelector('#qtyError .js-qty-cur');
  const totalSpan  = document.getElementById('qtyTotal');
  const planSpan   = document.getElementById('qtyPlanned');
  const remSpan    = document.getElementById('qtyRemaining');
  const saveBtn = document.getElementById('btnSaveDeliveries');

  let hadInvalid = false;

  function recalcTotal() {
    let total = 0;
    document.querySelectorAll('#editTbody tr').forEach(tr => {
      const delFlag = tr.querySelector('input[name$="[_delete]"]');
      const deleting = delFlag && delFlag.value === '1';
      if (deleting) return;

      const q = tr.querySelector('input[name$="[quantity]"]');
      const v = parseInt(q?.value || '0', 10);
      if (!isNaN(v)) total += v;
    });

    // curSpan.textContent = total;

    // const invalid = total > maxQty;
    // errBox.classList.toggle('d-none', !invalid);
    // if (saveBtn) saveBtn.disabled = invalid;

    // Update summary
    if (planSpan) planSpan.textContent = total;
    if (remSpan)  remSpan.textContent  = Math.max(maxQty - total, 0);
    if (totalSpan) totalSpan.textContent = maxQty; // static, but safe

    const invalid = total > maxQty;

    if (errBox) errBox.classList.toggle('d-none', !invalid);
    if (saveBtn) saveBtn.disabled = invalid;

    // Pop SweetAlert the first time it becomes invalid
    if (invalid && !hadInvalid) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: 'Quantity exceeded',
          text: `Delivery Plan (${total}) cannot exceed product total quantity (${maxQty}).`
        });
      } else {
        alert(`Delivery Plan (${total}) cannot exceed product total quantity (${maxQty}).`);
      }
    }
    hadInvalid = invalid;

    return total;
  }

  // Install-type / cost helpers (kept from your original code)
  function isDeliveryInstall(tr){
    const m = tr.querySelector('.js-method')?.value || '';
    return m === 'delivery_installation';
  }
  function costAllowed(tr){
    const type = (tr.querySelector('.js-install-type')?.value || '').toLowerCase();
    return isDeliveryInstall(tr) && (type === 'outsource' || type === 'both');
  }
  function syncInstallControls(tr){
    const costInput = tr.querySelector('.js-install-cost');
    const typeSel   = tr.querySelector('.js-install-type');
    if (!costInput || !typeSel) return;

    const enableCost = costAllowed(tr);
    costInput.disabled = !enableCost;
    if (!enableCost) costInput.value = '';
  }

  // Initialize all rows
  document.querySelectorAll('#editTbody tr').forEach(tr => {
    syncInstallControls(tr);
  });

  // Qty changes & delete flag changes recalc total
  tbody?.addEventListener('input', (e) => {
    if (e.target.name?.endsWith('[quantity]')) recalcTotal();
  });
  tbody?.addEventListener('change', (e) => {
    if (e.target.name?.endsWith('[_delete]')) {
      recalcTotal();
    }
    if (e.target.classList.contains('js-method') ||
        e.target.classList.contains('js-install-type')){
      syncInstallControls(e.target.closest('tr'));

      // re-apply global method rule if exposed
      if (window.applyDeliveryMethodRules) {
        window.applyDeliveryMethodRules();
      }
    }
  });

  // Initial run
  recalcTotal();
  if (window.applyDeliveryMethodRules) {
    window.applyDeliveryMethodRules();
  }

  // Submit guard – block submit if still exceeded
  form?.addEventListener('submit', function(e){
    const total = recalcTotal();
    if (total > maxQty) {
      e.preventDefault();
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: 'Quantity exceeded',
          text: `Delivery Plan (${total}) cannot exceed product total quantity (${maxQty}).`
        });
      } else {
        alert(`Delivery Plan (${total}) cannot exceed product total quantity (${maxQty}).`);
      }
    }
  });

  function isDeliveryInstall(tr){
    const m = tr.querySelector('.js-method')?.value || '';
    return m === 'delivery_installation';
  }
  function costAllowed(tr){
    const type = (tr.querySelector('.js-install-type')?.value || '').toLowerCase();
    return isDeliveryInstall(tr) && (type === 'outsource' || type === 'both');
  }
  function syncInstallControls(tr){
    const costInput = tr.querySelector('.js-install-cost');
    const typeSel   = tr.querySelector('.js-install-type');

    // type can always change; cost only enabled when allowed
    const enableCost = costAllowed(tr);
    costInput.disabled = !enableCost;
    if (!enableCost) costInput.value = '';
  }

  // initialize all rows
  document.querySelectorAll('#editTbody tr').forEach(syncInstallControls);

  // react to method/type changes
  tbody?.addEventListener('change', (e) => {
    if (e.target.classList.contains('js-method') ||
        e.target.classList.contains('js-install-type')){
      syncInstallControls(e.target.closest('tr'));
    }
  });

  // when adding a row we call sync for the last row in your existing “Add Row” handler (as before)

  // client-side quantity guard
  form?.addEventListener('submit', function(e){
    let total = 0;
    document.querySelectorAll('#editTbody tr').forEach(tr => {
      const del = tr.querySelector('input[name$="[_delete]"]');
      const isDeleting = del && del.value === '1';
      if (isDeleting) return;

      const q = tr.querySelector('input[name$="[quantity]"]');
      const val = parseInt(q?.value || '0', 10);
      if (!isNaN(val)) total += val;
    });

    if (total > maxQty) {
      e.preventDefault();
      alert(`Total delivery quantity (${total}) cannot exceed product quantity (${maxQty}).`);
    }
  });
})();

$(document).on('input', '.qty-input', function () {
  // keep only digits
  let v = this.value.replace(/\D+/g, '');

  // if it starts with zero and has more than one digit, remove leading zeros
  if (v.length > 1) v = v.replace(/^0+/, '');

  this.value = v;
});

(function () {
  // Get today's date in YYYY-MM-DD
  const today = new Date();
  const yyyyMmDd = today.toISOString().slice(0, 10);

  function applyDateLimit(input) {
    if (!input) return;

    // Set min for the UI (calendar will disable past days)
    input.setAttribute('min', yyyyMmDd);

    // If user somehow types an earlier date, snap it back to today
    input.addEventListener('change', function () {
      if (this.value && this.value < yyyyMmDd) {
        this.value = yyyyMmDd;
      }
    });
  }

  // apply to existing date inputs
  document.querySelectorAll('.js-date').forEach(applyDateLimit);

  // when new rows are added, re-apply
  const tbody = document.getElementById('editTbody');
  const observer = new MutationObserver(() => {
    document.querySelectorAll('.js-date').forEach(applyDateLimit);
  });
  if (tbody) {
    observer.observe(tbody, { childList: true, subtree: true });
  }
})();

// Validate Delivery Form Before Submit (Frontend)
// document.addEventListener("click", function (e) {
//     if (e.target.closest("#btnSaveDeliveries")) {

//         let hasError = false;
//         let errorMsg = "";

//         document.querySelectorAll(".delivery-row").forEach(function (row) {

//             const method = row.querySelector(".delivery-method")?.value?.trim();
//             const qty     = row.querySelector(".delivery-qty")?.value;

//             if (!method) {
//                 hasError = true;
//                 errorMsg = "Method is required for all delivery rows.";
//             }

//             if (!qty || qty <= 0) {
//                 hasError = true;
//                 errorMsg = "Quantity must be entered and greater than 0.";
//             }
//         });

//         if (hasError) {
//             e.preventDefault();
//             Swal.fire({
//                 icon: "warning",
//                 title: "Missing Required Fields",
//                 text: errorMsg,
//                 confirmButtonColor: "#7367F0"
//             });
//             return false;
//         }

//         return true;
//     }
// });
</script>
@endpush