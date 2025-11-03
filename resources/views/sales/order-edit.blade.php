@extends('layouts.app')

@section('content')
@push('styles')
<style>
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

<form id="order-form" action="{{ route('orders.update', $order->id) }}" method="POST">
    @csrf
    @method('PUT')
    <input type="hidden" name="from" value="{{ request('from') }}">
    <input type="hidden" name="lead_id" value="{{ request('lead_id') }}">
 
    <div class="row g-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-0">Edit Job Order</h5>
                    </div>
                </div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row g-4 align-items-stretch equal-cols">
                        <!-- Lead Information -->
                        <div class="col-lg-6 d-flex">
                            <div class="card h-100 flex-fill mb-0">
                                <div class="card-header">
                                    <h5 mb-0>Lead Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Company Name</label>
                                            <input type="text" class="form-control" value="{{ $order->lead->company_name ?? '' }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Lead Name</label>
                                            <input type="text" class="form-control" value="{{ $order->lead->name ?? '' }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Phone</label>
                                            <input type="text" class="form-control" value="{{ $order->lead->phone ?? '' }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input type="text" class="form-control" value="{{ $order->lead->email ?? '' }}" readonly>
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
                                            <input type="text" name="orderTitle" class="form-control" value="{{ old('orderTitle', $order->orderTitle) }}">
                                            @error('orderTitle')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Created Date</label>
                                            <input type="text" class="form-control" value="{{ $order->orderDate->format('d/m/Y') }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Deadline</label>
                                            <input type="date" name="deadline" class="form-control" value="{{ old('deadline', $order->deadline->format('Y-m-d')) }}">
                                            @error('deadline')
                                                <span class="text-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <label class="form-label">Created By</label>
                                            <input type="text" class="form-control" value="{{ optional($order->salesperson)->name }}" readonly>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label d-block mb-4">Design from artist would need client approval</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="approval" id="approval" value="1" {{ old('approval', $order->approval) ? 'checked' : '' }}>
                                                <label class="form-check-label" for="approval">Yes</label>
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
                                        @php $productIndex = 0; @endphp
                                        @foreach ($order->products as $product)
                                            <tr data-product-id="{{ $product->ProductID }}" data-index="{{ $productIndex }}">
                                                <td>
                                                    <input type="hidden" name="products[{{ $productIndex }}][id]" value="{{ $product->ProductID }}">
                                                    <input type="text" name="products[{{ $productIndex }}][product_name]" class="form-control" value="{{ $product->productName }}">
                                                </td>
                                                <td>
                                                    <input type="number" name="products[{{ $productIndex }}][quantity]" class="form-control" value="{{ $product->totalQuantity }}">
                                                </td>
                                                <td>
                                                    <input type="text" name="products[{{ $productIndex }}][material_remark]" class="form-control" value="{{ $product->materialRemark ?? '' }}">
                                                </td>
                                                <td>
                                                    <div id="remarks-container-{{ $productIndex }}">
                                                        @foreach ($product->remarks as $remark)
                                                            <div class="remark-row">
                                                                <select name="products[{{ $productIndex }}][remarks][{{ $loop->index }}][operation]" class="form-select w-auto" style="min-width:160px;">
                                                                    <option value="artist" {{ $remark->operation == 'artist' ? 'selected' : '' }}>To Artist</option>
                                                                    <option value="printing" {{ $remark->operation == 'printing' ? 'selected' : '' }}>To Printing</option>
                                                                    <option value="furnishing" {{ $remark->operation == 'furnishing' ? 'selected' : '' }}>To Furnishing</option>
                                                                    <option value="installation" {{ $remark->operation == 'installation' ? 'selected' : '' }}>To Installation</option>
                                                                    <option value="self_pickup" {{ $remark->operation == 'self_pickup' ? 'selected' : '' }}>To Self Pickup</option>
                                                                    <option value="courier" {{ $remark->operation == 'courier' ? 'selected' : '' }}>To Courier</option>
                                                                </select>
                                                                <input type="text" name="products[{{ $productIndex }}][remarks][{{ $loop->index }}][remark]" class="form-control" value="{{ $remark->remark ?? '' }}" placeholder="Write a note…">
                                                                <button type="button" class="btn btn-link text-danger p-0 remove-remark" title="Delete">
                                                                    <i class="bx bx-trash fs-5"></i>
                                                                </button>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <button type="button" class="btn btn-secondary btn-sm mt-2 add-remark" data-index="{{ $productIndex }}">Add Remark</button>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary edit-product" data-bs-toggle="modal" data-bs-target="#productModal" data-mode="edit" data-index="{{ $productIndex }}">Edit</button>
                                                    <button type="button" class="btn btn-sm btn-danger remove-product" data-index="{{ $productIndex }}">Delete</button>
                                                </td>
                                            </tr>
                                            @php $productIndex++; @endphp
                                        @endforeach
                                    </tbody>
                                </table>
                                @error('products')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Remarks -->
                            <div class="mt-3">
                                <label class="form-label">Remarks</label>
                                <textarea name="orderDetail" rows="3" class="form-control">{{ old('orderDetail', $order->orderDetail) }}</textarea>
                                @error('orderDetail')
                                    <span class="text-danger">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>

        {{-- Sticky save bar --}}
        <div class="col-12">
            <div class="bg-body position-sticky bottom-0 border-top py-3 d-flex gap-2 justify-content-end" style="z-index: 10">
                <button type="button" class="btn btn-outline-secondary" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Order</button>
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
                            <textarea id="material_remark" class="form-control"></textarea>
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
<script>
    document.addEventListener('DOMContentLoaded', function() {
        let productIndex = {{ count($order->products) }};

        document.querySelectorAll('.add-remark').forEach(button => {
            button.addEventListener('click', function() {
                const index = this.getAttribute('data-index');
                const container = document.getElementById(`remarks-container-${index}`);
                const remarks = container.getElementsByClassName('remark-row');
                const rindex = remarks.length;

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
                container.insertAdjacentHTML('beforeend', html);
            });
        });

        document.querySelectorAll('.remove-remark').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.remark-row').remove();
            });
        });

        document.querySelectorAll('.remove-product').forEach(button => {
            button.addEventListener('click', function() {
                if (confirm('Are you sure you want to delete this product?')) {
                    const index = this.getAttribute('data-index');
                    this.closest('tr').remove();
                    productIndex = Math.max(0, productIndex - 1);
                    if (productIndex < 5) {
                        document.getElementById('addProductBtn').style.display = 'inline-block';
                    }
                }
            });
        });

        // Modal functionality
        const productModal = document.getElementById('productModal');
        productModal.addEventListener('show.bs.modal', function(e) {
            const button = e.relatedTarget;
            const mode = button.dataset.mode;
            const index = button.dataset.index;

            document.getElementById('productForm').reset();
            document.getElementById('product_index').value = '';
            document.getElementById('remarks-container').innerHTML = '';
            document.getElementById('productModalTitle').textContent = 'Add Product';

            if (mode === 'edit' && index !== undefined) {
                document.getElementById('productModalTitle').textContent = 'Edit Product';
                document.getElementById('product_index').value = index;

                const row = document.querySelector(`tr[data-index="${index}"]`);
                document.getElementById('product_name').value = row.querySelector('input[name$="[product_name]"]').value;
                document.getElementById('quantity').value = row.querySelector('input[name$="[quantity]"]').value;
                document.getElementById('material_remark').value = row.querySelector('input[name$="[material_remark]"]').value;

                const container = document.getElementById(`remarks-container-${index}`);
                const remarkRows = container.querySelectorAll('.remark-row');
                remarkRows.forEach((row, rindex) => {
                    const select = row.querySelector('select');
                    const input = row.querySelector('input[type="text"]');
                    addRemarkRowModal(select.value, input.value);
                });
            }
        });

        document.getElementById('addRemarkBtn').addEventListener('click', function() {
            addRemarkRowModal();
        });

        function addRemarkRowModal(operation = '', remark = '') {
            const rindex = document.getElementById('remarks-container').children.length;
            const html = `
                <div class="remark-row mb-2">
                    <select class="form-select w-auto" style="min-width:160px;">
                        <option value="">— Select —</option>
                        <option value="artist" ${operation === 'artist' ? 'selected' : ''}>To Artist</option>
                        <option value="printing" ${operation === 'printing' ? 'selected' : ''}>To Printing</option>
                        <option value="furnishing" ${operation === 'furnishing' ? 'selected' : ''}>To Furnishing</option>
                        <option value="installation" ${operation === 'installation' ? 'selected' : ''}>To Installation</option>
                        <option value="self_pickup" ${operation === 'self_pickup' ? 'selected' : ''}>To Self Pickup</option>
                        <option value="courier" ${operation === 'courier' ? 'selected' : ''}>To Courier</option>
                    </select>
                    <input type="text" class="form-control" placeholder="Write a note…" value="${remark}">
                    <button type="button" class="btn btn-link text-danger p-0 remove-remark-modal" title="Delete">
                        <i class="bx bx-trash fs-5"></i>
                    </button>
                </div>
            `;
            document.getElementById('remarks-container').insertAdjacentHTML('beforeend', html);
        }

        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-remark-modal')) {
                e.target.closest('.remark-row').remove();
            }
        });

        document.getElementById('saveProduct').addEventListener('click', function() {
            const index = document.getElementById('product_index').value;
            const productName = document.getElementById('product_name').value;
            const quantity = document.getElementById('quantity').value;
            const materialRemark = document.getElementById('material_remark').value;
            const remarks = [];
            document.querySelectorAll('#remarks-container .remark-row').forEach(row => {
                const operation = row.querySelector('select').value;
                const remark = row.querySelector('input[type="text"]').value;
                if (operation) {
                    remarks.push({ operation, remark });
                }
            });

            if (!productName || !quantity) {
                alert('Product name and quantity are required.');
                return;
            }

            if (index !== '') {
                // Update existing
                const row = document.querySelector(`tr[data-index="${index}"]`);
                row.querySelector('input[name$="[product_name]"]').value = productName;
                row.querySelector('input[name$="[quantity]"]').value = quantity;
                row.querySelector('input[name$="[material_remark]"]').value = materialRemark;

                const container = document.getElementById(`remarks-container-${index}`);
                container.innerHTML = '';
                remarks.forEach((r, rindex) => {
                    const html = `
                        <div class="remark-row">
                            <select name="products[${index}][remarks][${rindex}][operation]" class="form-select w-auto" style="min-width:160px;">
                                <option value="artist" ${r.operation === 'artist' ? 'selected' : ''}>To Artist</option>
                                <option value="printing" ${r.operation === 'printing' ? 'selected' : ''}>To Printing</option>
                                <option value="furnishing" ${r.operation === 'furnishing' ? 'selected' : ''}>To Furnishing</option>
                                <option value="installation" ${r.operation === 'installation' ? 'selected' : ''}>To Installation</option>
                                <option value="self_pickup" ${r.operation === 'self_pickup' ? 'selected' : ''}>To Self Pickup</option>
                                <option value="courier" ${r.operation === 'courier' ? 'selected' : ''}>To Courier</option>
                            </select>
                            <input type="text" name="products[${index}][remarks][${rindex}][remark]" class="form-control" value="${r.remark}" placeholder="Write a note…">
                            <button type="button" class="btn btn-link text-danger p-0 remove-remark" title="Delete">
                                <i class="bx bx-trash fs-5"></i>
                            </button>
                        </div>
                    `;
                    container.insertAdjacentHTML('beforeend', html);
                });
                if (remarks.length === 0) {
                    container.insertAdjacentHTML('beforeend', '<button type="button" class="btn btn-secondary btn-sm mt-2 add-remark" data-index="${index}">Add Remark</button>');
                }
            } else {
                // Add new
                if (productIndex >= 5) {
                    alert('Maximum 5 products allowed.');
                    return;
                }
                const newIndex = productIndex;
                const html = `
                    <tr data-index="${newIndex}">
                        <td><input type="hidden" name="products[${newIndex}][id]" value="">
                            <input type="text" name="products[${newIndex}][product_name]" class="form-control" value="${productName}"></td>
                        <td><input type="number" name="products[${newIndex}][quantity]" class="form-control" value="${quantity}"></td>
                        <td><input type="text" name="products[${newIndex}][material_remark]" class="form-control" value="${materialRemark}"></td>
                        <td>
                            <div id="remarks-container-${newIndex}">
                                ${remarks.map((r, rindex) => `
                                    <div class="remark-row">
                                        <select name="products[${newIndex}][remarks][${rindex}][operation]" class="form-select w-auto" style="min-width:160px;">
                                            <option value="artist" ${r.operation === 'artist' ? 'selected' : ''}>To Artist</option>
                                            <option value="printing" ${r.operation === 'printing' ? 'selected' : ''}>To Printing</option>
                                            <option value="furnishing" ${r.operation === 'furnishing' ? 'selected' : ''}>To Furnishing</option>
                                            <option value="installation" ${r.operation === 'installation' ? 'selected' : ''}>To Installation</option>
                                            <option value="self_pickup" ${r.operation === 'self_pickup' ? 'selected' : ''}>To Self Pickup</option>
                                            <option value="courier" ${r.operation === 'courier' ? 'selected' : ''}>To Courier</option>
                                        </select>
                                        <input type="text" name="products[${newIndex}][remarks][${rindex}][remark]" class="form-control" value="${r.remark}" placeholder="Write a note…">
                                        <button type="button" class="btn btn-link text-danger p-0 remove-remark" title="Delete">
                                            <i class="bx bx-trash fs-5"></i>
                                        </button>
                                    </div>
                                `).join('')}
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm mt-2 add-remark" data-index="${newIndex}">Add Remark</button>
                        </td>
                        <td>
                            <button type="button" class="btn btn-sm btn-primary edit-product" data-bs-toggle="modal" data-bs-target="#productModal" data-mode="edit" data-index="${newIndex}">Edit</button>
                            <button type="button" class="btn btn-sm btn-danger remove-product" data-index="${newIndex}">Delete</button>
                        </td>
                    </tr>
                `;
                document.querySelector('#product-table tbody').insertAdjacentHTML('beforeend', html);
                productIndex++;
                if (productIndex >= 5) {
                    document.getElementById('addProductBtn').style.display = 'none';
                }
            }

            bootstrap.Modal.getInstance(productModal).hide();
        });

        // Rebind events after adding rows
        function rebindEvents() {
            document.querySelectorAll('.add-remark').forEach(button => {
                button.addEventListener('click', function() {
                    const index = this.getAttribute('data-index');
                    const container = document.getElementById(`remarks-container-${index}`);
                    const remarks = container.getElementsByClassName('remark-row');
                    const rindex = remarks.length;

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
                    container.insertAdjacentHTML('beforeend', html);
                });
            });

            document.querySelectorAll('.remove-remark').forEach(button => {
                button.addEventListener('click', function() {
                    this.closest('.remark-row').remove();
                });
            });

            document.querySelectorAll('.remove-product').forEach(button => {
                button.addEventListener('click', function() {
                    if (confirm('Are you sure you want to delete this product?')) {
                        const index = this.getAttribute('data-index');
                        this.closest('tr').remove();
                        productIndex = Math.max(0, productIndex - 1);
                        if (productIndex < 5) {
                            document.getElementById('addProductBtn').style.display = 'inline-block';
                        }
                        rebindEvents();
                    }
                });
            });

            document.querySelectorAll('.edit-product').forEach(button => {
                button.addEventListener('click', function() {
                    // Trigger modal show with data
                });
            });
        }
        rebindEvents();

        if (productIndex >= 5) {
            document.getElementById('addProductBtn').style.display = 'none';
        }
    });
</script>
@endpush

@endsection