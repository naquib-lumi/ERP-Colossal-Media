@extends('layouts.app')

@section('title','New Job Order')

@section('content')
@push('styles')
<style>
  .remove-item {
    display: flex;
    align-items: center;
    justify-content: center;
    color: #dc3545 !important;
    margin-right: 20px;
    margin-top: -0.1rem;
  }

  .remove-item:hover {
    color: #a71d2a !important;
    display: block !important;
  }

  .item-actions {
    top: -0.25rem;
    z-index: 10;
    background: var(--bs-body-bg);
    padding: .25rem 0 .5rem;
  }

  [data-bs-toggle="collapse"][aria-expanded="true"] .bx-chevron-down {
    transform: rotate(180deg);
    transition: transform 0.2s ease;
  }

  [data-bs-toggle="collapse"] .bx-chevron-down {
    transition: transform 0.2s ease;
  }

  .delete-delivery i,
  .delete-item i {
    font-size: 1.25rem;
    line-height: 1;
    vertical-align: middle;
  }

  .delete-delivery,
  .delete-item {
    background: none;
    border: 0;
    padding: 0;
    cursor: pointer;
  }

  .attach-box {
    position: relative;
    border: 2px dashed #cbd5e1;
    border-radius: 10px;
    padding: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #fff;
    cursor: pointer
  }

  .attach-inner {
    text-align: center;
    pointer-events: none
  }

  .attach-icon {
    width: 42px;
    height: 42px;
    margin: 0 auto 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f1f5f9;
    border-radius: 8px;
    font-size: 20px
  }

  .attach-title {
    color: #475569;
    font-weight: 600
  }

  .attach-hint {
    color: #64748b;
    font-size: 12px
  }

  .file-overlay {
    position: absolute;
    inset: 0;
    opacity: 0;
    cursor: pointer
  }

  .remove-x {
    border: 0;
    background: none;
    color: #dc2626;
    font-weight: 700;
    cursor: pointer;
    margin-left: 8px
  }

  .remove-x:hover {
    color: #b91c1c
  }

  .ok {
    color: #15803d
  }

  .err {
    color: #b91c1c
  }

  .overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(184, 184, 184, .6);
    z-index: 1020;
    align-items: center;
    justify-content: center;
  }

  .overlay.is-open {
    display: flex;
  }

  .overlay-box {
    padding: 14px 18px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
  }
</style>

@endpush

@if (session('success'))
@push('scripts')
<script>
  Swal.fire({
    icon: 'success',
    title: 'Success',
    text: @json(session('success'))
  });
</script>
@endpush
@endif
@if (session('error'))
@push('scripts')
<script>
  Swal.fire({
    icon: 'error',
    title: 'Error',
    text: @json(session('error'))
  });
</script>
@endpush
@endif

{{-- Loading overlay --}}
<div id="loading-overlay" class="overlay">
  <div class="overlay-box">Saving… please wait</div>
</div>

{{-- Validation errors (client-side 422) --}}
<div id="form-errors" class="text-danger small mb-2"></div>

<form id="order-form" action="{{ route('artist.orders.update', $order) }}" method="POST" enctype="multipart/form-data">
  @csrf
  @method('PUT')

  <div class="row g-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-0">Artist Job Order — <span class="text-body-secondary">#{{ $orderCode }}</span></h5>
            <small class="text-body-secondary">Last updated: {{ $today }}</small>
          </div>
          <span class="badge bg-label-secondary">Artist</span>
        </div>

        <div class="card-body">
          {{-- === Job Order Information === --}}
          <div class="card mb-4">
            <div class="card-header">
              <h5 class="mb-0"><i class="bx bx-info-circle me-2"></i>Job Order Information</h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-12 col-md-4">
                  <label class="form-label">Company Name</label>
                  <input type="text" class="form-control"
                    value="{{ old('company_name', $order->companyName ?? '') }}" readonly>
                  <input type="hidden" name="company_name"
                    value="{{ old('company_name', $order->companyName ?? '') }}">
                </div>

                <div class="col-12 col-md-4">
                  <label class="form-label">Job Title</label>
                  <input type="text" class="form-control"
                    value="{{ old('order_title', $order->orderTitle ?? '') }}" readonly>
                  <input type="hidden" name="order_title"
                    value="{{ old('order_title', $order->orderTitle ?? '') }}">
                </div>

                <div class="col-12 col-md-4">
                  <label class="form-label">Created Date</label>
                  <input type="text" class="form-control"
                    value="{{ old('created_date', optional($order->created_at)->format('Y-m-d')) }}" readonly>
                  <input type="hidden" name="created_date"
                    value="{{ old('created_date', optional($order->created_at)->toDateString()) }}">
                </div>

                <div class="col-12 col-md-4">
                  <label class="form-label">Deadline</label>
                  <input type="text" class="form-control"
                    value="{{ old('deadline', optional($order->deadline)->format('Y-m-d')) }}" readonly>
                  <input type="hidden" name="deadline"
                    value="{{ old('deadline', optional($order->deadline)->toDateString()) }}">
                </div>

                <div class="col-12 col-md-4">
                  <label class="form-label">Design Confirmation Required?</label>
                  @php $dc = old('design_confirm', $order->design_confirm ?? null); @endphp
                  <select id="design_confirmed" name="design_confirmed" class="form-select">
                    <option value="1" {{ $order->approval ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ !$order->approval ? 'selected' : '' }}>No</option>
                  </select>
                </div>

                <div class="col-12 col-md-4">
                  <label class="form-label">Created By</label>
                  <input type="text" class="form-control" value="{{ $order->salesperson->name ?? $order->created_by_name ?? '-' }}" readonly>
                  <input type="hidden" name="created_by" value="{{ $order->salesperson_id ?? $order->created_by_id }}">
                </div>

                <div class="col-12">
                  <label class="form-label d-flex align-items-center gap-2">
                    <span>Attachments</span>
                    <span class="text-body-secondary small">(read-only here — upload at bottom section)</span>
                  </label>

                  @isset($order->attachments)
                  @if($order->attachments->count())
                  <div class="d-flex flex-wrap gap-2">
                    @foreach($order->attachments as $file)
                    <a href="{{ Storage::url($file->file_location ?? $file->path) }}" target="_blank"
                      class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center">
                      <i class="bx bx-file me-1"></i>
                      <span class="text-truncate" style="max-width:220px">
                        {{ $file->original_name ?? basename($file->file_location ?? $file->path) }}
                      </span>
                    </a>
                    @endforeach
                  </div>
                  @else
                  <div class="text-body-secondary">No attachments</div>
                  @endif
                  @endisset
                </div>
              </div>
            </div>
          </div>

          {{-- Product block --}}
          <div class="card mb-6">
            <div class="card-header">
              <h5 class="mb-0">
                <i class="bx bx-package me-2"></i>Product
              </h5>
            </div>

            <div class="card-body p-4">
              <div class="row g-3 mb-4">
                <div class="col-12 col-md-6 col-xl-3">
                  <label class="form-label">Product Name</label>
                  <input
                    name="product[name]"
                    type="text"
                    class="form-control"
                    placeholder="e.g. Business Card"
                    value="{{ old('product.name', $product->productName ?? '') }}">
                </div>

                <div class="col-12 col-md-6 col-xl-3">
                  <label class="form-label">Total Quantity</label>
                  <input
                    name="product[qty_total]"
                    type="number"
                    min="0"
                    class="form-control"
                    placeholder="1000"
                    value="{{ old('product.qty_total', $product->totalQuantity ?? '') }}">
                </div>

                <div class="col-12 col-md-6 col-xl-6">
                  <label class="form-label">Material / Remark</label>
                  <input
                    name="product[material]"
                    type="text"
                    class="form-control"
                    placeholder="Premium Paper, Glossy"
                    value="{{ old('product.material', $product->materialRemark ?? '') }}">
                </div>
              </div>

              {{-- Items repeater --}}
              @php
              // existing items from DB or from old() after validation errors
              $itemsData = old('items', $items);
              @endphp

              <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Items</h6>
                <button type="button" id="addItemBtn" class="btn btn-sm btn-outline-primary">
                  <i class="bx bx-plus me-1"></i> Add Item
                </button>
              </div>
              <div class="accordion" id="productItems" data-start-number="{{ (is_countable($itemsData)?count($itemsData):$itemsData->count()) + 1 }}" data-next-index="{{ is_countable($itemsData)?count($itemsData):$itemsData->count() }}">
                @foreach($items as $i => $it)
                  @php
                    $qtyLabel = data_get($it, 'quantity', data_get($it, 'qty'));
                    $spec = data_get($it, 'spec'); // hasOne (lamination/printer/cutter)
                    $materialVal = data_get($it, 'material');
                    if (is_array($materialVal)) {
                    $materialVal = implode(', ', $materialVal); // show JSON array nicely
                    }
                  @endphp

                  <div class="accordion-item mb-3 border rounded" id="item{{ $i }}" data-item-id="{{ data_get($it,'ItemID') }}">
                    <div class="accordion-header d-flex justify-content-between align-items-center px-3 py-2">
                      <div>
                        <span class="fw-semibold">Item {{ $i+1 }}</span>
                        <span class="text-body-secondary ms-2 small">
                          {{ data_get($it, 'itemName') }}
                          {{ data_get($it, 'quantity') ? ' ×'.data_get($it,'quantity') : '' }}
                        </span>
                      </div>

                      <div class="d-flex align-items-center gap-2">
                        {{-- Trash Icon --}}
                        <button type="button"
                                class="btn btn-link text-danger p-0"
                                title="Delete this item"
                                data-action="delete-item"
                                data-item-id="{{ data_get($it,'ItemID') }}"
                                data-url="{{ route('artist.orders.items.destroy', [$order, data_get($it,'ItemID') ?: 0]) }}">
                          <i class="bx bx-trash fs-5"></i>
                        </button>

                        {{-- Collapse Toggle Icon --}}
                        <button class="btn btn-link p-0"
                                type="button"
                                data-bs-toggle="collapse"
                                data-bs-target="#itemPane{{ $i }}"
                                aria-expanded="{{ $i === 0 ? 'true' : 'false' }}"
                                aria-controls="itemPane{{ $i }}">
                          <i class="bx bx-chevron-down fs-4"></i>
                        </button>
                      </div>
                    </div>

                    <div id="itemPane{{ $i }}" class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}" data-bs-parent="#productItems">
                      <div class="accordion-body">

                        <div class="row g-3">
                          <div class="col-md-6">
                            <label class="form-label">Item Name</label>
                            @php $v = old("items.$i.itemName"); @endphp
                            <input class="form-control" name="items[{{ $i }}][itemName]"
                              value="{{ filled($v) ? $v : (data_get($it,'itemName') ?? '') }}">
                          </div>

                          <div class="col-md-6">
                            <label class="form-label">Quantity</label>
                            @php $v = old("items.$i.quantity"); @endphp
                            <input type="number" class="form-control" name="items[{{ $i }}][quantity]"
                              value="{{ filled($v) ? $v : (data_get($it,'quantity') ?? '') }}">
                          </div>

                          <div class="col-12">
                            <label class="form-label">Material</label>
                            @php
                            $v = old("items.$i.material");
                            $materialVal = filled($v) ? $v : (data_get($it,'material') ?? '');
                            @endphp
                            <input type="text" class="form-control" name="items[{{ $i }}][material]"
                              value="{{ $materialVal }}">
                          </div>

                          <div class="col-12 col-md-4">
                            <label class="form-label">Size (inches) – Width</label>
                            <input name="items[{{ $i }}][sizeWidth]" type="number" step="0.01" class="form-control"
                              value="{{ old("items.$i.sizeWidth", data_get($it,'sizeWidth')) }}">
                          </div>
                          <div class="col-12 col-md-4">
                            <label class="form-label">Height</label>
                            <input name="items[{{ $i }}][sizeHeight]" type="number" step="0.01" class="form-control"
                              value="{{ old("items.$i.sizeHeight", data_get($it,'sizeHeight')) }}">
                          </div>
                          <div class="col-12 col-md-4">
                            <label class="form-label">Length</label>
                            <input name="items[{{ $i }}][sizeLength]" type="number" step="0.01" class="form-control"
                              value="{{ old("items.$i.sizeLength", data_get($it,'sizeLength')) }}">
                          </div>

                          <div class="col-12 col-md-3">
                            <label class="form-label">Bleed (Top)</label>
                            <input name="items[{{ $i }}][bleedTop]" type="number" step="0.01" class="form-control"
                              value="{{ old("items.$i.bleedTop", data_get($it,'bleedTop')) }}">
                          </div>
                          <div class="col-12 col-md-3">
                            <label class="form-label">Bottom</label>
                            <input name="items[{{ $i }}][bleedBottom]" type="number" step="0.01" class="form-control"
                              value="{{ old("items.$i.bleedBottom", data_get($it,'bleedBottom')) }}">
                          </div>
                          <div class="col-12 col-md-3">
                            <label class="form-label">Left</label>
                            <input name="items[{{ $i }}][bleedLeft]" type="number" step="0.01" class="form-control"
                              value="{{ old("items.$i.bleedLeft", data_get($it,'bleedLeft')) }}">
                          </div>
                          <div class="col-12 col-md-3">
                            <label class="form-label">Right</label>
                            <input name="items[{{ $i }}][bleedRight]" type="number" step="0.01" class="form-control"
                              value="{{ old("items.$i.bleedRight", data_get($it,'bleedRight')) }}">
                          </div>

                          <div class="col-md-3">
                            <label class="form-label">Lamination</label>
                            @php $lam = filled(old("items.$i.lamination"))
                            ? old("items.$i.lamination")
                            : data_get($it,'lamination'); @endphp
                            <select name="items[{{ $i }}][lamination]" class="form-select">
                              <option value="">-</option>
                              <option {{ $lam==='Gloss' ? 'selected' : '' }}>Gloss</option>
                              <option {{ $lam==='Matte' ? 'selected' : '' }}>Matte</option>
                            </select>
                          </div>

                          <div class="col-md-3">
                            <label class="form-label">Printer</label>
                            @php $prt = filled(old("items.$i.printer"))
                            ? old("items.$i.printer")
                            : data_get($it,'printer'); @endphp
                            <select name="items[{{ $i }}][printer]" class="form-select">
                              <option value="">-</option>
                              <option {{ $prt==='Printer' ? 'selected' : '' }}>Printer</option>
                            </select>
                          </div>

                          <div class="col-md-3">
                            <label class="form-label">Cutter</label>
                            @php $cut = filled(old("items.$i.cutter"))
                            ? old("items.$i.cutter")
                            : data_get($it,'cutter'); @endphp
                            <select name="items[{{ $i }}][cutter]" class="form-select">
                              <option value="">-</option>
                              <option {{ $cut==='Cutter' ? 'selected' : '' }}>Cutter</option>
                            </select>
                          </div>
                          <div class="col-md-12">
                            <label class="form-label">Finishing</label>
                            <input name="items[{{ $i }}][finishing]" type="text" class="form-control"
                              placeholder="Coating, lamination, etc…"
                              value="{{ old("items.$i.finishing", data_get($it,'finishing')) }}">
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                @endforeach
              </div>

              {{-- Template used for a new item (placeholders __i__ and __n__) --}}
              <template id="itemTemplate">
                <div class="accordion-item mb-3 border rounded" data-kind="item" id="itemWrap__INDEX__">
                  <div class="accordion-header d-flex align-items-center px-3 py-2" id="itemHdr__INDEX__">
                    <span class="fw-semibold">
                      Item <span class="item-number"></span>
                    </span>

                    <!-- actions on the far right -->
                    <div class="ms-auto d-flex align-items-center gap-2">
                      <!-- delete -->
                      <button type="button"
                        class="btn btn-link p-0 text-danger delete-item"
                        data-index="__INDEX__" title="Delete item">
                        <i class="bx bx-trash fs-5"></i>
                      </button>

                      <!-- chevron: only this toggles collapse -->
                      <button type="button"
                        class="btn btn-link p-0 chevron"
                        data-bs-toggle="collapse"
                        data-bs-target="#itemPane__INDEX__"
                        aria-controls="itemPane__INDEX__"
                        aria-expanded="false"
                        title="Expand/Collapse">
                        <i class="bx bx-chevron-down fs-4"></i>
                      </button>
                    </div>
                  </div>

                  <div id="itemPane__INDEX__"
                    class="accordion-collapse collapse show"
                    data-bs-parent="#productItems">
                    <div class="accordion-body">

                      <input type="hidden" name="items[__INDEX__][id]" value="">

                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label">Item Name</label>
                          <input type="text" class="form-control"
                            name="items[__INDEX__][itemName]" value="">
                        </div>

                        <div class="col-md-6">
                          <label class="form-label">Quantity</label>
                          <input type="number" class="form-control"
                            name="items[__INDEX__][quantity]" value="">
                        </div>

                        <div class="col-12">
                          <label class="form-label">Material</label>
                          <input type="text" class="form-control"
                            name="items[__INDEX__][material]" placeholder="Coating, lamination, etc…" value="">
                        </div>

                        <div class="col-12 col-md-4">
                          <label class="form-label">Size (inches) – Width</label>
                          <input name="items[__INDEX__][sizeWidth]" type="number" step="0.01" class="form-control" value="">
                        </div>
                        <div class="col-12 col-md-4">
                          <label class="form-label">Height</label>
                          <input name="items[__INDEX__][sizeHeight]" type="number" step="0.01" class="form-control" value="">
                        </div>
                        <div class="col-12 col-md-4">
                          <label class="form-label">Length</label>
                          <input name="items[__INDEX__][sizeLength]" type="number" step="0.01" class="form-control" value="">
                        </div>

                        <div class="col-12 col-md-3">
                          <label class="form-label">Bleed (Top)</label>
                          <input name="items[__INDEX__][bleedTop]" type="number" step="0.01" class="form-control"
                            value="">
                        </div>
                        <div class="col-12 col-md-3">
                          <label class="form-label">Bottom</label>
                          <input name="items[__INDEX__][bleedBottom]" type="number" step="0.01" class="form-control"
                            value="">
                        </div>
                        <div class="col-12 col-md-3">
                          <label class="form-label">Left</label>
                          <input name="items[__INDEX__][bleedLeft]" type="number" step="0.01" class="form-control"
                            value="">
                        </div>
                        <div class="col-12 col-md-3">
                          <label class="form-label">Right</label>
                          <input name="items[__INDEX__][bleedRight]" type="number" step="0.01" class="form-control"
                            value="">
                        </div>

                        <div class="col-md-3">
                          <label class="form-label">Lamination</label>
                          <select name="items[__INDEX__][lamination]" class="form-select">
                            <option value="">-</option>
                            <option>Gloss</option>
                            <option>Matte</option>
                          </select>
                        </div>
                        <div class="col-md-3">
                          <label class="form-label">Printer</label>
                          <select name="items[__INDEX__][printer]" class="form-select">
                            <option>Printer</option>
                          </select>
                        </div>
                        <div class="col-md-3">
                          <label class="form-label">Cutter</label>
                          <select name="items[__INDEX__][cutter]" class="form-select">
                            <option>Cutter</option>
                          </select>
                        </div>
                        <div class="col-md-12">
                          <label class="form-label">Finishing</label>
                          <input name="items[__INDEX__][finishing]" type="text" class="form-control" placeholder="Coating, lamination, etc…">
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </template>

              {{-- Delivery Breakdown (repeater) --}}
              <div class="d-flex align-items-center justify-content-between mt-4 mb-2">
                <h6 class="mb-0">Delivery Breakdown</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addDeliveryBtn">
                  <i class="bx bx-plus me-1"></i> Add Delivery Breakdown
                </button>
              </div>

              <div id="deliveriesWrap" class="vstack gap-3">
                <div class="card border shadow-none" data-delivery>
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <strong>Delivery <span class="delivery-index">1</span></strong>
                      <button type="button" class="btn btn-link p-0 text-danger delete-delivery" title="Delete delivery" data-remove>
                        <i class="bx bx-trash fs-5"></i>
                      </button>
                    </div>

                    <div class="row g-3">
                      <div class="col-12 col-md-3">
                        <label class="form-label">Delivery Method</label>
                        <select name="deliveries[0][method]" class="form-select">
                          <option value="">Method</option>
                          <option value="courier">Courier</option>
                          <option value="pickup">Pickup</option>
                          <option value="install">Install</option>
                        </select>
                      </div>

                      <div class="col-12 col-md-3">
                        <label class="form-label">Location Address</label>
                        <input name="deliveries[0][location]" type="text" class="form-control" placeholder="Location">
                      </div>

                      <div class="col-12 col-md-2">
                        <label class="form-label">Quantity</label>
                        <input name="deliveries[0][qty]" type="number" min="0" class="form-control" placeholder="Qty">
                      </div>

                      <div class="col-12 col-md-4">
                        <label class="form-label">Date & Time</label>
                        <input name="deliveries[0][datetime]" type="datetime-local" class="form-control">
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              {{-- Template used when clicking “Add Delivery Breakdown” --}}
              <template id="deliveryTemplate">
                <div class="card border shadow-none" data-delivery>
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <strong>Delivery <span class="delivery-index">__INDEX_HUMAN__</span></strong>
                      <button type="button" class="btn btn-link p-0 text-danger delete-delivery" title="Delete delivery" data-remove>
                        <i class="bx bx-trash fs-5"></i>
                      </button>
                    </div>

                    <div class="row g-3">
                      <div class="col-12 col-md-3">
                        <label class="form-label">Delivery Method</label>
                        <select name="deliveries[__INDEX__][method]" class="form-select">
                          <option value="">Method</option>
                          <option value="courier">Courier</option>
                          <option value="pickup">Pickup</option>
                          <option value="install">Install</option>
                        </select>
                      </div>

                      <div class="col-12 col-md-3">
                        <label class="form-label">Location Address</label>
                        <input name="deliveries[__INDEX__][location]" type="text" class="form-control" placeholder="Location">
                      </div>

                      <div class="col-12 col-md-2">
                        <label class="form-label">Quantity</label>
                        <input name="deliveries[__INDEX__][qty]" type="number" min="0" class="form-control" placeholder="Qty">
                      </div>

                      <div class="col-12 col-md-4">
                        <label class="form-label">Date & Time</label>
                        <input name="deliveries[__INDEX__][datetime]" type="datetime-local" class="form-control">
                      </div>
                    </div>
                  </div>
                </div>
              </template>

              {{-- Product Remarks --}}
              <div class="mt-4">
                <h6 class="mb-2">Product Remarks</h6>
                <textarea
                  name="product[remarks]"
                  rows="3"
                  class="form-control"
                  placeholder="Client requested matte finish on cover page. Ensure color matching with Pantone 286C.">{{ old('product.remarks', $product->productRemark ?? '') }}</textarea>
              </div>

            </div>
          </div>

          {{-- Attachments (bottom) --}}
          <div class="card mt-4">
            <div class="card-header">
              <h5 class="card-title mb-0">Attachments</h5>
            </div>

            <div class="card-body">
              <div id="attach-box" class="attach-box">
                <div class="attach-inner">
                  <div class="attach-icon" aria-hidden="true"><i class="bx bx-upload display-6 mb-2 d-block justify-content-between align-items-center" style="pointer-events:none"></i></div>
                  <div class="attach-title">Drop files here or click to upload</div>
                  <div class="attach-hint">(PDF, images, docs, xlsx, ppt.)</div>
                </div>

                <!-- This input sits on top, invisible, and owns the click -->
                <input id="fileInput" type="file" multiple
                  accept=".pdf,.png,.jpg,.jpeg,.webp,.doc,.docx,.xlsx,.xls,.ppt,.pptx"
                  class="file-overlay">
              </div>

              <div id="attach-msg" class="mt-2 text-sm"></div>
              <ul id="preview" class="mt-3 space-y-2"></ul>
            </div>
          </div>

          <div id="form-errors" class="mt-3 text-red-600 text-sm"></div>

        </div>
      </div>

    </div>

    {{-- Sticky save bar --}}
    <div class="col-12">
      <div class="bg-body position-sticky bottom-0 border-top py-3 d-flex gap-2 justify-content-end" style="z-index: 10">
        <button type="button" class="btn btn-outline-secondary" onclick="history.back()">Cancel</button>
        <button type="submit" name="is_draft" value="1" class="btn btn-secondary">Save Draft</button>
        <button type="submit" name="is_draft" value="0" class="btn btn-primary">Save & Submit</button>
      </div>
    </div>
  </div>
</form>

{{-- Hidden templates for clones --}}
<template id="itemTemplate">
  <div class="card border shadow-none" data-item>
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <strong>Item <span class="item-index">X</span></strong>
        <button type="button" class="btn btn-sm btn-text text-danger" data-remove><i class="bx bx-trash"></i></button>
      </div>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Item Name</label>
          <input name="items[IDX][name]" type="text" class="form-control" placeholder="Item name">
        </div>
        <div class="col-md-2">
          <label class="form-label">Quantity</label>
          <input name="items[IDX][qty]" type="number" min="0" class="form-control" placeholder="Qty">
        </div>
        <div class="col-md-6">
          <label class="form-label">Material</label>
          <input name="items[IDX][material]" type="text" class="form-control" placeholder="Add material…">
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label">Size (inches) – Width</label>
          <input name="items[IDX][size][w]" type="text" class="form-control">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label">Height</label>
          <input name="items[IDX][size][h]" type="text" class="form-control">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label">Length</label>
          <input name="items[IDX][size][l]" type="text" class="form-control">
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label">Bleed (Top)</label>
          <input name="items[IDX][bleed][top]" type="text" class="form-control">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label">Bottom</label>
          <input name="items[IDX][bleed][bottom]" type="text" class="form-control">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label">Left</label>
          <input name="items[IDX][bleed][left]" type="text" class="form-control">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label">Right</label>
          <input name="items[IDX][bleed][right]" type="text" class="form-control">
        </div>

        <div class="col-md-3">
          <label class="form-label">Lamination</label>
          <select name="items[IDX][lamination]" class="form-select">
            <option value="">-</option>
            <option>Gloss</option>
            <option>Matte</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Printer</label>
          <select name="items[IDX][printer]" class="form-select">
            <option>Printer</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Cutter</label>
          <select name="items[IDX][cutter]" class="form-select">
            <option>Cutter</option>
          </select>
        </div>
        <div class="col-md-12">
          <label class="form-label">Finishing</label>
          <input name="items[IDX][finishing]" type="text" class="form-control" placeholder="Coating, lamination, etc…">
        </div>
      </div>
    </div>
  </div>
</template>

<template id="deliveryTemplate">
  <div class="card border shadow-none" data-delivery>
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <strong>Delivery <span class="delivery-index">X</span></strong>
        <button type="button" class="btn btn-sm btn-text text-danger" data-remove><i class="bx bx-trash"></i></button>
      </div>
      <div class="row g-3">
        <div class="col-12 col-md-3">
          <label class="form-label">Delivery Method</label>
          <select name="deliveries[IDX][method]" class="form-select">
            <option value="">Method</option>
            <option value="courier">Courier</option>
            <option value="pickup">Pickup</option>
            <option value="install">Install</option>
          </select>
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label">Location Address</label>
          <input name="deliveries[IDX][location]" type="text" class="form-control" placeholder="Location">
        </div>
        <div class="col-12 col-md-2">
          <label class="form-label">Quantity</label>
          <input name="deliveries[IDX][qty]" type="number" min="0" class="form-control" placeholder="Qty">
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label">Date & Time</label>
          <input name="deliveries[IDX][datetime]" type="datetime-local" class="form-control">
        </div>
      </div>
    </div>
  </div>
</template>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
  window.CSRF_TOKEN = @json(csrf_token());
  (function() {
    // add item --------------------------------------------------------------------------------------------
    function openOnly(id) {
      // id like '#itemPane3'
      document.querySelectorAll('#productItems .accordion-collapse.show')
        .forEach(el => new bootstrap.Collapse(el, {
          toggle: false
        }).hide());
      new bootstrap.Collapse(document.querySelector(id), {
        toggle: true
      }).show();
    }

    const container = document.getElementById('productItems');
    const addBtn = document.getElementById('addItemBtn');
    const tplEl = document.getElementById('itemTemplate');

    // read the starting display number and next array index from data-attrs
    const startNumber = parseInt(container?.dataset.startNumber ?? '1', 10);

    // seed nextIndex from data-next-index, else fall back to current count
    let nextIndex = parseInt(container?.dataset.nextIndex ??
      container.querySelectorAll('.accordion-item[data-kind="item"]').length, 10);

    function addItem() {
      const raw = tplEl.innerHTML;
      const idx = nextIndex++;
      const html = raw.replace(/__INDEX__/g, idx);
      const frag = document.createRange().createContextualFragment(html);
      container.appendChild(frag);
      renumberAndLockFirst();
    }

    function renumberAndLockFirst() {
      const items = [...container.querySelectorAll('.accordion-item[data-kind="item"]')];
      items.forEach((wrap, i) => {
        // keep the display numbering using your startNumber
        wrap.querySelector('.item-number').textContent = startNumber + i;

        const del = wrap.querySelector('.delete-item');
        if (del) del.classList.remove('d-none');
      });
    }

    // Keep header mini summary (name • qty) updated
    function updateSummary(wrap) {
      const name = wrap.querySelector('input[name^="items"][name$="[name]"]')?.value || '';
      const qty = wrap.querySelector('input[name^="items"][name$="[qty]"]')?.value || '';
      wrap.querySelector('.item-summary').textContent = name + (qty ? ` • ${qty}` : '');
    }

    // Delegated events for delete, chevron, and summary update
    container.addEventListener('click', (e) => {
      // Delete
      const delBtn = e.target.closest('.delete-item');
      if (delBtn) {
        const wrap = delBtn.closest('.accordion-item');
        if (wrap) {
          wrap.remove();
          renumberAndLockFirst();
        }
        e.preventDefault();
        e.stopPropagation();
        return;
      }

      // Chevron is handled by Bootstrap via data-attrs.
      // We only stop it from bubbling in case the header has listeners.
      const chev = e.target.closest('.chevron');
      if (chev) {
        e.stopPropagation();
      }
    });

    container.addEventListener('input', (e) => {
      const wrap = e.target.closest('.accordion-item[data-kind="item"]');
      if (wrap) updateSummary(wrap);
    });

    // Add item
    if (addBtn) addBtn.addEventListener('click', addItem);

    // Initialize summaries & first-item trash hide for server-rendered items
    renumberAndLockFirst();
    container.querySelectorAll('.accordion-item[data-kind="item"]').forEach(updateSummary);

    // delivery breakdown ----------------------------------------------------------------------------------
    const wrap = document.getElementById('deliveriesWrap');
    const addDeliveryBtn = document.getElementById('addDeliveryBtn');
    const tpl = document.getElementById('deliveryTemplate');

    function reindexDeliveries() {
      wrap.querySelectorAll('[data-delivery]').forEach((card, i) => {
        // Update the visible number
        const numEl = card.querySelector('.delivery-index');
        if (numEl) numEl.textContent = i + 1;

        // Fix names: deliveries[<i>][...]
        card.querySelectorAll('[name]').forEach((el) => {
          el.name = el.name.replace(/\[deliveries\]\[\d+\]|\[deliveries\]\[__INDEX__\]/g, ''); // safety if pasted differently
          el.name = el.name.replace(/\[?\bdeliveries\b\]?\[\d+\]/, 'deliveries[' + i + ']')
            .replace(/\[\d+\]/, '[' + i + ']');
          // More robust: always rewrite first index occurrence
          el.name = el.name.replace(/deliveries\[\d+\]/, 'deliveries[' + i + ']');
        });
      });
    }

    function addDelivery() {
      const index = wrap.querySelectorAll('[data-delivery]').length;
      const html = tpl.innerHTML
        .replace(/__INDEX__/g, index)
        .replace(/__INDEX_HUMAN__/g, index + 1);

      const temp = document.createElement('div');
      temp.innerHTML = html.trim();
      const node = temp.firstElementChild;

      wrap.appendChild(node);
      reindexDeliveries();
    }

    // Add delivery
    addDeliveryBtn.addEventListener('click', addDelivery);

    // Remove delivery (event delegation)
    wrap.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-remove]');
      if (!btn) return;

      const card = btn.closest('[data-delivery]');
      if (card) {
        card.remove();
        reindexDeliveries();
      }
    });

    function renumberItems() {
      // Update the "Item N" labels after a removal
      const items = container.querySelectorAll('.accordion-item');
      items.forEach((el, idx) => {
        const title = el.querySelector('.fw-semibold');
        if (title) title.textContent = `Item ${idx + 1}`;
      });
    }

    async function deleteItemOnServer(url) {
      const res = await fetch(url, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': window.CSRF_TOKEN,
          'Accept': 'application/json'
        }
      });
      if (!res.ok) {
        const txt = await res.text().catch(() => '');
        throw new Error(`HTTP ${res.status}: ${txt || 'Delete failed'}`);
      }
      const data = await res.json();
      if (!data?.ok) throw new Error('Delete failed');
      return true;
    }

    document.addEventListener('click', async (e) => {
      const btn = e.target.closest('[data-action="delete-item"]');
      if (!btn) return;

      const itemEl = btn.closest('.accordion-item');
      if (!itemEl) return;

      const itemId = btn.dataset.itemId; // may be "" for unsaved items
      const url    = btn.dataset.url;

      // Confirm
      if (window.Swal) {
        const c = await Swal.fire({
          icon: 'warning',
          title: 'Delete this item?',
          text: 'This cannot be undone.',
          showCancelButton: true,
          confirmButtonText: 'Delete',
          confirmButtonColor: '#d33'
        });
        if (!c.isConfirmed) return;
      } else if (!confirm('Delete this item?')) {
        return;
      }

      // If no ItemID (unsaved), just remove the block client-side
      try {
        if (!itemId) {
          itemEl.remove();
          renumberItems();
          return;
        }

        // Existing item → call server
        await deleteItemOnServer(url);

        // Remove from DOM
        itemEl.remove();
        renumberItems();

        // Optional toast
        if (window.Swal) {
          Swal.fire({icon:'success', title:'Item deleted', timer:1200, showConfirmButton:false});
        }
      } catch (err) {
        console.error(err);
        if (window.Swal) {
          Swal.fire({icon:'error', title:'Delete failed', text:String(err)});
        } else {
          alert('Delete failed: ' + err);
        }
      }
    });
  })();

  // upload attachemnt -------------------------------------------------------
  document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('fileInput');
    const listEl = document.getElementById('preview');
    const msgEl = document.getElementById('attach-msg');

    const ALLOWED = ['pdf', 'png', 'jpg', 'jpeg', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

    const selected = new Map();

    input.addEventListener('change', () => {
      if (!input.files?.length) return;
      const incoming = Array.from(input.files);

      incoming.forEach(f => {
        const ext = (f.name.split('.').pop() || '').toLowerCase();
        const key = `${f.name}|${f.size}|${f.lastModified}`;

        const errors = [];
        if (!ALLOWED.includes(ext)) errors.push('Invalid file type');
        if (selected.has(key)) errors.push('Duplicate');

        if (errors.length) {
          addRow(f, {
            status: 'error',
            note: errors.join(', ')
          });
        } else {
          selected.set(key, f);
          addRow(f, {
            key,
            status: 'ready'
          });
        }
      });

      updateSummary();
      input.value = '';
    });

    function addRow(file, {
      key = null,
      status = 'ready',
      note = ''
    }) {
      const li = document.createElement('li');
      li.dataset.key = key || '';
      li.innerHTML = `
        <span>${file.name}${
          status === 'error'
            ? ` – <span class="err">${note}</span>`
            : ` – <span class="ok">ready</span>`
        }</span>
        <button class="remove-x" title="Remove">×</button>
      `;

      li.querySelector('.remove-x').addEventListener('click', () => {
        const k = li.dataset.key;
        if (k && selected.has(k)) selected.delete(k);
        li.remove();
        updateSummary();
      });

      listEl.appendChild(li);
    }

    function updateSummary() {
      const count = selected.size;
      msgEl.innerHTML = count ?
        `<span class="ok">${count} file(s) selected for upload</span>` :
        '';
    }

    window.getSelectedFiles = () => Array.from(selected.values());

    // submit order form
    const form = document.getElementById('order-form');
    const btnDraft = document.getElementById('btn-draft');
    const btnSubmit = document.getElementById('btn-submit');
    const isDraftEl = document.getElementById('is_draft');
    const overlay = document.getElementById('loading-overlay');

    const action = @json(route('artist.orders.update', $order));
    const csrf = @json(csrf_token());

    function getSelectedFiles() {
      return (typeof window.getSelectedFiles === 'function') ? window.getSelectedFiles() : [];
    }

    function loading(on) {
      overlay.classList.toggle('is-open', !!on);
      btnDraft.disabled = btnSubmit.disabled = !!on;
    }
    const nextPaint = () => new Promise(r => requestAnimationFrame(() => r()));

    async function send(isDraft) {
      isDraftEl.value = isDraft ? 1 : 0;

      const fd = new FormData(form);
      fd.set('is_draft', isDraftEl.value);
      fd.append('_method', 'PUT');
      for (const f of getSelectedFiles()) fd.append('attachments[]', f);

      // 1) show loading and allow the browser to paint it
      loading(true);
      await nextPaint(); // ensures "Saving… please wait" is visible

      let res, data;
      try {
        res = await fetch(action, {
          method: 'POST',
          body: fd,
          credentials: 'same-origin',
          headers: {
            'X-CSRF-TOKEN': csrf,
            'X-Requested-With': 'XMLHttpRequest' // tell Laravel to return JSON
          }
        });

        if (res.status === 422) {
          data = await res.json().catch(() => ({}));
          // 2) hide loading BEFORE showing SweetAlert
          loading(false);
          const msg = Object.values(data.errors || {}).flat().join(' • ') || 'Validation failed.';
          await Swal.fire({
            icon: 'error',
            title: 'Validation error',
            text: msg
          });
          return;
        }

        data = await res.json().catch(() => ({}));

        // 2) hide loading BEFORE showing SweetAlert
        loading(false);

        if (res.ok && data?.ok) {
          await Swal.fire({
            icon: 'success',
            title: isDraft ? 'Draft saved' : 'Order saved',
            text: data.message || (isDraft ? 'Draft saved successfully.' : 'Order submitted successfully.')
          });
          // optional refresh
          window.location.reload();
        } else {
          await Swal.fire({
            icon: 'error',
            title: 'Save failed',
            text: data?.message || `HTTP ${res.status} — please try again`
          });
        }
      } catch (e) {
        console.error(e);
        loading(false); // be sure to hide on network errors too
        await Swal.fire({
          icon: 'error',
          title: 'Network error',
          text: 'Could not save. Please try again.'
        });
      }
    }

    document.getElementById('btn-draft').addEventListener('click', () => send(true));
    document.getElementById('btn-submit').addEventListener('click', () => send(false));

    const acc = document.getElementById('productItems');
    if (!acc) return;

    // Set next index based on how many items exist on load
    const existingCount = acc.querySelectorAll('.accordion-item').length;
    acc.dataset.nextIndex = String(existingCount);

    // Wire existing remove buttons
    acc.querySelectorAll('.remove-item-btn').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const idx = parseInt(btn.dataset.index, 10);
        removeItem(idx);
      });
    });

    // Public function for inline onclick="removeItem(i, event)" compatibility
    window.removeItem = function(idx, ev) {
      if (ev) ev.preventDefault();
      const el = document.getElementById(`item${idx}`);
      if (el) el.remove();
      reindexItems();
    };

    // Add item button (ensure you have a button with id="addItemBtn")
    const addBtn = document.getElementById('addItemBtn');
    if (addBtn) {
      addBtn.addEventListener('click', addItem);
    }

    function addItem() {
      const tpl = document.getElementById('item-template');
      if (!tpl) return;

      const next = parseInt(acc.dataset.nextIndex || '0', 10);
      const html = tpl.innerHTML
        .replaceAll('__INDEX__', next)
        .replaceAll('__HUMAN_INDEX__', next + 1);

      // insert at end
      const wrapper = document.createElement('div');
      wrapper.innerHTML = html.trim();
      const node = wrapper.firstElementChild;

      // Hide the delete icon on the very first item only; for new we keep it visible
      // (no change needed here—your template already shows it)

      acc.appendChild(node);

      // Wire its delete button
      const del = node.querySelector('.remove-item-btn');
      if (del) {
        del.dataset.index = String(next);
        del.addEventListener('click', (e) => {
          e.preventDefault();
          node.remove();
          reindexItems();
        });
      }

      // Bootstrap: ensure only the newly-added item is expanded (optional)
      // collapse others
      acc.querySelectorAll('.accordion-collapse.show').forEach(pane => {
        pane.classList.remove('show');
      });
      node.querySelector('.accordion-collapse')?.classList.add('show');

      // bump counter
      acc.dataset.nextIndex = String(next + 1);
    }

    function reindexItems() {
      const items = Array.from(acc.querySelectorAll('.accordion-item'));
      items.forEach((itemEl, newIdx) => {
        const oldId = itemEl.id; // e.g., "item3"
        const oldIdxMatch = oldId.match(/^item(\d+)$/);
        const oldIdx = oldIdxMatch ? parseInt(oldIdxMatch[1], 10) : newIdx;

        // IDs
        itemEl.id = `item${newIdx}`;

        const header = itemEl.querySelector('.fw-semibold');
        if (header) header.textContent = `Item ${newIdx + 1}`;

        // Collapse ids/targets
        const pane = itemEl.querySelector('.accordion-collapse');
        if (pane) {
          pane.id = `itemPane${newIdx}`;
          pane.setAttribute('data-bs-parent', '#productItems');
        }
        const toggleBtn = itemEl.querySelector('[data-bs-toggle="collapse"]');
        if (toggleBtn) {
          toggleBtn.setAttribute('data-bs-target', `#itemPane${newIdx}`);
          toggleBtn.setAttribute('aria-controls', `itemPane${newIdx}`);
        }

        // Hidden id input stays the same value, but rename the name index
        // Update all [name="items[<n>]..."] to the new index
        itemEl.querySelectorAll('[name^="items["]').forEach(inp => {
          inp.name = inp.name.replace(/items\[\d+\]/, `items[${newIdx}]`);
        });

        // Update remove button index
        const del = itemEl.querySelector('.remove-item-btn');
        if (del) {
          del.dataset.index = String(newIdx);
        }
      });

      // Set nextIndex to count
      acc.dataset.nextIndex = String(items.length);
    }
  });
</script>
@endpush