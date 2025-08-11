@extends('layouts.app')

@section('title','New Job Order')

@section('content')
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
          <h6 class="mb-3">Product</h6>
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
          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0">Items</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addItemBtn">
              <i class="bx bx-plus me-1"></i> Add Item
            </button>
          </div>

          <div id="itemsWrap" class="vstack gap-3">
            <div class="card border shadow-none" data-item>
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <strong>Item <span class="item-index">1</span></strong>
                  <button type="button" class="btn btn-sm btn-text text-danger" data-remove><i class="bx bx-trash"></i></button>
                </div>
                <div class="row g-3">
                  <div class="col-md-4">
                    <label class="form-label">Item Name</label>
                    <input name="items[0][name]" type="text" class="form-control" placeholder="Item name">
                  </div>
                  <div class="col-md-2">
                    <label class="form-label">Quantity</label>
                    <input name="items[0][qty]" type="number" min="0" class="form-control" placeholder="Qty">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Material</label>
                    <input name="items[0][material]" type="text" class="form-control" placeholder="Add material…">
                  </div>

                  <div class="col-12 col-md-3">
                    <label class="form-label">Size (inches) – Width</label>
                    <input name="items[0][size][w]" type="text" class="form-control">
                  </div>
                  <div class="col-12 col-md-3">
                    <label class="form-label">Height</label>
                    <input name="items[0][size][h]" type="text" class="form-control">
                  </div>
                  <div class="col-12 col-md-3">
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
                  <button type="button" class="btn btn-sm btn-text text-danger" data-remove><i class="bx bx-trash"></i></button>
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

          {{-- Product Remarks --}}
          <div class="mt-4">
            <h6 class="mb-2">Product Remarks</h6>
            <textarea name="product[remarks]" rows="3" class="form-control"
              placeholder="Client requested matte finish on cover page. Ensure color matching with Pantone 286C."></textarea>
          </div>

          {{-- Attachments (bottom) --}}
          <div class="mt-4">
            <h6 class="mb-2">Attachments</h6>
            <div class="border rounded-2 p-3">
              <input type="file" name="attachments[]" class="form-control" multiple>
              <small class="text-body-secondary">Supports PDF, JPG, PNG (Max 10MB each)</small>
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
    const itemsWrap = document.getElementById('itemsWrap');
    const deliveriesWrap = document.getElementById('deliveriesWrap');
    const itemTpl = document.getElementById('itemTemplate').content;
    const deliveryTpl = document.getElementById('deliveryTemplate').content;

    // add item
    document.getElementById('addItemBtn').addEventListener('click', () => {
      const idx = itemsWrap.querySelectorAll('[data-item]').length;
      const node = document.importNode(itemTpl, true);
      renameInputs(node, /items\[IDX]/g, `items[${idx}]`);
      itemsWrap.appendChild(node);
      reindex(itemsWrap, '[data-item]', '.item-index');
    });

    // add delivery
    document.getElementById('addDeliveryBtn').addEventListener('click', () => {
      const idx = deliveriesWrap.querySelectorAll('[data-delivery]').length;
      const node = document.importNode(deliveryTpl, true);
      renameInputs(node, /deliveries\[IDX]/g, `deliveries[${idx}]`);
      deliveriesWrap.appendChild(node);
      reindex(deliveriesWrap, '[data-delivery]', '.delivery-index');
    });

    // delegated remove buttons
    document.addEventListener('click', (e) => {
      if (e.target.closest('[data-remove]')) {
        const card = e.target.closest('[data-item],[data-delivery]');
        const parent = card.parentElement;
        const selector = card.hasAttribute('data-item') ? '[data-item]' : '[data-delivery]';
        if (parent.querySelectorAll(selector).length > 1) {
          card.remove();
          // reindex all names again
          if (selector === '[data-item]') {
            reindexNames(parent, selector, 'items');
            reindex(parent, selector, '.item-index');
          } else {
            reindexNames(parent, selector, 'deliveries');
            reindex(parent, selector, '.delivery-index');
          }
        }
      }
    });

    function renameInputs(root, pattern, replacement) {
      root.querySelectorAll('input,select,textarea').forEach(el => {
        if (el.name) el.name = el.name.replace(pattern, replacement);
      });
    }

    function reindex(container, cardSelector, indexSelector) {
      container.querySelectorAll(cardSelector).forEach((card, i) => {
        const span = card.querySelector(indexSelector);
        if (span) span.textContent = i + 1;
      });
    }

    function reindexNames(container, cardSelector, base) {
      container.querySelectorAll(cardSelector).forEach((card, i) => {
        card.querySelectorAll('input,select,textarea').forEach(el => {
          if (el.name) {
            el.name = el.name
              .replace(new RegExp(base + '\\\\[[0-9]+\\]','g'), `${base}[${i}]`);
          }
        });
      });
    }
  })();
</script>
@endpush

