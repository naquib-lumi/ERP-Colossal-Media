@extends('layouts.app')

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
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start;
        margin-bottom: 0.5rem;
    }

    .remarks-container{
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .remark-card{
        position: relative;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        padding: 12px;
        background: #fff;
    }

    .remark-card .btn-del{
        position: absolute;
        top: 6px;
        right: 6px;
        padding: 0;
    }

    .remark-card .remark-body{
        padding-right: 28px; /* give space so delete icon doesn't overlap */
    }

    .remark-card .form-label.small{
        font-size: 12px;
        font-weight: 600;
        color: #374151;
    }

    .remark-row input {
        flex: 1;
    }

    .remark-row button {
        flex: 0 0 auto;
    }

    .product-number {
        font-weight: bold;
        text-align: center;
        background: #f8f9fa;
    }

    .is-invalid {
        border-color: #dc3545 !important;
    }

    .validation-msg {
        color: #dc3545;
        font-size: 0.875em;
        margin-top: 0.25rem;
    }

    .delivery-card, .remark-card{
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 10px;
    margin-bottom: 10px;
    background: #fff;
    position: relative;
    }


    .delivery-card .btn-del, .remark-card .btn-del{
    position: absolute;
    top: 6px;
    right: 8px;
    padding: 0;
    }


    .delivery-card input, .remark-card input, .delivery-card select, .remark-card select{
    margin-top: 6px;
    }


    .small-label{
    font-size: 12px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 4px;
    }
</style>
@endpush

<form id="order-form" action="{{ route('orders.update', $order->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" name="from" value="{{ request('from') }}">
    <input type="hidden" name="lead_id" id="lead_id" value="{{ $order->lead_id }}">
    <input type="hidden" id="from_csv" name="from_csv" value="{{ old('from_csv', 0) }}">

    <div class="row g-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Edit Job Order</h5>
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
                                    @if($order->orderStatus === 'in_progress' && is_null($order->artist_id) && $order->draft == 1)
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Search Lead <span class="text-danger">*</span></label>

                                        <select id="leadSelect" class="form-select" style="width:100%;">
                                            @if($order->lead_id && $order->lead)
                                            <option value="{{ $order->lead_id }}" selected>
                                                {{ $order->lead->company_name }} - {{ $order->lead->name }}
                                            </option>
                                            @else
                                                <option value="" selected>Search for a lead</option>
                                            @endif
                                        </select>

                                        <div id="lead-error" class="validation-msg"></div>
                                    </div>
                                    @endif
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Company Name</label>
                                            <input id="companyDisplay" type="text" class="form-control" value="{{ $order->lead->company_name ?? $order->companyName ?? '' }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Lead Name</label>
                                            <input id="leadNameDisplay" type="text" class="form-control" value="{{ $order->lead->name ?? $order->leadName ?? '' }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Phone</label>
                                            <input id="phoneDisplay" type="text" class="form-control" value="{{ $order->lead->phone ?? $order->leadPhone ?? '' }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input id="emailDisplay" type="text" class="form-control" value="{{ $order->lead->email ?? $order->leadEmail ?? '' }}" readonly>
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
                                            <input name="orderTitle" type="text" class="form-control" value="{{ old('orderTitle', $order->orderTitle) }}">
                                            <div id="orderTitle-error" class="validation-msg"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Created Date</label>
                                            <input type="text" class="form-control" value="{{ optional($order->orderDate)->format('d/m/Y') }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Deadline <span class="text-danger">*</span></label>
                                            <input name="deadline" type="date" class="form-control" value="{{ old('deadline', optional($order->deadline)->format('Y-m-d')) }}">
                                            <div id="deadline-error" class="validation-msg"></div>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <label class="form-label">Created By</label>
                                            <input type="text" class="form-control" value="{{ optional($order->salesperson)->name }}" readonly>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label d-block mb-4">Design from artist would need client approval <span class="text-danger">*</span></label>
                                            <div class="d-flex gap-4">
                                                <label class="form-check-label">
                                                    <input class="form-check-input me-1" type="radio" name="approval" value="1" {{ old('approval', $order->approval) == 1 ? 'checked' : '' }}> YES
                                                </label>
                                                <label class="form-check-label">
                                                    <input class="form-check-input me-1" type="radio" name="approval" value="0" {{ old('approval', $order->approval) == 0 ? 'checked' : '' }}> NO
                                                </label>
                                            </div>
                                            <div id="approval-error" class="validation-msg"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

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
                                            <th>#</th>
                                            <th>Product Name</th>
                                            <th>Quantity</th>
                                            <th>Material Remark <span class="text-danger">*</span></th>
                                            <th style="width: 260px;">Delivery Breakdown</th>
                                            <th>Remarks</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php $productIndex = 0; @endphp
                                        @foreach ($order->products as $product)
                                            <tr data-product-id="{{ $product->ProductID }}" data-index="{{ $productIndex }}">
                                                <td class="product-number">{{ $loop->iteration }}</td>
                                                <td>
                                                    <input type="hidden" name="products[{{ $productIndex }}][id]" value="{{ $product->ProductID }}">
                                                    <input type="text" name="products[{{ $productIndex }}][product_name]" class="form-control" value="{{ old("products.{$productIndex}.product_name", $product->productName) }}">
                                                </td>
                                                <td>
                                                    <input type="number" name="products[{{ $productIndex }}][quantity]" class="form-control" value="{{ old("products.{$productIndex}.quantity", ltrim($product->totalQuantity, '0') ?: '') }}" min="1">
                                                </td>
                                                <td>
                                                    <input type="text" name="products[{{ $productIndex }}][material_remark]" class="form-control material-remark" value="{{ old("products.{$productIndex}.material_remark", $product->materialRemark ?? '') }}">
                                                </td>
                                                <td>
                                                    <div id="deliveries-container-{{ $productIndex }}">
                                                        @foreach ($product->deliveries ?? [] as $dindex => $d)
                                                            @php
                                                                $method = data_get($d, 'method', '');
                                                                $location = data_get($d, 'location', '');
                                                                $dtRaw = data_get($d, 'datetime', '');
                                                                $dtVal = '';
                                                                try {
                                                                    if ($dtRaw) $dtVal = \Carbon\Carbon::parse($dtRaw)->format('Y-m-d\TH:i');
                                                                } catch (\Exception $e) {
                                                                    $dtVal = '';
                                                                }
                                                            @endphp

                                                            <div class="delivery-card delivery-row">
                                                                <button type="button" class="btn btn-link text-danger btn-del remove-delivery" title="Delete">
                                                                    <i class="bx bx-trash fs-5"></i>
                                                                </button>

                                                                <select name="products[{{ $productIndex }}][deliveries][{{ $dindex }}][method]" class="form-select w-auto" style="min-width:160px;">
                                                                    <option value="">— Select —</option>
                                                                    <option value="courier" {{ $method==='courier'?'selected':'' }}>Courier</option>
                                                                    <option value="self_pickup" {{ $method==='self_pickup'?'selected':'' }}>Self Pickup</option>
                                                                    <option value="delivery" {{ $method==='delivery'?'selected':'' }}>Delivery</option>
                                                                    <option value="installation" {{ $method==='installation'?'selected':'' }}>Installation</option>
                                                                </select>

                                                                <input type="text"
                                                                    name="products[{{ $productIndex }}][deliveries][{{ $dindex }}][location]"
                                                                    class="form-control"
                                                                    placeholder="Location"
                                                                    value="{{ $location }}">

                                                                <input type="datetime-local"
                                                                    name="products[{{ $productIndex }}][deliveries][{{ $dindex }}][datetime]"
                                                                    class="form-control"
                                                                    value="{{ $dtVal }}">
                                                            </div>
                                                        @endforeach
                                                    </div>

                                                    <button type="button" class="btn btn-secondary btn-sm add-delivery mt-2" data-index="{{ $productIndex }}">
                                                        Add Delivery
                                                    </button>
                                                </td>
                                                <td>
                                                    <div id="remarks-container-{{ $productIndex }}">
                                                        @foreach ($product->remarks ?? [] as $rindex => $r)
                                                            @php
                                                                $op = data_get($r, 'operation', '');
                                                                $txt = data_get($r, 'remark', '');
                                                            @endphp

                                                            <div class="remark-card remark-row">
                                                                <button type="button" class="btn btn-link text-danger btn-del remove-remark" title="Delete">
                                                                    <i class="bx bx-trash fs-5"></i>
                                                                </button>

                                                                <div class="small-label">Operation</div>
                                                                <select name="products[{{ $productIndex }}][remarks][{{ $rindex }}][operation]" class="form-select">
                                                                    <option value="">— Select —</option>
                                                                    <option value="artist" {{ $op==='artist'?'selected':'' }}>To Artist</option>
                                                                    <option value="printing" {{ $op==='printing'?'selected':'' }}>To Printing</option>
                                                                    <option value="furnishing" {{ $op==='furnishing'?'selected':'' }}>To Furnishing</option>
                                                                    <option value="installation" {{ $op==='installation'?'selected':'' }}>To Installation</option>
                                                                    <option value="self_pickup" {{ $op==='self_pickup'?'selected':'' }}>To Self Pickup</option>
                                                                    <option value="courier" {{ $op==='courier'?'selected':'' }}>To Courier</option>
                                                                </select>

                                                                <div class="small-label mt-2">Remark</div>
                                                                <input type="text"
                                                                    name="products[{{ $productIndex }}][remarks][{{ $rindex }}][remark]"
                                                                    class="form-control"
                                                                    placeholder="Write a note…"
                                                                    value="{{ $txt }}">
                                                            </div>
                                                        @endforeach
                                                    </div>

                                                    <button type="button" class="btn btn-secondary btn-sm mt-2 add-remark" data-index="{{ $productIndex }}">
                                                        Add Remark
                                                    </button>
                                                </td>

                                                <td>
                                                    <button type="button" class="btn btn-sm btn-danger remove-product" data-index="{{ $productIndex }}">
                                                        Delete
                                                    </button>
                                                </td>
                                            </tr>
                                            @php $productIndex++; @endphp
                                        @endforeach
                                        @foreach (old('products', []) as $index => $product)
                                            @if ($index >= $order->products->count())
                                                <tr data-index="{{ $index }}">
                                                    <td class="product-number">{{ $index + 1 }}</td>
                                                    <td><input type="text" name="products[{{ $index }}][product_name]" class="form-control" value="{{ $product['product_name'] ?? '' }}"></td>
                                                    <td><input type="number" name="products[{{ $index }}][quantity]" class="form-control" value="{{ ltrim($product['quantity'] ?? '', '0') ?: '' }}" min="1"></td>
                                                    <td><input type="text" name="products[{{ $index }}][material_remark]" class="form-control material-remark" value="{{ $product['material_remark'] ?? '' }}"></td>
                                                    <td>
                                                        <div id="remarks-container-{{ $index }}">
                                                            @foreach ($product['remarks'] ?? [] as $rindex => $remark)
                                                                <div class="remark-row">
                                                                    <select name="products[{{ $index }}][remarks][{{ $rindex }}][operation]" class="form-select w-auto" style="min-width:160px;">
                                                                        <option value="artist" {{ $remark['operation'] == 'artist' ? 'selected' : '' }}>To Artist</option>
                                                                        <option value="printing" {{ $remark['operation'] == 'printing' ? 'selected' : '' }}>To Printing</option>
                                                                        <option value="furnishing" {{ $remark['operation'] == 'furnishing' ? 'selected' : '' }}>To Furnishing</option>
                                                                        <option value="installation" {{ $remark['operation'] == 'installation' ? 'selected' : '' }}>To Installation</option>
                                                                        <option value="self_pickup" {{ $remark['operation'] == 'self_pickup' ? 'selected' : '' }}>To Self Pickup</option>
                                                                        <option value="courier" {{ $remark['operation'] == 'courier' ? 'selected' : '' }}>To Courier</option>
                                                                    </select>
                                                                    <input type="text" name="products[{{ $index }}][remarks][{{ $rindex }}][remark]" class="form-control" value="{{ $remark['remark'] ?? '' }}" placeholder="Write a note…">
                                                                    <button type="button" class="btn btn-link text-danger p-0 remove-remark" title="Delete">
                                                                        <i class="bx bx-trash fs-5"></i>
                                                                    </button>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                        <button type="button" class="btn btn-secondary btn-sm mt-2 add-remark" data-index="{{ $index }}">Add Remark</button>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn btn-sm btn-danger remove-product" data-index="{{ $index }}">Delete</button>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                                <div id="products-error" class="validation-msg"></div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted">Upload CSV (Optional)</span>
                                <a id="csvTemplateBtn" href="{{ route('orders.csv_template') }}" class="btn btn-link p-0 text-decoration-none">
                                    <i class="bx bx-download me-1"></i> CSV Template Download
                                </a>
                            </div>

                            <div id="attach-box" class="attach-box">
                                <div class="attach-inner">
                                    <div class="attach-icon" aria-hidden="true">
                                        <i class="bx bx-upload display-6 mb-2 d-block justify-content-between align-items-center" style="pointer-events:none"></i>
                                    </div>
                                    <div class="attach-title">Drop CSV file here or click to upload</div>
                                    <div class="attach-hint">(CSV)</div>
                                </div>
                                <input id="fileInput" type="file" accept=".csv" class="file-overlay">
                            </div>
                            <div id="attach-msg" class="mt-2 text-sm"></div>
                            <ul id="preview" class="mt-3 space-y-2"></ul>

    <!-- Current Attachments -->


<!-- Unified Attachment Section -->
<div class="mt-4">
    <label class="form-label">Attachments <span class="text-danger">*required</span></label>
    
   

    <!-- Dropzone for new files -->
    <div id="attachment-dropzone" class="attach-box mt-3">
        <div class="attach-inner">
            <div class="attach-icon"><i class="bx bx-upload display-6"></i></div>
            <div class="attach-title">Drop files here or click to upload</div>
            <div class="attach-hint">PDF, JPG, PNG, AI, PSD, EPS, SVG, TIFF, INDD, XLSX · Max 50MB each</div>
        </div>
        <input type="file" name="attachments[]" multiple accept=".pdf,.jpg,.jpeg,.png,.ai,.psd,.eps,.svg,.tiff,.indd,.xls,.xlsx,.csv" class="file-overlay">
    </div>
     <div id="attachment-preview" class="mt-3">
        <!-- Existing attachments (rendered on page load) -->
        @foreach($order->attachments as $att)
            <div class="d-flex justify-content-between align-items-center border rounded p-2 mb-2 bg-light existing-attachment" 
                 data-attachment-id="{{ $att->id }}" data-url="{{ route('orders.attachment.delete', [$order->id, $att->id]) }}">
                <span>
                    <i class="bx bx-paperclip"></i>
                    <a href="{{ Storage::url($att->file_path) }}" target="_blank">
                        {{ $att->original_name }}
                    </a>
                    <span class="text-muted ms-2">({{ number_format($att->size/1024/1024, 2) }} MB)</span>
                </span>
                <button type="button" class="btn btn-sm text-danger">&times;</button>
            </div>
        @endforeach
    </div>
</div>
</div>
                            <div class="mt-3">
                                <label class="form-label">Remarks</label>
                                <textarea name="orderDetail" rows="3" class="form-control" placeholder="Remarks">{{ old('orderDetail', $order->orderDetail) }}</textarea>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="bg-body position-sticky bottom-0 border-top py-3 d-flex gap-2 justify-content-end" style="z-index:10">
            <button type="button" class="btn btn-outline-secondary" onclick="cancelOrder()">Cancel</button>

            @if($order->orderStatus === 'in_progress' && is_null($order->artist_id) && $order->draft == 1)
            {{-- Save Draft (skip validation) --}}
            <button type="submit" name="save_type" value="draft" class="btn btn-outline-primary">
            Save Draft
            </button>


            {{-- Update Order (full validation) --}}
            <button type="submit" name="save_type" value="final" class="btn btn-primary">
            Update Order
            </button>
            @else
            {{-- Normal Update --}}
            <button type="submit" name="save_type" value="final" class="btn btn-primary">
            Update Order
            </button>
            @endif
        </div>
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
                            <input id="product_name" class="form-control" required>
                            <div id="product_name-error" class="validation-msg"></div>
                        </div>
                        <div class="col-md-6">
                            <label>Quantity <span class="text-danger">*</span></label>
                            <input id="quantity" type="number" class="form-control" min="1" required>
                            <div id="quantity-error" class="validation-msg"></div>
                        </div>
                        <div class="col-12">
                            <label>Material Remark <span class="text-danger">*</span></label>
                            <textarea id="material_remark" class="form-control" required></textarea>
                            <div id="material_remark-error" class="validation-msg"></div>
                        </div>
                        <div class="col-12">
                            <label style="margin-bottom: 5px;">
                            Delivery Breakdown <span class="text-danger">*</span>
                            </label>
                            <div id="modal-deliveries-container"></div>
                                <button type="button" class="btn btn-secondary btn-sm mt-2" id="addDeliveryRowBtn">
                                    Add Delivery
                                </button>
                            </div>
                        </div>
                        <div class="col-12">
                            <label style="margin-top:10px;">Remarks</label>
                            <div id="remarks-container"></div>
                            <button type="button" id="addRemarkBtn" class="btn btn-secondary btn-sm mt-2">Add Remark</button>
                            <div id="remarks-error" class="validation-msg"></div>
                        </div>
                    </div>
                </form>
                <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveProduct">Save</button>
            </div>
            </div>
            
        </div>
    </div>
</div>
@push('scripts')
@if($order->orderStatus === 'in_progress' && is_null($order->artist_id) && $order->draft == 1)

<script>
$(document).ready(function() {
    var leadSelect = $('#leadSelect');

    leadSelect.select2({
        placeholder: 'Search for a lead',
        dropdownParent: leadSelect.parent(),
        minimumInputLength: 2,
        ajax: {
            url: '{{ route('orders.leads.search') }}',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    query: params.term,
                    _token: '{{ csrf_token() }}'
                };
            },
            processResults: function(data) {
                return {
                    results: data.map(lead => ({
                        id: lead.id,
                        text: lead.text
                    }))
                };
            },
            cache: true
        }
    });

    leadSelect.on('select2:select', function(e) {
        var data = e.params.data;
        var getLeadBase = "{{ route('orders.leads.get', ':id') }}";

        $.ajax({
            url: getLeadBase.replace(':id', data.id),
            type: 'GET',
            success: function(lead) {
                $('#companyDisplay').val(lead.company_name);
                $('#leadNameDisplay').val(lead.name);
                $('#phoneDisplay').val(lead.phone);
                $('#emailDisplay').val(lead.email);
                $('#lead_id').val(lead.id).trigger('change');
            }
        });
    });
});
</script>

@endif
<script>
    var isDirty = false;
    let productIndex = $('#product-table tbody tr').length;
    let selectedFiles = [];
    const dt = new DataTransfer();

    $(document).ready(function () {
        // Helper functions
        function escapeHtml(str) {
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                      .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }
        function ltrim(str, char = '0') {
            return str.replace(new RegExp(`^${char}+`), '') || '0';
        }

        // Initial state
        let initialFormState = $('#order-form').serialize();
        let isFromCsv = {{ old('from_csv', 0) }};
        if (isFromCsv || productIndex >= 5) $('#addProductBtn').hide();

        renumberProducts();

        // Track changes
        $('#order-form').on('change input', () => {
            isDirty = $('#order-form').serialize() !== initialFormState;
        });

        window.addEventListener('beforeunload', e => {
            if (isDirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // ——————————————————— CSV Upload ———————————————————
        const csvInput = document.getElementById('fileInput');
        const csvPreview = document.getElementById('preview');
        let csvFile = null;

        csvInput.addEventListener('change', function () {
            if (!this.files.length) return;
            const file = this.files[0];
            const ext = file.name.split('.').pop().toLowerCase();

            csvPreview.innerHTML = '';
            if (ext !== 'csv') {
                csvPreview.innerHTML = `<li class="text-danger">${file.name} – Invalid file type</li>`;
                csvFile = null;
                return;
            }

            csvFile = file;
            csvPreview.innerHTML = `<li>${file.name} – <span class="text-success">ready</span>
                <button type="button" class="remove-x">×</button></li>`;
            csvPreview.querySelector('.remove-x').onclick = () => {
                csvFile = null;
                csvPreview.innerHTML = '';
                $('#product-table tbody').empty();
                productIndex = 0;
                $('#from_csv').val(0);
                $('#addProductBtn').show();
                renumberProducts();
            };

            parseCsv(file);
            isDirty = true;
        });

        function parseCsv(file) {
            const reader = new FileReader();
            reader.onload = e => {
                const lines = e.target.result.split(/\r?\n/);
                const headers = lines[0].split(',').map(h => h.trim());
                $('#product-table tbody').empty();
                productIndex = 0;

                for (let i = 1; i < lines.length; i++) {
                    if (!lines[i].trim()) continue;
                    const row = lines[i].split(',').map(c => c.replace(/^"|"$/g, '').trim());
                    const product = {
                        product_name: row[headers.indexOf('Product_Name')] || '',
                        quantity: ltrim(row[headers.indexOf('Quantity')] || ''),
                        material_remark: row[headers.indexOf('Material_Info')] || '',
                        remarks: []
                    };

                    const remarkMap = {
                        'Artist_Remark': 'artist',
                        'Printing_Remark': 'printing',
                        'Furnishing_Remark': 'furnishing',
                        'Installation_Remark': 'installation',
                        'Courier_Remark': 'courier',
                        'Self_Pickup_Remark': 'self_pickup'
                    };

                    Object.keys(remarkMap).forEach((col, idx) => {
                        const idxCol = headers.indexOf(col);
                        if (idxCol !== -1 && row[idxCol]) {
                            product.remarks.push({ operation: remarkMap[col], remark: row[idxCol] });
                        }
                    });

                    addProductRow(product, productIndex++);
                }

                $('#from_csv').val(1);
                if (productIndex >= 5) $('#addProductBtn').hide();
                renumberProducts();
            };
            reader.readAsText(file);
        }

        // ——————————————————— Product Modal ———————————————————
        let modalData = {};

        $('#productModal').on('show.bs.modal', function (e) {
            const btn = $(e.relatedTarget);
            const mode = btn.data('mode');
            const index = btn.data('index');

            clearValidationErrors();
            $('#productForm')[0].reset();
            $('#product_index').val('');
            $('#remarks-container').empty();

            if (mode === 'edit' && index !== undefined) {
                const row = $(`tr[data-index="${index}"]`);
                $('#product_name').val(row.find('input[name$="[product_name]"]').val());
                $('#quantity').val(row.find('input[name$="[quantity]"]').val());
                $('#material_remark').val(row.find('input[name$="[material_remark]"]').val());
                row.find('.remark-row').each(function () {
                    const op = $(this).find('select').val();
                    const rem = $(this).find('input').val();
                    addRemarkRow(op, rem);
                });
                $('#product_index').val(index);
                $('#productModalTitle').text('Edit Product');
            } else if (Object.keys(modalData).length) {
                $('#product_name').val(modalData.product_name || '');
                $('#quantity').val(modalData.quantity || '');
                $('#material_remark').val(modalData.material_remark || '');
                modalData.remarks.forEach(r => addRemarkRow(r.operation, r.remark));
                modalData = {};
            }

            if ($('#modal-deliveries-container .delivery-row').length === 0) {
                addModalDeliveryRow('', '', '');
            }
        });

        $('#addRemarkBtn').on('click', () => {
            if ($('#remarks-container .remark-row').length >= 6) {
                Swal.fire('Warning', 'Maximum 6 remarks per product.', 'warning');
                return;
            }
            addRemarkRow();
        });

        function addRemarkRow(op = '', rem = '') {
            const rIdx = $('#remarks-container .remark-row').length;

            const html = `
                <div class="remark-row mb-2 d-flex align-items-center gap-2" style="flex-direction:unset;">
                    <select class="form-select w-auto" style="min-width:200px;">
                        <option value="">— Select —</option>
                        <option value="artist" ${op === 'artist' ? 'selected' : ''}>To Artist</option>
                        <option value="printing" ${op === 'printing' ? 'selected' : ''}>To Printing</option>
                        <option value="furnishing" ${op === 'furnishing' ? 'selected' : ''}>To Furnishing</option>
                        <option value="installation" ${op === 'installation' ? 'selected' : ''}>To Installation</option>
                        <option value="self_pickup" ${op === 'self_pickup' ? 'selected' : ''}>To Self Pickup</option>
                        <option value="courier" ${op === 'courier' ? 'selected' : ''}>To Courier</option>
                    </select>

                    <input type="text"
                        class="form-control"
                        placeholder="Write a note…"
                        value="${escapeHtml(rem || '')}"
                        style="min-width:320px;">

                    <button type="button" class="btn btn-link text-danger p-0 remove-modal-remark" title="Delete">
                        <i class="bx bx-trash fs-5"></i>
                    </button>
                </div>
            `;

            $('#remarks-container').append(html);
        }

        $(document).on('click', '.remove-remark', function (e) {
            e.preventDefault();

            const $tr = $(this).closest('tr');
            const idx = $tr.data('index');

            // remove either structure
            $(this).closest('.remark-card, .remark-row').remove();

            // reindex remark names so backend receives clean arrays
            renumberRemarks(idx);

            isDirty = true;
        });

        function renumberRemarks(productIdx) {
            const $container = $(`#remarks-container-${productIdx}`);

            $container.find('.remark-card, .remark-row').each(function (rIdx) {
                $(this).find('input[name], select[name]').each(function () {
                    this.name = this.name.replace(
                        new RegExp(`^products\\[${productIdx}\\]\\[remarks\\]\\[\\d+\\]`),
                        `products[${productIdx}][remarks][${rIdx}]`
                    );
                });
            });
        }

        $('#saveProduct').on('click', function () {
            const errors = validateProductForm();
            if (errors.length) {
                modalData = {
                    product_name: $('#product_name').val(),
                    quantity: $('#quantity').val(),
                    material_remark: $('#material_remark').val(),
                    remarks: []
                };
                $('#remarks-container .remark-row').each(function () {
                    modalData.remarks.push({
                        operation: $(this).find('select').val(),
                        remark: $(this).find('input').val()
                    });
                });
                $('#productModal').modal('hide');
                setTimeout(() => {
                    Swal.fire('Error', errors.map(m => `<div>${m}</div>`).join(''), 'error')
                        .then(() => $('#productModal').modal('show'));
                }, 300);
                return;
            }

            const data = {
                product_name: $('#product_name').val().trim(),
                quantity: ltrim($('#quantity').val()),
                material_remark: $('#material_remark').val(),
                deliveries: [],
                remarks: []
            };
            // collect deliveries (save if any field filled)
            $('#modal-deliveries-container .delivery-row').each(function () {
                const method = $(this).find('select').val() || '';
                const loc    = ($(this).find('input[type="text"]').val() || '').trim();
                const dtVal  = $(this).find('input[type="datetime-local"]').val() || '';

                // only skip if ALL empty
                if (!method && !loc && !dtVal) return;

                data.deliveries.push({
                    method: method,
                    location: loc,
                    datetime: dtVal
                });
            });
            $('#remarks-container .remark-row').each(function () {
                const op = $(this).find('select').val();
                const rem = $(this).find('input[type="text"]').val().trim();
                if (op || rem) { // allow partial save if you want; change to (op && rem) if you want strict
                    data.remarks.push({ operation: op, remark: rem });
                }
            });

            const idx = $('#product_index').val();
            if (idx !== '') {
                updateProductRow(idx, data);
            } else {
                if (productIndex >= 5) {
                    Swal.fire('Warning', 'Maximum 5 products allowed.', 'warning');
                    return;
                }
                addProductRow(data, productIndex++);
                if (productIndex >= 5) $('#addProductBtn').hide();
            }
            isDirty = true;
            $('#productModal').modal('hide');
        });

        function addProductRow(data, idx) {
            const row = `
            <tr data-index="${idx}">
            <td class="product-number">${idx + 1}</td>


            <td>
            <input type="text" name="products[${idx}][product_name]" class="form-control"
            value="${escapeHtml(data.product_name)}">
            </td>


            <td>
            <input type="number" name="products[${idx}][quantity]" class="form-control"
            value="${data.quantity}" min="1">
            </td>


            <td>
            <input type="text" name="products[${idx}][material_remark]" class="form-control material-remark"
            value="${escapeHtml(data.material_remark)}">
            </td>


            <td>
            <div id="deliveries-container-${idx}">
            ${(data.deliveries || []).map((d, i) => renderDeliveryCard(idx, d, i)).join('')}
            </div>


            <button type="button" class="btn btn-secondary btn-sm mt-2 add-delivery" data-index="${idx}">
            Add Delivery
            </button>
            </td>


            <td class="remarks-td">
                <div id="remarks-container-${idx}" class="remarks-container">
                ${(data.remarks || []).map((r, i) => renderRemarkCard(idx, r, i)).join('')}
                </div>


                <button type="button" class="btn btn-secondary btn-sm mt-2 add-remark" data-index="${idx}">
                Add Remark
                </button>
            </td>


            <td>
            <button type="button" class="btn btn-sm btn-danger remove-product" data-index="${idx}">
            Delete
            </button>
            </td>
            </tr>
            `;
            $('#product-table tbody').append(row);
        }

        function updateProductRow(idx, data) {
            const row = $(`tr[data-index="${idx}"]`);


            row.find('input[name$="[product_name]"]').val(data.product_name);
            row.find('input[name$="[quantity]"]').val(data.quantity);
            row.find('input[name$="[material_remark]"]').val(data.material_remark);


            // deliveries
            const dCon = row.find(`#deliveries-container-${idx}`).empty();
            (data.deliveries || []).forEach((d, i) => dCon.append(renderDeliveryCard(idx, d, i)));
            

            // remarks
            const rCon = row.find(`#remarks-container-${idx}`).empty();
            (data.remarks || []).forEach((r, i) => rCon.append(renderRemarkCard(idx, r, i)));


            renumberProducts();
        }

        function renderDeliveryCard(idx, d, i) {
            return `
            <div class="delivery-card delivery-row">
            <button type="button" class="btn btn-link text-danger btn-del remove-delivery" title="Delete">
            <i class="bx bx-trash fs-5"></i>
            </button>


            <select name="products[${idx}][deliveries][${i}][method]" class="form-select w-auto" style="min-width:160px;">
            <option value="">— Select —</option>
            <option value="courier" ${d.method === 'courier' ? 'selected' : ''}>Courier</option>
            <option value="self_pickup" ${d.method === 'self_pickup' ? 'selected' : ''}>Self Pickup</option>
            <option value="delivery" ${d.method === 'delivery' ? 'selected' : ''}>Delivery</option>
            <option value="installation" ${d.method === 'installation' ? 'selected' : ''}>Installation</option>
            </select>


            <input type="text"
            name="products[${idx}][deliveries][${i}][location]"
            class="form-control"
            placeholder="Location"
            value="${escapeHtml(d.location || '')}">


            <input type="datetime-local"
            name="products[${idx}][deliveries][${i}][datetime]"
            class="form-control"
            value="${escapeHtml(d.datetime || '')}">
            </div>
            `;
        }

        function addModalDeliveryRow(method = '', location = '', datetime = '') {
            const html = `
            <div class="delivery-row mb-2 d-flex align-items-center gap-2">
            <select class="form-select w-auto" style="min-width:160px;">
            <option value="">— Select —</option>
            <option value="courier" ${method === 'courier' ? 'selected' : ''}>Courier</option>
            <option value="self_pickup" ${method === 'self_pickup' ? 'selected' : ''}>Self Pickup</option>
            <option value="delivery" ${method === 'delivery' ? 'selected' : ''}>Delivery</option>
            <option value="installation" ${method === 'installation' ? 'selected' : ''}>Installation</option>
            </select>


            <input type="text" class="form-control" placeholder="Location"
            value="${escapeHtml(location)}" style="min-width:220px;">


            <input type="datetime-local" class="form-control"
            value="${escapeHtml(datetime)}" style="min-width:220px;">


            <button type="button" class="btn btn-link text-danger p-0 remove-modal-delivery" title="Delete">
            <i class="bx bx-trash fs-5"></i>
            </button>
            </div>
            `;
            $('#modal-deliveries-container').append(html);
            }


            $(document).on('click', '.remove-modal-delivery', function () {
            $(this).closest('.delivery-row').remove();
        });

        function renderRemarkCard(idx, r, i) {
            return `
                <div class="remark-card">
                    <button type="button" class="btn btn-link text-danger btn-del remove-remark" title="Delete">
                        <i class="bx bx-trash fs-5"></i>
                    </button>

                    <div class="remark-body">
                        <div class="form-group">
                            <label class="form-label small mb-1">Operation</label>
                            <select name="products[${idx}][remarks][${i}][operation]" class="form-select">
                                <option value="">— Select —</option>
                                <option value="artist" ${r.operation==='artist'?'selected':''}>To Artist</option>
                                <option value="printing" ${r.operation==='printing'?'selected':''}>To Printing</option>
                                <option value="furnishing" ${r.operation==='furnishing'?'selected':''}>To Furnishing</option>
                                <option value="installation" ${r.operation==='installation'?'selected':''}>To Installation</option>
                                <option value="self_pickup" ${r.operation==='self_pickup'?'selected':''}>To Self Pickup</option>
                                <option value="courier" ${r.operation==='courier'?'selected':''}>To Courier</option>
                            </select>
                        </div>

                        <div class="form-group mt-2">
                            <label class="form-label small mb-1">Remark</label>
                            <input type="text"
                                name="products[${idx}][remarks][${i}][remark]"
                                class="form-control"
                                placeholder="Write a note…"
                                value="${escapeHtml(r.remark || '')}">
                        </div>
                    </div>
                </div>
            `;
        }

        function renumberProducts() {
            $('#product-table tbody tr').each(function (i) {
            $(this).attr('data-index', i).find('.product-number').text(i + 1);


            $(this).find('.add-remark, .add-delivery, .remove-product').attr('data-index', i);


            $(this).find('[id^="remarks-container-"]').attr('id', 'remarks-container-' + i);
            $(this).find('[id^="deliveries-container-"]').attr('id', 'deliveries-container-' + i);


            $(this).find('input[name], select[name]').each(function () {
            // replace ONLY the first "products[x]" part, keep the nested indices
            this.name = this.name.replace(/^products\[\d+\]/, 'products[' + i + ']');
            });
            });
        }

        // table: add delivery
        $(document).on('click', '.add-delivery', function () {
        const idx = $(this).data('index');
        const container = $(`#deliveries-container-${idx}`);


        if (container.find('.delivery-row').length >= 6) {
        Swal.fire('Warning', 'Maximum 6 deliveries per product.', 'warning');
        return;
        }


        const dIdx = container.find('.delivery-row').length;
        container.append(renderDeliveryCard(idx, { method:'', location:'', datetime:'' }, dIdx));
        isDirty = true;
        });


        // table: remove delivery
        $(document).on('click', '.remove-delivery', function () {
        $(this).closest('.delivery-row').remove();
        isDirty = true;
        });


        // modal: add delivery row
        $('#addDeliveryRowBtn').on('click', function () {
        addModalDeliveryRow('', '', '');
        });


        // modal: remove delivery row
        $(document).on('click', '.remove-modal-delivery', function () {
        $(this).closest('.delivery-row').remove();
        });

        $(document).on('click', '.remove-product', function () {
            $(this).closest('tr').remove();
            renumberProducts();
            productIndex = $('#product-table tbody tr').length;
            if (productIndex < 5) $('#addProductBtn').show();
            isDirty = true;
        });

        $(document).on('click', '.add-remark', function () {
            const idx = $(this).data('index');
            const container = $(`#remarks-container-${idx}`);

            if (container.find('.remark-card, .remark-row').length >= 6) {
                Swal.fire('Warning', 'Maximum 6 remarks per product.', 'warning');
                return;
            }

            const rIdx = container.find('.remark-card, .remark-row').length;

            container.append(`
                <div class="remark-card remark-row">
                    <button type="button" class="btn btn-link text-danger btn-del remove-remark" title="Delete">
                        <i class="bx bx-trash fs-5"></i>
                    </button>

                    <div class="remark-body">
                        <div class="form-group">
                            <label class="form-label small mb-1">Operation</label>
                            <select name="products[${idx}][remarks][${rIdx}][operation]" class="form-select">
                                <option value="">— Select —</option>
                                <option value="artist">To Artist</option>
                                <option value="printing">To Printing</option>
                                <option value="furnishing">To Furnishing</option>
                                <option value="installation">To Installation</option>
                                <option value="self_pickup">To Self Pickup</option>
                                <option value="courier">To Courier</option>
                            </select>
                        </div>

                        <div class="form-group mt-2">
                            <label class="form-label small mb-1">Remark</label>
                            <input type="text"
                                name="products[${idx}][remarks][${rIdx}][remark]"
                                class="form-control"
                                placeholder="Write a note…">
                        </div>
                    </div>
                </div>
            `);

            isDirty = true;
        });

        // ——————————————————— Existing Attachment Delete ———————————————————
        $(document).on('click', '.existing-attachment button', function () {
            const el = $(this).closest('.existing-attachment');

            Swal.fire({
                title: 'Delete attachment?',
                text: 'This cannot be undone',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                cancelButtonText: 'Cancel'
            }).then(res => {
                if (!res.isConfirmed) return;

                $.ajax({
                    url: el.data('url'),
                    method: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: () => {
                        el.remove();
                        Swal.fire('Deleted!', 'Attachment removed.', 'success');
                    },
                    error: xhr => {
                        Swal.fire('Error', xhr.responseJSON?.message || 'Failed to delete', 'error');
                    }
                });
            });
        });

        // ——————————————————— New Attachments Dropzone ———————————————————
        const attZone = document.getElementById('attachment-dropzone');
        const attInput = attZone.querySelector('input[type=file]');
        const attPreview = document.getElementById('attachment-preview');

        attZone.addEventListener('click', e => {
            if (e.target === attZone || e.target.closest('.attach-inner')) attInput.click();
        });

        ['dragover', 'dragenter'].forEach(ev => attZone.addEventListener(ev, e => {
            e.preventDefault(); attZone.classList.add('drag-over');
        }));
        ['dragleave', 'drop'].forEach(ev => attZone.addEventListener(ev, e => {
            e.preventDefault(); attZone.classList.remove('drag-over');
        }));

        attZone.ondrop = e => handleNewFiles(e.dataTransfer.files);
        attInput.onchange = () => handleNewFiles(attInput.files);

        function handleNewFiles(files) {
            [...files].forEach(file => {
                const ext = file.name.split('.').pop().toLowerCase();
                const allowed = ['pdf','jpg','jpeg','png','ai','psd','eps','svg','tiff','indd', 'xls', 'xlsx', 'csv'];
                if (file.size > 50*1024*1024) return Swal.fire('Error', `${file.name} > 50MB`, 'error');
                if (!allowed.includes(ext)) return Swal.fire('Error', `${file.name} not allowed`, 'error');

                dt.items.add(file);
                selectedFiles.push(file);

                const div = document.createElement('div');
                div.className = 'd-flex justify-content-between align-items-center border rounded p-2 mb-2 bg-light';
                div.innerHTML = `<span><i class="bx bx-paperclip"></i> ${file.name} (${(file.size/1024/1024).toFixed(1)} MB)</span>
                                 <button type="button" class="btn btn-sm text-danger">×</button>`;
                div.querySelector('button').onclick = () => {
                    selectedFiles = selectedFiles.filter(f => f !== file);
                    dt.items.clear();
                    selectedFiles.forEach(f => dt.items.add(f));
                    attInput.files = dt.files;
                    div.remove();
                };
                attPreview.appendChild(div);
            });
            attInput.files = dt.files;
        }

        // ——————————————————— Form Submit Validation ———————————————————
        $('#order-form').on('submit', function (e) {
            clearValidationErrors();
            const errors = [];


            // ✅ detect which submit button clicked (draft / final)
            const submitter = e.originalEvent?.submitter || document.activeElement;
            const saveType = (submitter && submitter.name === 'save_type')
            ? submitter.value
            : 'final';


            const isDraftClick = (saveType === 'draft');


            // ✅ only allow skipping validation if this order is the "draft-state" you defined
            const canSkipValidation = @json($order->orderStatus === 'in_progress' && is_null($order->artist_id) && (int)$order->draft === 1);


            if (isDraftClick && canSkipValidation) {
            isDirty = false; // allow submit
            return true; // ✅ skip everything
            }


            // ==========================
            // ✅ Normal validation (Update Order)
            // ==========================
            const leadId = $('#lead_id').val();
            if (!leadId) {
            $('#lead-error').text('Lead selection is required').show();
            errors.push('Lead selection is required');
            }


            if (!$('input[name="orderTitle"]').val().trim()) {
            errors.push('Job title is required');
            $('input[name="orderTitle"]').addClass('is-invalid');
            }


            if (!$('input[name="deadline"]').val()) {
            errors.push('Deadline is required');
            $('input[name="deadline"]').addClass('is-invalid');
            }


            if (!$('input[name="approval"]:checked').length) {
            errors.push('Approval selection is required');
            $('input[name="approval"]').addClass('is-invalid');
            }


            const products = $('#product-table tbody tr');
            if (products.length === 0) {
            $('#products-error').text('At least one product is required').show();
            errors.push('At least one product is required');
            } else {
            products.each(function (idx) {
            const productErrors = [];
            const productName = $(this).find('input[name$="[product_name]"]').val().trim();
            const quantityInput = $(this).find('input[name$="[quantity]"]');
            const quantityStr = quantityInput.val().trim();
            const quantity = parseInt(quantityStr, 10);


            if (!productName) {
            $(this).find('input[name$="[product_name]"]').addClass('is-invalid');
            productErrors.push('Product name is required');
            }


            if (!quantityStr || isNaN(quantity) || quantity < 1) {
            quantityInput.addClass('is-invalid');
            productErrors.push('Quantity must be at least 1');
            }

            const materialInput = $(this).find('input[name$="[material_remark]"]');
            const materialRemark = (materialInput.val() || '').trim();

            if (!materialRemark) {
                materialInput.addClass('is-invalid');
                productErrors.push('Material remark is required');
            }

            // ✅ Delivery breakdown validation
            const deliveries = $(this).find('.delivery-row');

            if (deliveries.length === 0) {
                productErrors.push('Delivery breakdown is required');
            } else {
                deliveries.each(function(i){

                    const method = $(this).find('select').val();
                    const location = $(this).find('input[name$="[location]"]').val().trim();
                    const datetime = $(this).find('input[name$="[datetime]"]').val();

                    if (!method || !location || !datetime) {
                        productErrors.push(`Delivery ${i+1} requires Method, Location and Date & Time`);
                    }

                });
            }

            const remarks = $(this).find('.remark-row');
            const operations = [];
            remarks.each(function () {
            const select = $(this).find('select');
            const input = $(this).find('input');
            const operation = select.val();
            const remark = input.val().trim();
            const opText = select.find('option:selected').text();


            if (operation && !remark) {
            input.addClass('is-invalid');
            productErrors.push(`Remark text required for "${opText}"`);
            }


            if (operation) {
            if (operations.includes(operation)) {
            select.addClass('is-invalid');
            productErrors.push(`Duplicate operation: "${opText}"`);
            }
            operations.push(operation);
            }
            });


            if (productErrors.length > 0) {
            errors.push(`Product ${idx + 1}: ${productErrors.join(', ')}`);
            }
            });
            }


            // Attachment validation
            const hasExisting = $('.existing-attachment').length > 0;


            if (!hasExisting && selectedFiles.length === 0) {
            errors.push('At least one attachment is required');
            attZone.classList.add('border-danger');
            setTimeout(() => attZone.classList.remove('border-danger'), 3000);
            }


            if (errors.length) {
            e.preventDefault();
            Swal.fire({
            title: 'Please fix the following errors',
            html: '<ul class="text-start mb-0">' + errors.map(e => `<li>${e}</li>`).join('') + '</ul>',
            icon: 'error'
            });
            } else {
            isDirty = false;
            }
            });

        function clearValidationErrors() {
            $('.is-invalid').removeClass('is-invalid');
            $('.validation-msg').empty();
        }

        function validateProductForm() {
            clearValidationErrors();
            const errors = [];

            if (!$('#product_name').val().trim()) {
                errors.push('Product name is required');
                $('#product_name').addClass('is-invalid');
            }

            if (!$('#quantity').val() || $('#quantity').val() < 1) {
                errors.push('Quantity must be ≥ 1');
                $('#quantity').addClass('is-invalid');
            }

            if (!$('#material_remark').val().trim()) {
                errors.push('Material remark is required');
                $('#material_remark').addClass('is-invalid');
            }

            // ✅ Delivery Breakdown Validation
            const deliveries = $('#modal-deliveries-container .delivery-row');

            if (deliveries.length === 0) {
                errors.push('At least one delivery breakdown is required');
            } else {
                deliveries.each(function(i){

                    const method = $(this).find('select').val();
                    const location = $(this).find('input[type="text"]').val().trim();
                    const datetime = $(this).find('input[type="datetime-local"]').val();

                    if (!method || !location || !datetime) {
                        errors.push(`Delivery ${i+1}: Method, Location and Date & Time are required`);
                    }

                });
            }

            return errors;
        }
    });
       
    function cancelOrder() {
        if (!isDirty) return history.back();
        Swal.fire({
            title: 'Unsaved changes',
            text: 'Leave without saving?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Leave',
            cancelButtonText: 'Stay'
        }).then(r => r.isConfirmed && history.back());
    }
</script>
@endpush

@endsection