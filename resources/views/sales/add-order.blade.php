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
                                        <label class="form-label">Search Lead</label>
                                        <select id="leadSelect" class="form-select" style="width: 100%;">
                                            <option value="">Search for a lead</option>
                                        </select>
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
                                            <th>Material Remark</th>
                                            <th>Remarks</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (old('products', []) as $index => $product)
                                            <tr data-index="{{ $index }}">
                                                <td><input type="text" name="products[{{ $index }}][product_name]" class="form-control" value="{{ $product['product_name'] ?? '' }}"></td>
                                                <td><input type="number" name="products[{{ $index }}][quantity]" class="form-control" value="{{ $product['quantity'] ?? '' }}"></td>
                                                <td><input type="text" name="products[{{ $index }}][material_info]" class="form-control" value="{{ $product['material_info'] ?? '' }}"></td>
                                                <td>
                                                    <div id="remarks-container-{{ $index }}">
                                                        @foreach ($product['remarks'] ?? [] as $rindex => $remark)
                                                            <div class="remark-row">
                                                                <select name="products[{{ $index }}][remarks][{{ $rindex }}][operation]" class="form-select w-auto" style="min-width:160px;">
                                                                    <option value="printing" {{ $remark['operation'] == 'printing' ? 'selected' : '' }}>Printing</option>
                                                                    <option value="furnishing" {{ $remark['operation'] == 'furnishing' ? 'selected' : '' }}>Furnishing</option>
                                                                    <option value="installation" {{ $remark['operation'] == 'installation' ? 'selected' : '' }}>Installation</option>
                                                                    <option value="self_pickup" {{ $remark['operation'] == 'self_pickup' ? 'selected' : '' }}>Self Pickup</option>
                                                                    <option value="courier" {{ $remark['operation'] == 'courier' ? 'selected' : '' }}>Courier</option>
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
                                                    <button type="button" class="btn btn-sm btn-primary edit-product" data-bs-toggle="modal" data-bs-target="#productModal" data-mode="edit" data-index="{{ $index }}">Edit</button>
                                                    <button type="button" class="btn btn-sm btn-danger remove-product" data-index="{{ $index }}">Delete</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
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
                            @error('csv_file')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror
                            <div id="attach-msg" class="mt-2 text-sm"></div>
                            <ul id="preview" class="mt-3 space-y-2"></ul>

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
                            <label>Product Name</label>
                            <input id="product_name" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label>Quantity</label>
                            <input id="quantity" type="number" class="form-control">
                        </div>
                        <div class="col-12">
                            <label>Material Remark</label>
                            <textarea id="material_info" class="form-control"></textarea>
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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
                    $('#lead_id').val(lead.id);
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

        $('#productModal').on('show.bs.modal', function(e) {
            const button = $(e.relatedTarget);
            const mode = button.data('mode');
            const index = button.data('index');

            $('#productForm')[0].reset();
            $('#product_index').val('');
            $('#remarks-container').empty();
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
                        <option value="printing" ${operation === 'printing' ? 'selected' : ''}>Printing</option>
                        <option value="furnishing" ${operation === 'furnishing' ? 'selected' : ''}>Furnishing</option>
                        <option value="installation" ${operation === 'installation' ? 'selected' : ''}>Installation</option>
                        <option value="self_pickup" ${operation === 'self_pickup' ? 'selected' : ''}>Self Pickup</option>
                        <option value="courier" ${operation === 'courier' ? 'selected' : ''}>Courier</option>
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

        $('#saveProduct').on('click', function() {
            const index = $('#product_index').val();
            const data = {
                product_name: $('#product_name').val() || '',
                quantity: $('#quantity').val() || '',
                material_info: $('#material_info').val() || '',
                remarks: []
            };

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
                row.find('td:eq(1)').html(`<input type="number" name="products[${index}][quantity]" class="form-control" value="${escapeHtml(data.quantity)}">`);
                row.find('td:eq(2)').html(`<input type="text" name="products[${index}][material_info]" class="form-control" value="${escapeHtml(data.material_info)}">`);
                row.find('td:eq(3)').html(`
                    <div id="remarks-container-${index}">
                        ${data.remarks.map((r, rindex) => `
                            <div class="remark-row">
                                <select name="products[${index}][remarks][${rindex}][operation]" class="form-select w-auto" style="min-width:160px;">
                                    <option value="printing" ${r.operation === 'printing' ? 'selected' : ''}>Printing</option>
                                    <option value="furnishing" ${r.operation === 'furnishing' ? 'selected' : ''}>Furnishing</option>
                                    <option value="installation" ${r.operation === 'installation' ? 'selected' : ''}>Installation</option>
                                    <option value="self_pickup" ${r.operation === 'self_pickup' ? 'selected' : ''}>Self Pickup</option>
                                    <option value="courier" ${r.operation === 'courier' ? 'selected' : ''}>Courier</option>
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

                const html = `
                    <tr data-index="${productIndex}">
                        <td><input type="text" name="products[${productIndex}][product_name]" class="form-control" value="${escapeHtml(data.product_name)}"></td>
                        <td><input type="number" name="products[${productIndex}][quantity]" class="form-control" value="${escapeHtml(data.quantity)}"></td>
                        <td><input type="text" name="products[${productIndex}][material_info]" class="form-control" value="${escapeHtml(data.material_info)}"></td>
                        <td>
                            <div id="remarks-container-${productIndex}">
                                ${data.remarks.map((r, rindex) => `
                                    <div class="remark-row">
                                        <select name="products[${productIndex}][remarks][${rindex}][operation]" class="form-select w-auto" style="min-width:160px;">
                                            <option value="printing" ${r.operation === 'printing' ? 'selected' : ''}>Printing</option>
                                            <option value="furnishing" ${r.operation === 'furnishing' ? 'selected' : ''}>Furnishing</option>
                                            <option value="installation" ${r.operation === 'installation' ? 'selected' : ''}>Installation</option>
                                            <option value="self_pickup" ${r.operation === 'self_pickup' ? 'selected' : ''}>Self Pickup</option>
                                            <option value="courier" ${r.operation === 'courier' ? 'selected' : ''}>Courier</option>
                                        </select>
                                        <input type="text" name="products[${productIndex}][remarks][${rindex}][remark]" class="form-control" value="${escapeHtml(r.remark)}" placeholder="Write a note…">
                                        <button type="button" class="btn btn-link text-danger p-0 remove-remark" title="Delete">
                                            <i class="bx bx-trash fs-5"></i>
                                        </button>
                                    </div>
                                `).join('')}
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm mt-2 add-remark" data-index="${productIndex}">Add Remark</button>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary edit-product" data-bs-toggle="modal" data-bs-target="#productModal" data-mode="edit" data-index="${productIndex}">Edit</button>
                            <button type="button" class="btn btn-sm btn-danger remove-product" data-index="${productIndex}">Delete</button>
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

            $('#productModal').modal('hide');
        });

        $(document).on('click', '.remove-product', function() {
            const index = $(this).data('index');
            $(`#product-table tbody tr[data-index="${index}"]`).remove();
            $(`#hidden-products > div[data-index="${index}"]`).remove();

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
            const remarks = container.find('.remark-row');
            const rindex = remarks.length;
            const html = `
                <div class="remark-row">
                    <select name="products[${index}][remarks][${rindex}][operation]" class="form-select w-auto" style="min-width:160px;">
                        <option value="">— Select —</option>
                        <option value="printing">Printing</option>
                        <option value="furnishing">Furnishing</option>
                        <option value="installation">Installation</option>
                        <option value="self_pickup">Self Pickup</option>
                        <option value="courier">Courier</option>
                    </select>
                    <input type="text" name="products[${index}][remarks][${rindex}][remark]" class="form-control" placeholder="Write a note…">
                    <button type="button" class="btn btn-link text-danger p-0 remove-remark" title="Delete">
                        <i class="bx bx-trash fs-5"></i>
                    </button>
                </div>
            `;
            container.append(html);
        });

        $(document).on('click', '.remove-remark', function() {
            $(this).closest('.remark-row').remove();
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

        function stripQuotes(str) {
            return str.replace(/^"(.*)"$/, '$1');
        }

        function parseCsv(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const text = e.target.result;
                const lines = text.split(/\r?\n/);
                const headers = lines[0].split(',').map(h => h.trim()); // Preserve exact case

                $('#product-table tbody').empty();
                $('#hidden-products').empty();
                productIndex = 0;

                for (let i = 1; i < lines.length; i++) {
                    if (!lines[i].trim()) continue;
                    const data = lines[i].split(',').map(d => stripQuotes(d.trim()));
                    console.log('Headers:', headers); // Debug: Check headers
                    console.log('Data:', data); // Debug: Check data for each row
                    const product = {
                        product_name: data[headers.indexOf('Product_Name')] || '',
                        quantity: data[headers.indexOf('Quantity')] || '',
                        material_info: data[headers.indexOf('Material_Info')] || '',
                        remarks: []
                    };

                    // Populate remarks
                    const remarkColumns = ['Printing_Remark', 'Furnishing_Remark', 'Installation_Remark', 'Courier_Remark', 'Self_Pickup_Remark'];
                    remarkColumns.forEach((col, idx) => {
                        const remarkIdx = headers.indexOf(col);
                        if (remarkIdx !== -1 && data[remarkIdx]) {
                            product.remarks.push({
                                operation: ['printing', 'furnishing', 'installation', 'courier', 'self_pickup'][idx],
                                remark: data[remarkIdx]
                            });
                        }
                    });
                    console.log('productname: '+ product.product_name);
                    const html = `
                        <tr data-index="${productIndex}">
                            <td><input type="text" name="products[${productIndex}][product_name]" class="form-control" value="${escapeHtml(product.product_name)}"></td>
                            <td><input type="number" name="products[${productIndex}][quantity]" class="form-control" value="${escapeHtml(product.quantity)}"></td>
                            <td><input type="text" name="products[${productIndex}][material_info]" class="form-control" value="${escapeHtml(product.material_info)}"></td>
                            <td>
                                <div id="remarks-container-${productIndex}">
                                    ${product.remarks.map((r, rindex) => `
                                        <div class="remark-row">
                                            <select name="products[${productIndex}][remarks][${rindex}][operation]" class="form-select w-auto" style="min-width:160px;">
                                                <option value="printing" ${r.operation === 'printing' ? 'selected' : ''}>Printing</option>
                                                <option value="furnishing" ${r.operation === 'furnishing' ? 'selected' : ''}>Furnishing</option>
                                                <option value="installation" ${r.operation === 'installation' ? 'selected' : ''}>Installation</option>
                                                <option value="self_pickup" ${r.operation === 'self_pickup' ? 'selected' : ''}>Self Pickup</option>
                                                <option value="courier" ${r.operation === 'courier' ? 'selected' : ''}>Courier</option>
                                            </select>
                                            <input type="text" name="products[${productIndex}][remarks][${rindex}][remark]" class="form-control" value="${escapeHtml(r.remark)}" placeholder="Write a note…">
                                            <button type="button" class="btn btn-link text-danger p-0 remove-remark" title="Delete">
                                                <i class="bx bx-trash fs-5"></i>
                                            </button>
                                        </div>
                                    `).join('')}
                                </div>
                                <button type="button" class="btn btn-secondary btn-sm mt-2 add-remark" data-index="${productIndex}">Add Remark</button>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-primary edit-product" data-bs-toggle="modal" data-bs-target="#productModal" data-mode="edit" data-index="${productIndex}">Edit</button>
                                <button type="button" class="btn btn-sm btn-danger remove-product" data-index="${productIndex}">Delete</button>
                            </td>
                        </tr>
                    `;
                    $('#product-table tbody').append(html);

                    let hiddenHtml = `
                        <div data-index="${productIndex}">
                            <input type="hidden" name="products[${productIndex}][product_name]" value="${escapeHtml(product.product_name)}">
                            <input type="hidden" name="products[${productIndex}][quantity]" value="${escapeHtml(product.quantity)}">
                            <input type="hidden" name="products[${productIndex}][material_info]" value="${escapeHtml(product.material_info)}">
                    `;
                    product.remarks.forEach((r, rindex) => {
                        hiddenHtml += `<input type="hidden" name="products[${productIndex}][remarks][${rindex}][operation]" value="${escapeHtml(r.operation)}">`;
                        hiddenHtml += `<input type="hidden" name="products[${productIndex}][remarks][${rindex}][remark]" value="${escapeHtml(r.remark)}">`;
                    });
                    hiddenHtml += '</div>';
                    $('#hidden-products').append(hiddenHtml);

                    productIndex++;
                }

                isFromCsv = 1;
                $('#from_csv').val(1);
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
    });
</script>
@endpush

@endsection