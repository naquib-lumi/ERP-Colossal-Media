@extends('layouts.app')

@section('title', 'Edit Job Order')

@section('content')
<div class="container-xxl py-3">
    <form method="POST" action="{{ route('orders.update', $order->id) }}">
        @csrf @method('PUT')
        <div class="row">
            <div class="col-md-6">
                <h5>Lead Information</h5>
                <div class="form-group">
                    <label>Company Name</label>
                    <input type="text" class="form-control" readonly value="{{ $order->companyName }}">
                </div>
                <div class="form-group">
                    <label>Lead Name</label>
                    <input type="text" class="form-control" readonly value="{{ $order->leadName }}">
                </div>
                <div class="form-group">
                    <label>Phone</label>
                    <input type="text" class="form-control" readonly value="{{ $order->leadPhone }}">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="text" class="form-control" readonly value="{{ $order->leadEmail }}">
                </div>
            </div>
            <div class="col-md-6">
                <h5>Job Order Details</h5>
                <div class="form-group">
                    <label>Job Title</label>
                    <input type="text" class="form-control" readonly value="{{ $order->orderTitle }}">
                </div>
                <div class="form-group">
                    <label>Created Date</label>
                    <input type="text" class="form-control" readonly value="{{ $order->orderDate->format('d/m/Y') }}">
                </div>
                <div class="form-group">
                    <label>Deadline</label>
                    <input type="text" class="form-control" readonly value="{{ $order->deadline->format('d/m/Y') }}">
                </div>
                <div class="form-group">
                    <label>Created By</label>
                    <input type="text" class="form-control" readonly value="{{ $order->salesperson->name }}">
                </div>
                <div class="form-group">
                    <label>Design Approval</label>
                    <input type="text" class="form-control" readonly value="{{ $order->approval ? 'Yes' : 'No' }}">
                </div>
            </div>
        </div>
        <div class="mt-4">
            <h5>Product Details</h5>
            @foreach($order->products as $index => $product)
            <div class="product-row mt-3">
                <input type="hidden" name="products[{{$index}}][id]" value="{{ $product->ProductID }}">
                <label>Product Name</label>
                <input type="text" class="form-control" readonly value="{{ $product->productName }}">
                <label>Quantity</label>
                <input type="text" class="form-control" readonly value="{{ $product->totalQuantity }}">
                <label>Remark</label>
                <input type="text" class="form-control" readonly value="{{ $product->productRemark }}">
                <label>Material Info</label>
                <input type="text" class="form-control" readonly value="{{ $product->materialRemark }}">
                <label>Location</label>
                <input type="text" name="products[{{$index}}][location]" class="form-control" value="{{ $product->location }}">
                <label>Date & Time</label>
                <input type="text" class="form-control" readonly value="{{ $product->date_time }}">
            </div>
            @endforeach
        </div>
        <div class="form-group">
            <label>Remarks</label>
            <textarea class="form-control" readonly>{{ $order->orderDetail }}</textarea>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ route('sales.orders') }}" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<script>
$(function() {
    let hasEmptyLocation = {!! $order->products->some(fn($p) => empty($p->location)) ? 'true' : 'false' !!};
    if (hasEmptyLocation) {
        alert('Please update delivery address');
    }
});
</script>
@endsection