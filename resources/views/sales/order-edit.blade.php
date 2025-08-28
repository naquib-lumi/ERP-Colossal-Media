@extends('layouts.app')

@section('content')
@push('styles')
<style>
    /* Reuse from add-order, add if needed */
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
                                            <th>Remark</th>
                                            <th>Material Info</th>
                                            <th>Location</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($order->products as $product)
                                            <tr>
                                                <td>{{ $product->productName }}</td>
                                                <td>{{ $product->totalQuantity }}</td>
                                                <td>{{ $product->productRemark }}</td>
                                                <td>{{ $product->materialRemark }}</td>
                                                <td>
                                                    <input name="products[{{ $product->id }}][location]" type="text" class="form-control" value="{{ $product->location }}">
                                                </td>
                                                <td>{{ $product->date_time ? \Carbon\Carbon::parse($product->date_time)->format('d/m/Y') : '-' }}</td>
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

@endsection