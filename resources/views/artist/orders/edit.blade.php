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

  input[readonly], select[disabled], textarea[readonly] {
      background-color: #f1f1f1 !important;
      pointer-events: none;
  }
  
  .del-summary-pill strong{color:#111827}
  .del-summary-pill span{white-space:nowrap}
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

<input type="hidden" id="is_draft" name="is_draft" value="0">
@php
    $isSubmitted = isset($isSubmitted) ? (bool)$isSubmitted : ((int)($order->submit ?? 0) === 1);

    $readonly = $isSubmitted ? 'readonly disabled' : '';
    $disabled = $isSubmitted ? 'disabled' : '';

    $submitted = ((int)($order->draft ?? 0) === 1 || (int)($order->draft ?? 0) === 0) && (int)($order->submit ?? 0) === 0;
@endphp
  <div class="row g-4">
    <div class="col-12">
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          @php
            $displayOrderNo = $order->order_number;

            if (!is_null($order->redo) && optional($order->originalOrder)->order_number) {
                $orig = $order->originalOrder->order_number;
                $displayOrderNo = preg_match('/R\d*$/', $orig) ? $orig : ($orig . 'R');
            }
          @endphp
          <div>
            <h5 class="mb-0">Artist Job Order — <span class="text-body-secondary">{{ $displayOrderNo }}</span></h5>
            <small class="text-body-secondary">Last updated: {{ $today }}</small>
          </div>
          <span class="badge bg-label-secondary">{{ $order->artist->name ?? '—' }}</span>
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
                  <select id="design_confirmed" name="design_confirmed" class="form-select" {{ $disabled }}>
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
                    <span class="text-body-secondary small">(read-only — uploaded by salesperson)</span>
                  </label>

                  @if(isset($leadAttachments) && count($leadAttachments))
                    <div class="d-flex flex-wrap gap-2">
                      @foreach($leadAttachments as $f)
                        <a href="{{ $f->url }}" target="_blank"
                          class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center">
                          <i class="bx bx-file me-1"></i>
                          <span class="text-truncate" style="max-width:220px">{{ $f->name }}</span>
                          @if($f->size)
                            <small class="text-muted ms-2">({{ number_format($f->size/1024, 1) }} KB)</small>
                          @endif
                        </a>
                      @endforeach
                    </div>
                  @else
                    <div class="text-body-secondary">No attachments</div>
                  @endif
                </div>
              </div>
            </div>
          </div>

          <div class="accordion" id="productsAcc">
            @foreach($order->products as $pIndex => $product)
            @php
              $isRedo            = (bool) $order->redo;                             
              $selectedForRedo   = $isRedo && (int) ($product->editable ?? 0) === 1; 
              $locked            = $isRedo && !$selectedForRedo;                
            @endphp
            <input type="hidden" name="products[{{ $pIndex }}][product_id]" value="{{ $product->ProductID }}">
            <div class="accordion-item {{ $locked ? 'opacity-75' : '' }}">
              <h2 class="accordion-header" id="pHead{{ $pIndex }}">
                <button
                  class="accordion-button {{ !$loop->first ? 'collapsed' : '' }}"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#pCollapse{{ $pIndex }}"
                  aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                  aria-controls="pCollapse{{ $pIndex }}">
                  Product #{{ $product->display_code  ?? $loop->iteration }}
                  — {{ $product->productName ?? 'Product' }}
                  @if ($selectedForRedo)
                    <span class="badge bg-primary ms-2">REDO</span>
                  @endif
                </button>
              </h2>

              <div
                id="pCollapse{{ $pIndex }}"
                class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
                aria-labelledby="pHead{{ $pIndex }}"
                data-bs-parent="#productsAcc">
                <div class="accordion-body">
                  {{-- Product block --}}
                  <fieldset {{ $locked ? 'disabled' : '' }}>
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
                              value="{{ old('product.name', $product->productName ?? '') }}" {{ $readonly }}>
                          </div>

                          <div class="col-12 col-md-6 col-xl-3">
                            <label class="form-label">Total Quantity</label>
                            <input id="totalQty"
                              name="product[qty_total]"
                              type="number"
                              min="0"
                              class="form-control"
                              placeholder="1000"
                              value="{{ old('product.qty_total', $product->totalQuantity ?? '') }}" {{ $readonly }}>
                          </div>

                          <div class="col-12 col-md-6 col-xl-6">
                            <label class="form-label">Material / Remark</label>
                            <input
                              name="product[material]"
                              type="text"
                              class="form-control"
                              placeholder="Premium Paper, Glossy"
                              value="{{ old('product.material', $product->materialRemark ?? '') }}" {{ $readonly }}>
                          </div>
                        </div>

                        {{-- Items repeater --}}
                        @php
                          $itemsData = old('items', $items);
                        @endphp

                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <h6 class="mb-0">Items</h6>
                          @if ($submitted)
                          <button
                            type="button"
                            data-add-item
                            data-product-index="{{ $pIndex }}"
                            class="btn btn-sm btn-outline-primary">
                            Add Item
                          </button>
                          @endif
                        </div>
                        @php
                        $items = $product->items ?? [];
                        @endphp
                        {{-- make the accordion id unique per product --}}
                        <div class="accordion" id="productItems-{{ $pIndex }}" data-start-number="1" data-next-index="{{ count($items ?? []) }}">
                          @foreach ($items as $i => $it)
                            @php
                              $materialVal = data_get($it, 'material');
                              if (is_string($materialVal)) {
                                $decoded = json_decode($materialVal, true);
                                if (json_last_error() === JSON_ERROR_NONE) $materialVal = $decoded;
                              }
                              $materialVal = collect($materialVal ?? [])->filter()->values();

                              $materialSuggestions = collect($materials ?? [])
                                ->pluck('materialName')->filter()->values();
                            @endphp
                            <input type="hidden" name="products[{{ $pIndex }}][product_id]" value="{{ $product->ProductID }}">
                            <div class="accordion-item mb-3 border rounded" id="item{{ $pIndex }}_{{ $i }}" data-kind="item">
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
                                  @if ($submitted)
                                    @if (data_get($it,'ItemID'))
                                      <button type="button"
                                              class="btn btn-link text-danger p-0 delete-item"
                                              title="Delete this item from DB"
                                              data-action="delete-item"
                                              data-item-id="{{ data_get($it,'ItemID') }}"
                                              data-url="{{ route('artist.orders.items.destroy', [$order, data_get($it,'ItemID')]) }}">
                                        <i class="bx bx-trash fs-5"></i>
                                      </button>
                                    @endif
                                  @endif

                                  {{-- Collapse toggle --}}
                                  <button class="btn btn-link p-0"
                                          type="button"
                                          data-bs-toggle="collapse"
                                          data-bs-target="#itemPane{{ $pIndex }}_{{ $i }}"
                                          aria-expanded="{{ $i === 0 ? 'true' : 'false' }}"
                                          aria-controls="itemPane{{ $pIndex }}_{{ $i }}">
                                    <i class="bx bx-chevron-down fs-4"></i>
                                  </button>
                                </div>
                              </div>

                              <div id="itemPane{{ $pIndex }}_{{ $i }}"
                                  class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}"
                                  data-bs-parent="#productItems-{{ $pIndex }}">
                                <div class="accordion-body">
                                  <div class="row g-3">
                                    <div class="col-md-6">
                                      <label class="form-label">Item Name</label>
                                      <input class="form-control"
                                        name="products[{{ $pIndex }}][items][{{ $i }}][itemName]"
                                        value="{{ old("items.$i.itemName", data_get($it,'itemName')) }}" {{ $readonly }}>
                                    </div>

                                    <div class="col-md-6">
                                      <label class="form-label">Quantity</label>
                                      <input type="number" min="0" class="form-control"
                                        name="products[{{ $pIndex }}][items][{{ $i }}][quantity]"
                                        value="{{ old("items.$i.quantity", data_get($it,'quantity')) }}" {{ $readonly }}>
                                    </div>

                                    {{-- Material (chips) --}}
                                    <div class="col-12">
                                      <label class="form-label">Material</label>
                                      <div class="tags-input"
                                            data-name="products[{{ $pIndex }}][items][{{ $i }}][material][]"
                                            data-suggestions='@json($materialSuggestions)'
                                            data-values='@json($materialVal)'
                                            data-allow-custom="1" data-readonly="{{ $order->submit ? '1' : '0' }}">
                                      </div>
                                    </div>

                                    @php
                                      $units = ['mm' => 'mm', 'cm' => 'cm', 'inch' => 'inch', 'ft' => 'ft'];
                                      $unit  = old("items.$i.sizeUnit", data_get($it,'sizeUnit', 'mm'));
                                      $bleedUnit = old("items.$i.bleedUnit", data_get($it,'bleedUnit', 'mm'));
                                    @endphp

                                    {{-- Sizes --}}
                                    <div class="col-12 col-md-4">
                                      <label class="form-label">Unit (Size)</label>
                                      <select name="products[{{ $pIndex }}][items][{{ $i }}][sizeUnit]" class="form-select" {{ $disabled }}>
                                        @foreach($units as $val => $label)
                                          <option value="{{ $val }}" @selected($unit === $val)>{{ $label }}</option>
                                        @endforeach
                                      </select>
                                    </div>
                                    <div class="col-12 col-md-4">
                                      <label class="form-label">Size - Width</label>
                                      <input name="products[{{ $pIndex }}][items][{{ $i }}][sizeWidth]"
                                        type="number" step="0.01" class="form-control"
                                        value="{{ old("items.$i.sizeWidth", data_get($it,'sizeWidth')) }}" {{ $readonly }}>
                                    </div>
                                    <div class="col-12 col-md-4">
                                      <label class="form-label">Height</label>
                                      <input name="products[{{ $pIndex }}][items][{{ $i }}][sizeHeight]"
                                        type="number" step="0.01" class="form-control"
                                        value="{{ old("items.$i.sizeHeight", data_get($it,'sizeHeight')) }}" {{ $readonly }}>
                                    </div>

                                    {{-- Bleed --}}
                                    <div class="col-12 col-md-4">
                                      <label class="form-label">Unit (Bleed)</label>
                                      <select name="products[{{ $pIndex }}][items][{{ $i }}][bleedUnit]" class="form-select" {{ $disabled }}>
                                        @foreach($units as $v=>$lbl)
                                          <option value="{{ $v }}" @selected($bleedUnit===$v)>{{ $lbl }}</option>
                                        @endforeach
                                      </select>
                                    </div>
                                    <div class="col-12 col-md-2">
                                      <label class="form-label">Bleed (Top)</label>
                                      <input name="products[{{ $pIndex }}][items][{{ $i }}][bleedTop]"
                                        type="number" step="0.01" class="form-control"
                                        value="{{ old("items.$i.bleedTop", data_get($it,'bleedTop')) }}" {{ $readonly }}>
                                    </div>
                                    <div class="col-12 col-md-2">
                                      <label class="form-label">Bottom</label>
                                      <input name="products[{{ $pIndex }}][items][{{ $i }}][bleedBottom]"
                                        type="number" step="0.01" class="form-control"
                                        value="{{ old("items.$i.bleedBottom", data_get($it,'bleedBottom')) }}" {{ $readonly }}>
                                    </div>
                                    <div class="col-12 col-md-2">
                                      <label class="form-label">Left</label>
                                      <input name="products[{{ $pIndex }}][items][{{ $i }}][bleedLeft]"
                                        type="number" step="0.01" class="form-control"
                                        value="{{ old("items.$i.bleedLeft", data_get($it,'bleedLeft')) }}" {{ $readonly }}>
                                    </div>
                                    <div class="col-12 col-md-2">
                                      <label class="form-label">Right</label>
                                      <input name="products[{{ $pIndex }}][items][{{ $i }}][bleedRight]"
                                        type="number" step="0.01" class="form-control"
                                        value="{{ old("items.$i.bleedRight", data_get($it,'bleedRight')) }}" {{ $readonly }}>
                                    </div>

                                    {{-- Spec --}}
                                    @php
                                      $lamVal = old("products.$pIndex.items.$i.lamination", data_get($it, 'spec.lamination'));
                                      $prtVal = old("products.$pIndex.items.$i.printer",    data_get($it, 'spec.printer'));
                                      $cutVal = old("products.$pIndex.items.$i.cutter",     data_get($it, 'spec.cutter'));
                                      $assVal = old("products.$pIndex.items.$i.finishing",  data_get($it,'finishing'));

                                      $lamLc = strtolower((string) $lamVal);
                                      $prtLc = strtolower((string) $prtVal);
                                      $cutLc = strtolower((string) $cutVal);
                                      $assLc = strtolower((string) $assVal);
                                    @endphp
                                    <div class="col-md-3">
                                      <label class="form-label">Lamination</label>
                                      <select name="products[{{ $pIndex }}][items][{{ $i }}][lamination]" class="form-select" {{ $disabled }}>
                                        <option value="">-</option>
                                        <option value="Matt UV Lamination"               {{ $lamLc==='matt uv lamination' ? 'selected' : '' }}>Matt UV Lamination</option>
                                        <option value="Gloss UV Lamination"              {{ $lamLc==='gloss uv lamination' ? 'selected' : '' }}>Gloss UV Lamination</option>
                                        <option value="Matt Artcard Lamination"          {{ $lamLc==='matt artcard lamination' ? 'selected' : '' }}>Matt Artcard Lamination</option>
                                        <option value="Gloss Artcard Lamination"         {{ $lamLc==='gloss artcard lamination' ? 'selected' : '' }}>Gloss Artcard Lamination</option>
                                        <option value="Matt Tempered Film Lamination"    {{ $lamLc==='matt tempered film lamination' ? 'selected' : '' }}>Matt Tempered Film Lamination</option>
                                        <option value="Gloss Tempered Film Lamination"   {{ $lamLc==='gloss tempered film lamination' ? 'selected' : '' }}>Gloss Tempered Film Lamination</option>
                                        <option value="Matt Pigment Crystal Lamination"  {{ $lamLc==='matt pigment crystal lamination' ? 'selected' : '' }}>Matt Pigment Crystal Lamination</option>
                                        <option value="Gloss Pigment Crystal Lamination" {{ $lamLc==='gloss pigment crystal lamination' ? 'selected' : '' }}>Gloss Pigment Crystal Lamination</option>
                                        <option value="Hot Stamping Lamination"          {{ $lamLc==='hot stamping lamination' ? 'selected' : '' }}>Hot Stamping Lamination</option>
                                      </select>
                                    </div>

                                    <div class="col-md-3">
                                      <label class="form-label">Printer</label>
                                      <select name="products[{{ $pIndex }}][items][{{ $i }}][printer]" class="form-select" {{ $disabled }}>
                                        <option value="">-</option>
                                        <option value="Handtop Hybrid"                      {{ $prtLc==='handtop hybrid' ? 'selected' : '' }}>Handtop Hybrid</option>
                                        <option value="Handtop Roll2Roll"                   {{ $prtLc==='handtop roll2roll' ? 'selected' : '' }}>Handtop Roll2Roll</option>
                                        <option value="HP Latex"                            {{ $prtLc==='hp latex' ? 'selected' : '' }}>HP Latex</option>
                                        <option value="Solvent"                             {{ $prtLc==='solvent' ? 'selected' : '' }}>Solvent</option>
                                        <option value="Lanqi UV Gen 6 (A)"                  {{ $prtLc==='lanqi uv gen 6 (a)' ? 'selected' : '' }}>Lanqi UV Gen 6 (A)</option>
                                        <option value="Lanqi UV Gen 6 (B) (Bothside Print)" {{ $prtLc==='lanqi uv gen 6 (b) (bothside print)' ? 'selected' : '' }}>Lanqi UV Gen 6 (B) (Bothside Print)</option>
                                        <option value="ANS UV RD500"                        {{ $prtLc==='ans uv rd500' ? 'selected' : '' }}>ANS UV RD500</option>
                                        <option value="YF 1700 UV Epson i3600"              {{ $prtLc==='yf 1700 uv epson i3600' ? 'selected' : '' }}>YF 1700 UV Epson i3600</option>
                                        <option value="Pigment HDP"                         {{ $prtLc==='pigment hdp' ? 'selected' : '' }}>Pigment HDP</option>
                                        <option value="Flora Flatbed 8x10"                  {{ $prtLc==='flora flatbed 8x10' ? 'selected' : '' }}>Flora Flatbed 8x10</option>
                                        <option value="Grando Crystal Label"                {{ $prtLc==='grando crystal label' ? 'selected' : '' }}>Grando Crystal Label</option>
                                        <option value="Crystal Label Flatbed"               {{ $prtLc==='crystal label flatbed' ? 'selected' : '' }}>Crystal Label Flatbed</option>
                                        <option value="Konica Minolta"                      {{ $prtLc==='konica minolta' ? 'selected' : '' }}>Konica Minolta</option>
                                      </select>
                                    </div>

                                    <div class="col-md-3">
                                      <label class="form-label">Cutter</label>
                                      <select name="products[{{ $pIndex }}][items][{{ $i }}][cutter]" class="form-select" {{ $disabled }}>
                                        <option value="">-</option>
                                        <option value="AOL 1000 Flatbed Cutter (Small)"  {{ $cutLc==='aol 1000 flatbed cutter (small)' ? 'selected' : '' }}>AOL 1000 Flatbed Cutter (Small)</option>
                                        <option value="AOL 5x10 Flatbed Cutter (big)"    {{ $cutLc==='aol 5x10 flatbed cutter (big)' ? 'selected' : '' }}>AOL 5x10 Flatbed Cutter (big)</option>
                                        <option value="Jingwei 5x10 Flatbed Cutter"      {{ $cutLc==='jingwei 5x10 flatbed cutter' ? 'selected' : '' }}>Jingwei 5x10 Flatbed Cutter</option>
                                        <option value="Ruijie Flatbed Router"            {{ $cutLc==='ruijie flatbed router' ? 'selected' : '' }}>Ruijie Flatbed Router</option>
                                        <option value="Laser Cutter 150 (A)"             {{ $cutLc==='laser cutter 150 (a)' ? 'selected' : '' }}>Laser Cutter 150 (A)</option>
                                        <option value="Laser Cutter 150 (B)"             {{ $cutLc==='laser cutter 150 (b)' ? 'selected' : '' }}>Laser Cutter 150 (B)</option>
                                        <option value="Laser Cutter 300"                 {{ $cutLc==='laser cutter 300' ? 'selected' : '' }}>Laser Cutter 300</option>
                                        <option value="Mimaki Cutting Plotte"            {{ $cutLc==='mimaki cutting plotte' ? 'selected' : '' }}>Mimaki Cutting Plotte</option>
                                        <option value="AccuCut"                          {{ $cutLc==='accucut' ? 'selected' : '' }}>AccuCut</option>
                                      </select>
                                    </div>
                                    <div class="col-md-3">
                                      <label class="form-label">Prime Centre</label>
                                      @php
                                        $pcRaw = old("products.$pIndex.items.$i.prime_centre", data_get($it, 'prime_centre'));
                                        $pc = ($pcRaw === '' || $pcRaw === null) ? '' : (string) ((int) $pcRaw);
                                      @endphp
                                      <select name="products[{{ $pIndex }}][items][{{ $i }}][prime_centre]" class="form-select" {{ $disabled }}>
                                        <option value="">-</option>
                                        <option value="1" {{ $pc === '1' ? 'selected' : '' }}>Yes</option>
                                        <option value="0" {{ $pc === '0' ? 'selected' : '' }}>No</option>
                                      </select>
                                    </div>
                                    <div class="col-md-12">
                                      <label class="form-label">Assemble</label>
                                      <select name="products[{{ $pIndex }}][items][{{ $i }}][finishing]" class="form-select" {{ $disabled }}>
                                        <option value="">-</option>
                                        <option value="yes"  {{ $assLc==='yes' ? 'selected' : '' }}>Yes</option>
                                        <option value="no"   {{ $assLc==='no' ? 'selected' : '' }}>No</option>
                                      </select>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            </div>
                            @endforeach
                          </div>

                          {{-- Template used for a new item (placeholders __i__ and __n__) --}}
                          <div
                            id="itemsContainer-{{ $pIndex }}"
                            class="items-container"
                            data-product-index="{{ $pIndex }}"
                            data-product-id="{{ $product->ProductID }}"
                            data-items='@json($product->items ?? [])'></div>
                          <template id="itemTemplate-{{ $pIndex }}" name="products[__PINDEX__][items][__INDEX__][field]">
                            <div class="accordion-item mb-3 border rounded" data-kind="item" id="item__PINDEX__-__INDEX__">
                              <div class="accordion-header d-flex align-items-center px-3 py-2" id="itemHdr__PINDEX__-__INDEX__">
                                <span class="fw-semibold">
                                  Item <span class="item-number">__INDEX_HUMAN__</span>
                                </span>
                                <span class="text-body-secondary ms-2 small item-summary"></span>

                                <div class="ms-auto d-flex align-items-center gap-2">
                                  @if ($submitted)
                                  <button type="button" class="btn btn-link p-0 text-danger delete-item" data-index="__INDEX__" title="Delete item" data-remove>
                                    <i class="bx bx-trash fs-5"></i>
                                  </button>
                                  @endif
                                  <button type="button" class="btn btn-link p-0 chevron"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#itemPane__PINDEX__-__INDEX__"
                                    aria-controls="itemPane__PINDEX__-__INDEX__"
                                    aria-expanded="false"
                                    title="Expand/Collapse">
                                    <i class="bx bx-chevron-down fs-4"></i>
                                  </button>
                                </div>
                              </div>

                              <div id="itemPane__PINDEX__-__INDEX__" class="accordion-collapse collapse show" data-bs-parent="#productItems-__PINDEX__">
                                <div class="accordion-body">

                                  <input type="hidden" name="products[__PINDEX__][items][__INDEX__][id]" value="">

                                  <div class="row g-3">
                                    <div class="col-md-6">
                                      <label class="form-label">Item Name</label>
                                      <input type="text" class="form-control" name="products[__PINDEX__][items][__INDEX__][itemName]" value="" {{ $readonly }}>
                                    </div>

                                    <div class="col-md-6">
                                      <label class="form-label">Quantity</label>
                                      <input type="number" min="0" class="form-control" name="products[__PINDEX__][items][__INDEX__][quantity]" value="" {{ $readonly }}>
                                    </div>

                                    <div class="col-12">
                                      <label class="form-label">Material</label>
                                      <div class="tags-input"
                                          data-name="products[__PINDEX__][items][__INDEX__][material][]"
                                          data-suggestions='@json($allMaterials ?? [])'
                                          data-values='[]'
                                          data-allow-custom="1" data-readonly="{{ $order->submit ? '1' : '0' }}">
                                      </div>
                                    </div>

                                    <div class="col-12 col-md-4">
                                      <label class="form-label">Unit (Size)</label>
                                      <select name="products[__PINDEX__][items][__INDEX__][sizeUnit]" class="form-select" {{ $disabled }}>
                                        <option value="mm" selected>mm</option>
                                        <option value="cm">cm</option>
                                        <option value="inch">inch</option>
                                        <option value="ft">ft</option>
                                      </select>
                                    </div>

                                    <div class="col-12 col-md-4">
                                      <label class="form-label">Size - Width</label>
                                      <input name="products[__PINDEX__][items][__INDEX__][sizeWidth]" type="number" step="0.01" class="form-control" value="" {{ $readonly }}>
                                    </div>
                                    <div class="col-12 col-md-4">
                                      <label class="form-label">Height</label>
                                      <input name="products[__PINDEX__][items][__INDEX__][sizeHeight]" type="number" step="0.01" class="form-control" value="" {{ $readonly }}>
                                    </div>

                                    <div class="col-12 col-md-4">
                                      <label class="form-label">Unit (Bleed)</label>
                                      <select name="products[__PINDEX__][items][__INDEX__][bleedUnit]" class="form-select">
                                        <option value="mm" selected>mm</option>
                                        <option value="cm">cm</option>
                                        <option value="inch">inch</option>
                                        <option value="ft">ft</option>
                                      </select>
                                    </div>
                                    <div class="col-12 col-md-2">
                                      <label class="form-label">Bleed (Top)</label>
                                      <input name="products[__PINDEX__][items][__INDEX__][bleedTop]" type="number" step="0.01" class="form-control" value="" {{ $readonly }}>
                                    </div>
                                    <div class="col-12 col-md-2">
                                      <label class="form-label">Bottom</label>
                                      <input name="products[__PINDEX__][items][__INDEX__][bleedBottom]" type="number" step="0.01" class="form-control" value="" {{ $readonly }}>
                                    </div>
                                    <div class="col-12 col-md-2">
                                      <label class="form-label">Left</label>
                                      <input name="products[__PINDEX__][items][__INDEX__][bleedLeft]" type="number" step="0.01" class="form-control" value="" {{ $readonly }}>
                                    </div>
                                    <div class="col-12 col-md-2">
                                      <label class="form-label">Right</label>
                                      <input name="products[__PINDEX__][items][__INDEX__][bleedRight]" type="number" step="0.01" class="form-control" value="" {{ $readonly }}>
                                    </div>

                                    <div class="col-md-3">
                                      <label class="form-label">Lamination</label>
                                      <select name="products[__PINDEX__][items][__INDEX__][lamination]" class="form-select" {{ $disabled }}>
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
                                      <select name="products[__PINDEX__][items][__INDEX__][printer]" class="form-select" {{ $disabled }}>
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
                                      </select>
                                    </div>

                                    <div class="col-md-3">
                                      <label class="form-label">Cutter</label>
                                      <select name="products[__PINDEX__][items][__INDEX__][cutter]" class="form-select" {{ $disabled }}>
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

                                    <div class="col-md-3">
                                      <label class="form-label">Prime Centre</label>
                                      <select name="products[__PINDEX__][items][__INDEX__][prime_centre]" class="form-select" {{ $disabled }}>
                                        <option value="">-</option>
                                        <option>Yes</option>
                                        <option>No</option>
                                      </select>
                                    </div>

                                    <div class="col-md-12">
                                      <label class="form-label">Assemble</label>
                                      <select name="products[__PINDEX__][items][__INDEX__][finishing]" class="form-select" {{ $disabled }}>
                                        <option value="">-</option>
                                        <option>Yes</option>
                                        <option>No</option>
                                      </select>
                                    </div>
                                  </div>

                                </div>
                              </div>
                            </div>
                          </template>

                          {{-- Delivery Breakdown (repeater) --}}
                          @php
                            $pTotal     = (int) ($product->totalQuantity ?? 0);
                            $pDelivered = (int) ($product->deliveryBreakdowns?->sum('quantity') ?? 0);
                            $pRemain    = max($pTotal - $pDelivered, 0);
                          @endphp
                          <div class="d-flex align-items-center justify-content-between mt-4 mb-2">
                            <h6 class="mb-0">Delivery Breakdown</h6>
                            <div class="ms-auto d-flex align-items-center gap-3 small text-muted" style="margin-right: 10px;"
                                id="del-summary-{{ $pIndex }}">
                              <span> <strong>Total:</strong>
                                <span id="del-sum-total-{{ $pIndex }}">{{ $pTotal }}</span>
                              </span>
                              <span> <strong>Delivered:</strong>
                                <span id="del-sum-delivered-{{ $pIndex }}">{{ $pDelivered }}</span>
                              </span>
                              <span> <strong>Remaining:</strong>
                                <span id="del-sum-remaining-{{ $pIndex }}">{{ $pRemain }}</span>
                              </span>
                            </div>
                            @if ($submitted)
                            <button type="button"
                                    class="btn btn-sm btn-outline-primary"
                                    id="addDeliveryBtn-{{ $pIndex }}">
                              <i class="bx bx-plus me-1"></i> Add Delivery Breakdown
                            </button>
                            @endif
                          </div>
                            
                          <div id="deliveriesWrap-{{ $pIndex }}" class="vstack gap-3">
                            @php
                              // Only the deliveries for this product:
                              $productDeliveries = $product->deliveryBreakdowns ?? collect();
                            @endphp

                            @forelse ($productDeliveries as $i => $d)
                              <div class="card mb-3"
                                  data-delivery
                                  data-id="{{ $d->getKey() }}"
                                  data-url="{{ route('artist.orders.delivery.destroy', ['order' => $order, 'delivery' => $d->getKey()]) }}">
                                <div class="card-body">
                                  <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="fw-semibold">Delivery <span class="delivery-index">{{ $i + 1 }}</span></div>
                                    @if ($submitted)
                                      <button type="button" class="btn btn-link p-0 text-danger delete-delivery" title="Delete" data-remove>
                                        <i class="bx bx-trash fs-5"></i>
                                      </button>
                                    @endif
                                  </div>

                                  @php
                                    $dtValue = '';
                                    try {
                                      $dateOnly = !empty($d->date) ? \Illuminate\Support\Carbon::parse($d->date)->toDateString() : null;
                                      $timeOnly = !empty($d->time) ? \Illuminate\Support\Carbon::parse($d->time)->format('H:i') : null;
                                      $dtValue  = $dateOnly && $timeOnly ? ($dateOnly.'T'.$timeOnly) : ($dateOnly ? $dateOnly.'T00:00' : '');
                                    } catch (\Throwable $e) { $dtValue = ''; }

                                    $methodVal = strtolower((string)
                                        old("products.$pIndex.deliveries.$i.method", data_get($d,'method'))
                                    );

                                    $methodOptions = [
                                      'courier'               => 'Courier',
                                      'self_pickup'           => 'Self Pickup',
                                      'delivery_installation' => 'Delivery & Installation',
                                    ];

                                    // installation type + outsource cost
                                    $insTypeVal = strtolower((string)
                                        old("products.$pIndex.deliveries.$i.deliver_install_type", data_get($d,'deliver_install_type'))
                                    );
                                    $insTypeOptions = [
                                      'in_house'  => 'In-house',
                                      'outsource' => 'Outsource',
                                      'both'      => 'Both',
                                    ];

                                    $costVal = old("products.$pIndex.deliveries.$i.outsource_cost", data_get($d,'outsource_cost'));

                                    // initial enable/disable state (server-side)
                                    $isDI        = ($methodVal === 'delivery_installation');
                                    $costEnabled = $isDI && in_array($insTypeVal, ['outsource','both'], true);

                                    $insDisabledAttr  = trim($disabled.' '.($isDI ? '' : 'disabled'));
                                    $costDisabledAttr = trim($readonly.' '.($costEnabled ? '' : 'disabled'));
                                  @endphp

                                  <input type="hidden"
                                        name="products[{{ $pIndex }}][deliveries][{{ $i }}][id]"
                                        value="{{ $d->getKey() }}">

                                  <div class="row g-3 align-items-end" data-delivery-row>
                                    {{-- Method --}}
                                    <div class="col-12 col-md-4">
                                      <label class="form-label">Delivery Method</label>
                                      <select name="products[{{ $pIndex }}][deliveries][{{ $i }}][method]"
                                              class="form-select"
                                              data-method-select
                                              {{ $disabled }}>
                                        <option value="">Method</option>
                                        @foreach ($methodOptions as $k => $label)
                                          <option value="{{ $k }}" {{ $methodVal === $k ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                      </select>
                                    </div>

                                    {{-- Installation Type --}}
                                    <div class="col-12 col-md-4">
                                      <label class="form-label">Installation Type</label>
                                      <select name="products[{{ $pIndex }}][deliveries][{{ $i }}][deliver_install_type]"
                                              class="form-select"
                                              data-install-type
                                              {{ $insDisabledAttr }}>
                                        <option value="">Select…</option>
                                        @foreach ($insTypeOptions as $k => $label)
                                          <option value="{{ $k }}" {{ $insTypeVal === $k ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                      </select>
                                    </div>

                                    {{-- Outsource Cost --}}
                                    <div class="col-12 col-md-4">
                                      <label class="form-label">Costing (RM)</label>
                                      <input type="number" step="0.01" min="0"
                                            class="form-control"
                                            name="products[{{ $pIndex }}][deliveries][{{ $i }}][outsource_cost]"
                                            value="{{ $costVal }}"
                                            data-outsource-cost
                                            {{ $costDisabledAttr }}>
                                    </div>

                                    {{-- Location --}}
                                    <div class="col-12 col-md-4">
                                      <label class="form-label">Location Address</label>
                                      <input type="text" class="form-control"
                                            name="products[{{ $pIndex }}][deliveries][{{ $i }}][location]"
                                            value="{{ $d->location }}" {{ $readonly }}>
                                    </div>

                                    {{-- Quantity --}}
                                    <div class="col-12 col-md-4">
                                      <label class="form-label">Quantity</label>
                                      <input type="number" class="form-control del-qty"
                                            name="products[{{ $pIndex }}][deliveries][{{ $i }}][quantity]"
                                            value="{{ $d->quantity }}" {{ $readonly }}>
                                    </div>

                                    {{-- Date & Time --}}
                                    <div class="col-12 col-md-4">
                                      <label class="form-label">Date &amp; Time</label>
                                      <input type="datetime-local" class="form-control"
                                            name="products[{{ $pIndex }}][deliveries][{{ $i }}][datetime]"
                                            value="{{ $dtValue }}" {{ $readonly }}>
                                    </div>
                                  </div>
                                </div>
                              </div>
                            @empty
                            @endforelse
                          </div>

                          <template id="deliveryTemplate-{{ $pIndex }}">
                            <div class="card border shadow-none" data-delivery>
                              <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                  <strong>Delivery <span class="delivery-index">__INDEX_HUMAN__</span></strong>
                                  @if ($submitted)
                                  <button type="button" class="btn btn-link p-0 text-danger delete-delivery" title="Delete" data-remove>
                                    <i class="bx bx-trash fs-5"></i>
                                  </button>
                                  @endif
                                </div>

                                <input type="hidden" name="products[{{ $pIndex }}][deliveries][__INDEX__][id]" value="">

                                <div class="row g-3 align-items-end" data-delivery-row>
                                  <div class="col-12 col-md-4">
                                    <label class="form-label">Delivery Method</label>
                                    <select name="products[{{ $pIndex }}][deliveries][__INDEX__][method]" class="form-select" data-method-select>
                                      <option value="">Method</option>
                                      <option value="courier">Courier</option>
                                      <option value="delivery_installation">Delivery & Installation</option>
                                      <option value="self_pickup">Self Pickup</option>
                                    </select>
                                  </div>

                                  <div class="col-12 col-md-4">
                                    <label class="form-label">Installation Type</label>
                                    <select name="products[{{ $pIndex }}][deliveries][__INDEX__][deliver_install_type]" class="form-select" data-install-type disabled>
                                      <option value="">Select…</option>
                                      <option value="in_house">In-house</option>
                                      <option value="outsource">Outsource</option>
                                      <option value="both">Both</option>
                                    </select>
                                  </div>

                                  <div class="col-12 col-md-4">
                                    <label class="form-label">Costing (RM)</label>
                                    <input type="number" step="0.01" min="0"
                                          name="products[{{ $pIndex }}][deliveries][__INDEX__][outsource_cost]"
                                          class="form-control"
                                          data-outsource-cost
                                          disabled>
                                  </div>

                                  <div class="col-12 col-md-4">
                                    <label class="form-label">Location Address</label>
                                    <input type="text" name="products[{{ $pIndex }}][deliveries][__INDEX__][location]" class="form-control">
                                  </div>

                                  <div class="col-12 col-md-4">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" name="products[{{ $pIndex }}][deliveries][__INDEX__][quantity]" class="form-control del-qty">
                                  </div>

                                  <div class="col-12 col-md-4">
                                    <label class="form-label">Date &amp; Time</label>
                                    <input type="datetime-local" name="products[{{ $pIndex }}][deliveries][__INDEX__][datetime]" class="form-control">
                                  </div>
                                </div>
                              </div>
                            </div>
                          </template>

                          {{-- Product Remarks --}}
                          <div class="mt-4">
                            <h6 class="mb-2">Product Remarks</h6>

                            <div id="remarks-wrap-{{ $pIndex }}">
                              @php
                                $ops  = [
                                  'printing'     => 'Printing',
                                  'furnishing'   => 'Furnishing',
                                  'installation' => 'Installation',
                                  'courier'      => 'Courier',
                                  'self_pickup'  => 'Self Pickup',
                                ];
                                $rows = $product->remarks ?? collect();
                              @endphp

                              @forelse($rows as $r)
                                <div class="d-flex align-items-center gap-2 mb-2 remark-row" data-remark data-id="{{ $r->RemarkID }}" data-url="{{ route('artist.orders.remarks.destroy', [$order, $r->RemarkID]) }}">
                                  <input type="hidden" name="products[{{ $pIndex }}][remarks][{{ $loop->index }}][id]" value="{{ $r->RemarkID }}">
                                  <select name="products[{{ $pIndex }}][remarks][{{ $loop->index }}][operation]" class="form-select w-auto" style="min-width:160px;" {{$disabled}}>
                                    <option value="">— Select —</option>
                                    @foreach($ops as $k => $label)
                                      <option value="{{ $k }}" @selected(old("products.$pIndex.remarks.$loop->index.operation", $r->operation) === $k)>{{ $label }}</option>
                                    @endforeach
                                  </select>
                                  <input type="text"
                                        name="products[{{ $pIndex }}][remarks][{{ $loop->index }}][remark]"
                                        class="form-control"
                                        placeholder="Write a note…" {{ $readonly }}
                                        value="{{ old("products.$pIndex.remarks.$loop->index.remark", $r->remark) }}">
                                  @if ($submitted)
                                  <button type="button" class="btn btn-link text-danger p-0 remove-remark" title="Delete">
                                    <i class="bx bx-trash fs-5"></i>
                                  </button>
                                  @endif
                                </div>
                              @empty
                                <div class="d-flex align-items-center gap-2 mb-2 remark-row" data-remark>
                                  <select name="products[{{ $pIndex }}][remarks][0][operation]" class="form-select w-auto" style="min-width:160px;" {{$disabled}}>
                                    <option value="">— Select —</option>
                                    @foreach($ops as $k => $label)
                                      <option value="{{ $k }}">{{ $label }}</option>
                                    @endforeach
                                  </select>
                                  <input type="text" name="products[{{ $pIndex }}][remarks][0][remark]" class="form-control" placeholder="Write a note…" {{ $readonly }}>
                                  @if ($submitted)
                                  <button type="button" class="btn btn-link text-danger p-0 remove-remark" title="Delete">
                                    <i class="bx bx-trash fs-5"></i>
                                  </button>
                                  @endif
                                </div>
                              @endforelse
                            </div>
                            @if ($submitted)
                            <button type="button" id="add-remark-{{ $pIndex }}" class="btn btn-sm btn-outline-secondary mt-2">
                              <i class="bx bx-plus"></i> Add Remarks
                            </button>
                            @endif
                            {{-- per-product delete bin --}}
                            <div id="delete-remarks-bin-{{ $pIndex }}"></div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </fieldset>
                </div>
              </div>
            @endforeach

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
                  class="file-overlay" {{ $readonly }}>
              </div>

              {{-- Existing order files --}}
              <div class="mt-3">
                @php
                  // show trash only when order is still a draft (not submitted)
                  $canDeleteOrderFiles = ((int)($order->draft ?? 0) === 1) && (int)($order->submit ?? 0) === 0;
                @endphp

                <label class="form-label">Existing files</label>

                @if(isset($orderFiles) && count($orderFiles))
                  <div class="d-flex flex-column gap-2">
                    @foreach($orderFiles as $f)
                      <div class="d-flex align-items-center justify-content-between border rounded p-2"
                          data-file-row data-path="{{ $f['path'] }}">
                        <div class="d-flex align-items-center gap-2">
                          <i class="bx bx-file"></i>
                          <a href="{{ $f['url'] }}" target="_blank" class="text-decoration-none">{{ $f['name'] }}</a>
                          <small class="text-muted">.{{ $f['ext'] }}</small>
                        </div>

                        @if($canDeleteOrderFiles)
                          <button type="button"
                                  class="btn btn-sm btn-outline-danger delete-order-file"
                                  title="Delete"
                                  data-url="{{ route('artist.orders.attachments.destroy', $order) }}"
                                  data-path="{{ $f['path'] }}">
                            <i class="bx bx-trash"></i>
                          </button>
                        @endif
                      </div>
                    @endforeach
                  </div>
                @else
                  <div class="text-body-secondary">No files uploaded yet.</div>
                @endif
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
        <input type="hidden" name="submit" id="submit-input" value="0">
        @if(!$isSubmitted)
        <button type="submit" name="is_draft" onclick="document.getElementById('submit-input').value=0" class="btn btn-secondary" id="btn-draft">Save Draft</button>
        <button type="submit" name="is_draft" onclick="document.getElementById('submit-input').value=1" class="btn btn-primary" id="btn-submit" {{ $disabled }}>Save & Submit</button>
        @endif
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
        @if ($submitted)
        <button type="button" class="btn btn-sm btn-text text-danger" data-remove><i class="bx bx-trash"></i></button>
        @endif
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
          <label class="form-label">Size - Width</label>
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
        @if ($submitted)
        <button type="button" class="btn btn-sm btn-text text-danger" data-remove><i class="bx bx-trash"></i></button>
        @endif
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
    // Accordion: setup  (per-product scoping)
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

    function initTagsInput(container) {
      if (!container || container.dataset._bound === '1') return;
      container.dataset._bound = '1';

      // read data-* from Blade
      const name = container.dataset.name; // e.g. items[3][material][]
      const suggestions = JSON.parse(container.dataset.suggestions || '[]');
      const initial = JSON.parse(container.dataset.values || '[]');
      const allowCustom = container.dataset.allowCustom === '1';
      const isReadonly = container.dataset.readonly === '1';

      // build UI
      container.innerHTML = '';
      const wrap = document.createElement('div');
      wrap.className = 'ti-wrap';
      const box = document.createElement('div');
      box.className = 'ti';
      box.tabIndex = 0;
      const input = document.createElement('input');
      input.className = 'ti-input';
      input.placeholder = isReadonly ? '' : 'Click to select…';
      if (isReadonly) {
        input.readOnly = true;
        input.classList.add('bg-light'); 
      } else {
        input.readOnly = false;     
        input.classList.remove('bg-light');
      }
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
        if (selected.size === 0 && isReadonly) {
          // no material selected, show static grey text
          const noMat = document.createElement('span');
          noMat.className = 'text-muted small';
          noMat.textContent = 'No material selected';
          box.appendChild(noMat);
          return;
        }

        selected.forEach(v => {
          const chip = document.createElement('span');
          chip.className = 'ti-chip';
          chip.textContent = v;

          if (!isReadonly) {
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.innerHTML = '&times;';
            btn.addEventListener('click', () => {
              selected.delete(v);
              renderChips();
              buildList();
            });
            chip.appendChild(btn);
          }

          box.insertBefore(chip, input);
          container.appendChild(hidden(name, v));
        });
      }

      function buildList() {
        if (isReadonly) {
          dd.style.display = 'none';
          return;
        }
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

      if (!isReadonly) {
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
      } else {
        wrap.classList.add('ti-disabled');
      }

      renderChips(); // ← show chips for initial values from DB
    }

    function initAllTagsInputs(root = document) {
      root.querySelectorAll('.tags-input').forEach(initTagsInput);
    }

    initAllTagsInputs(document);

    document.querySelectorAll('.accordion-collapse[id^="pCollapse"]').forEach((root) => {
      const m = root.id.match(/^pCollapse(\d+)$/);
      const pIndex = m ? m[1] : '0';

      // ----- ITEMS (scoped to this product) -----
      const acc     = root.querySelector('#productItems-' + pIndex);
      const tplEl   = root.querySelector('#itemTemplate-' + pIndex);
      const addItem = root.querySelector('[data-add-item]');

      if (acc && tplEl) {
        let nextIndex = parseInt(
          acc.dataset.nextIndex ?? String(acc.querySelectorAll('.accordion-item[data-kind="item"]').length),
          10
        );
        if (!Number.isFinite(nextIndex)) nextIndex = 0;

        function addItemRow() {
          const humanNum = acc.querySelectorAll('.accordion-item[data-kind="item"]').length + 1;
          const html = tplEl.innerHTML
            .replace(/__INDEX__/g, String(nextIndex))
            .replace(/__INDEX_HUMAN__/g, String(humanNum))
            .replace(/__PINDEX__/g, String(pIndex));
          const frag = document.createRange().createContextualFragment(html);
          const row  = frag.firstElementChild;
          if (!row) return;

          acc.appendChild(row);

          // open collapse in this product only
          const pane = row.querySelector('.accordion-collapse');
          const btn  = row.querySelector('[data-bs-toggle="collapse"]');
          if (pane) {
            pane.setAttribute('data-bs-parent', `#${acc.id}`);
            bootstrap.Collapse.getOrCreateInstance(pane, { toggle: false }).show();
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
        }

        function wireRow(wrap) {
          if (!wrap || wrap.dataset.wired === '1') return;
          wrap.dataset.wired = '1';
          const localRemoveBtn = wrap.querySelector('[data-remove]');
          if (localRemoveBtn) {
            localRemoveBtn.addEventListener('click', (e) => {
              e.preventDefault();
              wrap.remove();
              renumberOnly();
              validateItems();
              acc.dataset.nextIndex = String(
                acc.querySelectorAll('.accordion-item[data-kind="item"]').length
              );
            });
          }
          wrap.addEventListener('input', () => updateSummary(wrap), { passive: true });
        }

        function updateSummary(wrap) {
          const name = wrap.querySelector('input[name$="[itemName]"]')?.value || '';
          const qty  = wrap.querySelector('input[name$="[quantity]"]')?.value
                    || wrap.querySelector('input[name$="[qty]"]')?.value || '';
          const el = wrap.querySelector('.item-summary');
          if (el) el.textContent = name + (qty ? ` • ${qty}` : '');
        }

        function renumberOnly() {
          const items = acc.querySelectorAll('.accordion-item[data-kind="item"]');
          items.forEach((el, idx) => {
            el.querySelectorAll('.item-number').forEach(n => n.textContent = String(idx + 1));
            const pane  = el.querySelector('.accordion-collapse');
            const paneId = `${acc.id}-pane-${idx}`;
            const itemId = `${acc.id}-item-${idx}`;
            if (pane) pane.id = paneId;
            const b = el.querySelector('[data-bs-toggle="collapse"]');
            if (b) {
              b.setAttribute('data-bs-target', `#${paneId}`);
              b.setAttribute('aria-controls', paneId);
            }
            el.id = itemId;
          });
          acc.dataset.nextIndex = String(items.length);
        }

        function sumItemQty() {
          let sum = 0;
          acc.querySelectorAll('input[name$="[quantity]"], input[name$="[qty]"]').forEach(inp => {
            const v = parseFloat(inp.value || '0');
            if (!Number.isNaN(v)) sum += v;
          });
          return sum;
        }

        function getTotalAllowed() {
          const totalQtyEl =
            root.querySelector('input[name="product[qty_total]"]') ||
            document.getElementById('totalQty');
          const v = (totalQtyEl?.value ?? '').trim();
          const n = parseFloat(v);
          return Number.isFinite(n) ? n : 0;
        }

        function setItemQtyValidity(ok, msg = '') {
          const id  = `item-qty-msg-${pIndex}`;
          let box = root.querySelector('#' + id);
          if (!box) {
            box = document.createElement('div');
            box.id = id;
            box.className = 'mt-2 small text-danger';
            acc.parentElement.insertBefore(box, acc.nextSibling);
          }
          box.textContent = ok ? '' : msg;

          acc.querySelectorAll('input[name$="[quantity]"], input[name$="[qty]"]').forEach(inp => {
            inp.classList.toggle('is-invalid', !ok);
            inp.setAttribute('aria-invalid', String(!ok));
          });

          // disable submit buttons if invalid
          document.getElementById('btn-submit')?.toggleAttribute('disabled', !ok);
          document.getElementById('btn-draft')?.toggleAttribute('disabled', !ok);
        }

        function validateItems() {
          const total = getTotalAllowed();
          const sum   = sumItemQty();
          // setItemQtyValidity(sum <= total,
          //   sum <= total ? '' : `Item quantities (${sum}) exceed Total Quantity (${total}).`
          // );
        }
        
        function updateDeliverySummaryBar() {
          const totalEl = document.getElementById('del-sum-total-' + pIndex);
          const delEl   = document.getElementById('del-sum-delivered-' + pIndex);
          const remEl   = document.getElementById('del-sum-remaining-' + pIndex);
          if (!totalEl || !delEl || !remEl) return;

          const total = getTotalAllowed();   // already defined in your code
          const delivered = sumDeliveryQty();// already defined in your code
          const remaining = Math.max(total - delivered, 0);

          totalEl.textContent = String(total);
          delEl.textContent   = String(delivered);
          remEl.textContent   = String(remaining);
        }

        // wire existing & hook add
        acc.querySelectorAll('.accordion-item[data-kind="item"]').forEach((wrap) => {
          wireRow(wrap);
          updateSummary(wrap);
          initAllTagsInputs(wrap);
        });
        renumberOnly();
        addItem?.addEventListener('click', (e) => { e.preventDefault(); addItemRow(); });
        acc.addEventListener('input', (e) => {
          if (e.target.matches('input[name$="[quantity]"], input[name$="[qty]"]')) {
            validateItems();
          }
        });
        validateItems();
      }

      // ----- DELIVERIES (scoped to this product) -----
      const delWrap = root.querySelector('#deliveriesWrap-' + pIndex);
      const addDel  = root.querySelector('#addDeliveryBtn-' + pIndex);
      const delTpl  = root.querySelector('#deliveryTemplate-' + pIndex);

      if (delWrap && delTpl) {
        function reindexDeliveries() {
          delWrap.querySelectorAll('[data-delivery]').forEach((card, i) => {
            const idxEl = card.querySelector('.delivery-index');
            if (idxEl) idxEl.textContent = i + 1;

            card.querySelectorAll('[name]').forEach((el) => {
              el.name = el.name
                .replace(
                  new RegExp(`products\\[${pIndex}\\]\\[deliveries\\]\\[__INDEX__\\]`, 'g'),
                  `products[${pIndex}][deliveries][${i}]`
                )
                .replace(
                  new RegExp(`products\\[${pIndex}\\]\\[deliveries\\]\\[\\d+\\]`),
                  `products[${pIndex}][deliveries][${i}]`
                );
            });
          });
        }

        function addDelivery() {
          const idx  = delWrap.querySelectorAll('[data-delivery]').length;
          const html = delTpl.innerHTML
            .replace(/__INDEX__/g, idx)
            .replace(/__INDEX_HUMAN__/g, idx + 1);
          const tmp = document.createElement('div');
          tmp.innerHTML = html.trim();
          const node = tmp.firstElementChild;
          if (!node) return;
          delWrap.appendChild(node);
          reindexDeliveries();
          validateDeliveries();
        }

        addDel?.addEventListener('click', (e) => { e.preventDefault(); addDelivery(); });

        delWrap.addEventListener('click', async (e) => {
          const btn = e.target.closest('.delete-delivery');
          if (!btn) return;
          e.preventDefault();

          const card = btn.closest('[data-delivery]');
          if (!card || card.dataset.deleting === '1') return; // guard against double click

          const id  = card?.dataset.id || card?.querySelector('input[name$="[id]"]')?.value || '';
          const url = card?.dataset.url || '';

          const confirmed = await (window.Swal
            ? Swal.fire({
                icon: 'warning',
                title: 'Delete this delivery?',
                text: id ? 'This will delete it permanently.' : 'This will remove the row.',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#d33'
              }).then(r => r.isConfirmed)
            : Promise.resolve(confirm('Delete this delivery?'))
          );
          if (!confirmed) return;

          async function removeCard() {
            card.remove();
            reindexDeliveries();
            validateDeliveries();
            if (window.Swal) {
              Swal.fire({ icon: 'success', title: 'Deleted', timer: 1100, showConfirmButton: false });
            }
          }

          // If it’s an unsaved card (no ID), just remove from the DOM.
          if (!id || !url) {
            await removeCard();
            return;
          }

          // Saved row → call server; only remove when it succeeds.
          card.dataset.deleting = '1';
          btn.disabled = true;

          try {
            const res = await fetch(url, {
              method: 'DELETE',
              credentials: 'same-origin',
              headers: {
                'X-CSRF-TOKEN': window.CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
              }
            });

            // Try to parse JSON; if not JSON, make an empty object
            let data = {};
            try { data = await res.json(); } catch {}

            if (res.ok && data?.ok) {
              await removeCard();
            } else {
              const msg = data?.message || `HTTP ${res.status}`;
              if (window.Swal) {
                await Swal.fire({ icon: 'error', title: 'Delete failed', text: msg });
              } else {
                alert('Delete failed: ' + msg);
              }
              // DO NOT remove the card when delete fails
            }
          } catch (err) {
            if (window.Swal) {
              await Swal.fire({ icon: 'error', title: 'Network error', text: String(err) });
            } else {
              alert('Network error: ' + err);
            }
          } finally {
            delete card.dataset.deleting;
            btn.disabled = false;
          }
        });

        // qty guard just for this product’s deliveries
        function getTotalAllowed() {
          const totalEl = root.querySelector('input[name="product[qty_total]"]') || document.getElementById('totalQty');
          const n = parseFloat((totalEl?.value ?? '').trim());
          return Number.isFinite(n) ? n : 0;
        }

        function sumDeliveryQty() {
          let sum = 0;
          delWrap.querySelectorAll('.del-qty').forEach(inp => { const v = parseFloat(inp.value || '0'); if (!Number.isNaN(v)) sum += v; });
          return sum;
        }

        function setQtyValidity(ok, msg = '') {
          const id = `del-qty-msg-${pIndex}`;
          let box = root.querySelector('#' + id);
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
          const ok = sumDeliveryQty() <= getTotalAllowed();
          setQtyValidity(ok, ok ? '' : 'Delivery quantities exceed Product Total Quantity.');
          updateDeliverySummaryBar();
        }

        delWrap.addEventListener('input', (e) => {
          if (e.target.matches('.del-qty') || e.target.closest('.del-qty')) validateDeliveries();
        });

        // first pass
        reindexDeliveries();
        validateDeliveries();
      }

      const remarksWrap = root.querySelector('#remarks-wrap-' + pIndex);
      const addRemarkBtn = root.querySelector('#add-remark-' + pIndex);
      const deleteBin = root.querySelector('#delete-remarks-bin-' + pIndex);

      function reindexRemarks() {
        if (!remarksWrap) return;
        remarksWrap.querySelectorAll('[data-remark]').forEach((row, i) => {
          row.querySelectorAll('select[name], input[name]').forEach((el) => {
            el.name = el.name
              .replace(
                new RegExp(`products\\[${pIndex}\\]\\[remarks\\]\\[\\d+\\]`, 'g'),
                `products[${pIndex}][remarks][${i}]`
              );
          });
        });
      }

      function addRemarkRow() {
        if (!remarksWrap) return;
        const i = remarksWrap.querySelectorAll('[data-remark]').length;
        const div = document.createElement('div');
        div.className = 'd-flex align-items-center gap-2 mb-2 remark-row';
        div.setAttribute('data-remark', '');
        div.innerHTML = `
          <select name="products[${pIndex}][remarks][${i}][operation]" class="form-select w-auto" style="min-width:160px;" {{$disabled}}>
            <option value="">— Select —</option>
            <option value="printing">Printing</option>
            <option value="furnishing">Furnishing</option>
            <option value="installation">Installation</option>
            <option value="self pickup">Self Pickup</option>
            <option value="courier">Courier</option>
          </select>
          <input type="text" name="products[${pIndex}][remarks][${i}][remark]" class="form-control" placeholder="Write a note…" {{$readonly}}>
          <button type="button" class="btn btn-link text-danger p-0 remove-remark" title="Delete">
            <i class="bx bx-trash fs-5"></i>
          </button>`;
        remarksWrap.appendChild(div);
      }

      addRemarkBtn?.addEventListener('click', (e) => {
        e.preventDefault();
        addRemarkRow();
      });

      remarksWrap?.addEventListener('click', async (e) => {
        const btn = e.target.closest('.remove-remark');
        if (!btn) return;

        const row = btn.closest('[data-remark]');
        const id  = row?.dataset?.id || '';
        const url = row?.dataset?.url || '';

        // If this is an existing remark and we have a URL, try live DELETE
        if (id && url) {
          try {
            // Optional confirm
            if (window.Swal) {
              const c = await Swal.fire({
                icon: 'warning',
                title: 'Delete this remark?',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#d33'
              });
              if (!c.isConfirmed) return;
            } else if (!confirm('Delete this remark?')) {
              return;
            }

            const res = await fetch(url, {
              method: 'DELETE',
              credentials: 'same-origin',
              headers: {
                'X-CSRF-TOKEN': window.CSRF_TOKEN,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
              }
            });

            let data = {};
            try { data = await res.json(); } catch {}

            if (res.ok && data?.ok) {
              row.remove();
              reindexRemarks();
              if (window.Swal) {
                Swal.fire({ icon: 'success', title: 'Remark deleted', timer: 1000, showConfirmButton: false });
              }
              return; // done
            }

            // If server refused, fall back to deferred delete on Save
            const msg = data?.message || `HTTP ${res.status}`;
            if (window.Swal) await Swal.fire({ icon: 'warning', title: 'Will delete on Save', text: msg });
            // fall through to bin push

          } catch (err) {
            // Network error → fall back to deferred delete on Save
            if (window.Swal) await Swal.fire({ icon: 'warning', title: 'Offline delete queued', text: String(err) });
            // fall through to bin push
          }
        }

        // Fallback / unsaved rows: push ID to delete bin if present, then remove from DOM
        if (id) {
          const hidden = document.createElement('input');
          hidden.type  = 'hidden';
          hidden.name  = `products[${pIndex}][delete_remarks][]`;
          hidden.value = id;
          deleteBin?.appendChild(hidden);
        }

        row.remove();
        reindexRemarks();
      });

      // First pass to normalize names
      reindexRemarks();
    });

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
          const acc = itemEl.closest('.accordion');
          if (acc) {
            const items = acc.querySelectorAll('.accordion-item[data-kind="item"]');
            items.forEach((el, idx) => {
              el.querySelectorAll('.item-number').forEach(n => n.textContent = String(idx + 1));
              const pane = el.querySelector('.accordion-collapse');
              const base = acc.id || 'productItems';
              const paneId = `${base}-pane-${idx}`;
              const itemId = `${base}-item-${idx}`;
              if (pane) pane.id = paneId;
              const btn = el.querySelector('[data-bs-toggle="collapse"]');
              if (btn) {
                btn.setAttribute('data-bs-target', `#${paneId}`);
                btn.setAttribute('aria-controls', paneId);
              }
              el.id = itemId;
            });
            acc.dataset.nextIndex = String(items.length);
          }
          return;
        }

        // Existing item → call server
        await deleteItemOnServer(url);

        // Remove from DOM
        itemEl.remove();
        const acc = itemEl.closest('.accordion');
        if (acc) {
          const items = acc.querySelectorAll('.accordion-item[data-kind="item"]');
          items.forEach((el, idx) => {
            el.querySelectorAll('.item-number').forEach(n => n.textContent = String(idx + 1));
            const pane = el.querySelector('.accordion-collapse');
            const base = acc.id || 'productItems';
            const paneId = `${base}-pane-${idx}`;
            const itemId = `${base}-item-${idx}`;
            if (pane) pane.id = paneId;
            const btn = el.querySelector('[data-bs-toggle="collapse"]');
            if (btn) {
              btn.setAttribute('data-bs-target', `#${paneId}`);
              btn.setAttribute('aria-controls', paneId);
            }
            el.id = itemId;
          });
          acc.dataset.nextIndex = String(items.length);
        }

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

  document.addEventListener('click', async (e) => {
  const btn = e.target.closest('.delete-order-file');
  if (!btn) return;

  const url  = btn.dataset.url;
  const path = btn.dataset.path;
  const row  = btn.closest('[data-file-row]');
  if (!url || !path || !row) return;

  // confirm
  const ok = window.Swal
    ? (await Swal.fire({
        icon: 'warning',
        title: 'Delete this file?',
        text: 'This will remove it from the order.',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        confirmButtonColor: '#d33'
      })).isConfirmed
    : confirm('Delete this file?');

  if (!ok) return;

  // prevent double click
  if (btn.disabled) return;
  btn.disabled = true;

  try {
    const res = await fetch(url, {
      method: 'DELETE',
      credentials: 'same-origin',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': window.CSRF_TOKEN || document.querySelector('meta[name=csrf-token]')?.content || ''
      },
      body: JSON.stringify({ path })
    });

    const data = await res.json().catch(() => ({}));
    if (res.ok && data?.ok) {
      row.remove();
      if (window.Swal) Swal.fire({ icon:'success', title:'Deleted', timer:1100, showConfirmButton:false });
    } else {
      const msg = data?.message || `HTTP ${res.status}`;
      if (window.Swal) Swal.fire({ icon:'error', title:'Delete failed', text: msg });
      else alert('Delete failed: ' + msg);
      btn.disabled = false;
    }
  } catch (err) {
    if (window.Swal) Swal.fire({ icon:'error', title:'Network error', text:String(err) });
    else alert('Network error: ' + err);
    btn.disabled = false;
  }
});
function setDeliveryRowState(row){
    const methodSel = row.querySelector('[data-method-select]');
    const typeSel   = row.querySelector('[data-install-type]');
    const costInp   = row.querySelector('[data-outsource-cost]');
    if (!methodSel || !typeSel || !costInp) return;

    const method = (methodSel.value || '').toLowerCase();
    const isDI   = (method === 'delivery_installation');

    // Rule 1: only enabled when "Delivery & Installation"
    typeSel.disabled = !isDI;
    costInp.disabled = !isDI;

    // Rule 2: cost only enabled when type is outsource/both
    if (isDI){
      const t = (typeSel.value || '').toLowerCase();
      costInp.disabled = !(t === 'outsource' || t === 'both');
    }
  }

  document.addEventListener('change', function(e){
    if (e.target.matches('[data-method-select], [data-install-type]')) {
      const row = e.target.closest('[data-delivery-row]');
      if (row) setDeliveryRowState(row);
    }
  });

  // initialize on load
  document.querySelectorAll('[data-delivery-row]').forEach(setDeliveryRowState);
</script>
@endpush