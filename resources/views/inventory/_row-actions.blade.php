{{-- Add / Deduct / Settings buttons for one material. Needs $m, $canAdjust, $isAdmin. --}}
@if ($canAdjust)
  <button type="button" class="inv-btn inv-btn-primary" data-move="add"
          data-url="{{ route('inventory.movements.store', $m) }}" data-name="{{ $m->materialName }}" data-unit="{{ $m->quantity_unit }}">
    <i class="bi bi-plus-lg"></i> Add
  </button>
  <button type="button" class="inv-btn inv-btn-ghost" data-move="deduct"
          data-url="{{ route('inventory.movements.store', $m) }}" data-name="{{ $m->materialName }}" data-unit="{{ $m->quantity_unit }}">
    <i class="bi bi-dash-lg"></i> Deduct
  </button>
@endif
@if ($isAdmin)
  <button type="button" class="inv-btn inv-btn-ghost" data-settings title="Stock settings"
          data-url="{{ route('inventory.settings.update', $m) }}" data-name="{{ $m->materialName }}"
          data-unit="{{ $m->quantity_unit }}"
          data-low-qty="{{ $m->low_stock_quantity }}">
    <i class="bi bi-gear"></i>
  </button>
@endif
