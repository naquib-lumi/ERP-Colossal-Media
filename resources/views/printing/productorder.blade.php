@extends('layouts.app')

@section('content')

<!-- Content -->
<div class="container-xxl flex-grow-1 container-p-y">

  <!-- Page title / back -->
  <div class="d-flex align-items-center gap-2 mb-4">
    <a href="javascript:history.back()" class="btn p-0 text-body"><i class="bx bx-chevron-left fs-4"></i></a>
    <h4 class="mb-0">Printing - <span class="fw-normal">ORD005-P1</span></h4>
  </div>

  <div class="card shadow-none border">
    <div class="card-body">

      <!-- Section: Task Information -->
      <h6 class="mb-4">Task Information</h6>

      <div class="row g-4">
        <div class="col-md-6">
          <label class="form-label">Product ID</label>
          <input type="text" class="form-control" value="ORD005-P1" readonly>
        </div>
        <div class="col-md-6">
          <label class="form-label">Job Order ID</label>
          <input type="text" class="form-control" value="#ORD-2025-005" readonly>
        </div>

        <div class="col-md-6">
          <label class="form-label">Product Name</label>
          <input type="text" class="form-control" value="Business Cards Premium" readonly>
        </div>
        <div class="col-md-6">
          <label class="form-label">Assigned By</label>
          <input type="text" class="form-control" value="Artist A" readonly>
        </div>

        <div class="col-md-6">
          <label class="form-label">Deadline</label>
          <div class="input-group">
            <input type="datetime-local" class="form-control" value="2025-01-20T14:30" readonly>
            <span class="input-group-text"><i class="bx bx-calendar"></i></span>
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Printer</label>
          <input type="text" class="form-control" value="Handtop Hybrid" readonly>
        </div>
      </div>

      <!-- Section: Printing Specifications -->
      <div class="mt-5">
        <label class="form-label mb-2">Printing Specifications</label>
        <div class="border rounded-2 p-3 bg-body">
          <div class="row g-0 small">
            <div class="col-md-4 p-3 border-end">
              <div class="text-body-secondary mb-2">Item 1</div>
              <div class="text-body-secondary">Front Side Design</div>
              <div class="text-body-secondary mt-2">Size</div>
              <div>3.5 × 2.0 (inches)</div>
            </div>
            <div class="col-md-4 p-3 border-end">
              <div class="text-body-secondary">Quantity per Item</div>
              <div>1000</div>
              <div class="text-body-secondary mt-2">Bleed Size</div>
              <div>3.6 × 2.1 (inches)</div>
            </div>
            <div class="col-md-4 p-3">
              <div class="text-body-secondary">Material</div>
              <div>350gsm Cardstock</div>
              <div class="text-body-secondary mt-2">Lamination</div>
              <div>Gloss UV Lamination</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Section: Remarks -->
      <div class="mt-4">
        <label class="form-label">Remarks</label>
        <textarea class="form-control" rows="3"
          placeholder="Customer requested expedited processing. Double-check color accuracy before final print run."></textarea>
      </div>

      <!-- Section: Attachments -->
      <div class="mt-4">
        <label class="form-label mb-2">Attachments</label>

        <div class="list-group list-group-flush border rounded-2">
          <a href="#" download class="list-group-item list-group-item-action d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-start gap-3">
              <div class="avatar">
                <span class="avatar-initial rounded-circle bg-label-secondary">
                  <i class="bx bx-file"></i>
                </span>
              </div>
              <div>
                <div class="fw-medium">brand-guidelines.pdf</div>
                <small class="text-body-secondary">1.2 MB</small>
              </div>
            </div>
            <i class="bx bx-download"></i>
          </a>

          <a href="#" download class="list-group-item list-group-item-action d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-start gap-3">
              <div class="avatar">
                <span class="avatar-initial rounded-circle bg-label-secondary">
                  <i class="bx bx-file"></i>
                </span>
              </div>
              <div>
                <div class="fw-medium">brand-guidelines.pdf</div>
                <small class="text-body-secondary">1.2 MB</small>
              </div>
            </div>
            <i class="bx bx-download"></i>
          </a>
        </div>
      </div>

    </div>
  </div>
</div>
<!-- / Content -->

@endsection