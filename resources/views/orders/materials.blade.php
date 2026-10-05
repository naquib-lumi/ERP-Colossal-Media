@extends('layouts.app')
@section('title', 'Materials – ' . $order->order_number)
@section('content')
@include('inventory._styles')

<div class="inv-wrap">
  <div class="inv-card">
    <div class="inv-hd">
      <div>
        <a href="{{ url()->previous() }}" class="inv-sub"><i class="bi bi-arrow-left"></i> Back</a>
        <div class="inv-title">Materials – {{ $order->order_number }}</div>
        <div class="inv-sub">{{ $order->companyName ?: '-' }} · {{ $order->orderTitle ?: '-' }} · from the order items; stock is not deducted automatically</div>
      </div>
    </div>

    <div class="inv-table-wrap">
      <table class="inv-table">
        <thead>
          <tr>
            <th>Material</th>
            <th>Used on</th>
            <th class="inv-num">Items</th>
            <th class="inv-num">Pieces</th>
            <th class="inv-num">In stock</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($needs as $need)
            @php($m = $need['material'])
            <tr>
              <td>
                @if ($m)
                  <a href="{{ route('inventory.show', $m) }}">{{ $m->materialName }}</a>
                @else
                  {{ $need['name'] }} <div class="inv-sub">Not in the material list</div>
                @endif
              </td>
              <td>{{ implode(', ', $need['products']) }}</td>
              <td class="inv-num">{{ $need['items'] }}</td>
              <td class="inv-num">{{ number_format($need['pieces']) }}</td>
              <td class="inv-num {{ $m && $m->stock_quantity < 0 ? 'inv-neg' : '' }}">
                {{ $m ? number_format($m->stock_quantity) . ' ' . $m->quantity_unit : '-' }}
              </td>
              <td>@if ($m)@include('inventory._status', ['m' => $m])@else - @endif</td>
            </tr>
          @empty
            <tr><td colspan="6" class="inv-empty">No materials on this order's items yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    <div class="inv-title" style="font-size:16px;margin-top:24px">Items per product</div>
    <div class="inv-table-wrap">
      <table class="inv-table">
        <thead>
          <tr>
            <th>Product</th>
            <th>Item</th>
            <th>Size</th>
            <th class="inv-num">Qty</th>
            <th>Materials</th>
          </tr>
        </thead>
        <tbody>
          @php($any = false)
          @foreach ($order->products as $product)
            @foreach ($product->items as $item)
              @php($any = true)
              <tr>
                <td>{{ $product->productName }}</td>
                <td>{{ $item->itemName ?: '-' }}</td>
                <td style="white-space:nowrap">
                  @if ($item->sizeWidth || $item->sizeHeight){{ $item->sizeWidth + 0 }} × {{ $item->sizeHeight + 0 }} {{ $item->sizeUnit }}@else - @endif
                </td>
                <td class="inv-num">{{ $item->quantity }}</td>
                <td>{{ collect((array) $item->material)->filter()->implode(', ') ?: '-' }}</td>
              </tr>
            @endforeach
          @endforeach
          @unless ($any)
            <tr><td colspan="5" class="inv-empty">No items yet.</td></tr>
          @endunless
        </tbody>
      </table>
    </div>

    <div class="inv-title" style="font-size:16px;margin-top:24px">Stock deducted for this order</div>
    <div class="inv-table-wrap">
      <table class="inv-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Material</th>
            <th class="inv-num">Change</th>
            <th>Reason</th>
            <th>By</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($deductions as $mv)
            <tr>
              <td style="white-space:nowrap">{{ $mv->created_at?->timezone('Asia/Kuala_Lumpur')->format('d M Y, H:i') }}</td>
              <td>{{ $mv->material_name }}</td>
              <td class="inv-num inv-neg">{{ sprintf('%+d', $mv->quantity_change) }}</td>
              <td>{{ $mv->reason ?? '-' }}</td>
              <td style="white-space:nowrap">{{ $mv->user->name ?? 'System' }}</td>
            </tr>
          @empty
            <tr><td colspan="5" class="inv-empty">No stock has been deducted for this order. Admin or boss can link a deduction to it on the Inventory page.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
