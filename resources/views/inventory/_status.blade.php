{{-- Stock status badge for material $m --}}
@if ($m->stock_quantity < 0)
  <span class="inv-badge neg">Negative</span>
@elseif ($m->isLowStock())
  <span class="inv-badge low">Low</span>
@elseif ($m->isNearLowStock())
  <span class="inv-badge near">Near low</span>
@else
  <span class="inv-badge ok">OK</span>
@endif
