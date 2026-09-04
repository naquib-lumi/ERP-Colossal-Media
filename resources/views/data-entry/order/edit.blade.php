@extends('layouts.app')

@section('title','New Job Order')

@section('content')
@push('styles')
<style>
  .reason-modal .modal-content { border: 0; overflow: hidden; }
  .reason-modal .modal-header { padding: 14px 16px; color: #fff; }
  .reason-modal .rm-chip{
    display:inline-flex;align-items:center;gap:.4rem;
    font-size:.75rem;font-weight:700;letter-spacing:.02em;
    padding:.25rem .6rem;border-radius:999px;background:#e9ecef;color:#212529
  }
  .reason-modal .rm-reason-box{
    border:1px solid rgba(0,0,0,.06);
    background:#fff;border-radius:.75rem;padding:14px
  }
  .reason-modal .rm-reason-text{white-space:pre-wrap;font-size:.95rem}
  .reason-modal.is-redo .modal-header{
    background: linear-gradient(135deg,#b00020 0%, #dc3545 60%, #ff6b6b 100%);
  }
  .reason-modal.is-redo .rm-chip{
    background:#ffe3e3;color:#b00020;border:1px solid #ffb3b3;
  }
  .reason-modal.is-reject .modal-header{
    background: linear-gradient(135deg,#e74c3c 0%, #ff6b6b 60%, #ffa8a8 100%);
  }
  .reason-modal.is-reject .rm-chip{
    background:#ffe1e1;color:#8a0018;border:1px solid #ffb3b3;
  }
  .reason-modal .btn-close-white{
    filter: brightness(0) invert(1); opacity:.85
  }
  .reason-modal .btn-close-white:hover{ opacity:1 }

  .redo-banner{
    display:inline-flex; align-items:center; gap:.5rem;
    background:#dc3545;           /* Bootstrap danger red */
    color:#fff;
    padding:.2rem .35rem;
    border-radius:12px;
    box-shadow:0 6px 14px rgba(220,53,69,.25);
    border:1px solid #b02a37;     /* darker red border */
  }
  .redo-banner .icon{ font-size:1.1rem; line-height:1; }
  .redo-banner .tag{
    background:rgba(255,255,255,.18);
    border:1px solid rgba(255,255,255,.35);
    color:#fff;
    border-radius:999px;
    padding:.2rem .55rem;
    font-weight:700;
    letter-spacing:.02em;
  }
  .redo-banner .by{
    font-weight:700;
    padding:.15rem .45rem;
    background:rgba(255,255,255,.12);
    border-radius:999px;
  }
  .redo-banner .reason{
    max-width:420px;
    white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
    padding:.15rem .45rem;
    background:#fff;
    color:#b02a37;
    border-radius:999px;
    border:1px solid #f1aeb5; /* bs-danger-subtle border */
  }
  /* small spacing helper so it doesn't crash into the title */
  .redo-offset { margin-left:.75rem; }
  .ti-disabled { opacity: 0.6; }
  .redo-reason{
    display:inline-flex;
    align-items:center;
    max-width: 48ch;             /* stays on one line */
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    padding: .20rem .55rem;
    border-radius: 999px;
    font-size: .875rem;
    font-weight: 500;
    background: #F4F6FF;         /* gentle indigo tint */
    color: #3842b0;
    border: 1px solid #E3E7FF;
  }

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

  input[readonly],
  select[disabled],
  textarea[readonly] {
    background-color: #f1f1f1 !important;
    pointer-events: none;
  }

  .del-summary-pill strong {
    color: #111827
  }

  .del-summary-pill span {
    white-space: nowrap
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
<meta name="csrf-token" content="{{ csrf_token() }}">

{{-- Loading overlay --}}
<div id="loading-overlay" class="overlay">
  <div class="overlay-box">Saving… please wait</div>
</div>

{{-- Validation errors (client-side 422) --}}
<div id="form-errors" class="text-danger small mb-2"></div>

<form id="order-form" action="{{ route('data-entry.orders.update', $order) }}" method="POST" enctype="multipart/form-data">
  @csrf
  @method('PUT')

  <input type="hidden" id="is_draft" name="is_draft" value="0">
  <input type="hidden" id="products-items-json" name="products_items_json" value="">

  @php
  $isSubmitted = isset($isSubmitted) ? (bool)$isSubmitted : ((int)($order->submit ?? 0) === 1);

  $readonly = $isSubmitted ? 'readonly disabled' : '';
  $disabled = $isSubmitted ? 'disabled' : '';

  $submitted = ((int)($order->draft ?? 0) === 1 || (int)($order->draft ?? 0) === 0) && (int)($order->submit ?? 0) === 0;

  $isRedo = false;
  if (!empty($order->redo) || (isset($order->order_number) && Str::endsWith($order->order_number, 'R'))) {
      $isRedo = true;
  }
  @endphp
  @php
    $user = auth()->user();
    $isArtist = $user->role === 'artist' || $user->role === 'head-artist' || $user->role === 'data-entry';
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

                @php
                    $permitVal = optional($order->products->first())->permit;

                    $permitText = match ((string) $permitVal) {
                        '1' => 'Yes',
                        '0' => 'No',
                        default => '-',
                    };
                @endphp

                <div class="col-md-4">
                    <label>Permit Required?</label>
                    <input type="text"
                          class="form-control"
                          value="{{ $permitText }}"
                          readonly>
                </div>

                <div class="col-12 col-md-4">
                  <label class="form-label">Created By</label>
                  <input type="text" class="form-control" value="{{ $order->salesperson->name ?? $order->created_by_name ?? '-' }}" readonly>
                  <input type="hidden" name="created_by" value="{{ $order->salesperson_id ?? $order->created_by_id }}">
                </div>

                @if(!empty($order->orderDetail))
                <div class="col-12 col-md-4">
                    <small class="text-muted d-block mb-1">Sales Remark</small>

                    <!-- Collapsed preview -->
                    <div class="fw-medium text-truncate"
                        style="max-height: 4.5em; overflow: hidden;"
                        data-bs-toggle="collapse"
                        data-bs-target="#remarkCollapse"
                        aria-expanded="false">
                        {{ $order->orderDetail }}
                    </div>
                    <div id="remarkCollapse" class="collapse mt-1">
                        <div class="fw-medium" style="white-space: pre-line;">
                            {{ $order->orderDetail }}
                        </div>
                    </div>
                </div>
                @endif

                <!-- <div class="col-12">
                  <label class="form-label d-flex align-items-center gap-2">
                    <span>Lead Attachments</span>
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
                </div> -->

                {{-- MOVE HERE if not artist --}}
                <div class="mt-3">
                    @if(isset($orderFilesSales) && count($orderFilesSales))
                          <div class="row mt-3">
                            <div class="col-12">
                              <label class="form-label fw-semibold">Existing Files (from Sales)</label>

                              <div class="d-flex flex-column gap-2">
                                @foreach($orderFilesSales as $f)
                                  <div class="d-flex align-items-center justify-content-between border rounded p-2">
                                    <div class="d-flex align-items-center gap-2 flex-wrap">
                                      <i class="bx bx-file"></i>
                                      <a href="{{ $f['url'] }}" target="_blank" class="text-decoration-none">
                                        {{ $f['name'] }}
                                      </a>
                                      <small class="text-muted">.{{ $f['ext'] }}</small>

                                      @if(!empty($f['uploaded_by']))
                                        <small class="text-muted">
                                          · Uploaded by {{ $f['uploaded_by'] }}
                                          @if(!empty($f['uploaded_at'])) · {{ $f['uploaded_at'] }} @endif
                                        </small>
                                      @endif
                                    </div>
                                  </div>
                                @endforeach
                              </div>
                            </div>
                          </div>
                        @endif
                </div>
              </div>
            </div>
          </div>

          <!-- add product btn -->
          <!-- <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0">Products</h5>
            @if(!$isSubmitted && !$isRedo)
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
              + Add Product
            </button>
            @endif
          </div> -->
          

          <div class="accordion" id="productsAcc">
            @foreach($order->products as $pIndex => $product)
            @php
            $isRedo = (bool) $order->redo;
            $selectedForRedo = $isRedo && (int) ($product->editable ?? 0) === 1;
            $locked = $isRedo && !$selectedForRedo;

            $redoRecord = DB::table('report_redo')
                ->where('OrderID', $order->redo ?: $order->id)
                ->latest('ReportID')
                ->first();

            $redoBy = null;
            if ($redoRecord && $redoRecord->user_id) {
                $redoBy = \App\Models\User::find($redoRecord->user_id)?->name;
            }
            @endphp
            <input type="hidden" name="products[{{ $pIndex }}][product_id]" value="{{ $product->ProductID }}">
            <div class="accordion-item {{ $locked ? 'opacity-75' : '' }}" data-product-row data-product-id="{{ $product->ProductID }}" data-url="{{ route('artist.orders.products.destroy', ['order' => $order->id, 'product' => $product->ProductID]) }}">
              <h2 class="accordion-header" id="pHead{{ $pIndex }}">
                <div class="d-flex justify-content-between align-items-center w-100">
                  <button
                    class="accordion-button {{ !$loop->first ? 'collapsed' : '' }}"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#pCollapse{{ $pIndex }}"
                    aria-expanded="{{ $loop->first ? 'true' : 'false' }}"
                    aria-controls="pCollapse{{ $pIndex }}">
                    Product #{{ $product->display_code ?? $loop->iteration }} — {{ $product->productName ?? 'Product' }}
                    {{-- REDO banner --}}
                      @if ($selectedForRedo)
                        <span
                          class="redo-banner redo-offset ms-2 js-reason-banner"
                          role="button"
                          tabindex="0"
                          data-reason="{{ $redoRecord->reason ?? '' }}"
                          data-by="{{ $redoBy ?? '' }}"
                          data-type="REDO"
                          aria-label="View redo reason"
                        >
                          <i class="bi bi-exclamation-octagon-fill icon"></i>
                          <span class="tag" style="font-size:12px;">REDO</span>
                          @if (!empty($redoRecord->reason))
                            <span style="font-size:12px;" class="reason">
                              {{ Str::limit($redoRecord->reason, 90) }}
                            </span>
                          @endif
                          @if ($redoBy)
                            <span class="by" style="font-size:12px;">by {{ $redoBy }}</span>
                          @endif
                        </span>
                      @endif
                  </button>

                  @if ($submitted)
                    <button type="button"
                            class="btn btn-link text-danger p-0 ms-2 me-3"
                            data-delete-product
                            aria-label="Delete product"
                            title="Delete this product"
                            style="text-decoration:none;">
                      <i class="bx bx-trash fs-5"></i>
                    </button>
                  @endif
                </div>
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
                      {{-- Permit radio data --}}
                      @php
                        $permitVal = old("products.$pIndex.permit", isset($product->permit) ? (string) $product->permit : null);
                      @endphp

                      <div class="card-header d-flex align-items-center justify-content-between">
                        <h5 class="mb-0">
                          <i class="bx bx-package me-2"></i>Product
                        </h5>
                      </div>

                      <div class="card-body p-4">
                        <div class="row g-3 mb-4">
                          <div class="col-12 col-md-6 col-xl-3">
                            <label class="form-label">Product Name</label>
                            <input
                              name="products[{{ $pIndex }}][name]"
                              type="text"
                              class="form-control"
                              placeholder="e.g. Business Card"
                              value="{{ old("products.$pIndex.name", $product->productName ?? '') }}"
                              {{ $readonly }}>
                          </div>

                          <div class="col-12 col-md-6 col-xl-3">
                            <label class="form-label">Total Quantity</label>
                            <input id="totalQty"
                              name="products[{{ $pIndex }}][qty_total]"
                              type="number"
                              class="form-control qty-input"
                              placeholder="1000"
                              onkeydown="return !['e','E','+','-'].includes(event.key)"
                              onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
                              oninput="restrictInteger(event)"
                              value="{{ old("products.$pIndex.qty_total", $product->totalQuantity ?? '') }}"
                              {{ $readonly }}>
                          </div>

                          <div class="col-12 col-md-6 col-xl-6">
                            <label class="form-label">Material / Remark</label>
                            <input
                              name="products[{{ $pIndex }}][material]"
                              type="text"
                              class="form-control"
                              placeholder="Premium Paper, Glossy"
                              value="{{ old("products.$pIndex.material", $product->materialRemark ?? '') }}"
                              {{ $readonly }}>
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
                                  data-url="{{ route('data-entry.orders.items.destroy', [$order, data_get($it,'ItemID')]) }}">
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
                                    <input type="number" min="0" class="form-control qty-input"
                                      name="products[{{ $pIndex }}][items][{{ $i }}][quantity]"
                                      onkeydown="return !['e','E','+','-'].includes(event.key)"
                                      onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
                                      oninput="restrictInteger(event)"
                                      value="{{ old("items.$i.quantity", data_get($it,'quantity')) }}" {{ $readonly }}>
                                  </div>

                                  {{-- Material (chips) --}}
                                  <div class="col-12">
                                    <label class="form-label">Material</label>
                                    <div class="tags-input"
                                      data-name="products[{{ $pIndex }}][items][{{ $i }}][material][]"
                                      data-suggestions='@json($materialSuggestions)'
                                      data-values='@json($materialVal)'
                                      data-allow-custom="1" data-max-tags="5"  data-readonly="{{ $order->submit ? '1' : '0' }}">
                                    </div>
                                  </div>

                                  @php
                                  $units = ['mm' => 'mm', 'cm' => 'cm', 'inch' => 'inch', 'ft' => 'ft', 'piece' => 'piece'];
                                  $unit = old("items.$i.sizeUnit", data_get($it,'sizeUnit', 'mm'));
                                  $bleedUnit = old("items.$i.bleedUnit", data_get($it,'bleedUnit', 'mm'));
                                  @endphp

                                  {{-- Sizes --}}
                                  <div class="col-12 col-md-4">
                                    <label class="form-label">Unit (Size)</label>
                                    <select name="products[{{ $pIndex }}][items][{{ $i }}][sizeUnit]" class="form-select" {{ $disabled }}>
                                      @foreach($units as $val => $label)
                                      <option value="{{ $val }}" @selected($unit===$val)>{{ $label }}</option>
                                      @endforeach
                                    </select>
                                  </div>
                                  <div class="col-12 col-md-4">
                                    <label class="form-label">Width</label>
                                    <input name="products[{{ $pIndex }}][items][{{ $i }}][sizeWidth]"
                                      type="number" min="0" step="0.01" class="form-control"
                                      onkeydown="return !['e','E','+','-'].includes(event.key)"
                                      onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
  oninput="restrict2dp(event)"
                                      value="{{ old("items.$i.sizeWidth", data_get($it,'sizeWidth')) }}" {{ $readonly }}>
                                  </div>
                                  <div class="col-12 col-md-4">
                                    <label class="form-label">Height</label>
                                    <input name="products[{{ $pIndex }}][items][{{ $i }}][sizeHeight]"
                                      type="number" min="0" step="0.01" class="form-control"
                                      onkeydown="return !['e','E','+','-'].includes(event.key)"
                                      onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
  oninput="restrict2dp(event)"
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
                                    <label class="form-label">Top</label>
                                    <input name="products[{{ $pIndex }}][items][{{ $i }}][bleedTop]"
                                      type="number" min="0" step="0.01" class="form-control"
                                      onkeydown="return !['e','E','+','-'].includes(event.key)"
                                      onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
  oninput="restrict2dp(event)"
                                      value="{{ old("items.$i.bleedTop", data_get($it,'bleedTop')) }}" {{ $readonly }}>
                                  </div>
                                  <div class="col-12 col-md-2">
                                    <label class="form-label">Bottom</label>
                                    <input name="products[{{ $pIndex }}][items][{{ $i }}][bleedBottom]"
                                      type="number" min="0" step="0.01" class="form-control"
                                      onkeydown="return !['e','E','+','-'].includes(event.key)"
                                      onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
  oninput="restrict2dp(event)"
                                      value="{{ old("items.$i.bleedBottom", data_get($it,'bleedBottom')) }}" {{ $readonly }}>
                                  </div>
                                  <div class="col-12 col-md-2">
                                    <label class="form-label">Left</label>
                                    <input name="products[{{ $pIndex }}][items][{{ $i }}][bleedLeft]"
                                      type="number" min="0" step="0.01" class="form-control"
                                      onkeydown="return !['e','E','+','-'].includes(event.key)"
                                      onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
  oninput="restrict2dp(event)"
                                      value="{{ old("items.$i.bleedLeft", data_get($it,'bleedLeft')) }}" {{ $readonly }}>
                                  </div>
                                  <div class="col-12 col-md-2">
                                    <label class="form-label">Right</label>
                                    <input name="products[{{ $pIndex }}][items][{{ $i }}][bleedRight]"
                                      type="number" min="0" step="0.01" class="form-control"
                                      onkeydown="return !['e','E','+','-'].includes(event.key)"
                                      onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
  oninput="restrict2dp(event)"
                                      value="{{ old("items.$i.bleedRight", data_get($it,'bleedRight')) }}" {{ $readonly }}>
                                  </div>

                                  {{-- Spec --}}
                                  @php
                                  $lamVal = old("products.$pIndex.items.$i.lamination", data_get($it, 'spec.lamination'));
                                  $prtVal = old("products.$pIndex.items.$i.printer", data_get($it, 'spec.printer'));
                                  $cutVal = old("products.$pIndex.items.$i.cutter", data_get($it, 'spec.cutter'));
                                  $assVal = old("products.$pIndex.items.$i.finishing", data_get($it,'finishing'));

                                  $sel = fn($cur, $val) => (strcasecmp((string)$cur, (string)$val) === 0) ? 'selected' : '';

                                  $lamLc = strtolower((string) $lamVal);
                                  $prtLc = strtolower((string) $prtVal);
                                  $cutLc = strtolower((string) $cutVal);
                                  $assLc = strtolower((string) $assVal);

                                  $currentLam = old("products.$pIndex.items.$i.lamination", data_get($it, 'spec.lamination'));
                                  $currentPrinter = old("products.$pIndex.items.$i.printer", data_get($it, 'spec.printer'));
                                  $currentCutter = old("products.$pIndex.items.$i.cutter", data_get($it, 'spec.cutter'));

                                  if (
                                      $currentPrinter === null ||
                                      trim((string) $currentPrinter) === '' ||
                                      strcasecmp(trim((string) $currentPrinter), 'no') === 0
                                  ) {
                                      $currentPrinter = 'TBC';
                                  }

                                  @endphp
                                  <div class="col-md-3">
                                      <label class="form-label">Lamination</label>
                                      <select name="products[{{ $pIndex }}][items][{{ $i }}][lamination]" class="form-select" {{ $disabled }}>
                                      @if($currentLam !== '')
                                          <option value="{{ $currentLam }}" selected>{{ $currentLam }}</option>
                                      @else
                                          <option value="" selected>-</option>
                                      @endif
                                      <option value="">-</option>
                                      <option value="no" {{ (isset($item->lamination) && $item->lamination === 'no') ? 'selected' : '' }}>No</option>

                                      @foreach($laminationMachines ?? [] as $m)
                                          @continue(isset($m->active) && !$m->active)  {{-- skip deactivated machines --}}

                                          <option value="{{ $m->machine_name }}"
                                              {{ (isset($item->lamination) && $item->lamination === $m->machine_name) ? 'selected' : '' }}>
                                              {{ $m->machine_name }}
                                          </option>
                                      @endforeach
                                    </select>
                                  </div>

                                  <div class="col-md-3">
                                      <label class="form-label">Printer</label>

                                      <select
                                          name="products[{{ $pIndex }}][items][{{ $i }}][printer]"
                                          class="form-select"
                                          {{ $disabled }}
                                      >
                                          {{-- Preserve an existing real printer value --}}
                                          @if(strcasecmp((string) $currentPrinter, 'TBC') !== 0)
                                              <option value="{{ $currentPrinter }}" selected>
                                                  {{ $currentPrinter }}
                                              </option>
                                          @endif

                                          {{-- Default printer value --}}
                                          <option
                                              value="TBC"
                                              {{ strcasecmp((string) $currentPrinter, 'TBC') === 0 ? 'selected' : '' }}
                                          >
                                              TBC
                                          </option>

                                          @foreach($printerMachines ?? [] as $m)
                                              {{-- Do not show deactivated printers --}}
                                              @continue(isset($m->active) && !$m->active)

                                              {{-- Prevent duplicate current printer --}}
                                              @continue(
                                                  strcasecmp(
                                                      (string) $m->machine_name,
                                                      (string) $currentPrinter
                                                  ) === 0
                                              )

                                              <option value="{{ $m->machine_name }}">
                                                  {{ $m->machine_name }}
                                              </option>
                                          @endforeach
                                      </select>
                                  </div>

                                  <div class="col-md-3">
                                    <label class="form-label">Cutter</label>
                                    <select name="products[{{ $pIndex }}][items][{{ $i }}][cutter]" class="form-select" {{ $disabled }} >
                                      @if($currentCutter !== '')
                                          <option value="{{ $currentCutter }}" selected>{{ $currentCutter }}</option>
                                      @else
                                          <option value="" selected>-</option>
                                      @endif
                                      <option value="">-</option>
                                      <option value="no" {{ (isset($item->cutter) && $item->cutter === 'no') ? 'selected' : '' }}>No</option>
                                      <option value="TBC" {{ (isset($item->cutter) && $item->cutter === 'TBC') ? 'selected' : '' }}>TBC</option>

                                      @foreach($cutterMachines ?? [] as $m)
                                          @continue(isset($m->active) && !$m->active)

                                          <option value="{{ $m->machine_name }}"
                                              {{ (isset($item->cutter) && $item->cutter === $m->machine_name) ? 'selected' : '' }}>
                                              {{ $m->machine_name }}
                                          </option>
                                      @endforeach
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
                                      <option value="yes" {{ $assLc==='yes' ? 'selected' : '' }}>Yes</option>
                                      <option value="no" {{ $assLc==='no' ? 'selected' : '' }}>No</option>
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
                                    <input type="number" min="0" class="form-control qty-input" name="products[__PINDEX__][items][__INDEX__][quantity]" value="" 
                                    onkeydown="return !['e','E','+','-'].includes(event.key)"
                                    onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
                                    oninput="restrictInteger(event)"
                                    {{ $readonly }}>
                                  </div>

                                  <div class="col-12">
                                    <label class="form-label">Material</label>
                                    <div class="tags-input"
                                      data-name="products[__PINDEX__][items][__INDEX__][material][]"
                                      data-suggestions='@json($allMaterials ?? [])'
                                      data-values='[]'
                                      data-allow-custom="1" data-max-tags="5" data-readonly="{{ $order->submit ? '1' : '0' }}">
                                    </div>
                                  </div>

                                  <div class="col-12 col-md-4">
                                    <label class="form-label">Unit (Size)</label>
                                    <select name="products[__PINDEX__][items][__INDEX__][sizeUnit]" class="form-select" {{ $disabled }}>
                                      <option value="mm" selected>mm</option>
                                      <option value="cm">cm</option>
                                      <option value="inch">inch</option>
                                      <option value="ft">ft</option>
                                      <option value="piece">piece</option>
                                    </select>
                                  </div>

                                  <div class="col-12 col-md-4">
                                    <label class="form-label">Width</label>
                                    <input name="products[__PINDEX__][items][__INDEX__][sizeWidth]" type="number" min="0" step="0.01" class="form-control" value="" 
                                    onkeydown="return !['e','E','+','-'].includes(event.key)"
                                    onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
  oninput="restrict2dp(event)"
                                    {{ $readonly }}>
                                  </div>
                                  <div class="col-12 col-md-4">
                                    <label class="form-label">Height</label>
                                    <input name="products[__PINDEX__][items][__INDEX__][sizeHeight]" type="number" min="0" step="0.01" class="form-control" value="" 
                                    onkeydown="return !['e','E','+','-'].includes(event.key)"
                                    onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
  oninput="restrict2dp(event)"
                                    {{ $readonly }}>
                                  </div>

                                  <div class="col-12 col-md-4">
                                    <label class="form-label">Unit (Bleed)</label>
                                    <select name="products[__PINDEX__][items][__INDEX__][bleedUnit]" class="form-select">
                                      <option value="mm" selected>mm</option>
                                      <option value="cm">cm</option>
                                      <option value="inch">inch</option>
                                      <option value="ft">ft</option>
                                      <option value="piece">piece</option>
                                    </select>
                                  </div>
                                  <div class="col-12 col-md-2">
                                    <label class="form-label">Top</label>
                                    <input name="products[__PINDEX__][items][__INDEX__][bleedTop]" type="number" min="0" step="0.01" class="form-control" value="" 
                                    onkeydown="return !['e','E','+','-'].includes(event.key)"
                                    onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
  oninput="restrict2dp(event)"
                                    {{ $readonly }}>
                                  </div>
                                  <div class="col-12 col-md-2">
                                    <label class="form-label">Bottom</label>
                                    <input name="products[__PINDEX__][items][__INDEX__][bleedBottom]" type="number" min="0" step="0.01" class="form-control" value="" 
                                    onkeydown="return !['e','E','+','-'].includes(event.key)"
                                    onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
  oninput="restrict2dp(event)"
                                    {{ $readonly }}>
                                  </div>
                                  <div class="col-12 col-md-2">
                                    <label class="form-label">Left</label>
                                    <input name="products[__PINDEX__][items][__INDEX__][bleedLeft]" type="number" min="0" step="0.01" class="form-control" value="" 
                                    onkeydown="return !['e','E','+','-'].includes(event.key)"
                                    onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
  oninput="restrict2dp(event)"
                                    {{ $readonly }}>
                                  </div>
                                  <div class="col-12 col-md-2">
                                    <label class="form-label">Right</label>
                                    <input name="products[__PINDEX__][items][__INDEX__][bleedRight]" type="number" min="0" step="0.01" class="form-control" value="" 
                                    onkeydown="return !['e','E','+','-'].includes(event.key)"
                                    onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
  oninput="restrict2dp(event)"
                                    {{ $readonly }}>
                                  </div>
                                  <div class="col-md-3">
                                    <label class="form-label">Lamination</label>
                                    <select name="products[__PINDEX__][items][__INDEX__][lamination]" class="form-select" {{ $disabled }} >
                                      <option value="">-</option>
                                      <option value="no" {{ (isset($item->lamination) && $item->lamination === 'no') ? 'selected' : '' }}>No</option>

                                      @foreach($laminationMachines ?? [] as $m)
                                          @continue(isset($m->active) && !$m->active)  {{-- skip deactivated machines --}}

                                          <option value="{{ $m->machine_name }}"
                                              {{ (isset($item->lamination) && $item->lamination === $m->machine_name) ? 'selected' : '' }}>
                                              {{ $m->machine_name }}
                                          </option>
                                      @endforeach
                                    </select>
                                  </div>

                                  <div class="col-md-3">
                                      <label class="form-label">Printer</label>

                                      <select
                                          name="products[__PINDEX__][items][__INDEX__][printer]"
                                          class="form-select"
                                          {{ $disabled }}
                                      >
                                          {{-- New items always default to TBC --}}
                                          <option value="TBC" selected>TBC</option>

                                          @foreach($printerMachines ?? [] as $m)
                                              {{-- Do not show deactivated printers --}}
                                              @continue(isset($m->active) && !$m->active)

                                              <option value="{{ $m->machine_name }}">
                                                  {{ $m->machine_name }}
                                              </option>
                                          @endforeach
                                      </select>
                                  </div>

                                  <div class="col-md-3">
                                    <label class="form-label">Cutter</label>
                                    <select name="products[__PINDEX__][items][__INDEX__][cutter]" class="form-select" {{ $disabled }} >
                                      <option value="">-</option>
                                      <option value="no" {{ (isset($item->cutter) && $item->cutter === 'no') ? 'selected' : '' }}>No</option>
                                      <option value="TBC" {{ (isset($item->cutter) && $item->cutter === 'TBC') ? 'selected' : '' }}>TBC</option>

                                      @foreach($cutterMachines ?? [] as $m)
                                          @continue(isset($m->active) && !$m->active)

                                          <option value="{{ $m->machine_name }}"
                                              {{ (isset($item->cutter) && $item->cutter === $m->machine_name) ? 'selected' : '' }}>
                                              {{ $m->machine_name }}
                                          </option>
                                      @endforeach
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
                        $pTotal = (int) ($product->totalQuantity ?? 0);
                        $pDelivered = (int) ($product->deliveryBreakdowns?->sum('quantity') ?? 0);
                        $pRemain = max($pTotal - $pDelivered, 0);
                        @endphp
                        <div class="d-flex align-items-center justify-content-between mt-4 mb-2">
                          <h6 class="mb-0">Delivery Breakdown</h6>
                          <div class="ms-auto d-flex align-items-center gap-3 small text-muted" style="margin-right: 10px;"
                            id="del-summary-{{ $pIndex }}">
                            <span> <strong>Total:</strong>
                              <span id="del-sum-total-{{ $pIndex }}">{{ $pTotal }}</span>
                            </span>
                            <span> <strong>Delivery Plan:</strong>
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
                            data-url="{{ route('data-entry.orders.delivery.destroy', ['order' => $order, 'delivery' => $d->getKey()]) }}">
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
                              $dtValue = $dateOnly && $timeOnly ? ($dateOnly.'T'.$timeOnly) : ($dateOnly ? $dateOnly.'T00:00' : '');
                              } catch (\Throwable $e) { $dtValue = ''; }

                              $methodVal = strtolower((string)
                              old("products.$pIndex.deliveries.$i.method", data_get($d,'method'))
                              );

                              $methodOptions = [
                              'courier' => 'Courier',
                              'self_pickup' => 'Self Pickup',
                              'delivery' => 'Delivery',
                              'installation' => 'Installation',
                              ];

                              // installation type + outsource cost
                              $insTypeVal = strtolower((string)
                              old("products.$pIndex.deliveries.$i.deliver_install_type", data_get($d,'deliver_install_type'))
                              );
                              $insTypeOptions = [
                              'in_house' => 'In-house',
                              'outsource' => 'Outsource',
                              'both' => 'Both',
                              ];

                              $costVal = old("products.$pIndex.deliveries.$i.outsource_cost", data_get($d,'outsource_cost'));

                              // initial enable/disable state (server-side)
                              $isDI = ($methodVal === 'delivery' || $methodVal === 'installation');
                              $costEnabled = $isDI && in_array($insTypeVal, ['outsource','both'], true);

                              $insDisabledAttr = trim($disabled.' '.($isDI ? '' : 'disabled'));
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
                                    {{ $insDisabledAttr }} data-optional="true">
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
                                    {{ $costDisabledAttr }} data-optional="true"
                                    onkeydown="return !['e','E','+','-'].includes(event.key)"
                             >
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
                                  <input type="number" class="form-control del-qty qty-input"
                                    name="products[{{ $pIndex }}][deliveries][{{ $i }}][quantity]"
                                    value="{{ $d->quantity }}" 
                                    onkeydown="return !['e','E','+','-'].includes(event.key)"
                                    onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
                                    oninput="restrictInteger(event)"
                                    {{ $readonly }}>
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
                                    <option value="delivery">Delivery</option>
                                    <option value="installation">Installation</option>
                                    <option value="self_pickup">Self Pickup</option>
                                  </select>
                                </div>

                                <div class="col-12 col-md-4">
                                  <label class="form-label">Installation Type</label>
                                  <select name="products[{{ $pIndex }}][deliveries][__INDEX__][deliver_install_type]" class="form-select" data-install-type disabled data-optional="true">
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
                                    disabled data-optional="true"
                                    onkeydown="return !['e','E','+','-'].includes(event.key)"
                             >
                                </div>

                                <div class="col-12 col-md-4">
                                  <label class="form-label">Location Address</label>
                                  <input type="text" name="products[{{ $pIndex }}][deliveries][__INDEX__][location]" class="form-control">
                                </div>

                                <div class="col-12 col-md-4">
                                  <label class="form-label">Quantity</label>
                                  <input type="number" name="products[{{ $pIndex }}][deliveries][__INDEX__][quantity]" class="form-control del-qty qty-input"
                                  onkeydown="return !['e','E','+','-'].includes(event.key)" onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
                                    oninput="restrictInteger(event)"
                             >
                                </div>

                                <div class="col-12 col-md-4">
                                  <label class="form-label">Date &amp; Time</label>
                                  <input type="datetime-local" name="products[{{ $pIndex }}][deliveries][__INDEX__][datetime]" class="form-control">
                                </div>
                              </div>
                            </div>
                          </div>
                        </template>

                        <!-- {{-- Product Remarks --}}
                        <div class="mt-4">
                          <h6 class="mb-2">Product Remarks</h6>

                          <div id="remarks-wrap-{{ $pIndex }}">
                            @php
                            $ops = [
                            'printing' => 'To Printing',
                            'furnishing' => 'To Furnishing',
                            'installation' => 'To Delivery & Installation',
                            'courier' => 'To Courier',
                            'self_pickup' => 'To Self Pickup',
                            'artist'       => 'To Artist',
                            ];
                            $noArtistAssigned = empty($order->artist_id);
                            $rows = $product->remarks ?? collect();
                            @endphp

                            @php
                            $orderMap = ['printing', 'furnishing', 'installation', 'courier', 'self_pickup', 'artist'];
                            $rank = array_flip($orderMap);

                            // sort by our operation order, then by created_at asc, then id (stable)
                            $rowsSorted = ($product->remarks ?? collect())->sortBy(function ($r) use ($rank) {
                                $op = strtolower((string) $r->operation);
                                $opRank = $rank[$op] ?? 999;
                                return [$opRank, $r->created_at ?? now(), $r->RemarkID];
                            })->values();
                            @endphp
                            @php $errKey = "products.$pIndex.remarks.$loop->index.operation"; @endphp
                            @forelse($rowsSorted as $r)
                            <div class="d-flex align-items-center gap-2 mb-2 remark-row" data-remark data-id="{{ $r->RemarkID }}" data-url="{{ route('data-entry.orders.remarks.destroy', [$order, $r->RemarkID]) }}">
                              <input type="hidden" name="products[{{ $pIndex }}][remarks][{{ $loop->index }}][id]" value="{{ $r->RemarkID }}">
                              <select name="products[{{ $pIndex }}][remarks][{{ $loop->index }}][operation]"
                                      class="form-select w-auto" style="min-width:160px;" {{$disabled}} data-optional="true">
                                <option value="">— Select Department —</option>
                                @foreach($ops as $k => $label)
                                  @php $isArtist = $k === 'artist'; @endphp
                                  <option value="{{ $k }}"
                                          @selected(old("products.$pIndex.remarks.$loop->index.operation", $r->operation) === $k)
                                          {{ $isArtist && $noArtistAssigned ? 'disabled' : '' }}>
                                    {{ $label }}{{ $isArtist && $noArtistAssigned ? ' (assign artist first)' : '' }}
                                  </option>
                                @endforeach
                              </select>
                              <input type="text"
                                name="products[{{ $pIndex }}][remarks][{{ $loop->index }}][remark]"
                                class="form-control"
                                placeholder="Write a note…" {{ $readonly }} data-optional="true"
                                value="{{ old("products.$pIndex.remarks.$loop->index.remark", $r->remark) }}">
                              <small class="text-muted ms-1">
                                by {{ $r->user?->name ?? 'Unknown' }}
                                @if($r->created_at)@endif
                              </small>
                              @if ($submitted)
                              <button type="button" class="btn btn-link text-danger p-0 remove-remark" title="Delete">
                                <i class="bx bx-trash fs-5"></i>
                              </button>
                              @endif
                            </div>
                            @empty
                            @endforelse
                          </div>
                          @if ($submitted)
                          <button type="button" id="add-remark-{{ $pIndex }}" class="btn btn-sm btn-outline-secondary mt-2">
                            <i class="bx bx-plus"></i> Add Remarks
                          </button>
                          @endif
                          {{-- per-product delete bin --}}
                          <div id="delete-remarks-bin-{{ $pIndex }}"></div>
                        </div> -->
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
            <div class="card-header" style="display: flex; align-items: center;">
              <h5 class="card-title mb-0" style="margin: 0;">Attachments</h5>
              <span style="color: red; font-size: 12px; margin-left: 6px;">*required</span>
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

                <label class="form-label">Existing files (From Artist)</label>

                @if(isset($orderFiles) && count($orderFiles))
    <div class="d-flex flex-column gap-2">
      @foreach($orderFiles as $f)
        <div class="d-flex align-items-center justify-content-between border rounded p-2"
             data-file-row data-path="{{ $f['path'] }}">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <i class="bx bx-file"></i>
            <a href="{{ $f['url'] }}" target="_blank" class="text-decoration-none">
              {{ $f['name'] }}
            </a>
            <small class="text-muted">.{{ $f['ext'] }}</small>
            @if(!empty($f['uploaded_by']))
              <small class="text-muted">
                · Uploaded by {{ $f['uploaded_by'] }}
                @if(!empty($f['uploaded_at'])) · {{ $f['uploaded_at'] }} @endif
              </small>
            @endif
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

          {{-- Head-artist only: Assign Artist --}}
          @if(auth()->user()->role === 'head-artist')
            @if(!$isSubmitted)
              <div class="card mt-4" id="assign-artist-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                  <strong>Assign Artist</strong>
                  
                </div>
                <div class="card-body">
                  <div class="d-flex align-items-end gap-2">
                    <div class="flex-grow-1">
                      <label for="assignee_artist_id" class="form-label mb-1 fw-semibold">Artist</label>
                      <select id="assignee_artist_id" class="form-select" style="width:100%">
                        @if(!empty($order->artist_id) && !empty($order->artist))
                          <option value="{{ $order->artist_id }}" selected>
                            {{ $order->artist->name }} ({{ $order->artist->role }})
                          </option>
                        @endif

                        @foreach($artists as $artist)
                          <option value="{{ $artist->id }}">
                            {{ $artist->name }} ({{ $artist->role }})
                          </option>
                        @endforeach
                      </select>
                    </div>
                    <div class="pb-1">
                      <button id="btn-assign-artist" type="button" class="btn btn-primary btn-sm">
                        Assign Artist
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            @endif
          @endif

          <div id="form-errors" class="mt-3 text-red-600 text-sm"></div>

        </div>
      </div>

    </div>

    {{-- Sticky save bar --}}
    <div class="col-12">
      <div class="bg-body position-sticky bottom-0 border-top py-3 d-flex gap-2 justify-content-end" style="z-index: 10">
        <button type="button" class="btn btn-outline-secondary" onclick="history.back()">Back</button>
        <input type="hidden" name="submit" id="submit-input" value="0">
        @if(!$isSubmitted)
        <button type="button" name="is_draft" onclick="document.getElementById('submit-input').value=0" class="btn btn-secondary" id="btn-draft">Save Draft</button>
        <button type="button" name="is_draft" onclick="document.getElementById('submit-input').value=1" class="btn btn-primary" id="btn-submit" {{ $disabled }}>Save & Submit</button>
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
          <input name="items[IDX][qty]" type="number" min="0" class="form-control qty-input" placeholder="Qty"
          onkeydown="return !['e','E','+','-'].includes(event.key)" onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
                                    oninput="restrictInteger(event)">
        </div>
        <div class="col-md-6">
          <label class="form-label">Material</label>
          <input name="items[IDX][material]" type="text" class="form-control" placeholder="Add material…">
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label">Width</label>
          <input name="items[IDX][size][w]" type="number" type="number" min="0" step="0.01"
          onkeydown="return !['e','E','+','-'].includes(event.key)" onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
  oninput="restrict2dp(event)" class="form-control">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label">Height</label>
          <input name="items[IDX][size][h]" type="number" type="number" min="0" step="0.01"
          onkeydown="return !['e','E','+','-'].includes(event.key)" onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
  oninput="restrict2dp(event)" class="form-control">
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label">Top</label>
          <input name="items[IDX][bleed][top]" type="number" type="number" min="0" step="0.01"
          onkeydown="return !['e','E','+','-'].includes(event.key)" onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
  oninput="restrict2dp(event)" class="form-control">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label">Bottom</label>
          <input name="items[IDX][bleed][bottom]" type="number" type="number" min="0" step="0.01"
          onkeydown="return !['e','E','+','-'].includes(event.key)" onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
  oninput="restrict2dp(event)" class="form-control">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label">Left</label>
          <input name="items[IDX][bleed][left]" type="number" type="number" min="0" step="0.01"
          onkeydown="return !['e','E','+','-'].includes(event.key)" onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
  oninput="restrict2dp(event)" class="form-control">
        </div>
        <div class="col-12 col-md-3">
          <label class="form-label">Right</label>
          <input name="items[IDX][bleed][right]" type="number" type="number" min="0" step="0.01"
          onkeydown="return !['e','E','+','-'].includes(event.key)" onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
  oninput="restrict2dp(event)"class="form-control">
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
          <input name="deliveries[IDX][qty]" type="number" min="0" class="form-control qty-input" placeholder="Qty"
          onkeydown="return !['e','E','+','-'].includes(event.key)" onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
                                    oninput="restrictInteger(event)">
        </div>
        <div class="col-12 col-md-4">
          <label class="form-label">Date & Time</label>
          <input name="deliveries[IDX][datetime]" type="datetime-local" class="form-control">
        </div>
      </div>
    </div>
  </div>
</template>

{{-- ===== Submit Modals ===== --}}
<div class="modal fade" id="modal-submit-ok" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-3">
      <div class="modal-header border-0">
        <h5 class="modal-title">Submit Job Order</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center">
        <div class="display-6 mb-3">✅</div>
        <p class="mb-0">All required fields are complete. What do you want to do with this order?</p>
      </div>
      <div class="modal-footer flex-column gap-2 border-0">
        <button type="button" class="btn btn-dark w-100" id="btn-confirm-send-printing">Confirm to Proceed to Operation</button>
        <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">← Back to Order Page</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-submit-incomplete" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-3">
      <div class="modal-header border-0">
        <h5 class="modal-title">Incomplete Order Information</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body text-center py-4">
        <div class="display-6 mb-3 text-warning">⚠️</div>
        <p class="mb-0 fs-5">
          Your order information is not fully filled up.<br>
          Please check all required fields and try again.
        </p>
      </div>
      <div class="modal-footer border-0">
        <button style="display: none;" type="button" class="btn btn-dark w-100" id="btn-open-choose-de">Pass to Data Entry</button>
        <button type="button" class="btn btn-dark w-100" data-bs-dismiss="modal">← Back to Order Page</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modal-choose-de-user" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-3">
      <div class="modal-header">
        <h5 class="modal-title">Assign to Data Entry</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <label class="form-label">Select Data Entry User</label>
        <select id="de-user-select" class="form-select">
          <option value="">Please select a user</option>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-dark" id="btn-confirm-assign">Confirm &amp; Assign</button>
      </div>
    </div>
  </div>
</div>
{{-- ===== /Submit Modals ===== --}}

<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Product</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="productForm" method="POST" action="{{ route('artist.orders.products.store', ['order' => $order]) }}" autocomplete="off">
        @csrf
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Product Name</label>
              <input type="text" class="form-control" id="p_name" name="product_name">
            </div>
            <div class="col-md-4">
              <label class="form-label">Quantity</label>
              <input type="number" class="form-control qty-input" id="p_qty" name="quantity" min="1" step="1"
              onkeydown="return !['e','E','+','-'].includes(event.key)" onfocus="this.dataset.last=this.value; this.dataset.pos=this.selectionStart"
                                    oninput="restrictInteger(event)">
            </div>
            <div class="col-12">
              <label class="form-label">Material Remark</label>
              <textarea class="form-control" id="p_material" name="material_info" rows="2" placeholder="Backlit Fabric"></textarea>
            </div>
          </div>

          <hr class="my-4">

          <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label m-0">Product Remarks</label>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addRemarkRow">+ Add Remarks</button>
          </div>

          <div id="remarkRows" class="vstack gap-2">
            {{-- rows injected by JS --}}
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade reason-modal" id="reasonModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0">
        <div class="d-flex align-items-center gap-2">
          <span class="rm-chip" id="reasonChip">
            <i class="bi" id="reasonIcon" aria-hidden="true"></i>
            <span id="reasonTag">REDO</span>
          </span>
          <h5 class="modal-title mb-0" id="reasonModalTitle" style="color: white;">Detail</h5>
        </div>
      </div>

      <div class="modal-body pt-0">
        <div id="reasonBy" class="text-muted small mb-2" style="margin-top: 20px; font-weight:bold;"></div>

        <div class="rm-reason-box">
          <div class="fw-medium text-muted small mb-1" style="font-weight: bold;">Reason</div>
          <div id="reasonText" class="rm-reason-text"></div>
        </div>
      </div>

      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- NEW: Missing Details Modal -->
<div class="modal fade" id="modal-missing-details" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content rounded-3">
      <div class="modal-header">
        <h5 class="modal-title">Missing Required Info</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <div class="text-body-secondary mb-2">
          Some required fields are incomplete. Please review the missing fields below.
        </div>

        <div id="missing-details-list" class="mt-2"></div>
      </div>

      <div class="modal-footer flex-column gap-2">
        <button type="button" class="btn btn-outline-secondary w-100" data-bs-dismiss="modal">
          ← Back to Order Page
        </button>
      </div>
    </div>
  </div>
</div>

{{-- Template for one remark row --}}
<script type="text/template" id="remarkRowTpl">
  <div class="remark-row d-flex gap-2 align-items-start">
    <select class="form-select" name="remarks[__IDX__][operation]">
      <option value="" disabled selected>Select operation</option>
      <option value="printing">To Printing</option>
      <option value="furnishing">To Furnishing</option>
      <option value="installation">To Delivery & Installation</option>
      <option value="courier">To Courier</option>
      <option value="self_pickup">To Self Pickup</option>
      <option value="artist">To Artist</option>
    </select>
    <input class="form-control" name="remarks[__IDX__][remark]" placeholder="Remark…">
    <button type="button" class="btn btn-outline-danger remove-remark">&times;</button>
  </div>
</script>

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
      const maxTags     = parseInt(container.dataset.maxTags || '5', 10);

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

      function enforceLimit() {
        const atLimit = selected.size >= maxTags;
        // disable typing/clicking when at limit
        if (!isReadonly) {
          input.readOnly = atLimit;
          input.classList.toggle('bg-light', atLimit);
          input.placeholder = atLimit ? `Limit ${maxTags} reached` : 'Click to select…';
        }
        // hide dropdown entirely at/over limit
        if (atLimit) dd.style.display = 'none';
        container.classList.toggle('ti-disabled', atLimit);
      }

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
              enforceLimit(); 
            });
            chip.appendChild(btn);
          }

          box.insertBefore(chip, input);
          container.appendChild(hidden(name, v));
        });
      }

      function buildList(query = '') {
        if (isReadonly) {
          dd.style.display = 'none';
          return;
        }

        const q = (query || '').trim().toLowerCase();

        // available options = not selected yet
        let avail = suggestions.filter(s => !selected.has(s));

        // filter by search keyword
        if (q) {
          avail = avail.filter(v => String(v).toLowerCase().includes(q));
        }

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
            if (selected.size >= maxTags) {
              if (window.Swal) {
                Swal.fire({
                  icon: 'warning',
                  title: 'Limit reached',
                  text: `You can select up to ${maxTags} materials.`,
                  timer: 1500,
                  showConfirmButton: false
                });
              }
              dd.style.display = 'none';
              enforceLimit();
              return;
            }

            selected.add(v);

            // clear search after pick
            input.value = '';

            renderChips();
            buildList('');     // rebuild full list (minus selected)
            enforceLimit();
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
        // live search as you type
        input.addEventListener('input', () => {
          buildList(input.value);
          dd.style.display = 'block';
        });

        // optional: ESC closes dropdown
        input.addEventListener('keydown', (e) => {
          if (e.key === 'Escape') dd.style.display = 'none';
        });
        document.addEventListener('click', (e) => {
          if (!wrap.contains(e.target)) dd.style.display = 'none';
        });
      } else {
        wrap.classList.add('ti-disabled');
      }

      renderChips(); // ← show chips for initial values from DB
      enforceLimit();
    }

    function initAllTagsInputs(root = document) {
      root.querySelectorAll('.tags-input').forEach(initTagsInput);
    }

    initAllTagsInputs(document);

    document.querySelectorAll('.accordion-collapse[id^="pCollapse"]').forEach((root) => {
      const m = root.id.match(/^pCollapse(\d+)$/);
      const pIndex = m ? m[1] : '0';

      // ----- ITEMS (scoped to this product) -----
      const acc = root.querySelector('#productItems-' + pIndex);
      const tplEl = root.querySelector('#itemTemplate-' + pIndex);
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
          const row = frag.firstElementChild;
          if (!row) return;

          acc.appendChild(row);

          // open collapse in this product only
          const pane = row.querySelector('.accordion-collapse');
          const btn = row.querySelector('[data-bs-toggle="collapse"]');
          if (pane) {
            pane.setAttribute('data-bs-parent', `#${acc.id}`);
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
          wrap.addEventListener('input', () => updateSummary(wrap), {
            passive: true
          });
        }

        function updateSummary(wrap) {
          const name = wrap.querySelector('input[name$="[itemName]"]')?.value || '';
          const qty = wrap.querySelector('input[name$="[quantity]"]')?.value ||
            wrap.querySelector('input[name$="[qty]"]')?.value || '';
          const el = wrap.querySelector('.item-summary');
          if (el) el.textContent = name + (qty ? ` • ${qty}` : '');
        }

        function renumberOnly() {
          const items = acc.querySelectorAll('.accordion-item[data-kind="item"]');
          items.forEach((el, idx) => {
            el.querySelectorAll('.item-number').forEach(n => n.textContent = String(idx + 1));
            const pane = el.querySelector('.accordion-collapse');
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
          // Prefer the live input value first for instant feedback while typing
          const totalQtyEl =
            root.querySelector(`input[name="products[${pIndex}][qty_total]"]`) ||
            root.querySelector('#totalQty');
          if (totalQtyEl) {
            const v = (totalQtyEl.value ?? '').replace(/,/g, '').trim();
            const n = parseFloat(v);
            if (Number.isFinite(n)) return n;
          }

          // Fallback to the summary span (may lag behind a bit)
          const span = root.querySelector('#del-sum-total-' + pIndex);
          if (span) {
            const s = (span.textContent || '').replace(/,/g, '').trim();
            const n = parseFloat(s);
            if (!Number.isNaN(n)) return n;
          }
          return 0;
        }

        function setItemQtyValidity(ok, msg = '') {
          const id = `item-qty-msg-${pIndex}`;
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
          const sum = sumItemQty();
          // setItemQtyValidity(sum <= total,
          //   sum <= total ? '' : `Item quantities (${sum}) exceed Total Quantity (${total}).`
          // );
        }

        function updateDeliverySummaryBar() {
          const totalEl = document.getElementById('del-sum-total-' + pIndex);
          const delEl = document.getElementById('del-sum-delivered-' + pIndex);
          const remEl = document.getElementById('del-sum-remaining-' + pIndex);
          if (!totalEl || !delEl || !remEl) return;

          const total = getTotalAllowed(); // already defined in your code
          const delivered = sumDeliveryQty(); // already defined in your code
          const remaining = Math.max(total - delivered, 0);

          totalEl.textContent = String(total);
          delEl.textContent = String(delivered);
          remEl.textContent = String(remaining);
        }

        // wire existing & hook add
        acc.querySelectorAll('.accordion-item[data-kind="item"]').forEach((wrap) => {
          wireRow(wrap);
          updateSummary(wrap);
          initAllTagsInputs(wrap);
        });
        renumberOnly();
        addItem?.addEventListener('click', (e) => {
          e.preventDefault();
          addItemRow();
        });
        acc.addEventListener('input', (e) => {
          if (e.target.matches('input[name$="[quantity]"], input[name$="[qty]"]')) {
            validateItems();
          }
        });
        validateItems();
      }

      // ----- DELIVERIES (scoped to this product) -----
      const delWrap = root.querySelector('#deliveriesWrap-' + pIndex);
      const addDel = root.querySelector('#addDeliveryBtn-' + pIndex);
      const delTpl = root.querySelector('#deliveryTemplate-' + pIndex);

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
          const idx = delWrap.querySelectorAll('[data-delivery]').length;
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

        addDel?.addEventListener('click', (e) => {
          e.preventDefault();
          addDelivery();
        });

        delWrap.addEventListener('click', async (e) => {
          const btn = e.target.closest('.delete-delivery');
          if (!btn) return;
          e.preventDefault();

          const card = btn.closest('[data-delivery]');
          if (!card || card.dataset.deleting === '1') return; // guard against double click

          const id = card?.dataset.id || card?.querySelector('input[name$="[id]"]')?.value || '';
          const url = card?.dataset.url || '';

          const confirmed = await (window.Swal ?
            Swal.fire({
              icon: 'warning',
              title: 'Delete this delivery?',
              text: id ? 'This will delete it permanently.' : 'This will remove the row.',
              showCancelButton: true,
              confirmButtonText: 'Delete',
              confirmButtonColor: '#d33'
            }).then(r => r.isConfirmed) :
            Promise.resolve(confirm('Delete this delivery?'))
          );
          if (!confirmed) return;

          async function removeCard() {
            card.remove();
            reindexDeliveries();
            validateDeliveries();
            if (window.Swal) {
              Swal.fire({
                icon: 'success',
                title: 'Deleted',
                timer: 1100,
                showConfirmButton: false
              });
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
            try {
              data = await res.json();
            } catch {}

            if (res.ok && data?.ok) {
              await removeCard();
            } else {
              const msg = data?.message || `HTTP ${res.status}`;
              if (window.Swal) {
                await Swal.fire({
                  icon: 'error',
                  title: 'Delete failed',
                  text: msg
                });
              } else {
                alert('Delete failed: ' + msg);
              }
              // DO NOT remove the card when delete fails
            }
          } catch (err) {
            if (window.Swal) {
              await Swal.fire({
                icon: 'error',
                title: 'Network error',
                text: String(err)
              });
            } else {
              alert('Network error: ' + err);
            }
          } finally {
            delete card.dataset.deleting;
            btn.disabled = false;
          }
        });

        function getTotalAllowed() {
          // Prefer the live input value first for instant feedback while typing
          const totalQtyEl =
            root.querySelector(`input[name="products[${pIndex}][qty_total]"]`) ||
            root.querySelector('#totalQty');
          if (totalQtyEl) {
            const v = (totalQtyEl.value ?? '').replace(/,/g, '').trim();
            const n = parseFloat(v);
            if (Number.isFinite(n)) return n;
          }

          // Fallback to the summary span (may lag behind a bit)
          const span = root.querySelector('#del-sum-total-' + pIndex);
          if (span) {
            const s = (span.textContent || '').replace(/,/g, '').trim();
            const n = parseFloat(s);
            if (!Number.isNaN(n)) return n;
          }
          return 0;
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

        const totalQtyInput =
          root.querySelector(`input[name="products[${pIndex}][qty_total]"]`) ||
          root.querySelector('#totalQty');
        const _revalidateFromTotal = () => validateDeliveries();
        totalQtyInput?.addEventListener('input', _revalidateFromTotal);
        totalQtyInput?.addEventListener('change', _revalidateFromTotal);

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
      const ORDER_HAS_ARTIST = @json((bool) $order->artist_id);
      function addRemarkRow() {
        if (!remarksWrap) return;
        const i = remarksWrap.querySelectorAll('[data-remark]').length;
        const div = document.createElement('div');
        div.className = 'd-flex align-items-center gap-2 mb-2 remark-row';
        div.setAttribute('data-remark', '');
        div.innerHTML = `
          <select name="products[${pIndex}][remarks][${i}][operation]"
                  class="form-select w-auto" style="min-width:160px;" {{$disabled}} data-optional="true">
            <option value="">— Select Department —</option>
            <option value="printing">To Printing</option>
            <option value="furnishing">To Furnishing</option>
            <option value="installation">To Delivery & Installation</option>
            <option value="self_pickup">To Self Pickup</option>
            <option value="courier">To Courier</option>
            <option value="artist" ${ORDER_HAS_ARTIST ? '' : 'disabled'}>
              To Artist${ORDER_HAS_ARTIST ? '' : ' (assign artist first)'}
            </option>
          </select>
          <input type="text" name="products[${pIndex}][remarks][${i}][remark]" class="form-control" placeholder="Write a note…" {{$readonly}} data-optional="true">
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
        const id = row?.dataset?.id || '';
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
            try {
              data = await res.json();
            } catch {}

            if (res.ok && data?.ok) {
              row.remove();
              reindexRemarks();
              if (window.Swal) {
                Swal.fire({
                  icon: 'success',
                  title: 'Remark deleted',
                  timer: 1000,
                  showConfirmButton: false
                });
              }
              return; // done
            }

            // If server refused, fall back to deferred delete on Save
            const msg = data?.message || `HTTP ${res.status}`;
            if (window.Swal) await Swal.fire({
              icon: 'warning',
              title: 'Will delete on Save',
              text: msg
            });
            // fall through to bin push

          } catch (err) {
            // Network error → fall back to deferred delete on Save
            if (window.Swal) await Swal.fire({
              icon: 'warning',
              title: 'Offline delete queued',
              text: String(err)
            });
            // fall through to bin push
          }
        }

        // Fallback / unsaved rows: push ID to delete bin if present, then remove from DOM
        if (id) {
          const hidden = document.createElement('input');
          hidden.type = 'hidden';
          hidden.name = `products[${pIndex}][delete_remarks][]`;
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

    const products = document.querySelectorAll('.accordion-collapse[id^="pCollapse"]');

    const productHasAtLeastOneItem = (root) =>
      !!root.querySelector('input[name^="products["][name*="[items]"], select[name^="products["][name*="[items]"], textarea[name^="products["][name*="[items]"]');

    const productHasAtLeastOneDelivery = (root) =>
      !!root.querySelector('input[name^="products["][name*="[deliveries]"], select[name^="products["][name*="[deliveries]"], textarea[name^="products["][name*="[deliveries]"]');

    const findBtn = (scope, selector, textRx) => {
      // Prefer data-attrs if you have them; else fall back to text match
      let btn = scope.querySelector(selector);
      if (btn) return btn;
      return Array.from(scope.querySelectorAll('button,a'))
        .find(b => textRx.test((b.textContent || '').trim().toLowerCase()));
    };

    products.forEach((root) => {
    const scope = root.closest('.accordion-item') || root;

    // Seed 1 Item if none
    if (!productHasAtLeastOneItem(root)) {
      const addItemBtn = findBtn(scope, '[data-add-item],[data-action="add-item"]', /\badd\s*item\b/);
      if (addItemBtn) addItemBtn.click();
    }

    // Seed 1 Delivery if none
    if (!productHasAtLeastOneDelivery(root)) {
      const addDelBtn = findBtn(scope, '[data-add-delivery],[data-action="add-delivery"]', /\badd\s*delivery(\s*breakdown)?\b/);
      if (addDelBtn) addDelBtn.click();
    }
  });

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

    function updateButtonsState() {
      // disable only if there is ANY invalid chip in the preview list
      const hasInvalid = document.querySelector('#preview .err') !== null;

      const btnSubmit = document.getElementById('btn-submit');
      const btnDraft  = document.getElementById('btn-draft');

      // If hasInvalid → disable; otherwise enable (even when 0 files)
      if (hasInvalid) {
        btnSubmit?.setAttribute('disabled', '');
        btnDraft ?.setAttribute('disabled', '');
      } else {
        // No invalid attachments. Enable only if there are no other invalid fields.
        const anyInvalid = document.querySelector('.is-invalid,[aria-invalid="true"]') !== null;
        btnSubmit?.toggleAttribute('disabled', anyInvalid);
        btnDraft ?.toggleAttribute('disabled', anyInvalid);
      }
    }

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
        updateButtonsState();
      });

      listEl.appendChild(li);
      updateButtonsState();
    }

    function updateSummary() {
      const count = selected.size;
      msgEl.innerHTML = count ? `<span class="ok">${count} file(s) selected for upload</span>` : '';
      document.dispatchEvent(new CustomEvent('attachments:updated', {
        detail: {
          count
        }
      }));
    }

    window.getSelectedFiles = () => Array.from(selected.values());

    // helpers
    const _trim = el => $.trim($(el).val() || '');

    // ≥1 file either newly uploaded or already attached
    function hasAtLeastOneAttachment() {
      const newCount = ($('input[type="file"][name="attachments[]"]')[0]?.files?.length) || 0;
      const existingCount =
        $('input[name^="existing_attachments["]').length ||
        $('#existing-attachments .attachment-item').length ||
        $('#attachment-list .file-row').length || 0;
      return (newCount + existingCount) > 0;
    }

    // ITEMS: ≥1 item AND each item has required fields (skip Lamination/Printer/Cutter)
    function validateItems() {
      const $wraps = $('#hidden-products > div');
      if ($wraps.length === 0) return false;

      let ok = true;
      $wraps.each(function () {
        const name = _trim($(this).find('input[name$="[product_name]"]'));
        const qty  = Number(_trim($(this).find('input[name$="[quantity]"]'))) || 0;
        if (!name || qty <= 0) { ok = false; return false; }

        // check other item fields you post (skip optional + remarks bundle)
        $(this).find('input,select,textarea').each(function () {
          const nm = (this.name || '').toLowerCase();
          if (!nm) return;
          // if (nm.includes('lamination') || nm.includes('printer') || nm.includes('cutter')) return; 
          if (nm.includes('[remarks]')) return; // remarks validated separately
          if (/\[product_name]$|\[quantity]$|\[material_info]$|\[size]$|\[color]$/.test(nm)) {
            if (!_trim(this)) { ok = false; return false; }
          }
        });
        if (!ok) return false;
      });
      return ok;
    }

    // DELIVERY: ≥1 breakdown AND required fields filled (skip Location Address & Date/Time)
    function validateDelivery() {
      let $rows = $('.delivery-row');
      if ($rows.length === 0) {
        $rows = $('[name^="deliveries["]').closest('.delivery-row, .row, .delivery-block');
      }
      if ($rows.length === 0) return false;

      let ok = true;
      $rows.each(function () {
        const $req = $(this).find('input,select,textarea').filter(function () {
          const nm = (this.name || '').toLowerCase();
          if (!nm) return false;
          if (nm.includes('location') || nm.includes('address') || nm.includes('date') || nm.includes('time')) return false; // optional
          if (nm.endsWith('[id]')) return false; // internal ids
          return true;
        });
        $req.each(function () { if (!_trim(this)) { ok = false; return false; }});
        if (!ok) return false;
      });
      return ok;
    }

    // single gate your modal logic calls
    function isReadyForPrinting() {
      return validateItems() && validateDelivery() && hasAtLeastOneAttachment();
    }

    // submit order form
    const form = document.getElementById('order-form');
    const btnDraft = document.getElementById('btn-draft');
    const btnSubmit = document.getElementById('btn-submit');
    const isDraftEl = document.getElementById('is_draft');
    const overlay = document.getElementById('loading-overlay');

    const action = @json(route('data-entry.orders.update', $order));
    const csrf = @json(csrf_token());

    function getSelectedFiles() {
      return (typeof window.getSelectedFiles === 'function') ? window.getSelectedFiles() : [];
    }

    function loading(on) {
      overlay.classList.toggle('is-open', !!on);
      btnDraft.disabled = btnSubmit.disabled = !!on;
    }
    const nextPaint = () => new Promise(r => requestAnimationFrame(() => r()));

    // Build FormData but include values from disabled inputs by temporarily enabling them.
    function buildFormDataIncludingDisabled(formEl) {
      // Only temporarily enable disabled fields that are NOT explicitly marked to skip
      const disabled = Array.from(
        formEl.querySelectorAll('[disabled]:not([data-skip-enable="1"])')
      );

      disabled.forEach(el => {
        el.dataset._wasDisabled = '1';
        el.disabled = false;
      });

      const fd = new FormData(formEl);

      disabled.forEach(el => {
        if (el.dataset._wasDisabled === '1') {
          el.disabled = true;
          delete el.dataset._wasDisabled;
        }
      });

      return fd;
    }

    /**
     * Collect all item rows for each product into a compact JSON structure:
     *   [{ product_id: 123, items: [ {itemName: '...', ...}, ...] }, ...]
     * and store it in the hidden <input name="products_items_json">.
     * Then mark all original item inputs with data-skip-enable="1" and disable them
     * so they are NOT included in the normal FormData payload (avoids max_input_vars).
     */
    function prepareItemsJson(formEl) {
      // productsIndexed[index] = { items: [...] }
      const productsIndexed = {};

      // Find all item-related fields in the form
      formEl
        .querySelectorAll('input[name^="products["][name*="[items]"], select[name^="products["][name*="[items]"], textarea[name^="products["][name*="[items]"]')
        .forEach(field => {
          const name = field.name;
          if (!name) return;

          // Match patterns like:
          // products[0][items][3][itemName]
          // products[1][items][10][material][]
          const m = name.match(/^products\[(\d+)]\[items]\[(\d+)]\[(.+?)](\[\])?$/);
          if (!m) return;

          const productIndex = parseInt(m[1], 10); // 0, 1, 2, ...
          const itemIndex    = parseInt(m[2], 10); // 0, 1, 2, ...
          const key          = m[3];              // e.g. itemName, quantity, material
          const isArray      = !!m[4];            // material[]

          // Initialize structures
          if (!productsIndexed[productIndex]) {
            productsIndexed[productIndex] = { items: [] };
          }
          if (!productsIndexed[productIndex].items[itemIndex]) {
            productsIndexed[productIndex].items[itemIndex] = {};
          }

          let value = field.value;

          // Handle checkbox / radio
          if (field.type === 'checkbox' || field.type === 'radio') {
            if (!field.checked) return;
          }

          if (value === '' || value === null || typeof value === 'undefined') {
            return;
          }

          const item = productsIndexed[productIndex].items[itemIndex];

          if (isArray) {
            if (!Array.isArray(item[key])) item[key] = [];
            item[key].push(value);
          } else {
            item[key] = value;
          }
        });

      // Apply the same "empty row" filter as backend:
      for (const pIndex in productsIndexed) {
        const product = productsIndexed[pIndex];
        const filteredItems = [];

        (product.items || []).forEach(row => {
          if (!row) return;

          const clone = { ...row };
          delete clone.id;
          delete clone.material;

          const hasNonEmpty = Object.values(clone).some(
            v => v !== '' && v !== null && typeof v !== 'undefined'
          );

          const hasMaterial =
            Array.isArray(row.material) ? row.material.length > 0 : !!row.material;

          if (hasNonEmpty || hasMaterial) {
            filteredItems.push(row);
          }
        });

        product.items = filteredItems;
      }

      // Store as JSON in hidden field
      const hidden = formEl.querySelector('#products-items-json');
      if (hidden) {
        hidden.value = JSON.stringify(productsIndexed);
      }

      // IMPORTANT: Disable all original item inputs so they don't count toward max_input_vars
      formEl
        .querySelectorAll('[name^="products["][name*="[items]"]')
        .forEach(el => {
          el.dataset.skipEnable = '1';
          el.disabled = true;
        });
    }

    async function send(isDraft, options = {}) {
      const silent = !!options.silent;

      isDraftEl.value = isDraft ? 1 : 0;

      // 🔴 NEW: build items JSON and disable original item inputs
      prepareItemsJson(form);

      const fd = buildFormDataIncludingDisabled(form);
      fd.set('is_draft', isDraftEl.value);
      fd.append('_method', 'PUT');
      for (const f of getSelectedFiles()) fd.append('attachments[]', f);
      
      if (isDraft) {
        fd.set('submit', '0');
      } else {
        // if your real Submit button doesn't include a field named "submit",
        // keep this line; it makes intent explicit for the controller:
        fd.set('submit', '1');
      }
      
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
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
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
          return false;
        }

        data = await res.json().catch(() => ({}));

        // 2) hide loading BEFORE showing SweetAlert
        loading(false);

        // Treat any HTTP 2xx as success; use JSON message only if available
        if (res.ok) {
          if (!silent) {
            await Swal.fire({
              icon: 'success',
              title: isDraft ? 'Draft saved' : 'Order saved',
              text: data.message || (isDraft ? 'Draft saved successfully.' : 'Order submitted successfully.')
            });

            // ✅ Only reload if it's a draft
            if (isDraft) {
              window.location.reload();
            } else {
              // ✅ Redirect to artist.orders when submitted
              window.location.href = '/data-entry/orders';
            }
          }
          return true;
        } else {
          if (!silent) {
            await Swal.fire({
              icon: 'error',
              title: 'Save failed',
              text: data.message || `HTTP ${res.status} — please try again`
            });
          }
          return false;
        }
      } catch (e) {
        console.error(e);
        loading(false); // be sure to hide on network errors too
        await Swal.fire({
          icon: 'error',
          title: 'Network error',
          text: 'Could not save. Please try again.'
        });
        return false;
      }
    }

    const OPTIONAL_NAME_WHITELIST = new Set([
      'lamination',
      'printer_id',
      'cutter_id',
      'delivery_installation_type',
      'delivery_cost'
    ]);

    function isOptional(el) {
      if (!el) return true;

      // Skip non-editable / system fields
      if (el.disabled) return true;
      if (el.readOnly) return true;
      if (el.type === 'hidden') return true;
      if (el.type === 'file') return true;

      // Permit radio is handled separately, not by general required logic
      if (el.classList.contains('permit-radio')) return true;
      if (el.hasAttribute('data-permit-required')) return true;

      if (el.hasAttribute('data-optional')) return true;

      const name = (el.getAttribute('name') || '').toLowerCase();

      // Product hidden/system fields
      if (name.includes('[product_id]')) return true;
      if (name.includes('[id]')) return true;

      // Job order read-only/system fields
      if (
        name === 'company_name' ||
        name === 'order_title' ||
        name === 'created_date' ||
        name === 'deadline' ||
        name === 'created_by' ||
        name === 'is_draft' ||
        name === 'products_items_json'
      ) return true;

      // Remarks optional
      if (name.includes('[remarks]')) return true;

      // Delivery optional fields
      if (name.includes('[deliveries]') && (
        name.includes('[deliver_install_type]') ||
        name.includes('[outsource_cost]') ||
        name.includes('[location]') ||
        name.includes('[datetime]')
      )) return true;

      return false;
    }

    function requiredElements() {
      // all inputs/selects/textareas with "required" that are NOT optional
      const all = Array.from(document.querySelectorAll('input[required], select[required], textarea[required]'));
      return all.filter(el => !isOptional(el));
    }

    function markRequired() {
      // your existing styling hook, keep if you had one:
      requiredElements().forEach(el => el.classList.toggle('is-invalid', !el.checkValidity()));
    }

    function requiredOK() {
      const baseOK = requiredElements().every(el => el.checkValidity());
      if (!baseOK) return false;

      const permitGroups = new Map();

      document.querySelectorAll('.permit-radio[data-permit-required]').forEach(radio => {
        if (!permitGroups.has(radio.name)) {
          permitGroups.set(radio.name, radio);
        }
      });

      const permitOK = Array.from(permitGroups.keys()).every(name => {
        const group = document.querySelectorAll(`input[name="${CSS.escape(name)}"]`);
        return Array.from(group).some(radio => radio.checked);
      });

      return permitOK;
    }

    function formComplete() {
      markRequired();
      return requiredOK();
    }

    function selectedAttachmentCount() {
      const newOnes = (window.getSelectedFiles?.() || []).length;
      const existing = document.querySelectorAll('[data-file-row]').length;
      return newOnes + existing;
    }

    // Intercept Save & Submit
    document.getElementById('btn-submit')?.addEventListener('click', onSubmitClick);

    // Modal 1 → Send to Printing (now it really submits)
    // document.getElementById('btn-confirm-send-printing').addEventListener('click', async () => {
    //   getModal('#modal-submit-ok').hide();
    //   await send(false);   // your existing submit flow
    // });

    // Modal 2 → Pass to Data Entry → open the picker AFTER the modal is fully hidden
    document.getElementById('btn-open-choose-de').addEventListener('click', () => {
      const inc = document.getElementById('modal-submit-incomplete');
      const onHidden = async () => {
        inc.removeEventListener('hidden.bs.modal', onHidden);
        const choose = getModal('#modal-choose-de-user');
        choose.show();

        // load users AFTER it opens (so you always see the modal)
        const sel = document.getElementById('de-user-select');
        sel.innerHTML = `<option value="">Loading…</option>`;
        try {
          const r = await fetch(@json(route('dataEntry.users')), { headers: { 'X-Requested-With':'XMLHttpRequest' }});
          const data = await r.json();
          sel.innerHTML = `<option value="">Please select a user</option>`;
          (data?.users || []).forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id; opt.textContent = u.name;
            sel.appendChild(opt);
          });
        } catch {
          sel.innerHTML = `<option value="">Failed to load users</option>`;
        }
      };
      inc.addEventListener('hidden.bs.modal', onHidden, { once:true });
      getModal(inc).hide();
    });

    // Choose DE → Confirm & Assign (now it really submits)
    document.getElementById('btn-confirm-assign').addEventListener('click', async () => {
      const sel = document.getElementById('de-user-select');
      if (!sel.value) { sel.focus(); return; }

      getModal('#modal-choose-de-user').hide();

      // NEW: Save current partial work as a Draft FIRST, silently
      loading(true);
      const saved = await send(true, { silent: true });   // isDraft = true
      if (!saved) { 
        loading(false);
        (window.Swal ? Swal.fire({icon:'error', title:'Save failed', text:'Could not save current progress before assigning.'}) : alert('Could not save draft.'));
        return;
      }

      try {
        const res = await fetch(@json(route('artist.orders.passToDataEntry', $order)), {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': @json(csrf_token()),
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            'Content-Type': 'application/json'
          },
          body: JSON.stringify({ user_id: sel.value })
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data?.ok) {
          window.location.href = @json(route('artist.orders.show', $order));
        } else {
          // const msg = data?.message || `HTTP ${res.status}`;
          // (window.Swal ? Swal.fire({icon:'error', title:'Assign failed', text: msg}) : alert(msg));
        }
      } catch {
        // (window.Swal ? Swal.fire({icon:'error', title:'Network error', text:'Could not assign to Data Entry.'}) : alert('Network error'));
      } finally {
        loading(false);
      }
    });

    // ===== /Submit UI logic =====
    const draftBtn = document.getElementById('btn-draft');
    const submitBtn = document.getElementById('btn-submit');

    if (draftBtn) draftBtn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      send(true); // Save Draft (no modal)
    });

    if (submitBtn) submitBtn.addEventListener('click', onSubmitClick);

    function selectedAttachmentCount() {
      const newOnes = (window.getSelectedFiles?.() || []).length;
      const existing = document.querySelectorAll('[data-file-row]').length;
      return newOnes + existing;
    }

    // Mark everything required EXCEPT: lamination, printer, cutter, install type & cost
    function markRequired() {
      document.querySelectorAll('#order-form input, #order-form select, #order-form textarea')
        .forEach(el => {
          if (!el.name || el.disabled) return;
          if (el.type === 'hidden' || el.type === 'file') return;
          if (isOptional(el)) return; // uses the new robust checker
          el.required = true;         // enforce required everywhere else
        });
    }

    function requiredElements() {
      const nodes = Array.from(document.querySelectorAll('input[required], select[required], textarea[required]'));
      return nodes.filter(el => !isOptional(el)); // belt-and-suspenders
    }

    function requiredOK() {
      const baseOK = requiredElements().every(el => {
        if (el.type === 'checkbox' || el.type === 'radio') {
          const group = document.querySelectorAll(`[name="${CSS.escape(el.name)}"]`);
          return Array.from(group).some(x => x.checked);
        }
        const v = (el.value || '').toString().trim();
        return v.length > 0;
      });
      if (!baseOK) return false;

      // Permit radio must be selected for each product
      const permitGroups = new Set(
        Array.from(document.querySelectorAll('.permit-radio'))
          .map(radio => radio.name)
      );

      return Array.from(permitGroups).every(name => {
        const group = document.querySelectorAll(`input[name="${CSS.escape(name)}"]`);
        return Array.from(group).some(radio => radio.checked);
      });
    }

    // Use our strict checker instead of browser's
    function formComplete() {
      // make sure required flags are applied before checking
      markRequired();
      return requiredOK();
    }

    function getLabelFor(el) {
      // Try: closest column -> label
      const wrap = el.closest('.col-12, .col-md-1, .col-md-2, .col-md-3, .col-md-4, .col-md-6, .col-xl-3, .col-xl-6, .mb-3, .row, .col-md-12');
      const lbl = wrap ? wrap.querySelector('label.form-label') : null;
      if (lbl && lbl.textContent) return lbl.textContent.trim();

      // Fallback: aria-label / placeholder / name
      return (el.getAttribute('aria-label') || el.getAttribute('placeholder') || el.name || 'Field').toString().trim();
    }

    function parseNamePath(name) {
      // products[0][items][2][sizeWidth]
      let m = name.match(/^products\[(\d+)\]\[items\]\[(\d+)\]\[(.+?)\]$/);
      if (m) return { kind: 'item', pIndex: +m[1], iIndex: +m[2], field: m[3] };

      // products[0][deliveries][1][quantity]  (if you have this pattern)
      m = name.match(/^products\[(\d+)\]\[deliveries\]\[(\d+)\]\[(.+?)\]$/);
      if (m) return { kind: 'delivery', pIndex: +m[1], dIndex: +m[2], field: m[3] };

      // deliveries[IDX][quantity] (your template shows this global pattern) :contentReference[oaicite:4]{index=4}
      m = name.match(/^deliveries\[(\d+)\]\[(.+?)\]$/);
      if (m) return { kind: 'delivery_global', dIndex: +m[1], field: m[2] };

      // products[0][name], products[0][qty_total], etc.
      m = name.match(/^products\[(\d+)\]\[(.+?)\]$/);
      if (m) return { kind: 'product', pIndex: +m[1], field: m[2] };

      return { kind: 'other', field: name };
    }

    function isEmptyRequired(el) {
      if (el.type === 'checkbox' || el.type === 'radio') {
        const group = document.querySelectorAll(`[name="${CSS.escape(el.name)}"]`);
        return !Array.from(group).some(x => x.checked);
      }
      const v = (el.value || '').toString().trim();
      return v.length === 0;
    }

    function productTitle(pIndex) {
      // Try to grab product name input: products[pIndex][name]
      const input = document.querySelector(`input[name="products[${pIndex}][name]"]`);
      const name = (input?.value || '').trim();
      return name ? `Product ${pIndex + 1} — ${name}` : `Product ${pIndex + 1}`;
    }

    function itemTitle(pIndex, iIndex) {
      // Try to get visible item number from DOM (best effort)
      const itemNode = document.querySelector(`#item${pIndex}_${iIndex} .item-number`);
      const n = (itemNode?.textContent || '').trim();
      return n ? `Item ${n}` : `Item ${iIndex + 1}`;
    }

    function collectMissingRequired() {
      const missing = [];

      requiredElements().forEach(el => {
        if (!isEmptyRequired(el)) return;

        const info = parseNamePath(el.name || '');
        missing.push({
          el,
          label: getLabelFor(el),
          info
        });
      });

      // ── Permit radio required check ──────────────────────────────────────
      const permitGroups = new Map();

      document.querySelectorAll('.permit-radio[data-permit-required]').forEach(radio => {
        if (!permitGroups.has(radio.name)) {
          permitGroups.set(radio.name, radio);
        }
      });

      permitGroups.forEach((radio, name) => {
        const group = document.querySelectorAll(`input[name="${CSS.escape(name)}"]`);
        const checked = Array.from(group).some(r => r.checked);

        if (!checked) {
          const pIndex = radio.dataset.pindex;
          const info = parseNamePath(`products[${pIndex}][permit]`);

          missing.push({
            el: radio,
            label: radio.dataset.permitLabel || 'Permit',
            info
          });
        }
      });

      return missing;
    }

    function renderMissingDetails(missing) {
      const root = document.getElementById('missing-details-list');
      if (!root) return;

      if (!missing.length) {
        root.innerHTML = `<div class="text-success">No missing required fields detected.</div>`;
        return;
      }

      // Group: Product -> (Item/Delivery) -> fields
      const grouped = new Map();

      for (const x of missing) {
        const { info } = x;

        let pKey = 'Product information';
        if (typeof info.pIndex === 'number') pKey = productTitle(info.pIndex);

        let subKey = 'Product information';
        if (info.kind === 'item') subKey = itemTitle(info.pIndex, info.iIndex);
        if (info.kind === 'delivery') subKey = `Delivery ${info.dIndex + 1}`;
        if (info.kind === 'delivery_global') subKey = `Delivery ${info.dIndex + 1}`;

        if (!grouped.has(pKey)) grouped.set(pKey, new Map());
        const sub = grouped.get(pKey);
        if (!sub.has(subKey)) sub.set(subKey, []);
        sub.get(subKey).push(x);
      }

      // Build HTML
      let html = '';
      for (const [pTitle, subMap] of grouped.entries()) {
        html += `<div class="border rounded p-3 mb-3">
          <div class="fw-semibold mb-2">${pTitle}</div>`;

        for (const [subTitle, arr] of subMap.entries()) {
          html += `<div class="ms-2 mb-2">
            <div class="text-body-secondary fw-semibold">${subTitle}</div>
            <ul class="mb-2">`;

          for (const entry of arr) {
            // clicking scrolls + focuses field
            const id = `miss_${Math.random().toString(16).slice(2)}`;
            entry.el.dataset.missingId = id;

            html += `<li>
              <a href="javascript:void(0)" data-jump-missing="${id}" class="text-decoration-none">
                ${entry.label}
              </a>
            </li>`;
          }

          html += `</ul></div>`;
        }

        html += `</div>`;
      }

      root.innerHTML = html;

      // Wire jump links
      root.querySelectorAll('[data-jump-missing]').forEach(a => {
        a.addEventListener('click', () => {
          const key = a.getAttribute('data-jump-missing');
          const target = document.querySelector(`[data-missing-id="${CSS.escape(key)}"]`);
          if (!target) return;

          // Close modal before jump so the screen can scroll
          bootstrap.Modal.getInstance(document.getElementById('modal-missing-details'))?.hide();

          setTimeout(() => {
            target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            target.focus?.();
            target.classList.add('is-invalid');
            setTimeout(() => target.classList.remove('is-invalid'), 1600);
          }, 250);
        });
      });
    }

    // Returns true if this element is inside the given root node
    function inside(el, root) {
      let p = el;
      while (p) {
        if (p === root) return true;
        p = p.parentElement;
      }
      return false;
    }

    // Check one product block: all required fields valid, ≥1 item, ≥1 delivery
    function productIsComplete(productRoot) {
      // Required fields scoped to this product, excluding optional ones
      const req = Array.from(document.querySelectorAll('input[required], select[required], textarea[required]'))
        .filter(el => inside(el, productRoot) && !isOptional(el));

      const reqOK = req.every(el => el.checkValidity());

      // At least one Item row in this product
      const hasItem = !!productRoot.querySelector('input[name*="[items]"][name$="[itemName]"], select[name*="[items]"][name$="[itemName]"]');

      // At least one Delivery row in this product
      const hasDelivery = !!productRoot.querySelector('input[name*="[deliveries]"][name$="[quantity]"], select[name*="[deliveries]"][name$="[quantity]"]');

      return reqOK && hasItem && hasDelivery;
    }

    // Validate all products
    function validateAllProducts() {
      const products = Array.from(document.querySelectorAll('.accordion-collapse[id^="pCollapse"]'));
      if (products.length === 0) return { allComplete: false, count: 0 };

      let allComplete = true;
      for (const root of products) {
        const ok = productIsComplete(root);
        if (!ok) { allComplete = false; break; }
      }
      return { allComplete, count: products.length };
    }

    async function onSubmitClick(e) {
      e.preventDefault();
      e.stopPropagation();

      const hasAttach = selectedAttachmentCount() > 0;
      const complete  = formComplete();

      const hasAtLeastOneItem =
        !!document.querySelector('input[name^="products["][name*="[items]"][name$="[itemName]"]');
      const hasAtLeastOneDelivery =
        !!document.querySelector('input[name^="products["][name*="[deliveries]"][name$="[quantity]"]');

      const strictComplete = complete && hasAtLeastOneItem && hasAtLeastOneDelivery;

      if (strictComplete && hasAttach) {
        new bootstrap.Modal(document.getElementById('modal-submit-ok')).show();
        return;
      }
      if (!strictComplete && hasAttach) {
        const missing = collectMissingRequired();
        renderMissingDetails(missing);
        new bootstrap.Modal(document.getElementById('modal-missing-details')).show();
        return;
      }
      if (!hasAttach) {
        if (window.Swal) {
          Swal.fire({icon:'error', title:'Missing info', text:'Please add at least one attachment.'});
        } else {
          alert('Please add at least one attachment.');
        }
      }
  
      // (Optional) complete + NO attachment → block
      if (formOK && !hasAttach) {
        if (window.Swal) {
          await Swal.fire({
            icon: 'error',
            title: 'Attachment required',
            text: 'Please upload at least one attachment.'
          });
        } else {
          alert('Please upload at least one attachment.');
        }
      }
    }

    // “Send to Printing” actually submits
    document.getElementById('btn-confirm-send-printing')
      ?.addEventListener('click', async () => {
        bootstrap.Modal.getInstance(document.getElementById('modal-submit-ok'))?.hide();
        await send(false);
      });

    // “Pass to Data Entry” → open picker modal and lazy-load users
    async function openChooseDE() {
    const sel = document.getElementById('de-user-select');
    sel.innerHTML = `<option value="">Loading...</option>`;


    try {
    const r = await fetch(@json(route('dataEntry.users')), {
    headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    const data = await r.json();
    sel.innerHTML = `<option value="">Please select a user</option>`;
    (data?.users || []).forEach(u => {
    const opt = document.createElement('option');
    opt.value = u.id;
    opt.textContent = u.name;
    sel.appendChild(opt);
    });
    } catch {
    sel.innerHTML = `<option value="">Failed to load users</option>`;
    }


    new bootstrap.Modal(document.getElementById('modal-choose-de-user')).show();
    }


    document.getElementById('btn-open-choose-de')
    ?.addEventListener('click', async () => {
    bootstrap.Modal.getInstance(document.getElementById('modal-submit-incomplete'))?.hide();
    await openChooseDE();
    });


    // NEW: from Missing Details modal
    document.getElementById('btn-missing-pass-de')
    ?.addEventListener('click', async () => {
    bootstrap.Modal.getInstance(document.getElementById('modal-missing-details'))?.hide();
    await openChooseDE();
    });

    // Confirm & Assign → POST to server then redirect
    document.getElementById('btn-confirm-assign')
      ?.addEventListener('click', async () => {
        const sel = document.getElementById('de-user-select');
        const userId = sel.value;
        if (!userId) {
          sel.focus();
          return;
        }

        bootstrap.Modal.getInstance(document.getElementById('modal-choose-de-user'))?.hide();

        loading(true);
        try {
          const res = await fetch(@json(route('artist.orders.passToDataEntry', $order)), {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': @json(csrf_token()),
              'X-Requested-With': 'XMLHttpRequest',
              'Accept': 'application/json',
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              user_id: userId
            })
          });
          const data = await res.json().catch(() => ({}));
          if (res.ok && data?.ok) {
            window.location.href = @json(route('artist.orders.show', $order));
          } else {
            // const msg = data?.message || `HTTP ${res.status}`;
            // if (window.Swal) await Swal.fire({
            //   icon: 'error',
            //   title: 'Assign failed',
            //   text: msg
            // });
            // else alert(msg);
          }
        } catch {
          // if (window.Swal) await Swal.fire({
          //   icon: 'error',
          //   title: 'Network error',
          //   text: 'Could not assign to Data Entry.'
          // });
          // else alert('Network error');
        } finally {
          loading(false);
        }
      });

      // --- helpers ---
      function getModal(el) {
        const node = (typeof el === 'string') ? document.querySelector(el) : el;
        return bootstrap.Modal.getInstance(node) || new bootstrap.Modal(node);
      }

      function cleanupModalArtifacts() {
        // If no modals are showing, remove any leftover classes/backdrops
        const anyOpen = document.querySelector('.modal.show');
        if (!anyOpen) {
          document.body.classList.remove('modal-open');
          document.body.style.removeProperty('padding-right');
          document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
        }
      }

      // Attach once to all modals present on the page
      document.querySelectorAll('.modal').forEach(m => {
        m.addEventListener('hidden.bs.modal', cleanupModalArtifacts);
      });

  });

  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.delete-order-file');
    if (!btn) return;

    const url = btn.dataset.url;
    const path = btn.dataset.path;
    const row = btn.closest('[data-file-row]');
    if (!url || !path || !row) return;

    // confirm
    const ok = window.Swal ?
      (await Swal.fire({
        icon: 'warning',
        title: 'Delete this file?',
        text: 'This will remove it from the order.',
        showCancelButton: true,
        confirmButtonText: 'Delete',
        confirmButtonColor: '#d33'
      })).isConfirmed :
      confirm('Delete this file?');

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
        body: JSON.stringify({
          path
        })
      });

      const data = await res.json().catch(() => ({}));
      if (res.ok && data?.ok) {
        row.remove();
        if (window.Swal) Swal.fire({
          icon: 'success',
          title: 'Deleted',
          timer: 1100,
          showConfirmButton: false
        });
      } else {
        const msg = data?.message || `HTTP ${res.status}`;
        if (window.Swal) Swal.fire({
          icon: 'error',
          title: 'Delete failed',
          text: msg
        });
        else alert('Delete failed: ' + msg);
        btn.disabled = false;
      }
    } catch (err) {
      if (window.Swal) Swal.fire({
        icon: 'error',
        title: 'Network error',
        text: String(err)
      });
      else alert('Network error: ' + err);
      btn.disabled = false;
    }
  });

  function setDeliveryRowState(row) {
    const methodSel = row.querySelector('[data-method-select]');
    const typeSel = row.querySelector('[data-install-type]');
    const costInp = row.querySelector('[data-outsource-cost]');
    if (!methodSel || !typeSel || !costInp) return;

    const method = (methodSel.value || '').toLowerCase();
    const isDI = (method === 'delivery' || method === 'installation');

    // Rule 1: only enabled when "Delivery & Installation"
    typeSel.disabled = !isDI;
    costInp.disabled = !isDI;

    // Rule 2: cost only enabled when type is outsource/both
    if (isDI) {
      const t = (typeSel.value || '').toLowerCase();
      costInp.disabled = !(t === 'outsource' || t === 'both');
    }
  }

  document.addEventListener('change', function(e) {
    if (e.target.matches('[data-method-select], [data-install-type]')) {
      const row = e.target.closest('[data-delivery-row]');
      if (row) setDeliveryRowState(row);
    }
  });

  // initialize on load
  document.querySelectorAll('[data-delivery-row]').forEach(setDeliveryRowState);

  function forceEnableScroll() {
    // remove Bootstrap locking + any stray styles/backdrops
    document.documentElement.style.removeProperty('overflow');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('position');
    document.body.style.removeProperty('top');
    document.body.style.removeProperty('width');
    document.body.style.removeProperty('padding-right');
    document.body.classList.remove('modal-open');
    document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
  }

  // bind to both hide and hidden (covers fast close / race cases)
  ['#modal-submit-ok', '#modal-submit-incomplete', '#modal-choose-de-user'].forEach(sel => {
    const el = document.querySelector(sel);
    if (!el) return;
    el.addEventListener('hide.bs.modal',   forceEnableScroll);
    el.addEventListener('hidden.bs.modal', forceEnableScroll);
  });

  // also run after any button with data-bs-dismiss="modal" is clicked
  document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
    btn.addEventListener('click', () => setTimeout(forceEnableScroll, 50));
  });

  // safety: before opening any modal, clear leftovers (optional)
  function openModalSafe(sel) { forceEnableScroll(); (bootstrap.Modal.getInstance(sel) || new bootstrap.Modal(sel)).show(); }

  (function () {
    const $rows = document.getElementById('remarkRows');
    const tpl   = document.getElementById('remarkRowTpl').innerHTML;
    let rIdx    = 0;

    function addRemarkRow() {
      const html = tpl.replaceAll('__IDX__', rIdx++);
      const wrap = document.createElement('div');
      wrap.innerHTML = html.trim();
      $rows.appendChild(wrap.firstElementChild);
    }

    document.getElementById('addRemarkRow').addEventListener('click', addRemarkRow);
    $rows.addEventListener('click', function (e) {
      if (e.target.closest('.remove-remark')) {
        e.target.closest('.remark-row').remove();
      }
    });

    // Ensure modal starts with one blank row
    document.getElementById('addProductModal').addEventListener('shown.bs.modal', function () {
      if (!$rows.querySelector('.remark-row')) addRemarkRow();
    });

    // Reset on close (optional)
    document.getElementById('addProductModal').addEventListener('hidden.bs.modal', function () {
      $rows.innerHTML = '';
      rIdx = 0;
      document.getElementById('p_name').value     = '';
      document.getElementById('p_qty').value      = '';
      document.getElementById('p_material').value = '';
    });

    function toInt(v){ v=String(v??'').trim(); const n=parseInt(v,10); return isNaN(n)?0:n; }

    // Parse pIndex and delivery row index from the input name
    function parseName(name){
      const m = name.match(/^products\[(\d+)\]\[deliveries\]\[(\d+)\]\[quantity\]$/);
      return m ? { pIndex: m[1], dIndex: m[2] } : null;
    }


  })();

  document.addEventListener('DOMContentLoaded', () => {
  const sel = document.getElementById('assignee_artist_id');
  const btn = document.getElementById('btn-assign-artist');
  if (!sel || !btn) return;

  const searchUrl = @json(route('artist.orders.assignees.search'));

  // ---------- Plain SELECT fallback (no Select2) ----------
  function initPlainSelect() {
    fetch(searchUrl + '?roles[]=artist&roles[]=head-artist', {
      headers: { 'Accept': 'application/json' }
    })
    .then(r => r.json())
    .then(payload => {
      const list = Array.isArray(payload) ? payload : (payload.results || []);
      const existing = new Set(Array.from(sel.options).map(o => String(o.value)));
      list.forEach(u => {
        const id   = String(u.id);
        const text = u.text ?? (u.name ? `${u.name} (${u.role})` : id);
        if (!existing.has(id)) {
          const opt = document.createElement('option');
          opt.value = id;
          opt.textContent = text;
          sel.appendChild(opt);
        }
      });
    })
    .catch(() => {});
  }

  // ---------- Select2 path ----------
  if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
    const $s = jQuery(sel);

    // (defensive) destroy if already initialized
    if ($s.data('select2')) $s.select2('destroy');

    $s.select2({
      placeholder: 'Select artist...',
      allowClear: true,
      width: '100%',
      minimumInputLength: 0,                               // ← show list without typing
      ajax: {
        url: searchUrl,
        dataType: 'json',
        delay: 150,
        data: params => ({
          q: params.term || '',                            // ← empty term triggers "all"
          roles: ['artist','head-artist']
        }),
        processResults: data => {
          // support both {results:[{id,text}]} and raw arrays
          const results = Array.isArray(data) ? data : (data.results || []);
          return { results };
        },
        cache: true
      }
    });

    // Force initial fetch when the dropdown opens (so it shows immediately)
    $s.on('select2:open', () => {
      const searchInput = document.querySelector('.select2-container--open .select2-search__field');
      if (searchInput) {
        // Trigger AJAX with empty query on open
        const ev = new Event('input', { bubbles: true });
        searchInput.dispatchEvent(ev);
      }
    });
  } else {
    // No Select2 present → populate once
    initPlainSelect();
  }

  // ---------- Save assignment ----------
  btn.addEventListener('click', async (e) => {
    e.preventDefault();
    const userId = sel.value || null;

    btn.disabled = true;
    try {
      const res = await fetch(@json(route('artist.orders.assigns', $order)), {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': @json(csrf_token()),
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ user_id: userId })
      });
      const data = await res.json().catch(() => ({}));
      if (res.ok && data?.ok) {
        if (window.Swal) {
          Swal.fire({ icon:'success', title:'Artist Assigned', timer:1200, showConfirmButton:false });
        }
      } else {
        const msg = data?.message || `HTTP ${res.status}`;
        if (window.Swal) Swal.fire({ icon:'error', title:'Failed to save', text: msg });
        else alert(msg);
      }
    } catch (err) {
      if (window.Swal) Swal.fire({ icon:'error', title:'Network error', text:'Please try again.' });
      else alert('Network error');
    } finally {
      btn.disabled = false;
    }
  });
});

(function () {
  // Utility: set value + fire events (supports Select2 if present)
  function setValueAndTrigger(el, val) {
    if (!el) return;
    const isSelect = el.tagName === 'SELECT';
    const old = el.value;
    if (old === String(val)) return;
    el.value = val;
    // native events
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
    // select2 support (if used)
    if (isSelect && window.jQuery && jQuery(el).data('select2')) {
      jQuery(el).val(val).trigger('change.select2');
    }
  }

  function syncRow(row, cause) {
    if (!row) return;
    const methodSel = row.querySelector('[data-method-select]');
    const typeSel   = row.querySelector('[data-install-type]');
    const costInp   = row.querySelector('[data-outsource-cost]');

    if (!methodSel || !typeSel || !costInp) return;

    const isDI      = (methodSel.value === 'delivery' || methodSel.value === 'installation');
    const needsCost = isDI && (typeSel.value === 'outsource' || typeSel.value === 'both');

    // When switching AWAY from DI → clear type + cost before disabling
    if (!isDI) {
      setValueAndTrigger(typeSel, '');
      setValueAndTrigger(costInp, '');
    }

    // Toggle disabled states
    typeSel.disabled = !isDI;

    // If type changes to in_house or nothing → clear cost
    if (!needsCost) {
      setValueAndTrigger(costInp, '');
    }
    costInp.disabled = !needsCost;

    // Optional: harden number input (block e/E/+/-)
    row.querySelectorAll('input[type="number"]').forEach(function (n) {
      if (n.__boundBlockSci) return;
      n.addEventListener('keydown', function (e) {
        if (['e','E','+','-'].includes(e.key)) e.preventDefault();
      });
      n.__boundBlockSci = true;
    });
  }

  // Event delegation (works for dynamic rows)
  document.addEventListener('change', function (e) {
    if (e.target.matches('[data-method-select]') || e.target.matches('[data-install-type]')) {
      const row = e.target.closest('[data-delivery-row]');
      syncRow(row, 'change');
    }
  });

  // Init existing rows on load
  function initAll() {
    document.querySelectorAll('[data-delivery-row]').forEach(function (row) {
      syncRow(row, 'init');
    });
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
  } else {
    initAll();
  }

  // Watch for newly added delivery rows
  const container = document.querySelector('#deliveries-container') || document.body;
  const mo = new MutationObserver(function (muts) {
    muts.forEach(function (m) {
      m.addedNodes.forEach(function (n) {
        if (n.nodeType === 1 && n.matches && n.matches('[data-delivery-row]')) {
          syncRow(n, 'added');
        }
        // handle wrappers adding children
        if (n.nodeType === 1) {
          n.querySelectorAll && n.querySelectorAll('[data-delivery-row]').forEach(function (row) {
            syncRow(row, 'added-deep');
          });
        }
      });
    });
  });
  mo.observe(container, { childList: true, subtree: true });
})();

(function () {
  function csrfToken() {
    const el = document.querySelector('meta[name="csrf-token"]');
    return el ? el.getAttribute('content') : '';
  }

  document.addEventListener('click', async function (e) {
    const btn = e.target.closest('[data-delete-product]');
    if (!btn) return;

    const row = btn.closest('[data-product-row]');
    const url = row?.dataset?.url;
    if (!url) return;

    // SweetAlert confirmation dialog
    const confirm = await Swal.fire({
      title: 'Delete this product?',
      text: 'This action cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Yes, delete it!',
    });

    if (!confirm.isConfirmed) return;

    // Perform DELETE request
    const res = await fetch(url, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': csrfToken(),
        'Accept': 'application/json'
      },
      credentials: 'same-origin'
    });

    const data = await res.json().catch(() => ({}));

    if (!res.ok || !data.ok) {
      return Swal.fire({
        icon: 'error',
        title: 'Error',
        text: data.message || 'Failed to delete product.'
      });
    }

    // Remove row visually
    row.remove();

    // Success message
    await Swal.fire({
      icon: 'success',
      title: 'Deleted!',
      text: 'The product has been deleted successfully.',
      timer: 1500,
      showConfirmButton: false
    });
  });
})();

(function () {
  const toInt = v => {
    v = (v ?? '').toString().trim();
    return v === '' ? 0 : Math.max(0, parseInt(v, 10) || 0);
  };

  // Find the product container that wraps the qty input + delivery summary
  function findProductRoot(el) {
    return el.closest('[data-product-block]')  // if you have a product wrapper
        || el.closest('.product-card')         // or your own wrapper
        || el.closest('.card');                // fallback
  }

  // Sum all delivery quantities inside this product
  function sumDeliveries(root) {
    let sum = 0;
    root.querySelectorAll('.del-qty').forEach(inp => sum += toInt(inp.value));
    return sum;
  }

  // Update the Delivery Breakdown labels using your IDs
  function renderCounts(root) {
    // qty input (use name$ to avoid depending on the #totalQty id being unique)
    const totalInp = root.querySelector('input[name$="[qty_total]"]') || root.querySelector('#totalQty');
    const total    = toInt(totalInp?.value);
    const planned  = sumDeliveries(root);

    // find the summary block and its children by prefix
    const summary   = root.querySelector('[id^="del-summary-"]');
    const totalEl   = summary ? summary.querySelector('[id^="del-sum-total-"]') : null;
    const remainEl  = summary ? summary.querySelector('[id^="del-sum-remaining-"]') : null;

    if (totalEl)  totalEl.textContent  = total;
    if (remainEl) remainEl.textContent = Math.max(0, total - planned);
  }

  function onTotalChange(inp) {
    const root = findProductRoot(inp);
    if (root) renderCounts(root);
  }
  function onDeliveryQtyChange(inp) {
    const root = findProductRoot(inp);
    if (root) renderCounts(root);
  }

  // Delegate for dynamic rows too
  document.addEventListener('input', function (e) {
    if (e.target.matches('input[name$="[qty_total]"], #totalQty')) onTotalChange(e.target);
    if (e.target.matches('.del-qty')) onDeliveryQtyChange(e.target);
  });
  document.addEventListener('change', function (e) {
    if (e.target.matches('input[name$="[qty_total]"], #totalQty')) onTotalChange(e.target);
    if (e.target.matches('.del-qty')) onDeliveryQtyChange(e.target);
  });

  // Initial sync on load
  function initAll() {
    document.querySelectorAll('input[name$="[qty_total]"], #totalQty').forEach(onTotalChange);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
  } else {
    initAll();
  }
})();

(function () {
  const form = document.getElementById('order-form');
  if (!form) return;

  let isDirty = false;
  let killBeforeUnload = null;

  // Mark page as dirty on *any* form input/change (captures dynamic rows too)
  const markDirty = () => { isDirty = true; };
  form.addEventListener('input',  markDirty, true);
  form.addEventListener('change', markDirty, true);

  // When we really save/submit, call this to silence the guard
  function markClean() { isDirty = false; }

  // Expose so your existing code can call it
  window.__markFormClean = markClean;
  window.__killBeforeUnload = killBeforeUnload;

  // Browser-level leave prompt
  function beforeUnload(e) {
    if (!isDirty) return;
    e.preventDefault();
    // Chrome/Edge/Firefox require returnValue to be set
    e.returnValue = '';
    return '';
  }
  killBeforeUnload = () => window.removeEventListener('beforeunload', beforeUnload);
  window.addEventListener('beforeunload', beforeUnload);

  // Intercept in-app link clicks (e.g., sidebar, tabs, back to list)
  document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href]');
    if (!a) return;

    const href = a.getAttribute('href') || '';
    // ignore anchors, JS, and modal toggles
    if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
    if (a.hasAttribute('target')) return;

    if (!isDirty) return; // safe to navigate
    e.preventDefault();

    const proceed = (ok) => {
      if (!ok) return;
      markClean();
      killBeforeUnload();
      window.location.href = a.href;
    };

    if (window.Swal) {
      Swal.fire({
        icon: 'warning',
        title: 'Leave this page?',
        text: 'You have unsaved changes. If you leave now, your changes will be lost.',
        showCancelButton: true,
        confirmButtonText: 'Leave page',
        cancelButtonText: 'Stay'
      }).then(r => proceed(r.isConfirmed));
    } else {
      proceed(confirm('You have unsaved changes. Leave this page?'));
    }
  });

  // If you use browser Back/Forward buttons, the beforeunload handler above will handle it.

  // --- Integrate with your existing save functions ---
  // Call markClean() right before you actually POST, so leaving (redirect/reload) won’t prompt.
  // You already have a send(isDraft, options) function; patch it once here.
  const _send = window.send;
  if (typeof _send === 'function') {
    window.send = async function wrappedSend(isDraft, options) {
      // silence the guard for this intentional navigation
      markClean();
      killBeforeUnload();
      try {
        const ok = await _send.call(this, isDraft, options);
        return ok;
      } finally {
        // If we stayed on page (e.g., validation error), re-arm the guard
        if (document.body.contains(form)) {
          window.addEventListener('beforeunload', beforeUnload);
        }
      }
    };
  }

  // Also clear the guard explicitly on the two main buttons (belt & suspenders)
  document.getElementById('btn-draft')?.addEventListener('click', () => { markClean(); killBeforeUnload(); }, { once:false });
  document.getElementById('btn-submit')?.addEventListener('click', () => { markClean(); killBeforeUnload(); }, { once:false });

  window.addEventListener('load', () => {
    setTimeout(() => window.__markFormClean && window.__markFormClean(), 0);
  });
})();

// ── Permit radio ──────────────────────────────────────────────────────────
document.addEventListener('change', function (e) {
  const radio = e.target.closest('.permit-radio');
  if (!radio) return;

  const group = document.querySelectorAll(
    `input[name="${CSS.escape(radio.name)}"]`
  );

  group.forEach(el => el.classList.remove('is-invalid'));
});

function restrict2dp(e) {
  const el = e.target;
  const v  = el.value;

  // ✅ allow "in-progress" states so caret doesn't jump:
  // '', '123', '123.', '123.4', '123.45'
  const partialOK = /^\d*(?:\.)?\d{0,2}$/.test(v);

  if (partialOK) {
    // accept and remember this state (no rewrite → no jump)
    el.dataset.last = v;
    el.dataset.pos  = el.selectionStart;
    return;
  }

  // ❌ invalid (extra dots, letters, >2 decimals, etc.) → revert
  const last = el.dataset.last ?? '';
  const pos  = parseInt(el.dataset.pos ?? last.length, 10);
  el.value = last;

  // restore caret gracefully
  requestAnimationFrame(() => {
    const p = Math.min(pos, el.value.length);
    el.setSelectionRange(p, p);
  });
}

function restrictInteger(e){
  const el = e.target;
  const v  = el.value;

  // ✅ allow only whole numbers (empty or digits)
  const partialOK = /^\d*$/.test(v);

  if (partialOK) {
    el.dataset.last = v;
    el.dataset.pos  = el.selectionStart;
    return;
  }

  // ❌ invalid → revert to last valid
  const last = el.dataset.last ?? '';
  const pos  = parseInt(el.dataset.pos ?? last.length, 10);
  el.value = last;
  requestAnimationFrame(() => {
    const p = Math.min(pos, el.value.length);
    el.setSelectionRange(p, p);
  });
}
$(document).on('input', '.qty-input', function () {
  // keep only digits
  let v = this.value.replace(/\D+/g, '');

  // if it starts with zero and has more than one digit, remove leading zeros
  if (v.length > 1) v = v.replace(/^0+/, '');

  this.value = v;
});

    // enable Bootstrap tooltips if not already
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
    });

    (function(){
  document.addEventListener('click', function (e) {
    const el = e.target.closest('.js-reason-banner');
    if (!el) return;

    e.preventDefault();
    e.stopPropagation();

    const type   = (el.getAttribute('data-type') || '').toUpperCase(); // REDO | REJECTED
    const reason = (el.getAttribute('data-reason') || '').trim();
    const by     = (el.getAttribute('data-by') || '').trim();

    const modal  = document.getElementById('reasonModal');
    const title  = document.getElementById('reasonModalTitle');
    const byEl   = document.getElementById('reasonBy');
    const textEl = document.getElementById('reasonText');
    const tag    = document.getElementById('reasonTag');
    const icon   = document.getElementById('reasonIcon');

    // theme classes
    modal.classList.remove('is-redo','is-reject');

    if (type === 'REDO') {
      modal.classList.add('is-redo');
      title.textContent = 'Redo Detail';
      tag.textContent = 'REDO';
      icon.className = 'bi bi-exclamation-octagon-fill';
    } else {
      modal.classList.add('is-reject');
      title.textContent = 'Rejected Detail';
      tag.textContent = 'REJECTED';
      icon.className = 'bi bi-x-octagon-fill';
    }

    byEl.textContent = by ? `By ${by}` : '';
    textEl.textContent = reason || '(No reason provided)';

    bootstrap.Modal.getOrCreateInstance(modal).show();
  });

  // Optional: keyboard "Enter" on focused banner
  document.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return;
    const a = document.activeElement;
    if (a && a.classList.contains('js-reason-banner')) a.click();
  });
})();
</script>
@endpush