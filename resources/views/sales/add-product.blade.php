
<form id="product-form" action="" method="POST" enctype="multipart/form-data">
  @csrf
  @method('PUT')
  <div class="card mb-6">
    <div class="card-header">
      <h5 class="mb-0">
        <i class="bx bx-package me-2"></i>Product
      </h5>
    </div>

    <div class="card-body p-4">
      <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-xl-3">
          <label class="form-label">Product Name</label>
          <input name="product[name]" type="text" class="form-control" placeholder="e.g. Business Card">
        </div>
        <div class="col-12 col-md-6 col-xl-3">
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
              <div class="col-12 col-md-3">
                <label class="form-label">Delivery Method</label>
                <select name="deliveries[0][method]" class="form-select">
                  <option value="">Method</option>
                  <option value="courier">Courier</option>
                  <option value="pickup">Pickup</option>
                  <option value="install">Install</option>
                </select>
              </div>

              <div class="col-12 col-md-3">
                <label class="form-label">Location Address</label>
                <input name="deliveries[0][location]" type="text" class="form-control" placeholder="Location">
              </div>

              <div class="col-12 col-md-2">
                <label class="form-label">Quantity</label>
                <input name="deliveries[0][qty]" type="number" min="0" class="form-control" placeholder="Qty">
              </div>

              <div class="col-12 col-md-4">
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
    <div class="bg-body position-sticky bottom-0 border-top py-3 d-flex gap-2 justify-content-end" style="z-index: 10">
      <button type="button" class="btn btn-outline-secondary" onclick="history.back()">Cancel</button>
      <button type="button" name="action" value="draft" id="btn-draft" class="btn btn-secondary">Save Draft</button>
      <button type="button" name="action" value="submit" id="btn-submit" class="btn btn-primary">Save and Submit</button>
    </div>
  </div>
  </div>
</form>


@push('scripts')
<script>
  (function() {
    // add item --------------------------------------------------------------------------------------------
    function openOnly(id) {
      // id like '#itemPane3'
      document.querySelectorAll('#productItems .accordion-collapse.show')
        .forEach(el => new bootstrap.Collapse(el, {
          toggle: false
        }).hide());
      new bootstrap.Collapse(document.querySelector(id), {
        toggle: true
      }).show();
    }

    const container = document.getElementById('productItems');
    const addBtn = document.getElementById('addItemBtn');
    const tplEl = document.getElementById('itemTemplate');

    // read the starting display number and next array index from data-attrs
    const startNumber = parseInt(container?.dataset.startNumber ?? '1', 10);

    // seed nextIndex from data-next-index, else fall back to current count
    let nextIndex = parseInt(container?.dataset.nextIndex ??
      container.querySelectorAll('.accordion-item[data-kind="item"]').length, 10);

    function addItem() {
      const raw = tplEl.innerHTML;
      const idx = nextIndex++;
      const html = raw.replace(/__INDEX__/g, idx);
      const frag = document.createRange().createContextualFragment(html);
      container.appendChild(frag);
      renumberAndLockFirst();
    }

    function renumberAndLockFirst() {
      const items = [...container.querySelectorAll('.accordion-item[data-kind="item"]')];
      items.forEach((wrap, i) => {
        // keep the display numbering using your startNumber
        wrap.querySelector('.item-number').textContent = startNumber + i;

        const del = wrap.querySelector('.delete-item');
        if (del) del.classList.remove('d-none');
      });
    }

    // Keep header mini summary (name • qty) updated
    function updateSummary(wrap) {
      const name = wrap.querySelector('input[name^="items"][name$="[name]"]')?.value || '';
      const qty = wrap.querySelector('input[name^="items"][name$="[qty]"]')?.value || '';
      wrap.querySelector('.item-summary').textContent = name + (qty ? ` • ${qty}` : '');
    }

    // Delegated events for delete, chevron, and summary update
    container.addEventListener('click', (e) => {
      // Delete
      const delBtn = e.target.closest('.delete-item');
      if (delBtn) {
        const wrap = delBtn.closest('.accordion-item');
        if (wrap) {
          wrap.remove();
          renumberAndLockFirst();
        }
        e.preventDefault();
        e.stopPropagation();
        return;
      }

      // Chevron is handled by Bootstrap via data-attrs.
      // We only stop it from bubbling in case the header has listeners.
      const chev = e.target.closest('.chevron');
      if (chev) {
        e.stopPropagation();
      }
    });

    container.addEventListener('input', (e) => {
      const wrap = e.target.closest('.accordion-item[data-kind="item"]');
      if (wrap) updateSummary(wrap);
    });

    // Add item
    if (addBtn) addBtn.addEventListener('click', addItem);

    // Initialize summaries & first-item trash hide for server-rendered items
    renumberAndLockFirst();
    container.querySelectorAll('.accordion-item[data-kind="item"]').forEach(updateSummary);

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
</script>
@endpush

@endsection