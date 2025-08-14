
<form id="product-form" action="" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <div class="card mb-6">

        <div class="card-body p-4">
            <div class="row g-3 mb-4">
                <div class="col-12 col-md-6 col-xl-6">
                    <label class="form-label">Product Name</label>
                    <input name="product[name]" type="text" class="form-control" placeholder="e.g. Business Card">
                </div>
                <div class="col-12 col-md-6 col-xl-6">
                    <label class="form-label">Total Quantity</label>
                    <input name="product[qty_total]" type="number" min="0" class="form-control" placeholder="1000">
                </div>
                <div class="col-12 col-md-6 col-xl-6">
                    <label class="form-label">Material / Remark</label>
                    <input name="product[material]" type="text" class="form-control" placeholder="Premium Paper, Glossy">
                </div>
            </div>

            {{-- Delivery Breakdown (repeater) --}}
            <div class="d-flex align-items-center justify-content-between mt-4 mb-2">
                <h6 class="mb-0">Delivery Breakdown</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" id="addDeliveryBtn">
                    <i class="bx bx-plus me-1"></i> Add Delivery Breakdown
                </button>
            </div>

            <div id="deliveriesWrap" class="vstack gap-3">
                <div class="card border shadow-none" data-delivery>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Delivery <span class="delivery-index">1</span></strong>
                            <button type="button" class="btn btn-link p-0 text-danger delete-delivery" title="Delete delivery" data-remove>
                                <i class="bx bx-trash fs-5"></i>
                            </button>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Delivery Method</label>
                                <select name="deliveries[0][method]" class="form-select">
                                    <option value="">Method</option>
                                    <option value="courier">Courier</option>
                                    <option value="pickup">Pickup</option>
                                    <option value="install">Install</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Location Address</label>
                                <input name="deliveries[0][location]" type="text" class="form-control" placeholder="Location">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Quantity</label>
                                <input name="deliveries[0][qty]" type="number" min="0" class="form-control" placeholder="Qty">
                            </div>

                            <div class="col-12 col-md-6">
                                <label class="form-label">Date & Time</label>
                                <input name="deliveries[0][datetime]" type="datetime-local" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Template used when clicking “Add Delivery Breakdown” --}}
            <template id="deliveryTemplate">
                <div class="card border shadow-none" data-delivery>
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <strong>Delivery <span class="delivery-index">__INDEX_HUMAN__</span></strong>
                            <button type="button" class="btn btn-link p-0 text-danger delete-delivery" title="Delete delivery" data-remove>
                                <i class="bx bx-trash fs-5"></i>
                            </button>
                        </div>

                        <div class="row g-3">
                            <div class="col-12 col-md-3">
                                <label class="form-label">Delivery Method</label>
                                <select name="deliveries[__INDEX__][method]" class="form-select">
                                    <option value="">Method</option>
                                    <option value="courier">Courier</option>
                                    <option value="pickup">Pickup</option>
                                    <option value="install">Install</option>
                                </select>
                            </div>

                            <div class="col-12 col-md-3">
                                <label class="form-label">Location Address</label>
                                <input name="deliveries[__INDEX__][location]" type="text" class="form-control" placeholder="Location">
                            </div>

                            <div class="col-12 col-md-2">
                                <label class="form-label">Quantity</label>
                                <input name="deliveries[__INDEX__][qty]" type="number" min="0" class="form-control" placeholder="Qty">
                            </div>

                            <div class="col-12 col-md-4">
                                <label class="form-label">Date & Time</label>
                                <input name="deliveries[__INDEX__][datetime]" type="datetime-local" class="form-control">
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
    {{-- Sticky save bar --}}
    <div class="col-12">
        <div class="position-sticky bottom-0 border-top py-3 d-flex gap-2 justify-content-end" style="z-index: 10">
            <button type="button" name="action" value="submit" id="btn-submit" class="btn btn-primary">Save and Submit</button>
        </div>
    </div>
    </div>
</form>