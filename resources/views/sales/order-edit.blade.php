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
                                            <input type="text" class="form-control" value="{{ $order->orderTitle }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Created Date</label>
                                            <input type="text" class="form-control" value="{{ $order->orderDate->format('d/m/Y') }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Deadline</label>
                                            <input type="text" class="form-control" value="{{ $order->deadline->format('d/m/Y') }}" readonly>
                                        </div>
                                        <div class="col-md-6 mb-4">
                                            <label class="form-label">Created By</label>
                                            <input type="text" class="form-control" value="{{ optional($order->salesperson)->name }}" readonly>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label d-block mb-4">Design from artist would need client approval</label>
                                            <div class="fw-medium">{{ $order->approval ? 'Yes' : 'No' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0">Product Details</h5>
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
                                        @foreach ($order->products as $product)
                                            <tr data-product-id="{{ $product->ProductID }}">
                                                <td>
                                                    <input type="hidden" name="products[{{ $product->ProductID }}][id]" value="{{ $product->ProductID }}">
                                                    <input type="text" name="products[{{ $product->ProductID }}][product_name]" class="form-control" value="{{ $product->productName }}">
                                                </td>
                                                <td>
                                                    <input type="number" name="products[{{ $product->ProductID }}][quantity]" class="form-control" value="{{ $product->totalQuantity }}">
                                                </td>
                                                <td>
                                                    <input type="text" name="products[{{ $product->ProductID }}][material_remark]" class="form-control" value="{{ $product->materialRemark ?? '' }}">
                                                </td>
                                                <td>
                                                    <div id="remarks-container-{{ $product->ProductID }}">
                                                        @foreach ($product->remarks as $remark)
                                                            <div class="remark-row">
                                                                <input type="hidden" name="products[{{ $product->ProductID }}][remarks][{{ $loop->index }}][operation]" value="{{ $remark->operation }}">
                                                                <select name="products[{{ $product->ProductID }}][remarks][{{ $loop->index }}][operation]" class="form-select w-auto" style="min-width:160px;">
                                                                    <option value="printing" {{ $remark->operation == 'printing' ? 'selected' : '' }}>Printing</option>
                                                                    <option value="furnishing" {{ $remark->operation == 'furnishing' ? 'selected' : '' }}>Furnishing</option>
                                                                    <option value="installation" {{ $remark->operation == 'installation' ? 'selected' : '' }}>Installation</option>
                                                                    <option value="self_pickup" {{ $remark->operation == 'self_pickup' ? 'selected' : '' }}>Self Pickup</option>
                                                                    <option value="courier" {{ $remark->operation == 'courier' ? 'selected' : '' }}>Courier</option>
                                                                </select>
                                                                <input type="text" name="products[{{ $product->ProductID }}][remarks][{{ $loop->index }}][remark]" class="form-control" value="{{ $remark->remark ?? '' }}" placeholder="Write a note…">
                                                                <button type="button" class="btn btn-link text-danger p-0 remove-remark" title="Delete">
                                                                    <i class="bx bx-trash fs-5"></i>
                                                                </button>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                    <button type="button" class="btn btn-secondary btn-sm mt-2 add-remark" data-product-id="{{ $product->ProductID }}">Add Remark</button>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-danger remove-product">Delete</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Remarks -->
                            <div class="mt-3">
                                <label class="form-label">Remarks</label>
                                <textarea rows="3" class="form-control" readonly>{{ $order->orderDetail }}</textarea>
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

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.add-remark').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.getAttribute('data-product-id');
                const container = document.getElementById(`remarks-container-${productId}`);
                const remarks = container.getElementsByClassName('remark-row');
                const index = remarks.length;

                const html = `
                    <div class="remark-row">
                        <input type="hidden" name="products[${productId}][remarks][${index}][operation]" value="">
                        <select name="products[${productId}][remarks][${index}][operation]" class="form-select w-auto" style="min-width:160px;">
                            <option value="">— Select —</option>
                            <option value="printing">Printing</option>
                            <option value="furnishing">Furnishing</option>
                            <option value="installation">Installation</option>
                            <option value="self_pickup">Self Pickup</option>
                            <option value="courier">Courier</option>
                        </select>
                        <input type="text" name="products[${productId}][remarks][${index}][remark]" class="form-control" placeholder="Write a note…">
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
                    this.closest('tr').remove();
                }
            });
        });
    });
</script>
@endpush

@endsection