@extends('layouts.app')

@section('content')

<!-- Content -->
<div class="container-xxl flex-grow-1 container-p-y">

  <!-- Dashboard Overview -->
  <h4 class="mb-4">Dashboard Overview</h4>

  <div class="row g-4 mb-4">
    <div class="col-sm-6 col-md-3">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center">
          <div class="avatar me-3">
            <span class="avatar-initial rounded-circle bg-label-secondary">
              <i class="bx bx-loader"></i>
            </span>
          </div>
          <div>
            <small class="text-body-secondary d-block">In Progress</small>
            <h4 class="mb-0">5</h4>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-md-3">
      <div class="card h-100">
        <div class="card-body d-flex align-items-center">
          <div class="avatar me-3">
            <span class="avatar-initial rounded-circle bg-label-secondary">
              <i class="bx bx-check-circle"></i>
            </span>
          </div>
          <div>
            <small class="text-body-secondary d-block">Completed</small>
            <h4 class="mb-0">21</h4>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Search + Filter -->
  <div class="card mb-4">
    <div class="card-body pb-0">
      <div class="row g-3 align-items-center">
        <div class="col-lg-6">
          <div class="input-group">
            <span class="input-group-text"><i class="bx bx-search"></i></span>
            <input type="text" class="form-control" placeholder="Search by Job Title or Product Name..." />
          </div>
        </div>
        <div class="col-lg-3 ms-auto">
          <select class="form-select">
            <option selected>All Status</option>
            <option>In Progress</option>
            <option>Completed</option>
            <option>Pending</option>
          </select>
        </div>
      </div>
    </div>

    <!-- Jobs Table -->
    <div class="table-responsive text-nowrap mt-4">
      <table class="table align-middle">
        <!-- Light Table head -->
        <thead class="table-light">
          <tr>
            <th>Product ID</th>
            <th>Product Name</th>
            <th>Printer</th>
            <th>Deadline</th>
            <th>Status</th>
            <th class="text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="table-border-bottom-0">
          <!-- row 1 -->
          <tr>
            <td class="fw-medium"><a href="javascript:void(0)">#ORD005-P1</a></td>
            <td>Business Card Design</td>
            <td>Handtop Hybrid <i class="bx bx-link-external opacity-50 ms-1"></i></td>
            <td>Jan 20, 2025</td>
            <td><span class="badge bg-label-primary">In Progress</span></td>
            <td class="text-center">
              <button class="btn btn-sm btn-icon btn-text-secondary"><i class="bx bx-show"></i></button>
              <button class="btn btn-sm btn-icon btn-text-secondary"><i class="bx bx-edit-alt"></i></button>
              <button class="btn btn-sm btn-icon btn-text-secondary"><i class="bx bx-bell"></i></button>
            </td>
          </tr>
          <!-- row 2 -->
          <tr>
            <td class="fw-medium"><a href="javascript:void(0)">#ORD003-P4</a></td>
            <td>Flyer Campaign</td>
            <td>HP Latex <i class="bx bx-link-external opacity-50 ms-1"></i></td>
            <td>Jan 22, 2025</td>
            <td><span class="badge bg-label-primary">In Progress</span></td>
            <td class="text-center">
              <button class="btn btn-sm btn-icon btn-text-secondary"><i class="bx bx-show"></i></button>
              <button class="btn btn-sm btn-icon btn-text-secondary"><i class="bx bx-edit-alt"></i></button>
              <button class="btn btn-sm btn-icon btn-text-secondary"><i class="bx bx-bell"></i></button>
            </td>
          </tr>
          <!-- row 3 -->
          <tr>
            <td class="fw-medium"><a href="javascript:void(0)">#ORD005-P2</a></td>
            <td>Poster Design</td>
            <td>Solvent <i class="bx bx-link-external opacity-50 ms-1"></i></td>
            <td>Jan 18, 2025</td>
            <td><span class="badge bg-label-secondary">Completed</span></td>
            <td class="text-center">
              <button class="btn btn-sm btn-icon btn-text-secondary"><i class="bx bx-show"></i></button>
              <button class="btn btn-sm btn-icon btn-text-secondary"><i class="bx bx-edit-alt"></i></button>
              <button class="btn btn-sm btn-icon btn-text-secondary"><i class="bx bx-bell"></i></button>
            </td>
          </tr>
          <!-- row 4 -->
          <tr>
            <td class="fw-medium"><a href="javascript:void(0)">#ORD001-P3</a></td>
            <td>Brochure Print</td>
            <td>Pigment HDP <i class="bx bx-link-external opacity-50 ms-1"></i></td>
            <td>Jan 25, 2025</td>
            <td><span class="badge bg-label-primary">In Progress</span></td>
            <td class="text-center">
              <button class="btn btn-sm btn-icon btn-text-secondary"><i class="bx bx-show"></i></button>
              <button class="btn btn-sm btn-icon btn-text-secondary"><i class="bx bx-edit-alt"></i></button>
              <button class="btn btn-sm btn-icon btn-text-secondary"><i class="bx bx-bell"></i></button>
            </td>
          </tr>
          <!-- row 5 -->
          <tr>
            <td class="fw-medium"><a href="javascript:void(0)">#ORD007-P6</a></td>
            <td>Banner Production</td>
            <td>Konica Minolta <i class="bx bx-link-external opacity-50 ms-1"></i></td>
            <td>Jan 24, 2025</td>
            <td><span class="badge bg-label-primary">In Progress</span></td>
            <td class="text-center">
              <button class="btn btn-sm btn-icon btn-text-secondary"><i class="bx bx-show"></i></button>
              <button class="btn btn-sm btn-icon btn-text-secondary"><i class="bx bx-edit-alt"></i></button>
              <button class="btn btn-sm btn-icon btn-text-secondary"><i class="bx bx-bell"></i></button>
            </td>
          </tr>
          <!-- 你可以继续按需补充更多行 -->
        </tbody>
      </table>
    </div>

    <!-- Footer: results + pagination -->
    <div class="card-footer d-md-flex align-items-center justify-content-between">
      <small class="text-body-secondary">Showing 1 to 8 of 248 results</small>
      <nav aria-label="Table pagination" class="mt-3 mt-md-0">
        <ul class="pagination mb-0">
          <li class="page-item">
            <a class="page-link" href="javascript:void(0);" aria-label="Previous">
              <i class="bx bx-chevron-left"></i>
            </a>
          </li>
          <li class="page-item"><a class="page-link" href="javascript:void(0);">1</a></li>
          <li class="page-item active"><a class="page-link" href="javascript:void(0);">2</a></li>
          <li class="page-item"><a class="page-link" href="javascript:void(0);">3</a></li>
          <li class="page-item disabled"><span class="page-link">…</span></li>
          <li class="page-item"><a class="page-link" href="javascript:void(0);">31</a></li>
          <li class="page-item">
            <a class="page-link" href="javascript:void(0);" aria-label="Next">
              <i class="bx bx-chevron-right"></i>
            </a>
          </li>
        </ul>
      </nav>
    </div>
  </div>
</div>
<!-- / Content -->
@endsection