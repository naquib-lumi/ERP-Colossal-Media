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

  .ti-wrap {
    position: relative;
    overflow: visible
  }

  .ti {
    display: flex;
    align-items: center;
    gap: .35rem;
    min-height: 44px;
    padding: .375rem .5rem;
    border: 1px solid #ced4da;
    border-radius: .375rem;
    flex-wrap: wrap;
    background: #fff;
    cursor: text
  }

  .ti:focus-within {
    outline: 0;
    border-color: #86b7fe;
    box-shadow: 0 0 0 .2rem rgba(13, 110, 253, .25)
  }

  .ti-chip {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    background: #edf2ff;
    border: 1px solid #cfe2ff;
    color: #244;
    padding: .2rem .5rem;
    border-radius: 999px;
    font-size: .85rem
  }

  .ti-chip button {
    appearance: none;
    border: 0;
    background: transparent;
    color: #6b7280;
    font-weight: 700;
    cursor: pointer;
    padding: 0 .1rem;
    line-height: 1
  }

  .ti-input {
    border: 0;
    outline: 0;
    min-width: 120px;
    flex: 1 0 120px;
    padding: .2rem
  }

  .ti-dd {
    position: absolute;
    left: 0;
    right: 0;
    z-index: 2000;
    background: #fff;
    border: 1px solid #ced4da;
    border-radius: .375rem;
    margin-top: .25rem;
    box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
    max-height: 220px;
    overflow: auto;
    display: none
  }

  .ti-dd-item {
    padding: .45rem .6rem;
    cursor: pointer
  }

  .ti-dd-item:hover,
  .ti-dd-item.is-active {
    background: #f5f8ff
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
                  <input id="totalQty"
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
              <div class="accordion" id="productItems" data-start-number="1" data-next-index="{{ count($items ?? []) }}">
                @foreach ($items as $i => $it)
                  @php
                    // Normalize material values to array for the tags widget
                    $materialVal = data_get($it, 'material');
                    if (is_string($materialVal)) {
                        $decoded = json_decode($materialVal, true);
                        if (json_last_error() === JSON_ERROR_NONE) $materialVal = $decoded;
                    }
                    $materialVal = collect($materialVal ?? [])->filter()->values();

                    $materialSuggestions = collect($materials ?? [])
                      ->pluck('materialName')
                      ->filter()
                      ->values();
                  @endphp

                  <div class="accordion-item mb-3 border rounded" id="item{{ $i }}" data-item-id="{{ data_get($it,'ItemID') }}" data-kind="item">
                    <div class="accordion-header d-flex justify-content-between align-items-center px-3 py-2">
                      <div>
                        <span class="fw-semibold">
                          Item <span class="item-number">{{ $i + 1 }}</span>
                        </span>
                        <span class="text-body-secondary ms-2 small item-summary">
                          {{ data_get($it, 'itemName') }}@if(data_get($it,'quantity')) • {{ data_get($it,'quantity') }}@endif
                        </span>
                      </div>

                      <div class="d-flex align-items-center gap-2">
                        {{-- Server delete (AJAX) --}}
                        @if (data_get($it,'ItemID'))
                          <button type="button"
                                  class="btn btn-link text-danger p-0"
                                  title="Delete this item from DB"
                                  data-action="delete-item"
                                  data-item-id="{{ data_get($it,'ItemID') }}"
                                  data-url="{{ route('artist.orders.items.destroy', [$order, data_get($it,'ItemID')]) }}">
                            <i class="bx bx-trash fs-5"></i>
                          </button>
                        @endif

                        {{-- Collapse toggle --}}
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
                        {{-- Hidden id so controller can upsert rather than always insert --}}
                        <input type="hidden" name="items[{{ $i }}][id]" value="{{ data_get($it,'ItemID') }}">

                        <div class="row g-3">
                          <div class="col-md-6">
                            <label class="form-label">Item Name</label>
                            <input class="form-control"
                                  name="items[{{ $i }}][itemName]"
                                  value="{{ old("items.$i.itemName", data_get($it,'itemName')) }}">
                          </div>

                          <div class="col-md-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" min="0" class="form-control"
                                  name="items[{{ $i }}][quantity]"
                                  value="{{ old("items.$i.quantity", data_get($it,'quantity')) }}">
                          </div>

                          {{-- Material (chips) --}}
                          <div class="col-12">
                            <label class="form-label">Material</label>
                            <div class="tags-input"
                                data-name="items[{{ $i }}][material][]"
                                data-suggestions='@json($materialSuggestions)'
                                data-values='@json($materialVal)'
                                data-allow-custom="1">
                            </div>
                          </div>

                          {{-- Sizes --}}
                          <div class="col-12 col-md-6">
                            <label class="form-label">Size (inches) – Width</label>
                            <input name="items[{{ $i }}][sizeWidth]" type="number" step="0.01" class="form-control"
                                  value="{{ old("items.$i.sizeWidth", data_get($it,'sizeWidth')) }}">
                          </div>
                          <div class="col-12 col-md-6">
                            <label class="form-label">Height</label>
                            <input name="items[{{ $i }}][sizeHeight]" type="number" step="0.01" class="form-control"
                                  value="{{ old("items.$i.sizeHeight", data_get($it,'sizeHeight')) }}">
                          </div>

                          {{-- Bleed --}}
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

                          {{-- Spec --}}
                          <div class="col-md-3">
                            <label class="form-label">Lamination</label>
                            @php $lam = old("items.$i.lamination", data_get($it,'lamination')); @endphp
                            <select name="items[{{ $i }}][lamination]" class="form-select">
                              <option value="">-</option>
                              <option value="Matt UV Lamination" {{ $lam==='Matt UV Lamination' ? 'selected' : '' }}>Matt UV Lamination</option>
                              <option value="Gloss UV Lamination" {{ $lam==='Gloss UV Lamination' ? 'selected' : '' }}>Gloss UV Lamination</option>
                              <option value="Matt Artcard Lamination" {{ $lam==='Matt Artcard Lamination' ? 'selected' : '' }}>Matt Artcard Lamination</option>
                              <option value="Gloss Artcard Lamination" {{ $lam==='Gloss Artcard Lamination' ? 'selected' : '' }}>Gloss Artcard Lamination</option>
                              <option value="Matt Tempered Film Lamination" {{ $lam==='Matt Tempered Film Lamination' ? 'selected' : '' }}>Matt Tempered Film Lamination</option>
                              <option value="Gloss Tempered Film Lamination" {{ $lam==='Gloss Tempered Film Lamination' ? 'selected' : '' }}>Gloss Tempered Film Lamination</option>
                              <option value="Matt Pigment Crystal Lamination" {{ $lam==='Matt Pigment Crystal Lamination' ? 'selected' : '' }}>Matt Pigment Crystal Lamination</option>
                              <option value="Gloss Pigment Crystal Lamination" {{ $lam==='Gloss Pigment Crystal Lamination' ? 'selected' : '' }}>Gloss Pigment Crystal Lamination</option>
                              <option value="Hot Stamping Lamination" {{ $lam==='Hot Stamping Lamination' ? 'selected' : '' }}>Hot Stamping Lamination</option>
                            </select>
                          </div>

                          <div class="col-md-3">
                            <label class="form-label">Printer</label>
                            @php $prt = old("items.$i.printer", data_get($it,'printer')); @endphp
                            <select name="items[{{ $i }}][printer]" class="form-select">
                              <option value="">-</option>
                              <option value="Handtop Hybrid" {{ $prt==='Handtop Hybrid' ? 'selected' : '' }}>Handtop Hybrid</option>
                              <option value="Handtop Roll2Roll" {{ $prt==='Handtop Roll2Roll' ? 'selected' : '' }}>Handtop Roll2Roll</option>
                              <option value="HP Latex" {{ $prt==='HP Latex' ? 'selected' : '' }}>HP Latex</option>
                              <option value="Solvent" {{ $prt==='Solvent' ? 'selected' : '' }}>Solvent</option>
                              <option value="Lanqi UV Gen 6 (A)" {{ $prt==='Lanqi UV Gen 6 (A)' ? 'selected' : '' }}>Lanqi UV Gen 6 (A)</option>
                              <option value="Lanqi UV Gen 6 (B) (Bothside Print)" {{ $prt==='Lanqi UV Gen 6 (B) (Bothside Print)' ? 'selected' : '' }}>Lanqi UV Gen 6 (B) (Bothside Print)</option>
                              <option value="ANS UV RD500" {{ $prt==='ANS UV RD500' ? 'selected' : '' }}>ANS UV RD500</option>
                              <option value="YF 1700 UV Epson i3600" {{ $prt==='YF 1700 UV Epson i3600' ? 'selected' : '' }}>YF 1700 UV Epson i3600</option>
                              <option value="Pigment HDP" {{ $prt==='Pigment HDP' ? 'selected' : '' }}>Pigment HDP</option>
                              <option value="Flora Flatbed 8x10" {{ $prt==='Flora Flatbed 8x10' ? 'selected' : '' }}>Flora Flatbed 8x10</option>
                              <option value="Grando Crystal Label" {{ $prt==='Grando Crystal Label' ? 'selected' : '' }}>Grando Crystal Label</option>
                              <option value="Crystal Label Flatbed" {{ $prt==='Crystal Label Flatbed' ? 'selected' : '' }}>Crystal Label Flatbed  </option>
                              <option value="Konica Minolta" {{ $prt==='Konica Minolta' ? 'selected' : '' }}>Konica Minolta</option>
                            </select>
                          </div>

                          <div class="col-md-3">
                            <label class="form-label">Cutter</label>
                            @php $cut = old("items.$i.cutter", data_get($it,'cutter')); @endphp
                            <select name="items[{{ $i }}][cutter]" class="form-select">
                              <option value="">-</option>
                              <option value="AOL 1000 Flatbed Cutter (Small)" {{ $cut==='AOL 1000 Flatbed Cutter (Small)' ? 'selected' : '' }}>AOL 1000 Flatbed Cutter (Small)</option>
                              <option value="AOL 5x10 Flatbed Cutter (big)" {{ $cut==='AOL 5x10 Flatbed Cutter (big)' ? 'selected' : '' }}>AOL 5x10 Flatbed Cutter (big)</option>
                              <option value="Jingwei 5x10 Flatbed Cutter" {{ $cut==='Jingwei 5x10 Flatbed Cutter' ? 'selected' : '' }}>Jingwei 5x10 Flatbed Cutter</option>
                              <option value="Ruijie Flatbed Router" {{ $cut==='Ruijie Flatbed Router' ? 'selected' : '' }}>Ruijie Flatbed Router</option>
                              <option value="Laser Cutter 150 (A)" {{ $cut==='Laser Cutter 150 (A)' ? 'selected' : '' }}>Laser Cutter 150 (A)</option>
                              <option value="Laser Cutter 150 (B)" {{ $cut==='Laser Cutter 150 (B)' ? 'selected' : '' }}>Laser Cutter 150 (B)</option>
                              <option value="Laser Cutter 300" {{ $cut==='Laser Cutter 300' ? 'selected' : '' }}>Laser Cutter 300</option>
                              <option value="Mimaki Cutting Plotte" {{ $cut==='Mimaki Cutting Plotte' ? 'selected' : '' }}>Mimaki Cutting Plotte</option>
                              <option value="AccuCut" {{ $cut==='AccuCut' ? 'selected' : '' }}>AccuCut</option>
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
                <div class="accordion-item mb-3 border rounded" data-kind="item" id="item__INDEX__">
                  <div class="accordion-header d-flex align-items-center px-3 py-2" id="itemHdr__INDEX__">
                    <span class="fw-semibold">
                      Item <span class="item-number">__INDEX_HUMAN__</span>
                    </span>
                    <span class="text-body-secondary ms-2 small item-summary"></span>

                    <!-- actions on the far right -->
                    <div class="ms-auto d-flex align-items-center gap-2">
                      <!-- client-side delete (unsaved row) -->
                      <button type="button"
                              class="btn btn-link p-0 text-danger delete-item"
                              data-index="__INDEX__"
                              title="Delete item">
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

                      <!-- keep hidden id so controller can upsert when this row becomes saved -->
                      <input type="hidden" name="items[__INDEX__][id]" value="">

                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label">Item Name</label>
                          <input type="text" class="form-control"
                                name="items[__INDEX__][itemName]" value="">
                        </div>

                        <div class="col-md-6">
                          <label class="form-label">Quantity</label>
                          <input type="number" min="0" class="form-control"
                                name="items[__INDEX__][quantity]" value="">
                        </div>

                        <div class="col-12">
                          <label class="form-label">Material</label>
                          <div class="tags-input"
                              data-name="items[__INDEX__][material][]"
                              data-suggestions='@json($allMaterials ?? [])'
                              data-values='[]'
                              data-allow-custom="1">
                          </div>
                        </div>

                        <div class="col-12 col-md-6">
                          <label class="form-label">Size (inches) – Width</label>
                          <input name="items[__INDEX__][sizeWidth]" type="number" step="0.01" class="form-control" value="">
                        </div>
                        <div class="col-12 col-md-6">
                          <label class="form-label">Height</label>
                          <input name="items[__INDEX__][sizeHeight]" type="number" step="0.01" class="form-control" value="">
                        </div>

                        <div class="col-12 col-md-3">
                          <label class="form-label">Bleed (Top)</label>
                          <input name="items[__INDEX__][bleedTop]" type="number" step="0.01" class="form-control" value="">
                        </div>
                        <div class="col-12 col-md-3">
                          <label class="form-label">Bottom</label>
                          <input name="items[__INDEX__][bleedBottom]" type="number" step="0.01" class="form-control" value="">
                        </div>
                        <div class="col-12 col-md-3">
                          <label class="form-label">Left</label>
                          <input name="items[__INDEX__][bleedLeft]" type="number" step="0.01" class="form-control" value="">
                        </div>
                        <div class="col-12 col-md-3">
                          <label class="form-label">Right</label>
                          <input name="items[__INDEX__][bleedRight]" type="number" step="0.01" class="form-control" value="">
                        </div>

                        <div class="col-md-3">
                          <label class="form-label">Lamination</label>
                          <select name="items[__INDEX__][lamination]" class="form-select">
                            <option value="">-</option>
                            <option>Matt UV Lamination</option>
                            <option>Gloss UV Lamination</option>
                            <option>Matt Artcard Lamination</option>
                            <option>Gloss Artcard Lamination</option>
                            <option>Matt Tempered Film Lamination</option>
                            <option>Gloss Tempered Film Lamination</option>
                            <option>Matt Pigment Crystal Lamination</option>
                            <option>Gloss Pigment Crystal Lamination</option>
                            <option>Hot Stamping Lamination</option>
                          </select>
                        </div>
                        <div class="col-md-3">
                          <label class="form-label">Printer</label>
                          <select name="items[__INDEX__][printer]" class="form-select">
                            <option value="">-</option>
                            <option>Handtop Hybrid</option>
                            <option>Handtop Roll2Roll</option>
                            <option>HP Latex</option>
                            <option>Solvent</option>
                            <option>Lanqi UV Gen 6 (A)</option>
                            <option>Lanqi UV Gen 6 (B) (Bothside Print)</option>
                            <option>ANS UV RD500</option>
                            <option>YF 1700 UV Epson i3600</option>
                            <option>Pigment HDP</option>
                            <option>Flora Flatbed 8x10</option>
                            <option>Grando Crystal Label</option>
                            <option>Crystal Label Flatbed</option>
                            <option>Crystal Label Flatbed</option>
                          </select>
                        </div>
                        <div class="col-md-3">
                          <label class="form-label">Cutter</label>
                          <select name="items[__INDEX__][cutter]" class="form-select">
                            <option value="">-</option>
                            <option>AOL 1000 Flatbed Cutter (Small)</option>
                            <option>AOL 5x10 Flatbed Cutter (big)</option>
                            <option>Jingwei 5x10 Flatbed Cutter</option>
                            <option>Ruijie Flatbed Router</option>
                            <option>Laser Cutter 150 (A)</option>
                            <option>Laser Cutter 150 (B)</option>
                            <option>Laser Cutter 300</option>
                            <option>Mimaki Cutting Plotte</option>
                            <option>AccuCut</option>
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
                @forelse($deliveries as $i => $d)
                <div class="card mb-3" data-delivery data-id="{{ $d->id }}" data-url="{{ route('artist.orders.delivery.destroy', [$order->id, $d->id]) }}">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <div class="fw-semibold">Delivery <span class="delivery-index">{{ $i + 1 }}</span></div>
                      <button type="button" class="btn btn-link p-0 text-danger delete-delivery" title="Delete" data-remove>
                        <i class="bx bx-trash fs-5"></i>
                      </button>
                    </div>
                    @php
                    $dtValue = '';

                    try {
                    $dateOnly = !empty($d->date)
                    ? \Illuminate\Support\Carbon::parse($d->date)->toDateString()
                    : null;

                    $timeOnly = !empty($d->time)
                    ? \Illuminate\Support\Carbon::parse($d->time)->format('H:i')
                    : null;

                    if ($dateOnly && $timeOnly) {
                    $dtValue = $dateOnly . 'T' . $timeOnly; // "YYYY-MM-DDTHH:MM"
                    } elseif ($dateOnly) {
                    $dtValue = $dateOnly . 'T00:00';
                    }
                    } catch (\Throwable $e) {
                    $dtValue = '';
                    }
                    @endphp

                    <input type="hidden" name="deliveries[{{ $i }}][id]" value="{{ $d->id }}">

                    <div class="row g-3">
                      <div class="col-12 col-md-3">
                        <label class="form-label">Delivery Method</label>
                        <input class="form-control" name="deliveries[{{ $i }}][method]" value="{{ $d->method }}">
                      </div>

                      <div class="col-12 col-md-3">
                        <label class="form-label">Location</label>
                        <input class="form-control" name="deliveries[{{ $i }}][location]" value="{{ $d->location }}">
                      </div>

                      <div class="col-12 col-md-2">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control del-qty" name="deliveries[{{ $i }}][quantity]" value="{{ $d->quantity }}">
                      </div>

                      <div class="col-12 col-md-4">
                        <label class="form-label">Date &amp; Time</label>
                        <input type="datetime-local"
                          class="form-control"
                          name="deliveries[{{ $i }}][datetime]"
                          value="{{ $dtValue }}">
                      </div>
                    </div>
                  </div>
                </div>
                @empty
                @endforelse
              </div>

              {{-- Template used when clicking “Add Delivery Breakdown” --}}
              <template id="deliveryTemplate">
                <div class="card border shadow-none" data-delivery>
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                      <strong>Delivery <span class="delivery-index">__INDEX_HUMAN__</span></strong>
                      <button type="button" class="btn btn-link p-0 text-danger delete-delivery" title="Delete" data-remove>
                        <i class="bx bx-trash fs-5"></i>
                      </button>
                    </div>

                    <input type="hidden" name="deliveries[__INDEX__][id]" value="">

                    <div class="row g-3">
                      <div class="col-12 col-md-3">
                        <label class="form-label">Delivery Method</label>
                        <select name="deliveries[__INDEX__][method]" class="form-select">
                          <option value="">Method</option>
                          <option value="Courier">Courier</option>
                          <option value="Pickup">Pickup</option>
                          <option value="Truck">Truck</option>
                        </select>
                      </div>

                      <div class="col-12 col-md-3">
                        <label class="form-label">Location Address</label>
                        <input type="text" name="deliveries[__INDEX__][location]" class="form-control" value="">
                      </div>

                      <div class="col-12 col-md-2">
                        <label class="form-label">Quantity</label>
                        <input type="number" step="1" min="0" name="deliveries[__INDEX__][quantity]" class="form-control del-qty" value="">
                      </div>

                      <div class="col-12 col-md-4">
                        <label class="form-label">Date & Time</label>
                        <input type="datetime-local"
                          class="form-control"
                          name="deliveries[__INDEX__][datetime]"
                          value="">
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
        <button type="submit" name="is_draft" value="1" class="btn btn-secondary" id="btn-draft">Save Draft</button>
        <button type="submit" name="is_draft" value="0" class="btn btn-primary" id="btn-submit">Save & Submit</button>
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

<script>
  window.CSRF_TOKEN = "{{ csrf_token() }}";
  (function() {
    // -------------------------------------------------------------
    // Accordion: setup
    // -------------------------------------------------------------
    function openOnly(id) {
      document.querySelectorAll('#productItems .accordion-collapse.show')
        .forEach(el => new bootstrap.Collapse(el, {
          toggle: false
        }).hide());
      new bootstrap.Collapse(document.querySelector(id), {
        toggle: true
      }).show();
    }

    const acc   = document.getElementById('productItems');
    const tplEl = document.getElementById('itemTemplate');
    if (acc && tplEl) {
      function normalize(v) {
        return (v || '').trim();
      }

      function hidden(name, val) {
        const h = document.createElement('input');
        h.type = 'hidden';
        h.name = name;
        h.value = val;
        return h;
      }

      function initTagsInput(container) {
        if (!container || container.dataset._bound === '1') return;
        container.dataset._bound = '1';

        // read data-* from Blade
        const name = container.dataset.name; // e.g. items[3][material][]
        const suggestions = JSON.parse(container.dataset.suggestions || '[]');
        const initial = JSON.parse(container.dataset.values || '[]');
        const allowCustom = container.dataset.allowCustom === '1';

        // build UI
        container.innerHTML = '';
        const wrap = document.createElement('div');
        wrap.className = 'ti-wrap';
        const box = document.createElement('div');
        box.className = 'ti';
        box.tabIndex = 0;
        const input = document.createElement('input');
        input.className = 'ti-input';
        input.placeholder = 'Click to select…';
        input.readOnly = true;
        const dd = document.createElement('div');
        dd.className = 'ti-dd';
        box.appendChild(input);
        wrap.appendChild(box);
        wrap.appendChild(dd);
        container.appendChild(wrap);

        const selected = new Set(initial.map(v => (v || '').trim()).filter(Boolean));

        const hidden = (n, v) => {
          const h = document.createElement('input');
          h.type = 'hidden';
          h.name = n;
          h.value = v;
          return h;
        };

        function renderChips() {
          [...box.querySelectorAll('.ti-chip')].forEach(n => n.remove());
          [...container.querySelectorAll('input[type=hidden]')].forEach(n => n.remove());
          selected.forEach(v => {
            const chip = document.createElement('span');
            chip.className = 'ti-chip';
            chip.textContent = v;
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.innerHTML = '&times;';
            btn.addEventListener('click', () => {
              selected.delete(v);
              renderChips();
              buildList();
            });
            chip.appendChild(btn);
            box.insertBefore(chip, input);
            container.appendChild(hidden(name, v)); // ← hidden inputs appended to container
          });
        }

        function buildList() {
          const avail = suggestions.filter(s => !selected.has(s));
          dd.innerHTML = '';
          if (!avail.length) {
            dd.style.display = 'none';
            return;
          }
          avail.forEach((v) => {
            const it = document.createElement('div');
            it.className = 'ti-dd-item';
            it.textContent = v;
            it.addEventListener('click', () => {
              selected.add(v);
              renderChips();
              buildList();
            });
            dd.appendChild(it);
          });
          dd.style.display = 'block';
        }

        box.addEventListener('click', () => {
          buildList();
          dd.style.display = 'block';
        });
        input.addEventListener('focus', () => {
          buildList();
          dd.style.display = 'block';
        });
        document.addEventListener('click', (e) => {
          if (!wrap.contains(e.target)) dd.style.display = 'none';
        });

        renderChips(); // ← show chips for initial values from DB
      }

      function initAllTagsInputs(root = document) {
        root.querySelectorAll('.tags-input').forEach(initTagsInput);
      }

      initAllTagsInputs(document);

      // seed nextIndex from data-next-index, else fall back to current count
      let nextIndex = parseInt(acc.dataset.nextIndex ?? String(acc.querySelectorAll('.accordion-item[data-kind="item"]').length), 10);

      function addItemRow() {
        const humanNum = acc.querySelectorAll('.accordion-item[data-kind="item"]').length + 1;

        const html = tplEl.innerHTML.replace(/__INDEX__/g, String(nextIndex)).replace(/__INDEX_HUMAN__/g, String(humanNum));
        const frag = document.createRange().createContextualFragment(html);
        const row = frag.firstElementChild;
        if (!row) return;

        acc.appendChild(row);

        // open new collapse via Bootstrap
        const pane = row.querySelector('.accordion-collapse');
        const btn = row.querySelector('[data-bs-toggle="collapse"]');
        if (pane) {
          pane.setAttribute('data-bs-parent', '#productItems');
          bootstrap.Collapse.getOrCreateInstance(pane, {
            toggle: false
          }).show();
        }
        if (btn) {
          btn.classList.remove('collapsed');
          btn.setAttribute('aria-expanded', 'true');
        }

        row.querySelectorAll('.item-number').forEach(n => n.textContent = String(humanNum));
        nextIndex++;
        acc.dataset.nextIndex = String(nextIndex);

        wireRow(row);
        initAllTagsInputs(row);
        updateSummary(row);
        renumberOnly();
        validateItems();

        acc.addEventListener('input', (e) => {
          if (e.target.matches('input[name^="items["][name$="[quantity]"], input[name^="items["][name$="[qty]"]')) {
            validateItems();
          }
        });
      }

      function wireRow(wrap) {
        if (!wrap || wrap.dataset.wired === '1') return;
        wrap.dataset.wired = '1';

        // delete button
        const delBtn = wrap.querySelector('.remove-item-btn, .delete-item');
        if (delBtn) {
          delBtn.addEventListener('click', (e) => {
            e.preventDefault();
            wrap.remove();
            renumberOnly(); 
            validateItems();
            acc.dataset.nextIndex = String(acc.querySelectorAll('.accordion-item').length);
          });
        }

        // inputs to keep summary updated
        wrap.addEventListener('input', () => updateSummary(wrap), {
          passive: true
        });
      }

      // Keep header mini summary (name • qty) updated
      function updateSummary(wrap) {
        const name = wrap.querySelector('input[name^="items"][name$="[itemName]"]')?.value || '';
        const qty  = wrap.querySelector('input[name^="items"][name$="[quantity]"]')?.value || '';
        const el   = wrap.querySelector('.item-summary');
        if (el) el.textContent = name + (qty ? ` • ${qty}` : '');
      }

      function renumberOnly() {
        const items = acc.querySelectorAll('.accordion-item[data-kind="item"]');
        items.forEach((el, idx) => {
          el.querySelectorAll('.item-number').forEach(n => n.textContent = String(idx + 1));
          // also keep collapse ids in sync if needed
          const pane = el.querySelector('.accordion-collapse');
          if (pane) pane.id = `itemPane${idx}`;
          const btn = el.querySelector('[data-bs-toggle="collapse"]');
          if (btn) {
            btn.setAttribute('data-bs-target', `#itemPane${idx}`);
            btn.setAttribute('aria-controls', `itemPane${idx}`);
          }
          el.id = `item${idx}`;
        });
        acc.dataset.nextIndex = String(items.length);
      }

      // Delegated events for delete, chevron, and summary update
      acc.querySelectorAll('.accordion-item[data-kind="item"]').forEach((wrap) => {
        wireRow(wrap);
        updateSummary(wrap);
        initAllTagsInputs(wrap); // ✅ add this
      });
      renumberOnly();

      document.getElementById('addItemBtn')?.addEventListener('click', (e) => {
        e.preventDefault();
        addItemRow();
      });
    }

    // delivery breakdown ----------------------------------------------------------------------------------
    const delWrap        = document.getElementById('deliveriesWrap');
    const addDeliveryBtn = document.getElementById('addDeliveryBtn');
    const delTpl         = document.getElementById('deliveryTemplate');
    const form           = document.getElementById('order-form');

    const totalQtyEl =
      document.querySelector('input[name="product[qty_total]"]') ||
      document.getElementById('totalQty');

    if (delWrap && delTpl && form) {

      function reindexDeliveries() {
        delWrap.querySelectorAll('[data-delivery]').forEach((card, i) => {
          const idxEl = card.querySelector('.delivery-index');
          if (idxEl) idxEl.textContent = i + 1;

          card.querySelectorAll('[name]').forEach((el) => {
            // for template names like deliveries[__INDEX__][field]
            el.name = el.name
              .replace(/deliveries\[__INDEX__\]/g, `deliveries[${i}]`)
              // for existing rows like deliveries[3][field]
              .replace(/deliveries\[\d+\]/, `deliveries[${i}]`);
          });
        });
      }

      function addDelivery() {
        const idx  = delWrap.querySelectorAll('[data-delivery]').length;
        const html = delTpl.innerHTML
          .replace(/__INDEX__/g, idx)
          .replace(/__INDEX_HUMAN__/g, idx + 1);

        const temp = document.createElement('div');
        temp.innerHTML = html.trim();
        const node = temp.firstElementChild;

        delWrap.appendChild(node);
        reindexDeliveries();
        validateDeliveries(); // keep totals in check
      }

      if (addDeliveryBtn) {
        addDeliveryBtn.addEventListener('click', (e) => {
          e.preventDefault();
          addDelivery();
        });
      }

      // Delete (supports two paths)
      // 1) If card has data-url (server DELETE endpoint) → use AJAX
      // 2) Else (no data-url) → fall back to hidden input delete_deliveries[] + submit
      delWrap.addEventListener('click', async (e) => {
        const btn = e.target.closest('.delete-delivery');
        if (!btn) return;

        e.preventDefault();
        const card = btn.closest('[data-delivery]');
        if (!card) return;

        const id   = card.dataset.id || card.querySelector('input[name$="[id]"]')?.value || '';
        const url  = card.dataset.url || '';

        const confirmed = await (window.Swal
          ? Swal.fire({
              icon: 'warning',
              title: 'Delete this delivery?',
              text: id ? 'This will delete it permanently.' : 'This will remove the row.',
              showCancelButton: true,
              confirmButtonText: 'Delete',
              confirmButtonColor: '#d33'
            }).then(r => r.isConfirmed)
          : Promise.resolve(confirm('Delete this delivery?')));

        if (!confirmed) return;

        async function removeCard() {
          card.remove();
          reindexDeliveries();
          validateDeliveries();
          if (window.Swal) {
            Swal.fire({ icon: 'success', title: 'Deleted', timer: 1100, showConfirmButton: false });
          }
        }

        // AJAX path
        if (id && url) {
          try {
            const res = await fetch(url, {
              method: 'DELETE',
              headers: { 'X-CSRF-TOKEN': window.CSRF_TOKEN, 'Accept': 'application/json' }
            });
            const data = await res.json().catch(() => ({}));
            if (res.ok && data?.ok) {
              await removeCard();
              return;
            }
            const msg = data?.message || `HTTP ${res.status}`;
            if (window.Swal) Swal.fire({ icon: 'error', title: 'Delete failed', text: msg });
            else alert('Delete failed: ' + msg);
            return;
          } catch (err) {
            if (window.Swal) Swal.fire({ icon: 'error', title: 'Network error', text: String(err) });
            else alert('Network error: ' + err);
            return;
          }
        }

        // Fallback (hidden input + submit)
        if (id) {
          const h = document.createElement('input');
          h.type  = 'hidden';
          h.name  = 'delete_deliveries[]';
          h.value = id;
          form.appendChild(h);
        }
        await removeCard();

        if (form.requestSubmit) form.requestSubmit();
        else form.submit();
      });

      // ---------- Quantity guard: sum(deliveries.quantity) ≤ total ----------
      function getTotalAllowed() {
        const v = (totalQtyEl?.value ?? '').trim();
        const n = parseFloat(v);
        return Number.isFinite(n) ? n : 0;
      }

      function sumDeliveryQty() {
        let sum = 0;
        delWrap.querySelectorAll('.del-qty').forEach(inp => {
          const v = parseFloat(inp.value || '0');
          if (!Number.isNaN(v)) sum += v;
        });
        return sum;
      }

      function setQtyValidity(ok, msg = '') {
        const id = 'del-qty-msg';
        let box = document.getElementById(id);
        if (!box) {
          box = document.createElement('div');
          box.id = id;
          box.className = 'mt-2 small text-danger';
          delWrap.parentElement.insertBefore(box, delWrap.nextSibling);
        }
        box.textContent = ok ? '' : msg;

        delWrap.querySelectorAll('.del-qty').forEach(inp => {
          inp.classList.toggle('is-invalid', !ok);
          inp.setAttribute('aria-invalid', String(!ok));
        });

        document.getElementById('btn-submit')?.toggleAttribute('disabled', !ok);
        document.getElementById('btn-draft')?.toggleAttribute('disabled', !ok);
      }

      function validateDeliveries() {
        const total = getTotalAllowed();
        const sum   = sumDeliveryQty();
        const ok    = sum <= total;
        setQtyValidity(ok,
          ok ? '' : `Delivery quantities (${sum}) exceed Total Quantity (${total}).`);
      }

      // ===== Items quantity guard: sum(items.quantity) ≤ total =====
      function sumItemQty() {
        let sum = 0;
        // supports both [quantity] and older [qty]
        acc.querySelectorAll(
          'input[name^="items["][name$="[quantity]"], input[name^="items["][name$="[qty]"]'
        ).forEach(inp => {
          const v = parseFloat(inp.value || '0');
          if (!Number.isNaN(v)) sum += v;
        });
        return sum;
      }

      function setItemQtyValidity(ok, msg = '') {
        const id = 'item-qty-msg';
        let box = document.getElementById(id);
        if (!box) {
          box = document.createElement('div');
          box.id = id;
          box.className = 'mt-2 small text-danger';
          // place the message immediately under the Items accordion
          acc.parentElement.insertBefore(box, acc.nextSibling);
        }
        box.textContent = ok ? '' : msg;

        // highlight all item quantity inputs
        acc.querySelectorAll(
          'input[name^="items["][name$="[quantity]"], input[name^="items["][name$="[qty]"]'
        ).forEach(inp => {
          inp.classList.toggle('is-invalid', !ok);
          inp.setAttribute('aria-invalid', String(!ok));
        });

        // disable submit buttons if invalid
        document.getElementById('btn-submit')?.toggleAttribute('disabled', !ok);
        document.getElementById('btn-draft')?.toggleAttribute('disabled', !ok);
      }

      function validateItems() {
        const total = getTotalAllowed();   // you already have this for deliveries
        const sum   = sumItemQty();
        const ok    = sum <= total;
        setItemQtyValidity(
          ok,
          ok ? '' : `Item quantities (${sum}) exceed Total Quantity (${total}).`
        );
      }

      // Delegate validation on qty inputs
      delWrap.addEventListener('input', (e) => {
        if (e.target.matches('.del-qty') || e.target.closest('.del-qty')) {
          validateDeliveries();
        }
      });

      // Also re-validate when the overall total changes
      totalQtyEl?.addEventListener('input', () => {
        validateItems();
        validateDeliveries(); // keep both consistent
      });

      // initial pass
      reindexDeliveries();
      validateDeliveries();
    }

    function renumberItems() {
      // Use the single numbering function from above
      renumberOnly();
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
      const url = btn.dataset.url;

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
          Swal.fire({
            icon: 'success',
            title: 'Item deleted',
            timer: 1200,
            showConfirmButton: false
          });
        }
      } catch (err) {
        console.error(err);
        if (window.Swal) {
          Swal.fire({
            icon: 'error',
            title: 'Delete failed',
            text: String(err)
          });
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

    const draftBtn = document.getElementById('btn-draft');
    const submitBtn = document.getElementById('btn-submit');

    if (draftBtn) draftBtn.addEventListener('click', () => send(true));
    if (submitBtn) submitBtn.addEventListener('click', () => send(false));

  });
</script>
@endpush