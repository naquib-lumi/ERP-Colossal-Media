{{-- Add / deduct stock and (admin) settings dialogs. Buttons carry data-* attributes; see script below. --}}
@if ($canAdjust)
<div class="inv-mask" id="mdlMove" aria-hidden="true">
  <form class="inv-dialog" method="POST" id="frmMove">
    @csrf
    <input type="hidden" name="direction" id="mvDirection">
    <div class="inv-dhd">
      <span id="mvTitle">Add stock</span>
      <button type="button" class="inv-x" data-close aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="inv-dbd">
      <div class="inv-field">
        <span class="inv-label">Material</span>
        <strong id="mvMaterial"></strong>
      </div>
      <div class="inv-field" id="mvTypeField">
        <label class="inv-label" for="mvType">Type</label>
        <select class="inv-control" name="type" id="mvType">
          <option value="restock">Restock (stock received)</option>
          <option value="adjustment">Adjustment (count correction, damage...)</option>
        </select>
      </div>
      <div class="inv-field">
        <label class="inv-label" for="mvQty">Quantity (<span id="mvUnit">units</span>) *</label>
        <input class="inv-control" type="number" step="1" min="1" name="quantity" id="mvQty" placeholder="0" required>
      </div>
      <div class="inv-field">
        <label class="inv-label" for="mvReason">Reason *</label>
        <textarea class="inv-control" name="reason" id="mvReason" maxlength="500" required placeholder="e.g. Supplier delivery INV-1234"></textarea>
      </div>
    </div>
    <div class="inv-dft">
      <button type="button" class="inv-btn inv-btn-ghost" data-close>Cancel</button>
      <button type="submit" class="inv-btn inv-btn-dark" id="mvSubmit">Save</button>
    </div>
  </form>
</div>
@endif

@if ($isAdmin)
<div class="inv-mask" id="mdlSettings" aria-hidden="true">
  <form class="inv-dialog" method="POST" id="frmSettings">
    @csrf
    @method('PATCH')
    <div class="inv-dhd">
      <span>Stock settings</span>
      <button type="button" class="inv-x" data-close aria-label="Close"><i class="bi bi-x-lg"></i></button>
    </div>
    <div class="inv-dbd">
      <div class="inv-field">
        <span class="inv-label">Material</span>
        <strong id="stMaterial"></strong>
      </div>
      <div class="inv-field">
        <label class="inv-label" for="stUnit">Quantity unit</label>
        <input class="inv-control" name="quantity_unit" id="stUnit" maxlength="30" placeholder="e.g. roll, sheet, piece, box">
        <div class="inv-hint">What counts as 1 in stock for this material.</div>
      </div>
      <div class="inv-field">
        <label class="inv-label" for="stLowQty">Low-stock alert at</label>
        <input class="inv-control" type="number" step="1" min="0" name="low_stock_quantity" id="stLowQty" placeholder="No alert">
      </div>
      <div class="inv-hint">When stock drops to this number or below, all admins get an email and a notification. Leave empty to turn the alert off.</div>
    </div>
    <div class="inv-dft">
      <button type="button" class="inv-btn inv-btn-ghost" data-close>Cancel</button>
      <button type="submit" class="inv-btn inv-btn-dark">Save</button>
    </div>
  </form>
</div>
@endif

<script>
(function () {
  const open  = id => document.getElementById(id).classList.add('open');
  const close = el => el.closest('.inv-mask').classList.remove('open');

  document.querySelectorAll('.inv-mask [data-close]').forEach(b => b.addEventListener('click', () => close(b)));
  document.querySelectorAll('.inv-mask').forEach(m => m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); }));

  document.querySelectorAll('[data-move]').forEach(btn => btn.addEventListener('click', () => {
    const add = btn.dataset.move === 'add';
    document.getElementById('frmMove').action = btn.dataset.url;
    document.getElementById('mvDirection').value = btn.dataset.move;
    document.getElementById('mvTitle').textContent = add ? 'Add stock' : 'Deduct stock';
    document.getElementById('mvMaterial').textContent = btn.dataset.name;
    document.getElementById('mvUnit').textContent = btn.dataset.unit || 'units';
    const type = document.getElementById('mvType');
    type.value = add ? 'restock' : 'adjustment';
    type.querySelector('option[value="restock"]').disabled = !add;   // restock only adds
    document.getElementById('mvQty').value = '';
    document.getElementById('mvReason').value = '';
    document.getElementById('mvSubmit').textContent = add ? 'Add stock' : 'Deduct stock';
    open('mdlMove');
  }));

  document.querySelectorAll('[data-settings]').forEach(btn => btn.addEventListener('click', () => {
    document.getElementById('frmSettings').action = btn.dataset.url;
    document.getElementById('stMaterial').textContent = btn.dataset.name;
    document.getElementById('stUnit').value = btn.dataset.unit || '';
    document.getElementById('stLowQty').value = btn.dataset.lowQty || '';
    open('mdlSettings');
  }));
})();
</script>
