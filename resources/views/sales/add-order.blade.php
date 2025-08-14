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
</style>

@endpush

<form id="order-form" action="" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" name="is_draft" id="is_draft" value="0">

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
                                    <h5 class="mb-0">Lead Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Company Name</label>
                                            <input type="text" class="form-control" value="">
                                            <input type="hidden" name="company_name" value="">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Lead Name</label>
                                            <input type="text" class="form-control" value="">
                                            <input type="hidden" name="lead_name" value="">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Phone</label>
                                            <input type="text" class="form-control">
                                            <input type="hidden" name="phone_num" value="">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input type="text" class="form-control">
                                            <input type="hidden" name="email" value="">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Job Order Details -->
                        <div class="col-lg-6 d-flex">
                            <div class="card h-100 flex-fill mb-0">
                                <div class="card-header">
                                    <h5 class="mb-0">Job Order Details</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Job Title</label>
                                            <input name="job_title" type="text" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Created Date</label>
                                            <input type="text" class="form-control">
                                            <input type="hidden" name="created_date" value="">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Deadline</label>
                                            <input type="text" class="form-control">
                                            <input type="hidden" name="deadline" value="">
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <label class="form-label">Created By</label>
                                            <input type="text" class="form-control" readonly>
                                            <input type="hidden" name="created_by" value="">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label d-block mb-4">Design from artist would need client approval</label>
                                            <div class="d-flex gap-4">
                                                <label class="form-check-label">
                                                    <input class="form-check-input me-1" type="radio" name="design_confirmed" value="1"> YES
                                                </label>
                                                <label class="form-check-label">
                                                    <input class="form-check-input me-1" type="radio" name="design_confirmed" value="0"> NO
                                                </label>
                                            </div>
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
                                <button type="button"
                                    class="btn btn-primary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#addProductModal">
                                    Add Product
                                </button>
                            </div>
                        </div>

                        <div class="card-body">
                            <!-- Top row: left label + right CSV template download -->
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted">Upload CSV (Optional)</span>
                                <a id="csvTemplateBtn" href="#" class="btn btn-link p-0 text-decoration-none">
                                    <i class="bx bx-download me-1"></i> CSV Template Download
                                </a>
                            </div>

                            <!-- Your EXISTING drop area (untouched) -->
                            <div id="attach-box" class="attach-box">
                                <div class="attach-inner">
                                    <div class="attach-icon" aria-hidden="true">
                                        <i class="bx bx-upload display-6 mb-2 d-block justify-content-between align-items-center" style="pointer-events:none"></i>
                                    </div>
                                    <div class="attach-title">Drop CSV file here or click to upload</div>
                                    <div class="attach-hint">(Excel or CVS)</div>
                                </div>

                                <!-- This input sits on top, invisible, and owns the click -->
                                <input id="fileInput" type="file" multiple
                                    accept=".xlsx,.xls,.csv"
                                    class="file-overlay">
                            </div>

                            <div id="attach-msg" class="mt-2 text-sm"></div>
                            <ul id="preview" class="mt-3 space-y-2"></ul>

                            <div id="product-list" class="mt-3"></div>

                            <!-- Remarks -->
                            <div class="mt-3">
                                <label class="form-label">Remarks</label>
                                <textarea id="remarks" name="remarks" rows="3" class="form-control" placeholder="Remarks"></textarea>
                            </div>
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
                <button type="button" name="action" value="submit" id="btn-submit" class="btn btn-primary">Save and Submit</button>
            </div>
        </div>
    </div>
</form>

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

<!-- pop out add product modal -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bx bx-package me-2"></i> Add Product
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-3">
                @include('sales.partials.add-product-form')
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script>
    (function() {

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

        // Highlight attach box on drag — noop if you already do something similar
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
        }

        // CSV template download stub (replace href if you have a real asset)
        const tmpl = document.getElementById('csvTemplateBtn');
        if (tmpl && !tmpl.dataset.wired) {
            tmpl.dataset.wired = '1';
            tmpl.addEventListener('click', (e) => {
                e.preventDefault();
                alert('CSV template download coming soon.');
            });
        }

        // Add product button stub (keeps your existing functions intact)
        const addBtn = document.getElementById('addProductBtn');
        if (addBtn && !addBtn.dataset.wired) {
            addBtn.dataset.wired = '1';
            addBtn.addEventListener('click', () => {
                // If you already have a function, call it here instead:
                // if (window.addProductCard) return window.addProductCard();
                const list = document.getElementById('product-list');
                if (list) {
                    const div = document.createElement('div');
                    div.className = 'border rounded p-3 mb-2';
                    div.textContent = 'Product item placeholder';
                    list.appendChild(div);
                }
            });
        }
    });
</script>
@endpush

@endsection