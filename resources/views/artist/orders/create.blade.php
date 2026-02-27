@extends('layouts.app')

@section('content')
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

<style>
    /* ========= TABLE (Product Details) ========= */
.delivery-box-table,
.remark-box-table{
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  padding: 10px;
  background: #fff;
  position: relative;
  margin-bottom: 10px;
  min-width: 320px;
}

.delivery-box-table .delete-delivery,
.remark-box-table .delete-remark{
  position: absolute;
  top: 6px;
  right: 8px;
  color: #dc3545;
  background: none;
  border: 0;
  padding: 0;
  cursor: pointer;
}

.delivery-box-table label,
.remark-box-table label{
  font-size: 12px;
  color: #64748b;
  margin-bottom: 4px;
}

.delivery-box-table .stack,
.remark-box-table .stack{
  display: grid;
  gap: 10px;
}

/* ========= MODAL (keep 3 fields one row) ========= */
.delivery-row-modal{
  display: flex;
  gap: .75rem;
  align-items: center;
  margin-bottom: .5rem;
}

.delivery-row-modal select{ flex: 0 0 180px; }
.delivery-row-modal input{ flex: 1; }
.delivery-row-modal .delete-delivery{
  flex: 0 0 auto;
  background: none;
  border: 0;
  padding: 0;
  cursor: pointer;
  color: #dc3545;
}

/* Modal remark can stay as row (your current modal layout is fine) */
.remark-row-modal{
  display:flex;
  gap:1rem;
  align-items:center;
  margin-bottom:.5rem;
}
.remark-row-modal select{ flex: 0 0 160px; }
.remark-row-modal input{ flex: 1; }

    .swal2-container { z-index: 200000 !important; }

    .alert.alert-danger,
    .text-danger ul,
    .text-danger li,
    .invalid-feedback,
    .parsley-errors-list {
        display: none !important;
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
        cursor: pointer;
    }

    .attach-inner {
        text-align: center;
        pointer-events: none;
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
        font-size: 20px;
    }

    .attach-title {
        color: #475569;
        font-weight: 600;
    }

    .attach-hint {
        color: #64748b;
        font-size: 12px;
    }

    .file-overlay {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
    }

    .remove-x {
        border: 0;
        background: none;
        color: #dc2626;
        font-weight: 700;
        cursor: pointer;
        margin-left: 8px;
    }

    .remove-x:hover {
        color: #b91c1c;
    }

    .ok {
        color: #15803d;
    }

    .err {
        color: #b91c1c;
    }

    .remark-row {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
        margin-bottom: 0.5rem;
    }

    .remark-row select {
        flex: 0 0 160px;
    }

    .remark-row input {
        flex: 1;
    }

    .remark-row button {
        flex: 0 0 auto;
    }
</style>
@endpush

<form id="order-form" action="{{ route('artist.orders.store') }}" method="POST" enctype="multipart/form-data" novalidate>
    @csrf
    <input type="hidden" name="lead_id" id="lead_id" value="{{ $lead->id ?? '' }}">
    <input type="hidden" id="from_csv" name="from_csv" value="{{ old('from_csv', 0) }}">
    <div id="hidden-products"></div>

    <div class="row g-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Job Order</h5>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row g-4 align-items-stretch equal-cols">
                        <!-- Lead Information -->
                        <div class="col-lg-6 d-flex">
                            <div class="card h-100 flex-fill mb-0">
                                <div class="card-header">
                                    <h5 mb-0>Lead Information</h5>
                                </div>
                                <div class="card-body">
                                    @if(!$lead)
                                    <div class="mb-3">
                                        <label for="lead_id" class="form-label">Search lead <span class="text-danger">*</span></label>
                                        <select id="lead_id" class="form-control js-lead-select" name="lead_id" style="width:100%"></select>
                                        <div class="form-text">Type at least 2 characters. Matches: name, company, phone, email.</div>
                                    </div>
                                    @endif
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Company Name</label>
                                            <input id="companyDisplay" name="companyName" type="text" class="form-control" value="{{ $lead->company_name ?? '' }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Lead Name</label>
                                            <input id="leadNameDisplay" name="leadName" type="text" class="form-control" value="{{ $lead->name ?? '' }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Phone</label>
                                            <input id="phoneDisplay" name="leadPhone" type="text" class="form-control" value="{{ $lead->phone ?? '' }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input id="emailDisplay" name="leadEmail" type="text" class="form-control" value="{{ $lead->email ?? '' }}" readonly>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Job Order Details -->
                        <div class="col-lg-6 d-flex">
                            <div class="card h-100 flex-fill mb-0">
                                <div class="card-header">
                                    <h5 mb-0>Job Order Details</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Job Title <span class="text-danger">*</span></label>
                                            <input name="orderTitle" type="text" class="form-control" value="{{ old('orderTitle') }}">
                                            @error('orderTitle')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Created Date</label>
                                            <input type="text" class="form-control" value="{{ now()->format('d/m/Y') }}" readonly>
                                            <input type="hidden" name="orderDate" value="{{ now() }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Deadline <span class="text-danger">*</span></label>
                                            <input name="deadline" type="date" class="form-control" value="{{ old('deadline') }}">
                                            @error('deadline')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <label class="form-label">Created By</label>
                                            <input type="text" class="form-control" value="{{ Auth::user()->name }}" readonly>
                                            <input type="hidden" name="created_by" value="{{ Auth::user()->id }}">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label d-block mb-4">Design from artist would need client approval</label>
                                            <div class="d-flex gap-4">
                                                <label class="form-check-label">
                                                    <input class="form-check-input me-1" type="radio" name="approval" value="1" {{ old('approval') == 1 ? 'checked' : '' }}> YES
                                                </label>
                                                <label class="form-check-label">
                                                    <input class="form-check-input me-1" type="radio" name="approval" value="0" {{ old('approval') == 0 ? 'checked' : '' }}> NO
                                                </label>
                                            </div>
                                            @error('approval')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Attachments (bottom) --}}
                    <div class="card mt-4">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0">Product Details</h5>

                            <div class="d-flex align-items-center gap-3">
                                <span class="text-muted small">Max 5 products</span>
                                <button type="button" id="addProductBtn"
                                    class="btn btn-primary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#productModal"
                                    data-mode="add">
                                    Add Product
                                </button>
                            </div>
                        </div>

                        <div class="card-body">
                            <div class="table-responsive mb-3">
                                <table id="product-table" class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th style="width:56px;">#</th>
                                            <th>Product Name</th>
                                            <th>Quantity</th>
                                            <th>Material Remarks</th>
                                            <th>Delivery Breakdown</th>
                                            <th>Remark</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (old('products', []) as $index => $product)
                                            <tr data-index="{{ $index }}">
                                                <td class="row-no align-middle fw-semibold text-muted">{{ $index + 1 }}</td>
                                                <td><input type="text" name="products[{{ $index }}][product_name]" class="form-control" value="{{ $product['product_name'] ?? '' }}"></td>
                                                <td><input type="number" name="products[{{ $index }}][quantity]" class="form-control qty-input" min="1" step="1" value="{{ $product['quantity'] ?? '' }}"></td>
                                                <td><input type="text" name="products[{{ $index }}][material_info]" class="form-control" value="{{ $product['material_info'] ?? '' }}"></td>
                                                <td>
                                                    <div id="deliveries-container-{{ $index }}">
                                                        @foreach ($product['deliveries'] ?? [] as $dindex => $del)
                                                            <div class="delivery-box" data-delivery-box>
                                                                <button type="button" class="delete-delivery remove-delivery" title="Delete">
                                                                <i class="bx bx-trash fs-5"></i>
                                                                </button>

                                                                <div class="mb-2">
                                                                <label>Delivery Method</label>
                                                                <select name="products[{{ $index }}][deliveries][{{ $dindex }}][method]" class="form-select">
                                                                    <option value="">— Select —</option>
                                                                    <option value="self_pickup" {{ ($del['method'] ?? '') === 'self_pickup' ? 'selected' : '' }}>Self Pickup</option>
                                                                    <option value="courier" {{ ($del['method'] ?? '') === 'courier' ? 'selected' : '' }}>Courier</option>
                                                                    <option value="installation" {{ ($del['method'] ?? '') === 'installation' ? 'selected' : '' }}>Installation</option>
                                                                </select>
                                                                </div>

                                                                <div class="mb-2">
                                                                <label>Location Address</label>
                                                                <input type="text"
                                                                    name="products[{{ $index }}][deliveries][{{ $dindex }}][location]"
                                                                    class="form-control"
                                                                    placeholder="Location"
                                                                    value="{{ $del['location'] ?? '' }}">
                                                                </div>

                                                                <div>
                                                                <label>Date & Time</label>
                                                                <input type="datetime-local"
                                                                    name="products[{{ $index }}][deliveries][{{ $dindex }}][date_time]"
                                                                    class="form-control"
                                                                    value="{{ $del['date_time'] ?? '' }}">
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>

                                                    <button type="button"
                                                        class="btn btn-secondary btn-sm mt-2 add-delivery"
                                                        data-index="{{ $index }}">
                                                        Add Delivery Breakdown
                                                    </button>
                                                </td>
                                                <td>
                                                    <div id="remarks-container-{{ $index }}">
                                                        @foreach ($product['remarks'] ?? [] as $rindex => $remark)
                                                            <div class="remark-box-table" data-remark-box>
  <button type="button" class="delete-remark remove-remark" title="Delete">
    <i class="bx bx-trash fs-5"></i>
  </button>

  <div class="stack">
    <div>
      <label>Remark Type</label>
      <select name="products[{{ $index }}][remarks][{{ $rindex }}][operation]" class="form-select">
        <option value="">— Select —</option>
        <option value="artist" {{ ($remark['operation'] ?? '') === 'artist' ? 'selected' : '' }}>To Artist</option>
        <option value="printing" {{ ($remark['operation'] ?? '') === 'printing' ? 'selected' : '' }}>To Printing</option>
        <option value="furnishing" {{ ($remark['operation'] ?? '') === 'furnishing' ? 'selected' : '' }}>To Furnishing</option>
        <option value="installation" {{ ($remark['operation'] ?? '') === 'installation' ? 'selected' : '' }}>To Installation</option>
        <option value="self_pickup" {{ ($remark['operation'] ?? '') === 'self_pickup' ? 'selected' : '' }}>To Self Pickup</option>
        <option value="courier" {{ ($remark['operation'] ?? '') === 'courier' ? 'selected' : '' }}>To Courier</option>
      </select>
    </div>

    <div>
      <label>Remark</label>
      <input type="text"
        name="products[{{ $index }}][remarks][{{ $rindex }}][remark]"
        class="form-control"
        value="{{ $remark['remark'] ?? '' }}"
        placeholder="Write a note…">
    </div>
  </div>
</div>
                                                        @endforeach
                                                    </div>
                                                    <button type="button" class="btn btn-secondary btn-sm mt-2 add-remark" data-index="{{ $index }}">Add Remark</button>
                                                </td>
                                                <td>
                                                    <!-- <button type="button" class="btn btn-sm btn-primary edit-product" data-bs-toggle="modal" data-bs-target="#productModal" data-mode="edit" data-index="{{ $index }}">Edit</button> -->
                                                    <button type="button" class="btn btn-sm btn-danger remove-product" data-index="{{ $index }}">Delete</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Top row: left label + right CSV template download -->
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted">Upload CSV (Optional)</span>
                                <a id="csvTemplateBtn" href="{{ route('artist.orders.csv_template') }}" class="btn btn-link p-0 text-decoration-none">
                                    <i class="bx bx-download me-1"></i> CSV Template Download
                                </a>
                            </div>

                            <!-- Drop area -->
                            <div id="attach-box-csv" class="attach-box">
                                <div class="attach-inner">
                                    <div class="attach-icon" aria-hidden="true">
                                        <i class="bx bx-upload display-6 mb-2 d-block justify-content-between align-items-center" style="pointer-events:none"></i>
                                    </div>
                                    <div class="attach-title">Drop CSV file here or click to upload</div>
                                    <div class="attach-hint">(CSV)</div>
                                </div>

                                <!-- This input sits on top, invisible, and owns the click -->
                                <input id="fileInputCsv" type="file"
                                    accept=".csv"
                                    class="file-overlay">
                            </div>
                            @error('csv_file')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror

                            <div id="csv-msg" class="mt-2 text-sm"></div>
                            <ul id="csv-preview" class="mt-3 space-y-2"></ul>
                        </div>
                    </div>

                    @if ($errors->has('products') || $errors->has('products.*'))
                    <div class="mt-3 text-danger text-sm">
                        <ul>
                            @foreach ($errors->get('products') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                            @foreach ($errors->get('products.*') as $fieldErrors)
                                @foreach ($fieldErrors as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    {{-- Attachments (bottom) --}}
                    <div class="card mt-4">
                        <div class="card-header" style="display: flex; align-items: center;">
                        <span class="text-muted">Order Attachments</span>
                        <span style="color: red; font-size: 12px; margin-left: 6px;">*required</span>
                        </div>

                        <div class="card-body">
                        <div id="attach-box" class="attach-box">
                            <div class="attach-inner">
                            <div class="attach-icon" aria-hidden="true"><i class="bx bx-upload display-6 mb-2 d-block justify-content-between align-items-center" style="pointer-events:none"></i></div>
                            <div class="attach-title">Drop files here or click to upload</div>
                            <div class="attach-hint">(PDF, images, docs, xlsx, ppt., ai, ps)</div>
                            </div>

                            <!-- This input sits on top, invisible, and owns the click -->
                            <input id="fileInput" name="attachments[]" type="file" multiple
                                accept=".pdf,.png,.jpg,.jpeg,.webp,.doc,.docx,.xlsx,.xls,.ppt,.pptx,.ai,.ps"
                                class="file-overlay">
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

                    @if(auth()->check() && auth()->user()->role === 'head-artist')
                      <hr class="my-4">

                      <div class="card">
                        <div class="card-body">
                          <div class="d-flex align-items-center mb-3">
                            <i class="bx bx-user-plus me-2"></i>
                            <h6 class="m-0">Assign Artist</h6>
                          </div>

                          <div class="mb-1 text-muted small">
                            Select an artist to assign this job order.
                          </div>

                          <label class="form-label">Artist <span class="text-danger">*</span></label>
                          <select id="assignee_artist_id"
                                  name="assignee_artist_id"
                                  class="form-control"
                                  style="width:100%"
                                  required>
                          </select>
                          @error('assignee_artist_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                          @enderror
                          <div class="form-text">Search by artist name.</div>
                        </div>
                      </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sticky save bar --}}
        <div class="col-12">
            <div class="bg-body position-sticky bottom-0 border-top py-3 d-flex gap-2 justify-content-end" style="z-index: 10">
                <a type="button" class="btn btn-outline-secondary" href="{{ route('artist.orders') }}">Cancel</a>
                <button type="submit" id="save-order-btn" class="btn btn-primary">Save Order</button>
            </div>
        </div>
    </div>
    <!-- Hidden products -->
    
<div id="hidden-products" style="display: none;">
    @foreach (old('products', []) as $index => $product)
        <div data-index="{{ $index }}">
            <input type="hidden" name="products[{{ $index }}][product_name]" value="{{ $product['product_name'] ?? '' }}">
            <input type="hidden" name="products[{{ $index }}][quantity]" value="{{ $product['quantity'] ?? '' }}">
            <input type="hidden" name="products[{{ $index }}][remark]" value="{{ $product['remark'] ?? '' }}">
            <input type="hidden" name="products[{{ $index }}][material_info]" value="{{ $product['material_info'] ?? '' }}">
        </div>
    @endforeach
</div>
</form>

<div class="modal fade" id="productModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productModalTitle">Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="productForm">
                    <input type="hidden" id="product_index">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label>Product Name <span class="text-danger">*</span></label>
                            <input id="product_name" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label>Quantity <span class="text-danger">*</span></label>
                            <input id="quantity" type="number" class="form-control qty-input" inputmode="numeric" pattern="[1-9]\d*" min="1" step="1" autocomplete="off">
                        </div>
                        <div class="col-12">
                            <label>Material Remark <span class="text-danger">*</span></label>
                            <textarea id="material_info" class="form-control"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="fw-semibold">Delivery Breakdown <span class="text-muted">(Optional)</span></label>
                            <div id="deliveries-modal-container"></div>
                            <button type="button" id="addDeliveryBtn" class="btn btn-secondary btn-sm mt-2">Add Delivery</button>
                        </div>
                        <div class="col-12">
                            <label>Remarks</label>
                            <div id="remarks-container"></div>
                            <button type="button" id="addRemarkBtn" class="btn btn-secondary btn-sm mt-2">Add Remark</button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveProduct">Save</button>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
<script>
window.currentUserRole = '{{ auth()->user()->hasRole("head-artist") ? "head-artist" : "artist" }}';
$(function () {
    /***********************
     * LEAD SELECT (Select2)
     ***********************/
    // Keep only the last #lead_id (the one inside Lead card)
    const $leadCandidates = $('select#lead_id');
    const $leadSel = $leadCandidates.last();
    $leadCandidates.not($leadSel).remove();

    // Init Select2
    $leadSel.select2({
        placeholder: 'Search lead…',
        allowClear: true,
        width: '100%',
        minimumInputLength: 2,
        dropdownParent: $leadSel.closest('.card, .modal, form'),
        ajax: {
        url: @json(route('artist.orders.leads.search')),
        dataType: 'json',
        delay: 250,
        data: params => ({ q: params.term }),
        processResults: data => ({ results: Array.isArray(data) ? data : (data.results || []) }),
        cache: true
        },
        templateResult: function (item) {
        if (!item.id) return item.text;
        const m = item.meta || {};
        const line = [item.text,
            m.company_name ? ' — ' + m.company_name : '',
            m.phone ? ' · ' + m.phone : '',
            m.email ? ' · ' + m.email : ''].join('');
        return $('<span>').text(line);
        },
        templateSelection: function (item) {
        if (!item.id) return item.text;
        const m = item.meta || {};
        return (item.text || '') + (m.company_name ? ' — ' + m.company_name : '');
        },
        escapeMarkup: m => m
    });

    // Auto-fill lead fields on selection (fallback to /get if meta missing)
    const set = ($el, v) => $el.length && $el.val(v || '').trigger('input').trigger('change');
    const $company = $('[name="companyName"], [name="company_name"], #companyName, #company_name').first();
    const $leadNm  = $('[name="leadName"],    [name="name"],         #leadName,    #name').first();
    const $phone   = $('[name="leadPhone"],   [name="phone"],        #leadPhone,   #phone').first();
    const $email   = $('[name="leadEmail"],   [name="email"],        #leadEmail,   #email').first();

    $leadSel.on('select2:select', function (e) {
        const d = e.params.data || {};
        $('input[type="hidden"][name="lead_id"]').val(d.id || '');
        const m = d.meta || {};
        if (m.company_name || m.phone || m.email) {
        set($leadNm,  d.text);
        set($company, m.company_name);
        set($phone,   m.phone);
        set($email,   m.email);
        } else {
        const url = @json(route('artist.orders.leads.get', ['id' => 'ID']));
        $.get(url.replace('ID', d.id)).done(l => {
            set($leadNm,  l.name);
            set($company, l.company_name);
            set($phone,   l.phone);
            set($email,   l.email);
        });
        }
    });

    $leadSel.on('select2:clear', function () {
        $('input[type="hidden"][name="lead_id"]').val('');
        set($leadNm,''); set($company,''); set($phone,''); set($email,'');
    });

        // delete delivery box content (keep UI, clear fields)
        $(document).on('click', '.delete-delivery', function() {
        const $box = $(this).closest('.delivery-box');
        $box.find('select, input').val('');
        });


        /**************************
         * PRODUCTS (+ REMARKS) UI
         **************************/
        function escapeHtml(str) {
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        /**************************
         * B) JS helpers
         **************************/
        function renderDeliveryBlock(index, dindex, d = {}) {
  const method    = d.method || '';
  const location  = d.location || '';
  const date_time = d.date_time || '';

  return `
    <div class="delivery-box-table" data-delivery-box>
      <button type="button" class="delete-delivery remove-delivery" title="Delete">
        <i class="bx bx-trash fs-5"></i>
      </button>

      <div class="stack">
        <div>
          <label>Delivery Method</label>
          <select name="products[${index}][deliveries][${dindex}][method]" class="form-select">
            <option value="">— Select —</option>
            <option value="self_pickup" ${method==='self_pickup'?'selected':''}>Self Pickup</option>
            <option value="courier" ${method==='courier'?'selected':''}>Courier</option>
            <option value="installation" ${method==='installation'?'selected':''}>Installation</option>
          </select>
        </div>

        <div>
          <label>Location Address</label>
          <input type="text"
            name="products[${index}][deliveries][${dindex}][location]"
            class="form-control"
            placeholder="Location"
            value="${escapeHtml(location)}">
        </div>

        <div>
          <label>Date & Time</label>
          <input type="datetime-local"
            name="products[${index}][deliveries][${dindex}][date_time]"
            class="form-control"
            value="${escapeHtml(date_time)}">
        </div>
      </div>
    </div>
  `;
}

        function renderRemarkRow(index, rindex, r = {}) {
  const op     = r.operation || '';
  const remark = r.remark || '';

  return `
    <div class="remark-box-table" data-remark-box>
      <button type="button" class="delete-remark remove-remark" title="Delete">
        <i class="bx bx-trash fs-5"></i>
      </button>

      <div class="stack">
        <div>
          <label>Remark Type</label>
          <select name="products[${index}][remarks][${rindex}][operation]" class="form-select">
            <option value="">— Select —</option>
            <option value="artist" ${op==='artist'?'selected':''}>To Artist</option>
            <option value="printing" ${op==='printing'?'selected':''}>To Printing</option>
            <option value="furnishing" ${op==='furnishing'?'selected':''}>To Furnishing</option>
            <option value="installation" ${op==='installation'?'selected':''}>To Installation</option>
            <option value="self_pickup" ${op==='self_pickup'?'selected':''}>To Self Pickup</option>
            <option value="courier" ${op==='courier'?'selected':''}>To Courier</option>
          </select>
        </div>

        <div>
          <label>Remark</label>
          <input type="text"
            name="products[${index}][remarks][${rindex}][remark]"
            class="form-control"
            value="${escapeHtml(remark)}"
            placeholder="Write a note…">
        </div>
      </div>
    </div>
  `;
}

        let productIndex = $('#product-table tbody tr').length;
        let isFromCsv = {{ old('from_csv', 0) }};
        updateAddButton();

        $('#productModal').on('show.bs.modal', function(e) {
            const button = $(e.relatedTarget);
            const mode = button.data('mode');
            const index = button.data('index');

            $('#productForm')[0].reset();
            $('#product_index').val('');
            $('#remarks-container').empty();
            $('#deliveries-modal-container').empty();
            $('#productModalTitle').text('Add Product');

            if (mode === 'edit' && index !== undefined) {
                $('#productModalTitle').text('Edit Product');
                $('#product_index').val(index);

                const hidden = $(`#hidden-products > div[data-index="${index}"]`);
                $('#product_name').val(hidden.find('input[name$="[product_name]"]').val());
                $('#quantity').val(hidden.find('input[name$="[quantity]"]').val());
                $('#material_info').val(hidden.find('input[name$="[material_info]"]').val());

                hidden.find('input[name^="products[' + index + '][remarks]"]').each(function() {
                    const name = $(this).attr('name');
                    if (name.includes('[operation]')) {
                        const rindex = name.match(/\[remarks\]\[(\d+)\]/)[1];
                        const operation = $(this).val();
                        const remark = hidden.find(`input[name="products[${index}][remarks][${rindex}][remark]"]`).val();
                        addRemarkRow(operation, remark);
                    }
                });
            }
        });

        $('#addRemarkBtn').on('click', function() {
            addRemarkRow();
        });

        function addRemarkRow(operation = '', remark = '') {
            const rindex = $('#remarks-container .remark-row').length;
            const html = `
                <div class="remark-row mb-2">
                    <select name="remark_operation" class="form-select w-auto" style="min-width:160px;">
                        <option value="">— Select —</option>
                        <option value="artist" ${operation === 'artist' ? 'selected' : ''}>To Artist</option>
                        <option value="printing" ${operation === 'printing' ? 'selected' : ''}>To Printing</option>
                        <option value="furnishing" ${operation === 'furnishing' ? 'selected' : ''}>To Furnishing</option>
                        <option value="installation" ${operation === 'installation' ? 'selected' : ''}>To Installation</option>
                        <option value="self_pickup" ${operation === 'self_pickup' ? 'selected' : ''}>To Self Pickup</option>
                        <option value="courier" ${operation === 'courier' ? 'selected' : ''}>To Courier</option>
                    </select>
                    <input type="text" name="remark_text" class="form-control" placeholder="Write a note…" value="${escapeHtml(remark)}">
                    <button type="button" class="btn btn-link text-danger p-0 remove-remark" title="Delete">
                        <i class="bx bx-trash fs-5"></i>
                    </button>
                </div>
            `;
            $('#remarks-container').append(html);
        }

        $(document).on('click', '.remove-remark', function() {
            $(this).closest('.remark-row').remove();
        });

        /**************************
         * DELIVERIES (UI HELPERS)
         **************************/
        function addDeliveryRowModal(method = '', location = '', date_time = '') {
  const html = `
    <div class="delivery-row-modal">
      <select class="form-select" name="delivery_method">
        <option value="">— Select —</option>
        <option value="self_pickup" ${method==='self_pickup'?'selected':''}>Self Pickup</option>
        <option value="courier" ${method==='courier'?'selected':''}>Courier</option>
        <option value="installation" ${method==='installation'?'selected':''}>Installation</option>
      </select>

      <input type="text" class="form-control" name="delivery_location"
        placeholder="Location" value="${escapeHtml(location)}">

      <input type="datetime-local" class="form-control" name="delivery_date_time"
        value="${escapeHtml(date_time)}">

      <button type="button" class="delete-delivery text-danger" title="Delete">
        <i class="bx bx-trash fs-5"></i>
      </button>
    </div>
  `;
  $('#deliveries-modal-container').append(html);
}

        // Add delivery row in modal
        $(document).on('click', '#addDeliveryBtn', function() {
            addDeliveryRowModal();
        });

        // Remove delivery row (modal or table)
        $(document).on('click', '.remove-delivery', function() {
        $(this).closest('[data-delivery-box]').remove();
        });

        $(document).on('click', '.remove-remark', function() {
        $(this).closest('[data-remark-box]').remove();
        });

        $('#saveProduct').on('click', function() {
            const nameEl = $('#product_name');
            const qtyEl  = $('#quantity');
            const matEl  = $('#material_info');

            const nameVal = (nameEl.val() || '').trim();
            const qtyVal  = (qtyEl.val()  || '').trim();
            const matVal  = (matEl.val()  || '').trim();

            const errs = [];
            if (!nameVal) errs.push('Product Name (in modal) is required.');
            const q = parseInt(qtyVal, 10);
            if (!qtyVal || isNaN(q) || q < 1) errs.push('Quantity (in modal) must be an integer ≥ 1.');
            if (!matVal) errs.push('Material Remark (in modal) is required.');

            if (errs.length) {
                if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Please complete the product info',
                    html: errs.map(m => `<div style="text-align:left">${m}</div>`).join(''),
                    confirmButtonText: 'OK'
                }).then(() => {
                    if (!nameVal) return nameEl.trigger('focus');
                    if (!qtyVal || isNaN(q) || q < 1) return qtyEl.trigger('focus');
                    if (!matVal) return matEl.trigger('focus');
                });
                } else {
                alert(errs.join('\n'));
                if (!nameVal) nameEl.focus();
                else if (!qtyVal || isNaN(q) || q < 1) qtyEl.focus();
                else if (!matVal) matEl.focus();
                }
                return; // stop; do not proceed to add/update row
            }

            const index = $('#product_index').val();
            const data = {
                product_name: $('#product_name').val() || '',
                quantity: $('#quantity').val() || '',
                material_info: $('#material_info').val() || '',
                deliveries: [],
                remarks: []
            };

            // collect deliveries from modal
            data.deliveries = [];
            $('#deliveries-modal-container .delivery-row-modal').each(function() {
            const method = $(this).find('select[name="delivery_method"]').val() || '';
            const location = $(this).find('input[name="delivery_location"]').val() || '';
            const date_time = $(this).find('input[name="delivery_date_time"]').val() || '';
            if (method || location || date_time) {
                data.deliveries.push({ method, location, date_time });
            }
            });

            $('#remarks-container .remark-row').each(function() {
                const operation = $(this).find('select').val();
                const remark = $(this).find('input').val() || '';
                if (operation) {
                    data.remarks.push({ operation, remark });
                }
            });

            let remarksHtml = '';
            if (data.remarks.length) {
                remarksHtml = '<ul>' + data.remarks.map(r => `<li>${escapeHtml(r.operation)}: ${escapeHtml(r.remark)}</li>`).join('') + '</ul>';
            }

            if (index !== '') {
                const row = $(`#product-table tbody tr[data-index="${index}"]`);
                row.find('td:eq(0)').html(`<input type="text" name="products[${index}][product_name]" class="form-control" value="${escapeHtml(data.product_name)}">`);
                row.find('td:eq(1)').html(`<input type="number" name="products[${index}][quantity]" class="form-control qty-input" min="1" step="1" value="${escapeHtml(data.quantity)}">`);
                row.find('td:eq(2)').html(`<input type="text" name="products[${index}][material_info]" class="form-control" value="${escapeHtml(data.material_info)}">`);
                row.find('td:eq(3)').html(`
                    <div id="remarks-container-${index}">
                        ${data.remarks.map((r, rindex) => `
                            <div class="remark-row">
                                <select name="products[${index}][remarks][${rindex}][operation]" class="form-select w-auto" style="min-width:160px;">
                                    <option value="artist" ${r.operation === 'artist' ? 'selected' : ''}>To Artist</option>
                                    <option value="printing" ${r.operation === 'printing' ? 'selected' : ''}>To Printing</option>
                                    <option value="furnishing" ${r.operation === 'furnishing' ? 'selected' : ''}>To Furnishing</option>
                                    <option value="installation" ${r.operation === 'installation' ? 'selected' : ''}>To Installation</option>
                                    <option value="self_pickup" ${r.operation === 'self_pickup' ? 'selected' : ''}>To Self Pickup</option>
                                    <option value="courier" ${r.operation === 'courier' ? 'selected' : ''}>To Courier</option>
                                </select>
                                <input type="text" name="products[${index}][remarks][${rindex}][remark]" class="form-control" value="${escapeHtml(r.remark)}" placeholder="Write a note…">
                                <button type="button" class="btn btn-link text-danger p-0 remove-remark" title="Delete">
                                    <i class="bx bx-trash fs-5"></i>
                                </button>
                            </div>
                        `).join('')}
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm mt-2 add-remark" data-index="${index}">Add Remark</button>
                `);

                const hidden = $(`#hidden-products > div[data-index="${index}"]`);
                hidden.find('input[name$="[product_name]"]').val(data.product_name);
                hidden.find('input[name$="[quantity]"]').val(data.quantity);
                hidden.find('input[name$="[material_info]"]').val(data.material_info);
                hidden.find('input[name^="products[' + index + '][remarks]"]').remove();
                data.remarks.forEach((r, rindex) => {
                    hidden.append(`<input type="hidden" name="products[${index}][remarks][${rindex}][operation]" value="${escapeHtml(r.operation)}">`);
                    hidden.append(`<input type="hidden" name="products[${index}][remarks][${rindex}][remark]" value="${escapeHtml(r.remark)}">`);
                });
            } else {
                if (productIndex >= 5) {
                    alert('Maximum 5 products allowed. Use CSV for more.');
                    return;
                }

                const nextNo = $('#product-table tbody tr').length + 1;

                const html = `
                    <tr data-index="${productIndex}">
                    <td class="row-no align-middle fw-semibold text-muted">${nextNo}</td>

                    <td><input type="text" name="products[${productIndex}][product_name]" class="form-control" value="${escapeHtml(data.product_name)}"></td>

                    <td><input type="number" name="products[${productIndex}][quantity]" class="form-control qty-input" min="1" step="1" value="${escapeHtml(data.quantity)}"></td>

                    <td><input type="text" name="products[${productIndex}][material_info]" class="form-control" value="${escapeHtml(data.material_info)}"></td>

                    <td>
                        <div id="deliveries-container-${productIndex}">
                        ${(data.deliveries || []).map((d, dindex) => renderDeliveryBlock(productIndex, dindex, d)).join('')}
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm mt-2 add-delivery" data-index="${productIndex}">
                        Add Delivery
                        </button>
                    </td>

                    <td>
                        <div id="remarks-container-${productIndex}">
                        ${data.remarks.map((r, rindex) => renderRemarkRow(productIndex, rindex, r)).join('')}
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm mt-2 add-remark" data-index="${productIndex}">
                        Add Remark
                        </button>
                    </td>

                    <td>
                        <button type="button" class="btn btn-sm btn-danger remove-product" data-index="${productIndex}">
                        Delete
                        </button>
                    </td>
                    </tr>
                `;
                $('#product-table tbody').append(html);

                let hiddenHtml = `
                    <div data-index="${productIndex}">
                        <input type="hidden" name="products[${productIndex}][product_name]" value="${escapeHtml(data.product_name)}">
                        <input type="hidden" name="products[${productIndex}][quantity]" value="${escapeHtml(data.quantity)}">
                        <input type="hidden" name="products[${productIndex}][material_info]" value="${escapeHtml(data.material_info)}">
                `;
                data.remarks.forEach((r, rindex) => {
                    hiddenHtml += `<input type="hidden" name="products[${productIndex}][remarks][${rindex}][operation]" value="${escapeHtml(r.operation)}">`;
                    hiddenHtml += `<input type="hidden" name="products[${productIndex}][remarks][${rindex}][remark]" value="${escapeHtml(r.remark)}">`;
                });
                hiddenHtml += '</div>';
                $('#hidden-products').append(hiddenHtml);

                productIndex++;
                if (productIndex >= 5) {
                    $('#addProductBtn').hide();
                }
            }
            // updateIndices();
            updateAddButton();
            const modalEl = document.getElementById('productModal');
            bootstrap.Modal.getOrCreateInstance(modalEl).hide();
        });

        $(document).on('click', '.remove-product', function() {
            const index = $(this).data('index');
            $(`#product-table tbody tr[data-index="${index}"]`).remove();
            $(`#hidden-products > div[data-index="${index}"]`).remove();
            updateIndices();
            updateAddButton();

            $('#product-table tbody tr').each(function(i) {
                $(this).attr('data-index', i);
                $(this).find('.edit-product').attr('data-index', i);
                $(this).find('.remove-product').attr('data-index', i);
                $(this).find('.add-remark').attr('data-index', i);
                $(this).find('#remarks-container-' + i).attr('id', 'remarks-container-' + i);
            });
            $('#hidden-products > div').each(function(i) {
                $(this).attr('data-index', i);
                $(this).find('input').each(function() {
                    const name = $(this).attr('name').replace(/\[\d+\]/, `[${i}]`);
                    $(this).attr('name', name);
                });
            });
            productIndex = $('#product-table tbody tr').length;
            if (productIndex < 5 && !isFromCsv) {
                $('#addProductBtn').show();
            }
        });

        $(document).on('click', '.add-remark', function() {
        const index = $(this).data('index');
        const container = $(`#remarks-container-${index}`);
        const rindex = container.find('[data-remark-box]').length;
        container.append(renderRemarkRow(index, rindex));
        });

        // Add delivery row on table product row
        $(document).on('click', '.add-delivery', function() {
            const index = $(this).data('index');
            const container = $(`#deliveries-container-${index}`);
            const dindex = container.find('[data-delivery-box]').length;

            container.append(renderDeliveryBlock(index, dindex));
        });

        $(document).on('click', '.remove-remark', function() {
            $(this).closest('.remark-row').remove();
        });

        // ===== CSV upload (Product Details) =====
        const csvInput  = document.getElementById('fileInputCsv');
        const csvListEl = document.getElementById('csv-preview');
        const csvMsgEl  = document.getElementById('csv-msg');
        const csvBox    = document.getElementById('attach-box-csv');

        const CSV_ALLOWED = ['csv'];
        let csvSelectedFile = null;

        if (csvInput) {
        csvInput.addEventListener('change', handleCsvUpload);
        }

        function handleCsvUpload() {
        if (!csvInput || !csvInput.files?.length) return;
        const f = csvInput.files[0];

        const ext = (f.name.split('.').pop() || '').toLowerCase();
        const errors = [];
        if (!CSV_ALLOWED.includes(ext)) errors.push('Invalid file type');

        if (csvListEl) csvListEl.innerHTML = '';

        if (errors.length) {
            csvAddRow(f, { status: 'error', note: errors.join(', ') });
            csvSelectedFile = null;
        } else {
            csvSelectedFile = f;
            csvAddRow(f, { status: 'ready' });
            parseCsv(f);
        }

        csvUpdateSummary();
        csvInput.value = '';
        }

        function csvAddRow(file, { status = 'ready', note = '' }) {
        if (!csvListEl) return;

        const li = document.createElement('li');
        li.innerHTML = `
            <span>${file.name}${
            status === 'error'
                ? ` – <span class="err">${note}</span>`
                : ` – <span class="ok">ready</span>`
            }</span>
            <button class="remove-x" title="Remove">×</button>
        `;

        li.querySelector('.remove-x').addEventListener('click', () => {
            li.remove();
            csvSelectedFile = null;
            csvUpdateSummary();

            // reset product table when CSV removed
            $('#product-table tbody').empty();
            $('#hidden-products').empty();
            productIndex = 0;
            isFromCsv = 0;
            $('#from_csv').val(0);
            $('#addProductBtn').show();
        });

        csvListEl.appendChild(li);
        }

        function csvUpdateSummary() {
        if (!csvMsgEl) return;
        const count = csvSelectedFile ? 1 : 0;
        csvMsgEl.innerHTML = count
            ? `<span class="ok">${count} file selected for upload</span>`
            : '';
        }

        function stripQuotes(str) {
        return str.replace(/^"(.*)"$/, '$1');
        }

        function parseCsv(file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const text    = e.target.result;
            const lines   = text.split(/\r?\n/);
            const headers = lines[0].split(',').map(h => h.trim());

            $('#product-table tbody').empty();
            $('#hidden-products').empty();
            productIndex = 0;

            for (let i = 1; i < lines.length; i++) {
            if (!lines[i].trim()) continue;
            const data = lines[i].split(',').map(d => stripQuotes(d.trim()));

            const product = {
                product_name:  data[headers.indexOf('Product_Name')]  || '',
                quantity:      data[headers.indexOf('Quantity')]      || '',
                material_info: data[headers.indexOf('Material_Info')] || '',
                remarks: []
            };

            const remarkColumns = [
                'Artist_Remark',
                'Printing_Remark',
                'Furnishing_Remark',
                'Installation_Remark',
                'Courier_Remark',
                'Self_Pickup_Remark'
            ];
            remarkColumns.forEach((col, idx) => {
                const remarkIdx = headers.indexOf(col);
                if (remarkIdx !== -1 && data[remarkIdx]) {
                product.remarks.push({
                    operation: ['artist','printing','furnishing','installation','courier','self_pickup'][idx],
                    remark: data[remarkIdx]
                });
                }
            });

            const nextNo = $('#product-table tbody tr').length + 1;
            const rowHtml = `
                <tr data-index="${productIndex}">
                <td class="row-no align-middle fw-semibold text-muted">${nextNo}</td>

                <td><input type="text" name="products[${productIndex}][product_name]" class="form-control" value="${escapeHtml(product.product_name)}"></td>

                <td><input type="number" name="products[${productIndex}][quantity]" class="form-control qty-input" min="1" step="1" value="${escapeHtml(product.quantity)}"></td>

                <td><input type="text" name="products[${productIndex}][material_info]" class="form-control" value="${escapeHtml(product.material_info)}"></td>

                <td>
                    <div id="deliveries-container-${productIndex}"></div>
                    <button type="button" class="btn btn-secondary btn-sm mt-2 add-delivery" data-index="${productIndex}">
                    Add Delivery
                    </button>
                </td>

                <td>
                    <div id="remarks-container-${productIndex}">
                    ${product.remarks.map((r, rindex) => renderRemarkRow(productIndex, rindex, r)).join('')}
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm mt-2 add-remark" data-index="${productIndex}">
                    Add Remark
                    </button>
                </td>

                <td>
                    <button type="button" class="btn btn-sm btn-danger remove-product" data-index="${productIndex}">
                    Delete
                    </button>
                </td>
                </tr>
            `;
            $('#product-table tbody').append(rowHtml);

            let hiddenHtml = `
                <div data-index="${productIndex}">
                <input type="hidden" name="products[${productIndex}][product_name]"  value="${escapeHtml(product.product_name)}">
                <input type="hidden" name="products[${productIndex}][quantity]"      value="${escapeHtml(product.quantity)}">
                <input type="hidden" name="products[${productIndex}][material_info]" value="${escapeHtml(product.material_info)}">
            `;
            product.remarks.forEach((r, rindex) => {
                hiddenHtml += `<input type="hidden" name="products[${productIndex}][remarks][${rindex}][operation]" value="${escapeHtml(r.operation)}">`;
                hiddenHtml += `<input type="hidden" name="products[${productIndex}][remarks][${rindex}][remark]"    value="${escapeHtml(r.remark)}">`;
            });
            hiddenHtml += '</div>';
            $('#hidden-products').append(hiddenHtml);

            productIndex++;
            }

            isFromCsv = 1;
            $('#from_csv').val(1);
            if (productIndex >= 5) $('#addProductBtn').hide();
            updateAddButton();
        };
        reader.readAsText(file);
        }

        // drag & drop for CSV box
        if (csvBox && csvInput) {
          ['dragenter', 'dragover'].forEach(evt =>
            csvBox.addEventListener(evt, e => {
              e.preventDefault();
              csvBox.classList.add('ring');
            })
          );
          ['dragleave', 'drop'].forEach(evt =>
            csvBox.addEventListener(evt, e => {
              e.preventDefault();
              csvBox.classList.remove('ring');
            })
          );

          // Avoid double-OPEN: if the click is on the input itself,
          // let the browser handle it once.
          csvBox.addEventListener('click', (e) => {
            if (e.target === csvInput) return;
            csvInput.click();
          });

          csvBox.addEventListener('drop', e => {
            csvInput.files = e.dataTransfer.files;
            handleCsvUpload();
          });
        }

    // head artist search artist to assign 
    const $assignee = $('#assignee_artist_id');
    if (!$assignee.length) return; 

    $assignee.select2({
      placeholder: 'Select artist…',
      allowClear: true,
      width: '100%',
      minimumInputLength: 0,                         // ← allow opening with no typing
      dropdownParent: $assignee.closest('.card, .modal, form'),
      ajax: {
        url: @json(route('artist.orders.assignees.search')),
        dataType: 'json',
        delay: 150,
        data: params => ({
          q: params.term || '',                      // ← empty term triggers “all”
          roles: ['artist','head-artist']
        }),
        processResults: data => ({ results: Array.isArray(data) ? data : (data.results || []) }),
        cache: true
      },
      templateResult: item => {
        if (!item.id) return item.text;
        const m = item.meta || {};
        return $(`<div>
          <div class="fw-semibold">${item.text || ''}</div>
          <div class="text-muted small">${m.email || ''}</div>
        </div>`);
      },
      templateSelection: item => item.text || item.id,
      escapeMarkup: m => m
    });

    // Kick off an empty search as soon as the dropdown opens so the list shows immediately
    $assignee.on('select2:open', () => {
      const input = document.querySelector('.select2-container--open .select2-search__field');
      if (input) {
        const ev = new Event('input', { bubbles: true });
        input.dispatchEvent(ev);
      }
    });
});

// Global counter of valid order attachments (create page)
let orderAttachmentCount = 0;

document.addEventListener('attachments:updated', function (e) {
  const detail = e.detail || {};
  const c = typeof detail.count === 'number' ? detail.count : 0;
  orderAttachmentCount = c;
});

document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('order-form');
  if (!form) return;

  function val(el) { return (el?.value ?? '').trim(); }
  function err(msg) { errors.push(msg); }

  form.addEventListener('submit', function (e) {
    errors = [];
    let firstBad = null;

    // ======= Lead Information (required) =======
    const leadSelectEl =
    form.querySelector('select[name="lead_id"]') ||
    Array.from(form.querySelectorAll('[name="lead_id"]')).pop(); // last element as fallback

    if (!leadSelectEl || !(leadSelectEl.value || '').trim()) {
    err('Lead Information is required. Please select a valid lead from the search.');
    firstBad = firstBad || leadSelectEl;
    }

    

    // ======= Top-level fields =======
    const titleEl = form.querySelector('[name="orderTitle"]');
    const deadlineEl = form.querySelector('[name="deadline"]');
    const approvalYes = form.querySelector('input[name="approval"][value="1"]');
    const approvalNo  = form.querySelector('input[name="approval"][value="0"]');

    if (!val(titleEl)) {
      err('Job Title is required.');
      firstBad = firstBad || titleEl;
    }

    if (!val(deadlineEl)) {
      err('Deadline is required.');
      firstBad = firstBad || deadlineEl;
    } else {
      const today = new Date(); today.setHours(0,0,0,0);
      const d = new Date(deadlineEl.value);
      if (isNaN(d.getTime()) || d < today) {
        err('Deadline must be today or later.');
        firstBad = firstBad || deadlineEl;
      }
    }

    if (!(approvalYes?.checked || approvalNo?.checked)) {
      err('Approval (Yes / No) is required.');
      firstBad = firstBad || (approvalYes || approvalNo);
    }

    // ======= Products array (required, min:1) =======
    const productRows = Array.from(form.querySelectorAll(
    // prefer explicit flags if you already have them:
    '[data-product-row]:not([hidden])'
    ))
    .concat(
    // fallback: visible TRs that contain any input with name starting products[
    Array.from(form.querySelectorAll('table tbody tr')).filter(tr =>
        tr.offsetParent !== null &&                    // visible
        !tr.hasAttribute('data-template') &&           // skip template rows if you use this
        tr.querySelector('input[name^="products["], textarea[name^="products["], select[name^="products["]') &&
        !tr.querySelector('[disabled]')                // skip disabled rows
    )
    ).filter((row, idx, arr) => arr.indexOf(row) === idx); // uniq

    if (productRows.length < 1) {
    err('Please add at least 1 product.');
    }

    // helper to get either snake_case or camelCase field from a row
    function getField(row, endsWithList) {
    for (const suffix of endsWithList) {
        const el = row.querySelector(
        `input[name$="${suffix}"], textarea[name$="${suffix}"], select[name$="${suffix}"]`
        );
        if (el) return el;
    }
    return null;
    }

    productRows.forEach((row, i) => {
    const idx = i + 1;

    // Try both name styles:
    const nameEl = getField(row, ['[product_name]', '[productName]']);
    const qtyEl  = getField(row, ['[quantity]', '[totalQuantity]']);
    const matEl  = getField(row, ['[material_info]', '[materialRemark]']);

    // If a row is just a template/empty shell, skip it
    const isTemplateRow =
        (!nameEl && !qtyEl && !matEl) ||
        row.hasAttribute('data-template') ||
        row.style.display === 'none';
    if (isTemplateRow) return;

    // Basic requireds
    if (!nameEl || !nameEl.value.trim()) {
        err(`Product ${idx}: Product Name is required.`);
        firstBad = firstBad || nameEl;
    }

    const qtyVal = (qtyEl?.value ?? '').trim();
    const qtyInt = parseInt(qtyVal, 10);
    if (!qtyEl || !qtyVal || isNaN(qtyInt) || qtyInt < 1) {
        err(`Product ${idx}: Quantity must be an integer ≥ 1.`);
        firstBad = firstBad || qtyEl;
    }

    if (!matEl || !matEl.value.trim()) {
        err(`Product ${idx}: Material Remark is required.`);
        firstBad = firstBad || matEl;
    }

    // ======= Remarks rule (OP chosen → text required; other combos allowed) =======
    const opEls = row.querySelectorAll(`select[name*="[remarks]"][name$="[operation]"]`);
    const rmEls = row.querySelectorAll(`input[name*="[remarks]"][name$="[remark]"], textarea[name*="[remarks]"][name$="[remark]"]`);
    const pairCount = Math.max(opEls.length, rmEls.length);

    // if no remark rows at all, skip validation for remarks
    if (pairCount > 0) {
        let allEmpty = true; // track if every remark row is totally blank

        for (let r = 0; r < pairCount; r++) {
            const opEl = opEls[r];
            const rmEl = rmEls[r];
            const op = (opEl?.value ?? '').trim();
            const rm = (rmEl?.value ?? '').trim();

            if (op || rm) allEmpty = false; // found at least something filled

            // invalid only if both operation and remark are blank for a visible row
            if (!op && !rm) {
            err(`Product ${idx}: Each added remark must have both Operation and Remark text filled.`);
            firstBad = firstBad || (opEl || rmEl);
            }
        }
    

      for (let r = 0; r < pairCount; r++) {
        const opEl = opEls[r];
        const rmEl = rmEls[r];
        const op = val(opEl);
        const rm = val(rmEl);

        // Only this condition is enforced now:
        if ((op && !rm) || (rm && !op)) {
        err(`Product ${idx}: Each remark must have both Operation and Remark text.`);
        // focus the missing one if possible
        firstBad = firstBad || (!op ? opEl : rmEl);
        }
        // If rm filled but no op selected → allowed (as requested)
        // If both empty → allowed
      }
    }
    });

    // ======= Assign Artist (required) =======
    (function () {
    const role = (window.currentUserRole || '').toString().trim().toLowerCase();
    if (role !== 'head-artist') return; // only validate for head-artist

    // prefer real <select> elements; take the last visible one
    const candidates = Array.from(
        form.querySelectorAll('select[name="assignee_artist_id"], select[name="artist_id"]')
    );
    const visible = el => el && el.offsetParent !== null && !el.disabled;
    const artistSelect = [...candidates].reverse().find(visible) || candidates.pop() || null;

    const artistVal = (artistSelect?.value ?? '').toString().trim();

    if (!artistSelect || artistVal === '' || artistVal === '0') {
        err('Please assign an artist before saving the order.');
        if (!firstBad) firstBad = artistSelect || form.querySelector('[name="assignee_artist_id"], [name="artist_id"]');
    }
    })();


    // ======= Attachments (unchanged) =======
    const attachInputs = form.querySelectorAll('input[type="file"][name="attachments[]"]');
    if (attachInputs.length) {
      const allowed = ['application/pdf','image/jpeg','image/png','application/postscript'];
      for (const f of attachInputs) {
        for (const file of (f.files || [])) {
          if (file.size > 2 * 1024 * 1024) {
            err(`Attachment "${file.name}" exceeds 2 MB.`);
            firstBad = firstBad || f;
          }
          if (file.type && !allowed.includes(file.type)) {
            err(`Attachment "${file.name}" must be pdf, jpg, png, or ai.`);
            firstBad = firstBad || f;
          }
        }
      }
    }

    // Require at least one order attachment on CREATE
    // (orderAttachmentCount is updated via `attachments:updated` event)
    if (orderAttachmentCount < 1) {
      err('Please upload at least one order attachment before saving this order.');
      const fileInput = document.getElementById('fileInput'); // your attachments input
      firstBad = firstBad || fileInput;
    }

    if (errors.length) {
      e.preventDefault();
      const html = [...new Set(errors)].map(m => `<div style="text-align:left">${m}</div>`).join('');
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: 'Please complete all required fields',
          html,
          confirmButtonText: 'OK'
        }).then(() => { if (firstBad?.focus) firstBad.focus(); });
      } else {
        alert(errors.join('\n'));
        if (firstBad?.focus) firstBad.focus();
      }
    }
  });
});

$(document).on('beforeinput', '.qty-input', function (e) {
  // Prevent inserting non-digit or a leading zero at start
  if (e.originalEvent && e.originalEvent.data != null) {
    const data = String(e.originalEvent.data);
    const el = this;
    const start = el.selectionStart;
    const end   = el.selectionEnd;
    const next = el.value.slice(0, start) + data + el.value.slice(end);

    // only digits allowed
    if (!/^\d*$/.test(data)) return e.preventDefault();

    // no leading zero
    // if (/^0/.test(next)) return e.preventDefault();
  }
});

// 2) Normalize on input (e.g., strip non-digits coming from autofill/IME)
$(document).on('input', '.qty-input', function () {
  // keep only digits
  let v = this.value.replace(/\D+/g, '');

  // if it starts with zero and has more than one digit, remove leading zeros
  if (v.length > 1) v = v.replace(/^0+/, '');

  this.value = v;
});

// Block special chars (e, E, -, +, ., ,)
$(document).on('keydown', '.qty-input', function (e) {
  const blocked = ['e', 'E', '-', '+', '.', ','];
  if (blocked.includes(e.key)) e.preventDefault();
});


function updateAddButton() {
  const count   = $('#product-table tbody tr').length;
  const fromCsv = Number($('#from_csv').val()) === 1 || Number(window.isFromCsv) === 1;
  // show only when: NOT from CSV and < 5 rows
  $('#addProductBtn').toggle(!fromCsv && count < 5).prop('disabled', false);
}

function updateIndices() {
  const $tbody = $('#product-table tbody');
  const $rows  = $tbody.find('tr');

  $rows.each(function(i) {
    const $tr = $(this);
    $tr.find('.row-no').text(i + 1);
    $tr.attr('data-index', i);
    $tr.find('.edit-product').attr('data-index', i);
    $tr.find('.remove-product').attr('data-index', i);
    $tr.find('.add-remark').attr('data-index', i);

    const $rc = $tr.find('[id^="remarks-container-"]');
    if ($rc.length) $rc.attr('id', `remarks-container-${i}`);

    $tr.find('[name^="products["]').each(function() {
      this.name = this.name.replace(/products\[\d+\]/, `products[${i}]`);
    });
  });

  const $hidden = $('#hidden-products').empty();
  $rows.each(function(i) {
    const $div = $('<div>').attr('data-index', i);
    $(this).find('[name^="products["]').each(function() {
      $div.append($('<input>', { type: 'hidden', name: this.name, value: $(this).val() }));
    });
    $hidden.append($div);
  });

  productIndex = $rows.length;
//   if (!isFromCsv) $('#addProductBtn').toggle(productIndex < 5);
updateAddButton();
}

let isFormDirty = false;

// mark dirty on any meaningful edit
$(document).on('input change', '#order-form input, #order-form select, #order-form textarea', () => {
  isFormDirty = true;
});
$(document).on('click', '#addProductBtn, .edit-product, .remove-product, .add-remark, .remove-remark', () => {
  isFormDirty = true;
});

// clean on successful submit
$('#order-form').on('submit', function () {
  isFormDirty = false;
});

// ---- SweetAlert confirm helper ----
function confirmLeaveWithSwal() {
  return Swal.fire({
    icon: 'warning',
    title: 'Leave this page?',
    html: 'You have unsaved changes. If you leave now, your changes will be lost.',
    showCancelButton: true,
    confirmButtonText: 'Leave page',
    cancelButtonText: 'Stay here',
    reverseButtons: true,
    focusCancel: true
  }).then(r => r.isConfirmed);
}

// ---- Intercept in-app navigations and show SweetAlert ----

// 1) All normal links (same-tab, not anchors)
$(document).on('click', 'a[href]:not([target]):not([href^="#"])', function (e) {
  if (!isFormDirty) return; // allow
  e.preventDefault();
  const href = this.href;
  confirmLeaveWithSwal().then(ok => {
    if (ok) {
      isFormDirty = false;
      window.location.href = href;
    }
  });
});

// 2) Your Cancel button that calls history.back()
$(document).on('click', 'button[onclick="history.back()"], .js-cancel-leave', function (e) {
  if (!isFormDirty) return; // allow
  e.preventDefault();
  confirmLeaveWithSwal().then(ok => {
    if (ok) {
      isFormDirty = false;
      history.back();
    }
  });
});

// 3) Any custom programmatic navigation: call askToLeave(() => { doNav(); })
function askToLeave(next) {
  if (!isFormDirty) return next();
  confirmLeaveWithSwal().then(ok => {
    if (ok) {
      isFormDirty = false;
      next();
    }
  });
}

// ---- Fallback for hard unloads (refresh/close tab/new URL) ----
window.addEventListener('beforeunload', function (e) {
  if (!isFormDirty) return;
  e.preventDefault();
  e.returnValue = '';
});

document.addEventListener('DOMContentLoaded', () => {
  const input  = document.getElementById('fileInput');
  const listEl = document.getElementById('preview');
  const msgEl  = document.getElementById('attach-msg');

  if (!input || !listEl || !msgEl) return;

  const ALLOWED = [
    'pdf','png','jpg','jpeg','webp',
    'doc','docx','xls','xlsx','ppt','pptx',
    'ai','ps'
  ];

  const selected = new Map();

  // --------- Seed "Items" & "Delivery" on each product (same as edit) ---------
  const products = document.querySelectorAll('.accordion-collapse[id^="pCollapse"]');

  const productHasAtLeastOneItem = (root) =>
    !!root.querySelector(
      'input[name^="products["][name*="[items]"], ' +
      'select[name^="products["][name*="[items]"], ' +
      'textarea[name^="products["][name*="[items]"]'
    );

  const productHasAtLeastOneDelivery = (root) =>
    !!root.querySelector(
      'input[name^="products["][name*="[deliveries]"], ' +
      'select[name^="products["][name*="[deliveries]"], ' +
      'textarea[name^="products["][name*="[deliveries]"]'
    );

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
      const addItemBtn = findBtn(scope,
        '[data-add-item],[data-action="add-item"]',
        /\badd\s*item\b/
      );
      if (addItemBtn) addItemBtn.click();
    }

    // Seed 1 Delivery if none
    if (!productHasAtLeastOneDelivery(root)) {
      const addDelBtn = findBtn(scope,
        '[data-add-delivery],[data-action="add-delivery"]',
        /\badd\s*delivery(\s*breakdown)?\b/
      );
      if (addDelBtn) addDelBtn.click();
    }
  });

  // --------- Attachments uploader ---------
  input.addEventListener('change', () => {
    if (!input.files?.length) return;
    const incoming = Array.from(input.files);
    const invalids = [];

    incoming.forEach(f => {
      const ext = (f.name.split('.').pop() || '').toLowerCase();
      const key = `${f.name}|${f.size}|${f.lastModified}`;

      const errors = [];
      if (!ALLOWED.includes(ext)) {
        errors.push('Invalid file type');
        invalids.push({ name: f.name, ext });
      }
      if (selected.has(key)) errors.push('Duplicate');

      if (errors.length) {
        addRow(f, { status: 'error', note: errors.join(', ') });
      } else {
        selected.set(key, f);
        addRow(f, { key, status: 'ready' });
      }
    });

    if (invalids.length && typeof Swal !== 'undefined') {
      const uniqAllowed = [...new Set(ALLOWED)].map(e => `.${e}`).join(', ');
      const list = invalids
        .map(({ name, ext }) =>
          `<li><code>${name}</code> <small>(.${ext})</small></li>`
        ).join('');
      Swal.fire({
        icon: 'error',
        title: 'Unsupported file type',
        html: `
          <p>The following file(s) are not allowed:</p>
          <ul style="text-align:left;margin:0 0 8px 18px;">${list}</ul>
          <p><small>Allowed types: ${uniqAllowed || '–'}</small></p>
        `,
        confirmButtonText: 'OK'
      });
    }

    updateSummary();
    input.value = '';
  });

  function updateButtonsState() {
    // any invalid attachment chip? (created in addRow with <span class="err">)
    const hasInvalidAttachment = document.querySelector('#preview .err') !== null;

    const btnSubmit    = document.getElementById('btn-submit');
    const btnDraft     = document.getElementById('btn-draft');
    const btnSaveOrder = document.getElementById('save-order-btn');

    const allButtons = [btnSubmit, btnDraft, btnSaveOrder];

    if (hasInvalidAttachment) {
      // hard-disable all save buttons if there is any invalid file
      allButtons.forEach(btn => {
        if (!btn) return;
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
      });
    } else {
      // otherwise, follow existing “other validation errors” rule
      const anyInvalidField = document.querySelector('.is-invalid,[aria-invalid="true"]') !== null;

      allButtons.forEach(btn => {
        if (!btn) return;
        btn.disabled = !!anyInvalidField;
        btn.classList.toggle('opacity-50', !!anyInvalidField);
        btn.classList.toggle('cursor-not-allowed', !!anyInvalidField);
      });
    }
  }

  function addRow(file, { key = null, status = 'ready', note = '' }) {
    const li = document.createElement('li');
    li.dataset.status = status;      // 'ready' or 'error'
    if (key) li.dataset.key = key;   // so we can delete from `selected`

    li.innerHTML = `
      <span>${file.name}${
        status === 'error'
          ? ` – <span class="err">${note || 'Invalid file type'}</span>`
          : ` – <span class="ok">ready</span>`
      }</span>
      <button class="remove-x" title="Remove">×</button>
    `;

    li.querySelector('.remove-x').addEventListener('click', () => {
      const k = li.dataset.key;
      if (k && selected.has(k)) selected.delete(k);
      li.remove();
      updateButtonsState();
      updateSummary();
    });

    listEl.appendChild(li);
    updateButtonsState();
  }

  function updateSummary() {
    const count = selected.size;
    msgEl.innerHTML = count
      ? `<span class="ok">${count} file(s) selected for upload</span>`
      : '';
    document.dispatchEvent(new CustomEvent('attachments:updated', {
      detail: { count }
    }));
  }

  // expose for your AJAX submit if needed
  window.getSelectedFiles = () => Array.from(selected.values());

  const form = document.getElementById('order-form');
  if (form) {
    form.addEventListener('submit', () => {
      if (!input) return;
      const dt = new DataTransfer();
      selected.forEach(file => dt.items.add(file));
      input.files = dt.files;   // now Laravel sees all selected files
    });
  }
});

// ===========================
// Disable Save Order if invalid files exist
// ===========================

// Call this after every file add/remove
function checkInvalidFiles() {
    const invalids = document.querySelectorAll('[data-invalid-file="yes"]');
    const saveBtn = document.querySelector('#save-order-btn');

    if (!saveBtn) return;

    if (invalids.length > 0) {
        saveBtn.disabled = true;
        saveBtn.classList.add('opacity-50', 'cursor-not-allowed');
    } else {
        saveBtn.disabled = false;
        saveBtn.classList.remove('opacity-50', 'cursor-not-allowed');
    }
}
</script>

@endpush

@endsection
@if ($errors->any())
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const rawErrors = @json($errors->all());

      // Replace technical field names with friendly labels
      const cleaned = rawErrors.map(msg => {
        return msg
          .replace(/products\.\d+\.product_name/gi, 'Product Name')
          .replace(/products\.\d+\.quantity/gi, 'Quantity')
          .replace(/products\.\d+\.material_info/gi, 'Material Remark')
          .replace(/products\.\d+\.remarks(\.\d+)?\.operation/gi, 'Remark Operation')
          .replace(/products\.\d+\.remarks(\.\d+)?\.remark/gi, 'Remark Text')
          .replace(/products\.\d+\.remarks/gi, 'Remark')
          .replace(/products\.\d+/gi, 'Product')
          .replace(/\bfield is required\.?/gi, 'is required.')
          .replace(/The order detail field is required\./gi, 'Order Detail is required.');
      });

      // Merge duplicates and make it neat
      const uniqueMsgs = [...new Set(cleaned)];
      const html = uniqueMsgs.map(e => `<div style="text-align:left">${e}</div>`).join('');

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'error',
          title: 'Please complete all required fields',
          html: html,
          confirmButtonText: 'OK'
        });
      } else {
        alert(uniqueMsgs.join('\n'));
      }
    });
  </script>
@endif