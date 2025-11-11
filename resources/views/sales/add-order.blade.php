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
</style>
@endpush

<form id="order-form" action="{{ route('orders.store') }}" method="POST" enctype="multipart/form-data">
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
                                    <div class="col-12 mb-3">
                                        <label class="form-label">Search Lead <span class="text-danger">*</span></label>
                                        <select id="leadSelect" class="form-select" style="width: 100%;">
                                            <option value="">Search for a lead</option>
                                        </select>
                                        <div id="lead-error" class="validation-msg"></div>
                                    </div>
                                    @endif
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Company Name</label>
                                            <input id="companyDisplay" type="text" class="form-control" value="{{ $lead->company_name ?? '' }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Lead Name</label>
                                            <input id="leadNameDisplay" type="text" class="form-control" value="{{ $lead->name ?? '' }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Phone</label>
                                            <input id="phoneDisplay" type="text" class="form-control" value="{{ $lead->phone ?? '' }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input id="emailDisplay" type="text" class="form-control" value="{{ $lead->email ?? '' }}" readonly>
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
                                            <div id="orderTitle-error" class="validation-msg"></div>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Created Date</label>
                                            <input type="text" class="form-control" value="{{ now()->format('d/m/Y') }}" readonly>
                                            <input type="hidden" name="orderDate" value="{{ now() }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Deadline <span class="text-danger">*</span></label>
                                            <input name="deadline" type="date" class="form-control" value="{{ old('deadline') }}">
                                            <div id="deadline-error" class="validation-msg"></div>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <label class="form-label">Created By</label>
                                            <input type="text" class="form-control" value="{{ Auth::user()->name }}" readonly>
                                            <input type="hidden" name="created_by" value="{{ Auth::user()->id }}">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label d-block mb-4">Design from artist would need client approval <span class="text-danger">*</span></label>
                                            <div class="d-flex gap-4">
                                                <label class="form-check-label">
                                                    <input class="form-check-input me-1" type="radio" name="approval" value="1" {{ old('approval') == 1 ? 'checked' : '' }}> YES
                                                </label>
                                                <label class="form-check-label">
                                                    <input class="form-check-input me-1" type="radio" name="approval" value="0" {{ old('approval') == 0 ? 'checked' : '' }}> NO
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
                                            <th>Material Remark</th>
                                            <th>Remarks</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (old('products', []) as $index => $product)
                                            <tr data-index="{{ $index }}">
                                                <td class="product-number">{{ $loop->iteration }}</td>
                                                <td><input type="text" name="products[{{ $index }}][product_name]" class="form-control" value="{{ $product['product_name'] ?? '' }}"></td>
                                                <td><input type="number" name="products[{{ $index }}][quantity]" class="form-control" value="{{ ltrim($product['quantity'] ?? '', '0') ?: '' }}" min="1"></td>
                                                <td><input type="text" name="products[{{ $index }}][material_remark]" class="form-control" value="{{ $product['material_remark'] ?? '' }}"></td>
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

                            <div class="mt-3">
                                <label class="form-label">Remarks</label>
                                <textarea name="orderDetail" rows="3" class="form-control" placeholder="Remarks">{{ old('orderDetail') }}</textarea>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="bg-body position-sticky bottom-0 border-top py-3 d-flex gap-2 justify-content-end" style="z-index: 10">
                <button type="button" class="btn btn-outline-secondary" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Order</button>
            </div>
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
                            <label>Material Remark</label>
                            <textarea id="material_remark" class="form-control"></textarea>
                        </div>
                        <div class="col-12">
                            <label>Remarks</label>
                            <div id="remarks-container"></div>
                            <button type="button" id="addRemarkBtn" class="btn btn-secondary btn-sm mt-2">Add Remark</button>
                            <div id="remarks-error" class="validation-msg"></div>
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<script>
var isDirty = false;
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
            },
            escapeMarkup: function(markup) {
                return markup;
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

        function escapeHtml(str) {
            return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
        }

        let productIndex = $('#product-table tbody tr').length;
        let isFromCsv = {{ old('from_csv', 0) }};
        if (isFromCsv) {
            $('#addProductBtn').hide();
        } else if (productIndex >= 5) {
            $('#addProductBtn').hide();
        }

        renumberProducts();

        let initialFormState = $('#order-form').serialize();
        let isDirty = false;

        $('#order-form').on('change input', function() {
            if ($('#order-form').serialize() !== initialFormState) {
                isDirty = true;
            }
        });

        window.addEventListener('beforeunload', function (e) {
            if (isDirty) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        let modalData = {}; // Store modal data for validation errors

        $('#productModal').on('show.bs.modal', function(e) {
            const button = $(e.relatedTarget);
            const mode = button.data('mode');
            const index = button.data('index');

            clearValidationErrors();
            $('#productForm')[0].reset();
            $('#product_index').val('');
            $('#remarks-container').empty();
            $('#productModalTitle').text('Add Product');

            if (mode === 'edit' && index !== undefined) {
                $('#productModalTitle').text('Edit Product');
                $('#product_index').val(index);

                const row = $(`#product-table tbody tr[data-index="${index}"]`);
                $('#product_name').val(row.find('input[name$="[product_name]"]').val());
                $('#quantity').val(row.find('input[name$="[quantity]"]').val());
                $('#material_remark').val(row.find('input[name$="[material_remark]"]').val());

                row.find('.remark-row').each(function() {
                    const operation = $(this).find('select').val();
                    const remark = $(this).find('input').val();
                    addRemarkRow(operation, remark);
                });
            } else if (Object.keys(modalData).length > 0) {
                // Restore data after validation error
                $('#product_name').val(modalData.product_name);
                $('#quantity').val(modalData.quantity);
                $('#material_remark').val(modalData.material_remark);
                modalData.remarks.forEach((r, idx) => {
                    setTimeout(() => addRemarkRow(r.operation, r.remark), idx * 50);
                });
                modalData = {};
            }
        });

        $('#addRemarkBtn').on('click', function() {
            if ($('#remarks-container .remark-row').length >= 6) {
                modalData = {
                    product_name: $('#product_name').val() || '',
                    quantity: $('#quantity').val() || '',
                    material_remark: $('#material_remark').val() || '',
                    remarks: []
                };
                $('#remarks-container .remark-row').each(function() {
                    const operation = $(this).find('select').val();
                    const remark = $(this).find('input').val() || '';
                    modalData.remarks.push({ operation, remark });
                });
                $('#productModal').modal('hide');
                setTimeout(() => {
                    Swal.fire({
                        title: 'Max Remarks Reached',
                        text: 'Maximum 6 remarks per product.',
                        icon: 'warning'
                    }).then(() => {
                        $('#productModal').modal('show');
                    });
                }, 500);
                return;
            }
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
            validateRemarks();
        });

        $(document).on('change', '.remark-row select', function() {
            const current = $(this);
            const val = current.val();
            if (!val) return;
            const container = current.closest('#remarks-container') || current.closest('[id^="remarks-container-"]');
            let duplicate = false;
            container.find('.remark-row select').not(current).each(function() {
                if ($(this).val() === val) {
                    duplicate = true;
                }
            });
            if (duplicate) {
                modalData = {
                    product_name: $('#product_name').val() || '',
                    quantity: $('#quantity').val() || '',
                    material_remark: $('#material_remark').val() || '',
                    remarks: []
                };
                container.find('.remark-row').each(function() {
                    const opSelect = $(this).find('select');
                    const remInput = $(this).find('input');
                    const operation = opSelect.val();
                    const remark = remInput.val() || '';
                    modalData.remarks.push({ operation, remark });
                });
                current.val('');
                if ($('#productModal').hasClass('show')) {
                    $('#productModal').modal('hide');
                    setTimeout(() => {
                        Swal.fire({
                            title: 'Duplicate Operation',
                            text: 'This operation is already selected.',
                            icon: 'error'
                        }).then(() => {
                            $('#productModal').modal('show');
                        });
                    }, 500);
                } else {
                    Swal.fire({
                        title: 'Duplicate Operation',
                        text: 'This operation is already selected.',
                        icon: 'error'
                    });
                }
            }
        });

        $('#saveProduct').on('click', function() {
            const errors = validateProductForm();
            if (errors.length > 0) {
                // Store current modal data
                modalData = {
                    product_name: $('#product_name').val() || '',
                    quantity: $('#quantity').val() || '',
                    material_remark: $('#material_remark').val() || '',
                    remarks: []
                };
                $('#remarks-container .remark-row').each(function() {
                    const operation = $(this).find('select').val();
                    const remark = $(this).find('input').val() || '';
                    if (operation && remark.trim()) {
                        modalData.remarks.push({ operation, remark });
                    }
                });

                $('#productModal').modal('hide');
                setTimeout(() => {
                    Swal.fire({
                        title: 'Please complete the product info',
                        html: errors.map(m => `<div style="text-align:left">${m}</div>`).join(''),
                        icon: 'error'
                    }).then(() => {
                        $('#productModal').modal('show');
                    });
                }, 500);
                return;
            }

            const index = $('#product_index').val();
            const data = {
                product_name: $('#product_name').val() || '',
                quantity: ltrim($('#quantity').val() || '', '0') || '',
                material_remark: $('#material_remark').val() || '',
                remarks: []
            };

            $('#remarks-container .remark-row').each(function() {
                const operation = $(this).find('select').val();
                const remark = $(this).find('input').val() || '';
                if (operation && remark.trim()) {
                    data.remarks.push({ operation, remark });
                }
            });

            if (data.remarks.length === 0) {
                data.remarks = [];
            }

            if (index !== '') {
                updateProductRow(index, data);
            } else {
                if (productIndex >= 5) {
                    $('#productModal').modal('hide');
                    setTimeout(() => {
                        Swal.fire({
                            title: 'Maximum Products Reached',
                            text: 'Maximum 5 products allowed. Use CSV for more.',
                            icon: 'warning'
                        }).then(() => {
                            $('#productModal').modal('show');
                        });
                    }, 500);
                    return;
                }
                addProductRow(data, productIndex);
                productIndex++;
                if (productIndex >= 5) {
                    $('#addProductBtn').hide();
                }
            }
            isDirty = true;
            $('#productModal').modal('hide');
        });

        function addProductRow(data, index) {
            const html = `
                <tr data-index="${index}">
                    <td class="product-number">${index + 1}</td>
                    <td><input type="text" name="products[${index}][product_name]" class="form-control" value="${escapeHtml(data.product_name)}" required></td>
                    <td><input type="number" name="products[${index}][quantity]" class="form-control" value="${escapeHtml(data.quantity)}" min="1" required></td>
                    <td><input type="text" name="products[${index}][material_remark]" class="form-control" value="${escapeHtml(data.material_remark)}"></td>
                    <td>
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
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-danger remove-product" data-index="${index}">Delete</button>
                    </td>
                </tr>
            `;
            $('#product-table tbody').append(html);
        }

        function updateProductRow(index, data) {
            const row = $(`#product-table tbody tr[data-index="${index}"]`);
            row.find('td:eq(1)').html(`<input type="text" name="products[${index}][product_name]" class="form-control" value="${escapeHtml(data.product_name)}" required>`);
            row.find('td:eq(2)').html(`<input type="number" name="products[${index}][quantity]" class="form-control" value="${escapeHtml(data.quantity)}" min="1" required>`);
            row.find('td:eq(3)').html(`<input type="text" name="products[${index}][material_remark]" class="form-control" value="${escapeHtml(data.material_remark)}">`);
            row.find('td:eq(4)').html(`
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
            renumberProducts();
        }

        function renumberProducts() {
            $('#product-table tbody tr').each(function(i) {
                $(this).attr('data-index', i);
                $(this).find('.product-number').text(i + 1);
                $(this).find('.edit-product, .remove-product, .add-remark').attr('data-index', i);
                const remarksId = `remarks-container-${i}`;
                $(this).find('[id^="remarks-container-"]').attr('id', remarksId);
                $(this).find('input[name^="products"], select[name^="products"]').each(function() {
                    let name = $(this).attr('name').replace(/\[\d+\]/, '[' + i + ']');
                    $(this).attr('name', name);
                });
            });
        }

        $(document).on('click', '.remove-product', function() {
            const index = $(this).data('index');
            $(`#product-table tbody tr[data-index="${index}"]`).remove();
            renumberProducts();
            productIndex = $('#product-table tbody tr').length;
            if (productIndex < 5 && !isFromCsv) {
                $('#addProductBtn').show();
            }
            isDirty = true;
        });

        $(document).on('click', '.add-remark', function() {
            const index = $(this).data('index');
            const container = $(`#remarks-container-${index}`);
            if (container.find('.remark-row').length >= 6) {
                Swal.fire({
                    title: 'Max Remarks Reached',
                    text: 'Maximum 6 remarks per product.',
                    icon: 'warning'
                });
                return;
            }
            const rindex = container.find('.remark-row').length;
            const html = `
                <div class="remark-row">
                    <select name="products[${index}][remarks][${rindex}][operation]" class="form-select w-auto" style="min-width:160px;">
                        <option value="">— Select —</option>
                        <option value="artist">To Artist</option>
                        <option value="printing">To Printing</option>
                        <option value="furnishing">To Furnishing</option>
                        <option value="installation">To Installation</option>
                        <option value="self_pickup">To Self Pickup</option>
                        <option value="courier">To Courier</option>
                    </select>
                    <input type="text" name="products[${index}][remarks][${rindex}][remark]" class="form-control" placeholder="Write a note…">
                    <button type="button" class="btn btn-link text-danger p-0 remove-remark" title="Delete">
                        <i class="bx bx-trash fs-5"></i>
                    </button>
                </div>
            `;
            container.append(html);
            isDirty = true;
        });

        $(document).on('input', 'input[type="number"]', function() {
            this.value = ltrim(this.value, '0') || '';
        });

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

            listEl.innerHTML = '';

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
            isDirty = true;
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
                    $('#product-table tbody').empty();
                    productIndex = 0;
                    isFromCsv = 0;
                    $('#from_csv').val(0);
                    $('#addProductBtn').show();
                    renumberProducts();
                }
                isDirty = true;
            });

            listEl.appendChild(li);
        }

        function updateSummary() {
            const count = selectedFile ? 1 : 0;
            msgEl.innerHTML = count ?
                `<span class="ok">${count} file selected for upload</span>` :
                '';
        }

        function stripQuotes(str) {
            return str.replace(/^"(.*)"$/, '$1');
        }

        function ltrim(str, char) {
            return str.replace(new RegExp(`^${char}+`), '');
        }

        function parseCsv(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const text = e.target.result;
                const lines = text.split(/\r?\n/);
                const headers = lines[0].split(',').map(h => h.trim());

                $('#product-table tbody').empty();
                productIndex = 0;

                for (let i = 1; i < lines.length; i++) {
                    if (!lines[i].trim()) continue;
                    const data = lines[i].split(',').map(d => stripQuotes(d.trim()));
                    const product = {
                        product_name: data[headers.indexOf('Product_Name')] || '',
                        quantity: ltrim(data[headers.indexOf('Quantity')] || '', '0'),
                        material_remark: data[headers.indexOf('Material_Info')] || '',
                        remarks: []
                    };

                    const remarkColumns = ['Artist_Remark', 'Printing_Remark', 'Furnishing_Remark', 'Installation_Remark', 'Courier_Remark', 'Self_Pickup_Remark'];
                    remarkColumns.forEach((col, idx) => {
                        const remarkIdx = headers.indexOf(col);
                        if (remarkIdx !== -1 && data[remarkIdx]) {
                            product.remarks.push({
                                operation: ['artist', 'printing', 'furnishing', 'installation', 'courier', 'self_pickup'][idx],
                                remark: data[remarkIdx]
                            });
                        }
                    });

                    addProductRow(product, productIndex);
                    productIndex++;
                }

                isFromCsv = 1;
                $('#from_csv').val(1);
                renumberProducts();
                if (productIndex >= 5) {
                    $('#addProductBtn').hide();
                }
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

        function clearValidationErrors() {
            $('.is-invalid').removeClass('is-invalid');
            $('.validation-msg').empty();
        }

        function showValidationError(selector, msg) {
            $(selector).addClass('is-invalid');
            $(selector + '-error').text(msg);
        }

        function validateProductForm() {
            clearValidationErrors();
            const errors = [];

            const productName = $('#product_name').val().trim();
            if (!productName) {
                showValidationError('#product_name', 'Product name is required');
                errors.push('Product name is required');
            }

            const quantityStr = $('#quantity').val().trim();
            const quantity = parseInt(quantityStr, 10);
            if (!quantityStr || isNaN(quantity) || quantity < 1) {
                showValidationError('#quantity', 'Quantity must be at least 1');
                errors.push('Quantity must be at least 1');
            }

            const remarks = $('#remarks-container .remark-row');
            const operations = [];
            remarks.each(function() {
                const select = $(this).find('select');
                const input = $(this).find('input');
                const operation = select.val();
                const remark = input.val().trim();
                const opText = select.find('option:selected').text();
                if (operation && !remark) {
                    input.addClass('is-invalid');
                    errors.push(`Remark text required for "${opText}"`);
                }
                if (operation) {
                    if (operations.includes(operation)) {
                        select.addClass('is-invalid');
                        errors.push(`Duplicate operation: "${opText}"`);
                    }
                    operations.push(operation);
                }
            });

            return errors;
        }

        function validateRemarks() {
            clearValidationErrors();
            const errors = [];
            $('#remarks-container .remark-row').each(function() {
                const operation = $(this).find('select').val();
                const remark = $(this).find('input').val().trim();
                if (operation && !remark) {
                    $(this).find('input').addClass('is-invalid');
                    errors.push('Remark text required');
                }
            });
            return errors.length === 0;
        }

        $('#order-form').on('submit', function(e) {
            clearValidationErrors();
            const errors = [];

            if (!$('#lead_id').val() && !{{ $lead ? 1 : 0 }}) {
                showValidationError('#leadSelect', 'Lead selection is required');
                errors.push('Lead selection is required');
            }

            const orderTitle = $('input[name="orderTitle"]').val().trim();
            if (!orderTitle) {
                showValidationError('input[name="orderTitle"]', 'Job title is required');
                errors.push('Job title is required');
            }

            const deadline = $('input[name="deadline"]').val();
            if (!deadline) {
                showValidationError('input[name="deadline"]', 'Deadline is required');
                errors.push('Deadline is required');
            }

            const approval = $('input[name="approval"]:checked').length;
            if (!approval) {
                showValidationError('input[name="approval"]', 'Approval selection is required');
                errors.push('Approval selection is required');
            }

            const products = $('#product-table tbody tr');
            if (products.length === 0) {
                $('#products-error').text('At least one product is required').show();
                errors.push('At least one product is required');
            } else {
                products.each(function(idx) {
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
                    const remarks = $(this).find('.remark-row');
                    const operations = [];
                    remarks.each(function() {
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

            if (errors.length > 0) {
                e.preventDefault();
                Swal.fire({
                    title: 'Please fix the following errors',
                    html: '<div style="text-align:left;"><ul><li>' + errors.join('</li><li>') + '</li></ul></div>',
                    icon: 'error',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                });
            } else {
                isDirty = false;
            }
        });
    });

    function cancelOrder() {
        if (!isDirty) {
            history.back();
            return;
        }
        Swal.fire({
            title: 'Unsaved Changes',
            html: 'You have unsaved changes. If you leave now, your changes will be lost.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Leave',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                history.back();
            }
        });
    }
</script>
@endpush

@endsection