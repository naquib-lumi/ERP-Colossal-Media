@extends('layouts.app')

@section('content')
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

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

    .read-only td:last-child {
        display: none;
    }
</style>
@endpush

<form id="order-form" action="{{ route('artist.orders.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="lead_id" id="lead_id" value="{{ $lead->id ?? '' }}">
    <input type="hidden" id="from_csv" name="from_csv" value="{{ old('from_csv', 0) }}">

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
                                        <label for="lead_id" class="form-label">Search lead...</label>
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
                                            <label class="form-label">Job Title</label>
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
                                            <label class="form-label">Deadline</label>
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
                                            <th>Product Name</th>
                                            <th>Quantity</th>
                                            <th>Remark</th>
                                            <th>Material Info</th>
                                            <th>Location</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (old('products', []) as $index => $product)
                                            <tr data-index="{{ $index }}" {{ old('from_csv') ? 'class="read-only"' : '' }}>
                                                <td>{!! e($product['product_name'] ?? '') !!}</td>
                                                <td>{!! e($product['quantity'] ?? '') !!}</td>
                                                <td>{!! e($product['remark'] ?? '') !!}</td>
                                                <td>{!! e($product['material_info'] ?? '') !!}</td>
                                                <td>{!! e($product['location'] ?? '') !!}</td>
                                                <td>{!! e($product['date_time'] ?? '') !!}</td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary edit-product" data-bs-toggle="modal" data-bs-target="#productModal" data-mode="edit">Edit</button>
                                                    <button type="button" class="btn btn-sm btn-danger remove-product">Delete</button>
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
                            <div id="attach-box" class="attach-box">
                                <div class="attach-inner">
                                    <div class="attach-icon" aria-hidden="true">
                                        <i class="bx bx-upload display-6 mb-2 d-block justify-content-between align-items-center" style="pointer-events:none"></i>
                                    </div>
                                    <div class="attach-title">Drop CSV file here or click to upload</div>
                                    <div class="attach-hint">(CSV)</div>
                                </div>

                                <!-- This input sits on top, invisible, and owns the click -->
                                <input id="fileInput" type="file"
                                    accept=".csv"
                                    class="file-overlay">
                            </div>
                            @error('csv_file')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror

                            <div id="attach-msg" class="mt-2 text-sm"></div>
                            <ul id="preview" class="mt-3 space-y-2"></ul>

                            <!-- Remarks -->
                            <div class="mt-3">
                                <label class="form-label">Remarks</label>
                                <textarea name="orderDetail" rows="3" class="form-control" placeholder="Remarks">{{ old('orderDetail') }}</textarea>
                                @error('orderDetail')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
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

                </div>
            </div>

        </div>

        {{-- Sticky save bar --}}
        <div class="col-12">
            <div class="bg-body position-sticky bottom-0 border-top py-3 d-flex gap-2 justify-content-end" style="z-index: 10">
                <button type="button" class="btn btn-outline-secondary" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Order</button>
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
            <input type="hidden" name="products[{{ $index }}][location]" value="{{ $product['location'] ?? '' }}">
            <input type="hidden" name="products[{{ $index }}][date_time]" value="{{ $product['date_time'] ?? '' }}">
        </div>
    @endforeach
</div>
</form>



<!-- Product Modal (for add/edit) -->
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
                            <label>Product Name</label>
                            <input id="product_name" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label>Quantity</label>
                            <input id="quantity" type="number" class="form-control">
                        </div>
                        <div class="col-12">
                            <label>Remark</label>
                            <textarea id="remark" class="form-control"></textarea>
                        </div>
                        <div class="col-12">
                            <label>Material Info</label>
                            <textarea id="material_info" class="form-control"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label>Location</label>
                            <input id="location" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label>Date</label>
                            <input id="date_time" type="date" class="form-control">
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
    $(function () {
    // 1) Pick the LAST #lead_id on the page (the one inside the Lead card),
    //    and remove any earlier duplicates (the stray top bar).
    const $candidates = $('select#lead_id');
    const $leadSel = $candidates.last();
    $candidates.not($leadSel).remove();

    // 2) Initialize Select2 on the kept element only
    $leadSel.select2({
        placeholder: 'Search lead…',
        allowClear: true,
        width: '100%',
        minimumInputLength: 2,
        dropdownParent: $leadSel.closest('.card, .modal, form'), // keeps the dropdown in the card
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

    // 3) Autofill fields on pick
    const set = ($el, v) => $el.length && $el.val(v || '').trigger('input').trigger('change');
    const $company = $('[name="companyName"], [name="company_name"], #companyName, #company_name').first();
    const $leadNm  = $('[name="leadName"],    [name="name"],         #leadName,    #name').first();
    const $phone   = $('[name="leadPhone"],   [name="phone"],        #leadPhone,   #phone').first();
    const $email   = $('[name="leadEmail"],   [name="email"],        #leadEmail,   #email').first();

    $leadSel.on('select2:select', function (e) {
        const d = e.params.data || {};
        const m = d.meta || {};
        if (m.company_name || m.phone || m.email) {
        set($leadNm,  d.text);
        set($company, m.company_name);
        set($phone,   m.phone);
        set($email,   m.email);
        } else {
        // fallback fetch if meta missing
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
        set($leadNm,''); set($company,''); set($phone,''); set($email,'');
    });
    });

    $(document).ready(function() {
        function escapeHtml(str) {
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        let productIndex = $('#product-table tbody tr').length;
        let isFromCsv = {{ old('from_csv', 0) }};
        if (isFromCsv) {
            $('#addProductBtn').hide();
            $('#product-table tbody tr').addClass('read-only');
        } else if (productIndex >= 5) {
            $('#addProductBtn').hide();
        }

        $('#productModal').on('show.bs.modal', function(e) {
            const button = $(e.relatedTarget);
            const mode = button.data('mode');
            const index = button.closest('tr').data('index');

            $('#productForm')[0].reset();
            $('#product_index').val('');
            $('#productModalTitle').text('Add Product');

            if (mode === 'edit') {
                $('#productModalTitle').text('Edit Product');
                $('#product_index').val(index);

                const hidden = $(`#hidden-products > div[data-index="${index}"]`);
                $('#product_name').val(hidden.find('input[name$="[product_name]"]').val());
                $('#quantity').val(hidden.find('input[name$="[quantity]"]').val());
                $('#remark').val(hidden.find('input[name$="[remark]"]').val());
                $('#material_info').val(hidden.find('input[name$="[material_info]"]').val());
                $('#location').val(hidden.find('input[name$="[location]"]').val());
                $('#date_time').val(hidden.find('input[name$="[date_time]"]').val());
            }
        });

        $('#saveProduct').on('click', function() {
            const index = $('#product_index').val();
            const data = {
                product_name: $('#product_name').val() || '',
                quantity: $('#quantity').val() || '',
                remark: $('#remark').val() || '',
                material_info: $('#material_info').val() || '',
                location: $('#location').val() || '',
                date_time: $('#date_time').val() || ''
            };

            if (index !== '') {
                // Edit existing
                const row = $(`#product-table tbody tr[data-index="${index}"]`);
                row.find('td:eq(0)').html(escapeHtml(data.product_name));
                row.find('td:eq(1)').html(escapeHtml(data.quantity));
                row.find('td:eq(2)').html(escapeHtml(data.remark));
                row.find('td:eq(3)').html(escapeHtml(data.material_info));
                row.find('td:eq(4)').html(escapeHtml(data.location));
                row.find('td:eq(5)').html(escapeHtml(data.date_time));

                const hidden = $(`#hidden-products > div[data-index="${index}"]`);
                hidden.find('input[name$="[product_name]"]').val(data.product_name);
                hidden.find('input[name$="[quantity]"]').val(data.quantity);
                hidden.find('input[name$="[remark]"]').val(data.remark);
                hidden.find('input[name$="[material_info]"]').val(data.material_info);
                hidden.find('input[name$="[location]"]').val(data.location);
                hidden.find('input[name$="[date_time]"]').val(data.date_time);
            } else {
                // Add new
                if (productIndex >= 5) {
                    alert('Maximum 5 products allowed. Use CSV for more.');
                    return;
                }

                const html = `
                    <tr data-index="${productIndex}">
                        <td>${escapeHtml(data.product_name)}</td>
                        <td>${escapeHtml(data.quantity)}</td>
                        <td>${escapeHtml(data.remark)}</td>
                        <td>${escapeHtml(data.material_info)}</td>
                        <td>${escapeHtml(data.location)}</td>
                        <td>${escapeHtml(data.date_time)}</td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary edit-product" data-bs-toggle="modal" data-bs-target="#productModal" data-mode="edit">Edit</button>
                            <button type="button" class="btn btn-sm btn-danger remove-product">Delete</button>
                        </td>
                    </tr>
                `;
                $('#product-table tbody').append(html);

                const hiddenHtml = `
                    <div data-index="${productIndex}">
                        <input type="hidden" name="products[${productIndex}][product_name]" value="${escapeHtml(data.product_name)}">
                        <input type="hidden" name="products[${productIndex}][quantity]" value="${escapeHtml(data.quantity)}">
                        <input type="hidden" name="products[${productIndex}][remark]" value="${escapeHtml(data.remark)}">
                        <input type="hidden" name="products[${productIndex}][material_info]" value="${escapeHtml(data.material_info)}">
                        <input type="hidden" name="products[${productIndex}][location]" value="${escapeHtml(data.location)}">
                        <input type="hidden" name="products[${productIndex}][date_time]" value="${escapeHtml(data.date_time)}">
                    </div>
                `;
                $('#hidden-products').append(hiddenHtml);

                productIndex++;
                if (productIndex >= 5) {
                    $('#addProductBtn').hide();
                }
            }

            $('#productModal').modal('hide');
        });

        $(document).on('click', '.remove-product', function() {
            const row = $(this).closest('tr');
            const index = row.data('index');
            row.remove();
            $(`#hidden-products > div[data-index="${index}"]`).remove();

            // Reindex
            $('#product-table tbody tr').each(function(i) {
                $(this).attr('data-index', i);
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

        // CSV upload handling
        const input = document.getElementById('fileInput');
        const listEl = document.getElementById('preview');
        const msgEl = document.getElementById('attach-msg');

        const ALLOWED = ['csv'];

        let selectedFile = null;

        input.addEventListener('change', handleCsvUpload);

        function handleCsvUpload() {
            if (!input.files?.length) return;
            const f = input.files[0];

            const ext = (f.name.split('.').pop() || '').toLowerCase();

            const errors = [];
            if (!ALLOWED.includes(ext)) errors.push('Invalid file type');

            listEl.innerHTML = ''; // Clear previous

            if (errors.length) {
                addRow(f, { status: 'error', note: errors.join(', ') });
                selectedFile = null;
            } else {
                selectedFile = f;
                addRow(f, { status: 'ready' });
                parseCsv(f);
            }

            updateSummary();
            input.value = '';
        }

        function addRow(file, { status = 'ready', note = '' }) {
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
                selectedFile = null;
                updateSummary();
                if (!selectedFile) {
                    // Clear products when removing CSV
                    $('#product-table tbody').empty();
                    $('#hidden-products').empty();
                    productIndex = 0;
                    isFromCsv = 0;
                    $('#from_csv').val(0);
                    $('#addProductBtn').show();
                }
            });

            listEl.appendChild(li);
        }

        function updateSummary() {
            const count = selectedFile ? 1 : 0;
            msgEl.innerHTML = count ?
                `<span class="ok">${count} file selected for upload</span>` :
                '';
        }

        function parseCsv(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const text = e.target.result;
                const lines = text.split(/\r?\n/);
                const headers = lines[0].split(',').map(h => h.trim());

                // Clear existing
                $('#product-table tbody').empty();
                $('#hidden-products').empty();
                productIndex = 0;

                for (let i = 1; i < lines.length; i++) {
                    if (!lines[i].trim()) continue;
                    const data = lines[i].split(',').map(d => d.trim());
                    const product = {
                        product_name: data[0] || '',
                        quantity: data[1] || '',
                        remark: data[2] || '',
                        material_info: data[3] || '',
                        location: data[4] || '',
                        date_time: data[5] || ''
                    };

                    const html = `
                        <tr data-index="${productIndex}" class="read-only">
                            <td>${escapeHtml(product.product_name)}</td>
                            <td>${escapeHtml(product.quantity)}</td>
                            <td>${escapeHtml(product.remark)}</td>
                            <td>${escapeHtml(product.material_info)}</td>
                            <td>${escapeHtml(product.location)}</td>
                            <td>${escapeHtml(product.date_time)}</td>
                            <td></td>
                        </tr>
                    `;
                    $('#product-table tbody').append(html);

                    const hiddenHtml = `
                        <div data-index="${productIndex}">
                            <input type="hidden" name="products[${productIndex}][product_name]" value="${escapeHtml(product.product_name)}">
                            <input type="hidden" name="products[${productIndex}][quantity]" value="${escapeHtml(product.quantity)}">
                            <input type="hidden" name="products[${productIndex}][remark]" value="${escapeHtml(product.remark)}">
                            <input type="hidden" name="products[${productIndex}][material_info]" value="${escapeHtml(product.material_info)}">
                            <input type="hidden" name="products[${productIndex}][location]" value="${escapeHtml(product.location)}">
                            <input type="hidden" name="products[${productIndex}][date_time]" value="${escapeHtml(product.date_time)}">
                        </div>
                    `;
                    $('#hidden-products').append(hiddenHtml);

                    productIndex++;
                }

                isFromCsv = 1;
                $('#from_csv').val(1);
                $('#addProductBtn').hide();
            };
            reader.readAsText(file);
        }

        const box = document.getElementById('attach-box');
        if (box) {
            ['dragenter', 'dragover'].forEach(evt =>
                box.addEventListener(evt, e => {
                    e.preventDefault();
                    box.classList.add('ring');
                })
            );
            ['dragleave', 'drop'].forEach(evt =>
                box.addEventListener(evt, e => {
                    e.preventDefault();
                    box.classList.remove('ring');
                })
            );
            box.addEventListener('drop', e => {
                input.files = e.dataTransfer.files;
                handleCsvUpload();
            });
        }
    });
</script>
@endpush

@endsection