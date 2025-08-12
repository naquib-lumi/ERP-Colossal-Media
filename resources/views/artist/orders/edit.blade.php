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
    
  .attach-box{
    position:relative; border:2px dashed #cbd5e1; border-radius:10px;
    padding:48px; display:flex; align-items:center; justify-content:center;
    background:#fff; cursor:pointer;
  }
  .attach-inner{ text-align:center; pointer-events:none; }
  .attach-icon{ width:42px;height:42px;margin:0 auto 12px;display:flex;align-items:center;justify-content:center;background:#f1f5f9;border-radius:8px;font-size:20px; }
  .attach-title{ color:#475569; font-weight:600; }
  .attach-hint{ color:#64748b; font-size:12px; }

  /* the magic: input covers the box and receives the click */
  .file-overlay{
    position:absolute; inset:0;
    opacity:0; cursor:pointer;
  }

  .remove-x{
    border:none; background:none; color:#dc2626;  /* red-600 */
    font-weight:700; cursor:pointer; margin-left:8px;
  }
  .remove-x:hover{ color:#b91c1c; }               /* red-700 */

</style>

@endpush
<form id="orderForm" action="{{ route('artist.orders.update', $order->id) }}" method="POST" enctype="multipart/form-data">
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
                  <select name="design_confirm" class="form-select">
                    <option value=""  {{ $dc === null ? 'selected' : '' }}>-</option>
                    <option value="yes" {{ $dc === 'yes' ? 'selected' : '' }}>Yes</option>
                    <option value="no"  {{ $dc === 'no'  ? 'selected' : '' }}>No</option>
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
                  <input name="product[name]" type="text" class="form-control" placeholder="e.g. Business Card">
                </div>
                <div class="col-12 col-md-6 col-xl-3">
                  <label class="form-label">Total Quantity</label>
                  <input name="product[qty_total]" type="number" min="0" class="form-control" placeholder="1000">
                </div>
                <div class="col-12 col-md-6 col-xl-6">
                  <label class="form-label">Material / Remark</label>
                  <input name="product[material]" type="text" class="form-control" placeholder="Premium Paper, Glossy">
                </div>
              </div>

              {{-- Items repeater --}}
              @php
                // existing items from DB or from old() after validation errors
                $items = old('items', $order->items ?? [[]]);
              @endphp

              <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="mb-0">Items</h6>
                <button type="button" id="addItemBtn" class="btn btn-sm btn-outline-primary">
                  <i class="bx bx-plus me-1"></i> Add Item
                </button>
              </div>

              <div class="accordion" id="productItems" data-start-number="2" data-next-index="1">
                @foreach($items as $i => $item)
                  <div class="accordion-item mb-3 border rounded" id="item{{ $i }}">
                    <div class="accordion-header d-flex justify-content-between align-items-center px-3 py-2">

                      <div>
                        <span class="fw-semibold">Item {{ $i+1 }}</span>
                        <span class="text-body-secondary ms-2 small">
                          {{ data_get($item, 'name', '') }}
                          {{ data_get($item, 'quantity') ? ' ' . data_get($item, 'quantity') : '' }}
                        </span>
                      </div>

                      <div class="d-flex align-items-center gap-2">
                        {{-- Trash Icon --}}
                        @if ($i > 0)
                          <button type="button"
                                  class="btn btn-link text-danger p-0"
                                  onclick="removeItem({{ $i }}, event)">
                            <i class="bx bx-trash fs-5"></i>
                          </button>
                        @endif

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

                    <div id="itemPane{{ $i }}"
                      class="accordion-collapse collapse {{ $i === 0 ? 'show' : '' }}"
                      data-bs-parent="#productItems">
                      <div class="accordion-body">
                        @if ($i > 0)
                          <div class="item-actions sticky-top d-flex justify-content-end">
                            <button type="button"
                                    class="btn btn-outline-danger btn-sm"
                                    onclick="removeItem({{ $i }}, event)">
                              <i class="bx bx-trash me-1"></i> Delete
                            </button>
                          </div>
                        @endif
                        <div class="row g-3">
                          <div class="col-md-6">
                            <label class="form-label">Item Name</label>
                            <input type="text" class="form-control" name="items[{{ $i }}][name]" value="{{ old('items.$i.name', data_get($item,'name')) }}">
                          </div>

                          <div class="col-md-6">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="items[{{ $i }}][qty]" value="{{ old('items.$i.qty', data_get($item,'qty')) }}">
                          </div>

                          <div class="col-12">
                            <label class="form-label">Material</label>
                            <input type="text" class="form-control" name="items[{{ $i }}][material]" value="{{ old('items.$i.material', data_get($item,'material')) }}">
                          </div>
                          <div class="col-12 col-md-4">
                            <label class="form-label">Size (inches) – Width</label>
                            <input name="items[0][size][w]" type="text" class="form-control">
                          </div>
                          <div class="col-12 col-md-4">
                            <label class="form-label">Height</label>
                            <input name="items[0][size][h]" type="text" class="form-control">
                          </div>
                          <div class="col-12 col-md-4">
                            <label class="form-label">Length</label>
                            <input name="items[0][size][l]" type="text" class="form-control">
                          </div>

                          <div class="col-12 col-md-3">
                            <label class="form-label">Bleed (Top)</label>
                            <input name="items[0][bleed][top]" type="text" class="form-control">
                          </div>
                          <div class="col-12 col-md-3">
                            <label class="form-label">Bottom</label>
                            <input name="items[0][bleed][bottom]" type="text" class="form-control">
                          </div>
                          <div class="col-12 col-md-3">
                            <label class="form-label">Left</label>
                            <input name="items[0][bleed][left]" type="text" class="form-control">
                          </div>
                          <div class="col-12 col-md-3">
                            <label class="form-label">Right</label>
                            <input name="items[0][bleed][right]" type="text" class="form-control">
                          </div>

                          <div class="col-md-3">
                            <label class="form-label">Lamination</label>
                            <select name="items[0][lamination]" class="form-select">
                              <option value="">-</option>
                              <option>Gloss</option><option>Matte</option>
                            </select>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label">Printer</label>
                            <select name="items[0][printer]" class="form-select">
                              <option>Printer</option>
                            </select>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label">Cutter</label>
                            <select name="items[0][cutter]" class="form-select">
                              <option>Cutter</option>
                            </select>
                          </div>
                          <div class="col-md-12">
                            <label class="form-label">Finishing</label>
                            <input name="items[0][finishing]" type="text" class="form-control" placeholder="Coating, lamination, etc…">
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
                      class="accordion-collapse collapse"
                      aria-labelledby="itemHdr__INDEX__"
                      data-bs-parent="#productItems">
                    <div class="accordion-body">
                      <div class="row g-3">
                        <div class="col-md-6">
                          <label class="form-label">Item Name</label>
                          <input type="text" class="form-control" name="items[__INDEX__][name]" value="">
                        </div>

                        <div class="col-md-6">
                          <label class="form-label">Quantity</label>
                          <input type="number" class="form-control" name="items[__INDEX__][qty]" value="">
                        </div>

                        <div class="col-12">
                          <label class="form-label">Material</label>
                          <input type="text" class="form-control" name="items[__INDEX__][material]" value="">
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Size (inches) – Width</label>
                            <input name="items[0][size][w]" type="text" class="form-control">
                          </div>
                          <div class="col-12 col-md-4">
                            <label class="form-label">Height</label>
                            <input name="items[0][size][h]" type="text" class="form-control">
                          </div>
                          <div class="col-12 col-md-4">
                            <label class="form-label">Length</label>
                            <input name="items[0][size][l]" type="text" class="form-control">
                          </div>

                          <div class="col-12 col-md-3">
                            <label class="form-label">Bleed (Top)</label>
                            <input name="items[0][bleed][top]" type="text" class="form-control">
                          </div>
                          <div class="col-12 col-md-3">
                            <label class="form-label">Bottom</label>
                            <input name="items[0][bleed][bottom]" type="text" class="form-control">
                          </div>
                          <div class="col-12 col-md-3">
                            <label class="form-label">Left</label>
                            <input name="items[0][bleed][left]" type="text" class="form-control">
                          </div>
                          <div class="col-12 col-md-3">
                            <label class="form-label">Right</label>
                            <input name="items[0][bleed][right]" type="text" class="form-control">
                          </div>

                          <div class="col-md-3">
                            <label class="form-label">Lamination</label>
                            <select name="items[0][lamination]" class="form-select">
                              <option value="">-</option>
                              <option>Gloss</option><option>Matte</option>
                            </select>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label">Printer</label>
                            <select name="items[0][printer]" class="form-select">
                              <option>Printer</option>
                            </select>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label">Cutter</label>
                            <select name="items[0][cutter]" class="form-select">
                              <option>Cutter</option>
                            </select>
                          </div>
                          <div class="col-md-12">
                            <label class="form-label">Finishing</label>
                            <input name="items[0][finishing]" type="text" class="form-control" placeholder="Coating, lamination, etc…">
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
                <textarea name="product[remarks]" rows="3" class="form-control"
                  placeholder="Client requested matte finish on cover page. Ensure color matching with Pantone 286C."></textarea>
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
                  <div class="attach-hint">(PDF, images, docs. Max 20MB each)</div>
                </div>

                <!-- This input sits on top, invisible, and owns the click -->
                <input id="fileInput" type="file" multiple
                      accept=".pdf,.png,.jpg,.jpeg,.gif,.webp,.doc,.docx,.xlsx,.xls,.ppt,.pptx"
                      class="file-overlay">
              </div>

              <ul id="preview" class="mt-4 space-y-2"></ul>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- Sticky save bar --}}
    <div class="col-12">
      <div class="bg-body position-sticky bottom-0 border-top py-3 d-flex gap-2 justify-content-end" style="z-index: 10">
        <button type="button" class="btn btn-outline-secondary" onclick="history.back()">Cancel</button>
        <button type="submit" name="action" value="draft" class="btn btn-secondary">Save Draft</button>
        <button type="submit" name="action" value="submit" class="btn btn-primary">Save and Submit</button>
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
            <option>Gloss</option><option>Matte</option>
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
  (function () {
    // add item --------------------------------------------------------------------------------------------
    function openOnly(id) {
      // id like '#itemPane3'
      document.querySelectorAll('#productItems .accordion-collapse.show')
        .forEach(el => new bootstrap.Collapse(el, {toggle:false}).hide());
      new bootstrap.Collapse(document.querySelector(id), {toggle:true}).show();
    }

    const container = document.getElementById('productItems');
    const addBtn    = document.getElementById('addItemBtn');
    const tplEl     = document.getElementById('itemTemplate');

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
      const qty  = wrap.querySelector('input[name^="items"][name$="[qty]"]')?.value || '';
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
  })();

  // upload attachemnt -------------------------------------------------------
  document.addEventListener('DOMContentLoaded', () => {
  const input   = document.getElementById('fileInput');   // overlay input
  const preview = document.getElementById('preview');

  const uploadUrl = @json(route('artist.orders.attachments.upload', $order));
  const deleteUrl = @json(route('artist.orders.attachments.delete', $order));
  const csrf      = @json(csrf_token());

  input.addEventListener('change', () => handleFiles(input.files));

  async function handleFiles(fileList) {
    for (const file of Array.from(fileList)) {
      const li = document.createElement('li');
      li.innerHTML = `<span>${file.name}</span> <button class="remove-x" title="Remove">×</button>`;
      preview.appendChild(li);
      const removeBtn = li.querySelector('.remove-x');

      const fd = new FormData();
      fd.append('_token', csrf);
      fd.append('file', file);

      try {
        const res = await fetch(uploadUrl, {
          method: 'POST',
          body: fd,
          credentials: 'same-origin',
          headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (!res.ok) throw new Error(`HTTP ${res.status}`);
        const data = await res.json();
        li.dataset.savedPath = data.path;

        removeBtn.onclick = async () => {
          await safeDelete(li);
        };
      } catch (err) {
        // Mark as failed but still allow removing the list item
        li.firstChild.textContent = `${file.name} – upload failed`;
        removeBtn.onclick = () => li.remove();
        console.error(err);
      }
    }
    input.value = '';
  }

  async function safeDelete(li) {
    const path = li.dataset.savedPath;
    li.remove();  // optimistic UI
    if (!path) return;

    try {
      await fetch(deleteUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ path })
      });
    } catch (e) {
      console.error(e);
    }
  }
});

</script>
@endpush

