@extends('layouts.app')
@section('title', $quotation->exists ? 'Edit ' . $quotation->quotation_number : 'New quotation')
@section('content')
@include('inventory._styles')
@php
  $editing = $quotation->exists;
  // Products for the editor: what was just submitted (after a validation error), else what is saved.
  $initialProducts = old('products') ?? $quotation->products->map(fn ($p) => [
      'product_name' => $p->product_name,
      'description'  => $p->description,
      'materials'    => $p->materials ?? [],
      'items'        => $p->items->map(fn ($i) => [
          'description'   => $i->description,
          'size_width'    => $i->size_width !== null ? $i->size_width + 0 : null,
          'size_height'   => $i->size_height !== null ? $i->size_height + 0 : null,
          'size_unit'     => $i->size_unit,
          'quantity'      => $i->quantity,
          'quantity_unit' => $i->quantity_unit,
          'unit_price'    => $i->unit_price,
          'total'         => $i->total,
      ])->all(),
  ])->all();
@endphp
<style>
  .qt-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:12px 16px}
  .qt-product{border:1px solid #E5E7EB;border-radius:12px;padding:14px;margin:14px 0;background:#FBFCFE}
  .qt-product-hd{display:flex;gap:8px;align-items:center;justify-content:space-between;margin-bottom:8px}
  .qt-items{width:100%;border-collapse:collapse;margin-top:8px}
  .qt-items th{font-size:12px;color:#667085;text-align:left;padding:4px}
  .qt-items td{padding:4px;vertical-align:top}
  .qt-items input,.qt-items select{width:100%}
  .qt-num{text-align:right}
  .qt-totals{max-width:360px;margin-left:auto}
  @media (max-width:760px){.qt-items thead{display:none}.qt-items tr{display:grid;grid-template-columns:1fr 1fr;gap:4px;border-top:1px solid #EEF2F7;padding:6px 0}.qt-items td:first-child{grid-column:1/-1}}
</style>

<div class="inv-wrap">
  <div class="inv-card">
    <div class="inv-hd">
      <div>
        <a href="{{ $editing ? route('quotations.show', $quotation) : route('quotations.index') }}" class="inv-sub"><i class="bi bi-arrow-left"></i> {{ $editing ? $quotation->quotation_number : 'Quotations' }}</a>
        <div class="inv-title">{{ $editing ? 'Edit quotation ' . $quotation->quotation_number : 'New quotation' }}</div>
        <div class="inv-sub">Prices, discount, tax and totals are typed by hand; nothing is calculated.</div>
      </div>
    </div>

    @include('inventory._flash')

    <form method="POST" action="{{ $editing ? route('quotations.update', $quotation) : route('quotations.store') }}" id="qtForm">
      @csrf
      @if ($editing) @method('PUT') @endif

      <div class="qt-grid">
        <div class="inv-field">
          <label class="inv-label" for="qtCompany">Company *</label>
          <select class="inv-control" name="company_id" id="qtCompany" required>
            @foreach ($companies as $c)
              <option value="{{ $c->id }}" @selected((string) old('company_id', $quotation->company_id) === (string) $c->id)>{{ $c->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="inv-field">
          <label class="inv-label" for="qtLead">Customer *</label>
          <select class="inv-control" name="lead_id" id="qtLead" required>
            <option value="">Select customer...</option>
            @foreach ($leads as $l)
              <option value="{{ $l->id }}" @selected((string) old('lead_id', $quotation->lead_id) === (string) $l->id)>{{ $l->company_name ?: $l->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="inv-field">
          <label class="inv-label" for="qtAttn">Attn</label>
          <input class="inv-control" name="attention" id="qtAttn" maxlength="255" value="{{ old('attention', $quotation->attention) }}" placeholder="e.g. Mr. Chang YK">
        </div>
        <div class="inv-field">
          <label class="inv-label" for="qtDate">Date *</label>
          <input class="inv-control" type="date" name="quotation_date" id="qtDate" required value="{{ old('quotation_date', $quotation->quotation_date?->format('Y-m-d')) }}">
        </div>
        <div class="inv-field">
          <label class="inv-label" for="qtTerms">Terms</label>
          <input class="inv-control" name="terms" id="qtTerms" maxlength="255" value="{{ old('terms', $quotation->terms) }}">
        </div>
        <div class="inv-field">
          <label class="inv-label" for="qtPo">P/O no.</label>
          <input class="inv-control" name="po_number" id="qtPo" maxlength="50" value="{{ old('po_number', $quotation->po_number) }}">
        </div>
      </div>

      <div class="inv-title" style="font-size:16px;margin-top:18px">Products</div>
      <div id="qtProducts"></div>
      <button type="button" class="inv-btn inv-btn-ghost" id="qtAddProduct"><i class="bi bi-plus-lg"></i> Add product</button>

      <div class="qt-totals">
        @foreach (['subtotal' => 'Subtotal (RM)', 'discount' => 'Discount (RM)', 'tax' => 'Tax (RM)', 'grand_total' => 'Grand total (RM) *'] as $field => $label)
          <div class="inv-field">
            <label class="inv-label" for="qt_{{ $field }}">{{ $label }}</label>
            <input class="inv-control qt-num" type="number" step="0.01" min="0" name="{{ $field }}" id="qt_{{ $field }}"
                   value="{{ old($field, $quotation->exists ? $quotation->$field : '') }}" @if ($field === 'grand_total') required @endif>
          </div>
        @endforeach
      </div>

      <div class="inv-field">
        <label class="inv-label" for="qtNotes">Notes</label>
        <textarea class="inv-control" name="notes" id="qtNotes" rows="3" maxlength="2000">{{ old('notes', $quotation->notes) }}</textarea>
      </div>

      <div class="inv-actions" style="justify-content:flex-end">
        <a class="inv-btn inv-btn-ghost" href="{{ $editing ? route('quotations.show', $quotation) : route('quotations.index') }}">Cancel</a>
        <button class="inv-btn inv-btn-dark" type="submit"><i class="bi bi-check2"></i> {{ $editing ? 'Save quotation' : 'Create quotation' }}</button>
      </div>
    </form>
  </div>
</div>

<template id="qtProductTpl">
  <div class="qt-product">
    <div class="qt-product-hd">
      <strong class="qt-product-no"></strong>
      <button type="button" class="inv-btn inv-btn-ghost qt-remove-product"><i class="bi bi-trash"></i> Remove product</button>
    </div>
    <div class="qt-grid">
      <div class="inv-field"><label class="inv-label">Product name *</label><input class="inv-control" data-f="product_name" maxlength="255" required></div>
      <div class="inv-field"><label class="inv-label">Materials (from the material list)</label><select class="inv-control qt-materials" data-f="materials" multiple></select></div>
    </div>
    <div class="inv-field"><label class="inv-label">Description</label><textarea class="inv-control" data-f="description" rows="2" maxlength="2000"></textarea></div>
    <table class="qt-items">
      <thead><tr><th>Item / description</th><th>Width</th><th>Height</th><th>Unit</th><th>Qty *</th><th></th><th>Unit price</th><th>Total</th><th></th></tr></thead>
      <tbody></tbody>
    </table>
    <button type="button" class="inv-btn inv-btn-ghost qt-add-item"><i class="bi bi-plus"></i> Add item</button>
  </div>
</template>

<template id="qtItemTpl">
  <tr>
    <td><input class="inv-control" data-f="description" maxlength="255" placeholder="e.g. Front panel"></td>
    <td><input class="inv-control qt-num" data-f="size_width" type="number" step="0.01" min="0"></td>
    <td><input class="inv-control qt-num" data-f="size_height" type="number" step="0.01" min="0"></td>
    <td><select class="inv-control" data-f="size_unit"><option value="">-</option>@foreach ($sizeUnits as $u)<option value="{{ $u }}">{{ $u }}</option>@endforeach</select></td>
    <td><input class="inv-control qt-num" data-f="quantity" type="number" step="1" min="1" required></td>
    <td><select class="inv-control" data-f="quantity_unit">@foreach ($quantityUnits as $u)<option value="{{ $u }}">{{ $u }}</option>@endforeach</select></td>
    <td><input class="inv-control qt-num" data-f="unit_price" type="number" step="0.01" min="0"></td>
    <td><input class="inv-control qt-num" data-f="total" type="number" step="0.01" min="0"></td>
    <td><button type="button" class="inv-btn inv-btn-ghost qt-remove-item" title="Remove item"><i class="bi bi-x-lg"></i></button></td>
  </tr>
</template>

<script>
(function () {
  const MATERIALS = @json($materials);
  const initial   = @json(array_values($initialProducts));
  const wrap      = document.getElementById('qtProducts');
  const pTpl      = document.getElementById('qtProductTpl');
  const iTpl      = document.getElementById('qtItemTpl');
  let pSeq = 0;

  const setName = (el, name) => el.setAttribute('name', name);

  function addItem(card, pIdx, item) {
    const body = card.querySelector('tbody');
    const iIdx = Number(card.dataset.itemSeq || 0);
    card.dataset.itemSeq = iIdx + 1;
    const row = iTpl.content.firstElementChild.cloneNode(true);
    row.querySelectorAll('[data-f]').forEach(el => {
      setName(el, `products[${pIdx}][items][${iIdx}][${el.dataset.f}]`);
      const v = item ? item[el.dataset.f] : null;
      if (v !== null && v !== undefined) el.value = v;
    });
    if (!item?.quantity) row.querySelector('[data-f="quantity"]').value = 1;
    row.querySelector('.qt-remove-item').addEventListener('click', () => {
      if (body.children.length > 1) row.remove();
    });
    body.appendChild(row);
  }

  function addProduct(product) {
    const pIdx = pSeq++;
    const card = pTpl.content.firstElementChild.cloneNode(true);
    card.querySelector('[data-f="product_name"]').value = product?.product_name ?? '';
    setName(card.querySelector('[data-f="product_name"]'), `products[${pIdx}][product_name]`);
    const desc = card.querySelector('textarea[data-f="description"]');
    desc.value = product?.description ?? '';
    setName(desc, `products[${pIdx}][description]`);

    const sel = card.querySelector('.qt-materials');
    setName(sel, `products[${pIdx}][materials][]`);
    const chosen = (product?.materials ?? []).map(String);
    const names = MATERIALS.slice();
    chosen.forEach(n => { if (!names.includes(n)) names.push(n); }); // keep a saved name even if it was deactivated
    names.forEach(n => {
      const o = document.createElement('option');
      o.value = n; o.textContent = n; o.selected = chosen.includes(n);
      sel.appendChild(o);
    });

    card.querySelector('.qt-add-item').addEventListener('click', () => addItem(card, pIdx, null));
    card.querySelector('.qt-remove-product').addEventListener('click', () => {
      if (wrap.children.length > 1) { card.remove(); renumber(); }
    });

    const items = product?.items?.length ? product.items : [null];
    items.forEach(it => addItem(card, pIdx, it));
    wrap.appendChild(card);
    renumber();

    if (window.jQuery && jQuery.fn && jQuery.fn.select2) {
      jQuery(sel).select2({ width: '100%', placeholder: 'Pick materials...' });
    }
  }

  function renumber() {
    [...wrap.children].forEach((c, i) => c.querySelector('.qt-product-no').textContent = 'Product ' + (i + 1));
  }

  (initial.length ? initial : [null]).forEach(p => addProduct(p));
  document.getElementById('qtAddProduct').addEventListener('click', () => addProduct(null));
})();
</script>
@endsection
