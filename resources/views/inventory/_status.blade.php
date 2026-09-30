{{-- Stock status badge for material $m --}}
@if ((float) $m->stock_quantity < 0 || (float) $m->stock_volume < 0)
  <span class="inv-badge neg">Negative</span>
@elseif ($m->isLowStock())
  <span class="inv-badge low">Low</span>
@else
  <span class="inv-badge ok">OK</span>
@endif
