@extends('layouts.app')

@section('content')
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">

<style>
    .remark-line { margin-bottom: .25rem; }
    .remark-line:last-child { margin-bottom: 0; }
    .remark-line .badge { font-weight: 600; }
    .remark-note { color: #6c757d; }

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

    .read-only td:last-child {
        display: none;
    }
</style>
@endpush

<form id="order-form" action="{{ route('artist.orders.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="lead_id" id="lead_id" value="{{ $lead->id ?? '' }}">
    <input type="hidden" id="from_csv" name="from_csv" value="{{ old('from_csv', 0) }}">
    <div id="hidden-products"></div>

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
                                    <div class="mb-3">
                                        <label for="lead_id" class="form-label">Search lead...</label>
                                        <select id="lead_id" class="form-control js-lead-select" name="lead_id" style="width:100%"></select>
                                        <div class="form-text">Type at least 2 characters. Matches: name, company, phone, email.</div>
                                    </div>
                                    @endif
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Company Name</label>
                                            <input id="companyDisplay" name="companyName" type="text" class="form-control" value="{{ $lead->company_name ?? '' }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Lead Name</label>
                                            <input id="leadNameDisplay" name="leadName" type="text" class="form-control" value="{{ $lead->name ?? '' }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Phone</label>
                                            <input id="phoneDisplay" name="leadPhone" type="text" class="form-control" value="{{ $lead->phone ?? '' }}" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input id="emailDisplay" name="leadEmail" type="text" class="form-control" value="{{ $lead->email ?? '' }}" readonly>
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

                    {{-- Attachments (bottom) --}}
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
                                            <th>Remark</th>
                                            <th>Material Remark</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach (old('products', []) as $index => $product)
                                            <tr data-index="{{ $index }}" {{ old('from_csv') ? 'class="read-only"' : '' }}>
                                                <td>{!! e($product['product_name'] ?? '') !!}</td>
                                                <td>{!! e($product['quantity'] ?? '') !!}</td>
                                                <td>{!! e($product['remark'] ?? '') !!}</td>
                                                <td>{!! e($product['material_info'] ?? '') !!}</td>
                                                <td>{!! e($product['location'] ?? '') !!}</td>
                                                <td>{!! e($product['date_time'] ?? '') !!}</td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-danger remove-product">Delete</button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <!-- Top row: left label + right CSV template download -->
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="text-muted">Upload CSV (Optional)</span>
                                <a id="csvTemplateBtn" href="{{ route('artist.orders.csv_template') }}" class="btn btn-link p-0 text-decoration-none">
                                    <i class="bx bx-download me-1"></i> CSV Template Download
                                </a>
                            </div>

                            <!-- Drop area -->
                            <div id="attach-box" class="attach-box">
                                <div class="attach-inner">
                                    <div class="attach-icon" aria-hidden="true">
                                        <i class="bx bx-upload display-6 mb-2 d-block justify-content-between align-items-center" style="pointer-events:none"></i>
                                    </div>
                                    <div class="attach-title">Drop CSV file here or click to upload</div>
                                    <div class="attach-hint">(CSV)</div>
                                </div>

                                <!-- This input sits on top, invisible, and owns the click -->
                                <input id="fileInput" type="file"
                                    accept=".csv"
                                    class="file-overlay">
                            </div>
                            @error('csv_file')
                                <span class="text-danger">{{ $message }}</span>
                            @enderror

                            <div id="attach-msg" class="mt-2 text-sm"></div>
                            <ul id="preview" class="mt-3 space-y-2"></ul>
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

                    @if(auth()->check() && auth()->user()->role === 'head-artist')
                      <hr class="my-4">

                      <div class="card">
                        <div class="card-body">
                          <div class="d-flex align-items-center mb-3">
                            <i class="bx bx-user-plus me-2"></i>
                            <h6 class="m-0">Assign Artist</h6>
                          </div>

                          <div class="mb-1 text-muted small">
                            Select an artist to assign this job order.
                          </div>

                          <label class="form-label">Artist <span class="text-danger">*</span></label>
                          <select id="assignee_artist_id"
                                  name="assignee_artist_id"
                                  class="form-control"
                                  style="width:100%"
                                  required>
                          </select>
                          @error('assignee_artist_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                          @enderror
                          <div class="form-text">Search by artist name or email.</div>
                        </div>
                      </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Sticky save bar --}}
        <div class="col-12">
            <div class="bg-body position-sticky bottom-0 border-top py-3 d-flex gap-2 justify-content-end" style="z-index: 10">
                <button type="button" class="btn btn-outline-secondary" onclick="history.back()">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Order</button>
            </div>
        </div>
    </div>
    <!-- Hidden products -->
    
<div id="hidden-products" style="display: none;">
    @foreach (old('products', []) as $index => $product)
        <div data-index="{{ $index }}">
            <input type="hidden" name="products[{{ $index }}][product_name]" value="{{ $product['product_name'] ?? '' }}">
            <input type="hidden" name="products[{{ $index }}][quantity]" value="{{ $product['quantity'] ?? '' }}">
            <input type="hidden" name="products[{{ $index }}][remark]" value="{{ $product['remark'] ?? '' }}">
            <input type="hidden" name="products[{{ $index }}][material_info]" value="{{ $product['material_info'] ?? '' }}">
        </div>
    @endforeach
</div>
</form>


<!-- Product Modal (for add/edit) -->
<div class="modal fade" id="addProductModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add Product</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="productForm" autocomplete="off">
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Product Name</label>
              <input type="text" class="form-control" id="p_name" required>
            </div>
            <div class="col-md-4">
              <label class="form-label">Quantity</label>
              <input type="number" class="form-control" id="p_qty" min="1" step="1" required>
            </div>
            <div class="col-12">
              <label class="form-label">Material Remark</label>
              <textarea class="form-control" id="p_material" rows="2" placeholder="Backlit Fabric"></textarea>
            </div>
          </div>

          <hr class="my-4">

          <div class="d-flex justify-content-between align-items-center mb-2">
            <label class="form-label m-0">Product Remarks</label>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addRemarkRow">+ Add Remarks</button>
          </div>

          <div id="remarkRows" class="vstack gap-2">
            {{-- rows injected by JS --}}
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>


@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.full.min.js"></script>
<script>
$(function () {
  /***********************
   * LEAD SELECT (Select2)
   ***********************/
  // Keep only the last #lead_id (the one inside Lead card)
  const $leadCandidates = $('select#lead_id');
  const $leadSel = $leadCandidates.last();
  $leadCandidates.not($leadSel).remove();

  // Init Select2
  $leadSel.select2({
    placeholder: 'Search lead…',
    allowClear: true,
    width: '100%',
    minimumInputLength: 2,
    dropdownParent: $leadSel.closest('.card, .modal, form'),
    ajax: {
      url: @json(route('artist.orders.leads.search')),
      dataType: 'json',
      delay: 250,
      data: params => ({ q: params.term }),
      processResults: data => ({ results: Array.isArray(data) ? data : (data.results || []) }),
      cache: true
    },
    templateResult: function (item) {
      if (!item.id) return item.text;
      const m = item.meta || {};
      const line = [item.text,
        m.company_name ? ' — ' + m.company_name : '',
        m.phone ? ' · ' + m.phone : '',
        m.email ? ' · ' + m.email : ''].join('');
      return $('<span>').text(line);
    },
    templateSelection: function (item) {
      if (!item.id) return item.text;
      const m = item.meta || {};
      return (item.text || '') + (m.company_name ? ' — ' + m.company_name : '');
    },
    escapeMarkup: m => m
  });

  // Auto-fill lead fields on selection (fallback to /get if meta missing)
  const set = ($el, v) => $el.length && $el.val(v || '').trigger('input').trigger('change');
  const $company = $('[name="companyName"], [name="company_name"], #companyName, #company_name').first();
  const $leadNm  = $('[name="leadName"],    [name="name"],         #leadName,    #name').first();
  const $phone   = $('[name="leadPhone"],   [name="phone"],        #leadPhone,   #phone').first();
  const $email   = $('[name="leadEmail"],   [name="email"],        #leadEmail,   #email').first();

  $leadSel.on('select2:select', function (e) {
    const d = e.params.data || {};
    const m = d.meta || {};
    if (m.company_name || m.phone || m.email) {
      set($leadNm,  d.text);
      set($company, m.company_name);
      set($phone,   m.phone);
      set($email,   m.email);
    } else {
      const url = @json(route('artist.orders.leads.get', ['id' => 'ID']));
      $.get(url.replace('ID', d.id)).done(l => {
        set($leadNm,  l.name);
        set($company, l.company_name);
        set($phone,   l.phone);
        set($email,   l.email);
      });
    }
  });

  $leadSel.on('select2:clear', function () {
    set($leadNm,''); set($company,''); set($phone,''); set($email,'');
  });

  /**************************
 * PRODUCTS (+ REMARKS) UI
 **************************/
  const $hidden = $('#hidden-products');             
  const $tbody  = $('#product-table tbody');        
  const $addBtn = $('#addProductBtn');         
  let fromCsv   = Number({{ old('from_csv', 0) }}); 
  let pIndex    = $tbody.find('tr').length || 0;  
  const MAX_MANUAL = 5;

  function escapeHtml(str) {
    return (str ?? '').toString()
      .replace(/&/g,'&amp;').replace(/</g,'&lt;')
      .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#039;');
  }

  // ---------- modal helpers ----------
  function addRemarkRowHTML(op = '', text = '') {
    return `
      <div class="row g-2 align-items-center remark-row">
        <div class="col-md-3">
          <select class="form-select remark-op">
            <option value="">— Select Department —</option>
            <option value="printing"     ${op==='printing'?'selected':''}>To Printing</option>
            <option value="furnishing"   ${op==='furnishing'?'selected':''}>To Furnishing</option>
            <option value="installation" ${op==='installation'?'selected':''}>To Delivery & Installation</option>
            <option value="courier"      ${op==='courier'?'selected':''}>To Courier</option>
            <option value="self_pickup"  ${op==='self_pickup'?'selected':''}>To Self Pickup</option>
            <option value="artist"  ${op==='artist'?'selected':''}>To Artist</option>
          </select>
        </div>
        <div class="col-md-8">
          <input type="text" class="form-control remark-text" placeholder="Write a note…" value="${escapeHtml(text)}">
        </div>
        <div class="col-md-1 text-end">
          <button type="button" class="btn btn-link text-danger remove-remark" title="Remove">✕</button>
        </div>
      </div>
    `;
  }

  // Build HTML for remarks (each on its own line)
  function buildRemarksHtml(prod) {
    // normalize any legacy values (e.g. "Delivery & Installation", "Self Pickup")
    const alias = {
      'delivery & installation': 'installation',
      'delivery and installation': 'installation',
      'self pickup': 'self_pickup',
    };

    const colors = {
      printing:     'primary',
      furnishing:   'warning',
      installation: 'secondary',
      courier:      'info',
      self_pickup:  'dark',
      artist     :  'success'
    };

    const labels = {
      printing:     'Printing',
      furnishing:   'Furnishing',
      installation: 'Delivery & Installation',
      courier:      'Courier',
      self_pickup:  'Self Pickup',
      artist     :  'Artist'
    };

    if (prod.remarks && prod.remarks.length) {
      return prod.remarks.map(r => {
        const raw = (r.operation || '').toLowerCase().trim();
        const key = alias[raw] || raw;                  
        const color = colors[key] || 'secondary';
        const label = labels[key] || (key || 'Remark');
        return `
          <div class="remark-line">
            <span class="badge bg-${color} me-2">${label}</span>
            <span class="remark-note">${escapeHtml(r.remark || '')}</span>
          </div>
        `;
      }).join('');
    }

    return prod.remark
      ? `<div class="remark-line">
          <span class="badge bg-primary me-2">Remark</span>
          <span class="remark-note">${escapeHtml(prod.remark)}</span>
        </div>`
      : '<span class="text-muted">-</span>';
  }

  // Fill modal with a product object
  function setModalFromProduct(prod) {
    $('#p_name').val(prod.product_name || '');
    $('#p_qty').val(prod.quantity || '');
    $('#p_material').val(prod.material_info || '');
    const $rows = $('#remarkRows').empty();
    if (prod.remarks && prod.remarks.length) {
      prod.remarks.forEach(r => $rows.append(addRemarkRowHTML(r.operation, r.remark)));
    } else {
      $rows.append(addRemarkRowHTML());
    }
  }

  // Extract a product object from hidden inputs for index idx
  function getProductFromHidden(idx) {
    const wrap = $hidden.find(`div[data-index="${idx}"]`);
    const prod = {
      product_name: wrap.find(`input[name="products[${idx}][product_name]"]`).val() || '',
      quantity:     Number(wrap.find(`input[name="products[${idx}][quantity]"]`).val() || 0),
      material_info:wrap.find(`input[name="products[${idx}][material_info]"]`).val() || '',
      remarks: []
    };
    // collect remarks
    wrap.find(`input[name^="products[${idx}][remarks]"][name$="[operation]"]`).each(function () {
      const m = this.name.match(/\[remarks]\[(\d+)]\[operation]/);
      if (!m) return;
      const k = m[1];
      const op = $(this).val();
      const tx = wrap.find(`input[name="products[${idx}][remarks][${k}][remark]"]`).val() || '';
      if (op && tx) prod.remarks.push({ operation: op, remark: tx });
    });
    return prod;
  }

  // Replace wrapper content for index idx with new hidden inputs for prod
  function rewriteHiddenProduct(idx, prod) {
    const $wrap = $hidden.find(`div[data-index="${idx}"]`).empty();
    $wrap.append(`<input type="hidden" name="products[${idx}][product_name]"  value="${escapeHtml(prod.product_name)}">`);
    $wrap.append(`<input type="hidden" name="products[${idx}][quantity]"      value="${escapeHtml(prod.quantity)}">`);
    $wrap.append(`<input type="hidden" name="products[${idx}][material_info]" value="${escapeHtml(prod.material_info || '')}">`);
    (prod.remarks || []).forEach((r, i) => {
      $wrap.append(`<input type="hidden" name="products[${idx}][remarks][${i}][operation]" value="${escapeHtml(r.operation)}">`);
      $wrap.append(`<input type="hidden" name="products[${idx}][remarks][${i}][remark]"    value="${escapeHtml(r.remark)}">`);
    });
  }

  // Create wrapper for new product (used when adding)
  function appendHiddenProduct(idx, prod) {
    $hidden.append(`<div data-index="${idx}"></div>`);
    rewriteHiddenProduct(idx, prod);
  }

  // Visible row + hidden inputs
  function addProductRow(prod, readOnly = false) {
    const idx = pIndex++;

    if ($tbody.length) {
      const tr = $(`
        <tr data-index="${idx}" ${readOnly ? 'class="read-only"' : ''}>
          <td>${escapeHtml(prod.product_name)}</td>
          <td>${escapeHtml(prod.quantity)}</td>
          <td class="remarks-cell"></td>
          <td>${escapeHtml(prod.material_info || '')}</td>
          <td>
            ${ readOnly ? '' : `
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-secondary edit-product">Edit</button>
                <button type="button" class="btn btn-sm btn-danger remove-product">Delete</button>
              </div>
            `}
          </td>
        </tr>
      `);
      tr.find('.remarks-cell').html(buildRemarksHtml(prod)); 
      $tbody.append(tr);
    }

    appendHiddenProduct(idx, prod);
    if (!fromCsv && pIndex >= MAX_MANUAL) $addBtn.hide();
  }

  // ----- modal open / add / edit -----
  const $addModal = $('#addProductModal');
  let editIdx = null; 

  $addBtn.on('click', function () {
    if (!fromCsv && pIndex >= MAX_MANUAL) return;
    editIdx = null;
    // fresh modal
    $('#p_name').val('');
    $('#p_qty').val('');
    $('#p_material').val('');
    $('#remarkRows').html(addRemarkRowHTML());
    $addModal.modal('show');
  });

  // When modal is shown without editIdx, ensure at least one blank row exists
  $addModal.on('shown.bs.modal', function () {
    if (editIdx !== null) return; 
    if (!$('#remarkRows .remark-row').length) {
      $('#remarkRows').html(addRemarkRowHTML());
    }
  });

  // Add/remove remark rows in modal
  $('#addRemarkRow').on('click', () => $('#remarkRows').append(addRemarkRowHTML()));
  $('#remarkRows').on('click', '.remove-remark', function () { $(this).closest('.remark-row').remove(); });

  // Click "Edit" on a row
  $tbody.on('click', '.edit-product', function () {
    if (fromCsv) return; 
    editIdx = Number($(this).closest('tr').data('index'));
    const current = getProductFromHidden(editIdx);
    setModalFromProduct(current);
    $addModal.modal('show');
  });

  // Save from modal (add or edit)
  $('#productForm').on('submit', function (e) {
    e.preventDefault();
    if (fromCsv) return; 

    const name = ($('#p_name').val() || '').trim();
    const qty  = parseInt($('#p_qty').val(), 10) || 0;
    const mat  = ($('#p_material').val() || '').trim();
    if (!name || qty <= 0) return;

    const remarks = [];
    $('#remarkRows .remark-row').each(function () {
      const op = $(this).find('.remark-op').val();
      const tx = ($(this).find('.remark-text').val() || '').trim();
      if (op && tx) remarks.push({ operation: op, remark: tx });
    });

    const prod = { product_name: name, quantity: qty, material_info: mat, remarks };

    if (editIdx === null) {
      addProductRow(prod, false);
    } else {
      // update existing (row + hidden)
      const $row = $tbody.find(`tr[data-index="${editIdx}"]`);
      $row.find('td:eq(0)').text(name);
      $row.find('td:eq(1)').text(qty);
      $row.find('.remarks-cell').html(buildRemarksHtml(prod));
      $row.find('td:eq(3)').text(mat || '');
      rewriteHiddenProduct(editIdx, prod);
      editIdx = null;
    }

    $addModal.modal('hide');
  });

  // Remove row
  $tbody.on('click', '.remove-product', function () {
    const $tr = $(this).closest('tr');
    const idx = Number($tr.data('index'));
    $tr.remove();
    $hidden.find(`div[data-index="${idx}"]`).remove();

    // Reindex wrappers' names to keep products[0..n-1]
    $hidden.children('div').each(function (i) {
      $(this).attr('data-index', i);
      $(this).find('input').each(function () {
        this.name = this.name.replace(/\[\d+\]/, `[${i}]`);
      });
    });

    // Reindex rows' data-index
    $tbody.find('tr').each(function (i) { $(this).attr('data-index', i); });

    pIndex = $tbody.find('tr').length;
    if (!fromCsv && pIndex < MAX_MANUAL) $addBtn.show();
  });


    /*************
     * CSV UPLOAD
     *************/
    const fileInput = document.getElementById('fileInput');
    const listEl    = document.getElementById('preview');
    const msgEl     = document.getElementById('attach-msg');
    const box       = document.getElementById('attach-box');
    const ALLOWED   = ['csv'];
    const $fromCsv  = $('#from_csv');

    let selectedFile = null;

    function addPreviewRow(file, { status = 'ready', note = '' }) {
      const li = document.createElement('li');
      li.innerHTML = `
        <span>${escapeHtml(file.name)}${
          status === 'error' ? ` – <span class="err">${escapeHtml(note)}</span>` :
                              ` – <span class="ok">ready</span>`}
        </span>
        <button class="remove-x" title="Remove">×</button>
      `;
      li.querySelector('.remove-x').addEventListener('click', () => {
        li.remove();
        selectedFile = null;
        // Clear CSV products
        $tbody.empty();
        $hidden.empty();
        pIndex  = 0;
        fromCsv = 0; $fromCsv.val(0);
        $addBtn.show();
        updateSummary();
      });
      listEl.innerHTML = '';
      listEl.appendChild(li);
    }

    function updateSummary() {
      msgEl.innerHTML = selectedFile ? `<span class="ok">1 file selected for upload</span>` : '';
    }

    function parseCsv(file) {
      const reader = new FileReader();
      reader.onload = function (e) {
        const text = e.target.result || '';
        const lines = text.split(/\r?\n/).filter(l => l.trim().length);
        if (!lines.length) return;

        // Clear current products
        $tbody.empty();
        $hidden.empty();
        pIndex = 0;

        // Expected columns: name, quantity, remark(optional), material_info(optional)
        for (let i = 1; i < lines.length; i++) {
          const cols = lines[i].split(',').map(s => s.trim());
          if (!cols[0]) continue;
          const prod = {
            product_name: cols[0],
            quantity:     Number(cols[1] || 0),
            material_info:(cols[3] || '')
          };
          // If a single remark column is present, treat as Printing
          const rText = cols[2] || '';
          if (rText) prod.remarks = [{ operation: 'printing', remark: rText }];

          addProductRow(prod, true); 
        }

        fromCsv = 1; $fromCsv.val(1);
        $addBtn.hide();
      };
      reader.readAsText(file);
    }

    function handleCsvUpload() {
      if (!fileInput?.files?.length) return;
      const f = fileInput.files[0];
      const ext = (f.name.split('.').pop() || '').toLowerCase();
      if (!ALLOWED.includes(ext)) {
        addPreviewRow(f, { status: 'error', note: 'Invalid file type' });
        selectedFile = null;
      } else {
        selectedFile = f;
        addPreviewRow(f, { status: 'ready' });
        parseCsv(f);
      }
      updateSummary();
      fileInput.value = '';
    }

    if (fileInput) fileInput.addEventListener('change', handleCsvUpload);

    if (box) {
      ['dragenter', 'dragover'].forEach(evt =>
        box.addEventListener(evt, e => { e.preventDefault(); box.classList.add('ring'); })
      );
      ['dragleave', 'drop'].forEach(evt =>
        box.addEventListener(evt, e => { e.preventDefault(); box.classList.remove('ring'); })
      );
      box.addEventListener('drop', e => {
        fileInput.files = e.dataTransfer.files;
        handleCsvUpload();
      });
    }

    // Initial button visibility
    if (!fromCsv && pIndex >= MAX_MANUAL) $addBtn.hide();

    // head artist search artist to assign 
    const $assignee = $('#assignee_artist_id');
    if (!$assignee.length) return; 

    $assignee.select2({
      placeholder: 'Search artists…',
      allowClear: true,
      width: '100%',
      minimumInputLength: 1,
      dropdownParent: $assignee.closest('.card, .modal, form'),
      ajax: {
        url: @json(route('artist.orders.assignees.search')),
        dataType: 'json',
        delay: 200,
        data: params => ({ q: params.term }),
        processResults: data => data, 
        cache: true
      },
      templateResult: item => {
        if (!item.id) return item.text;
        const m = item.meta || {};
        return $(`<div>
          <div class="fw-semibold">${item.text || ''}</div>
          <div class="text-muted small">${m.email || ''}</div>
        </div>`);
      },
      templateSelection: item => item.text || item.id,
      escapeMarkup: m => m
    });
});
</script>

@endpush

@endsection