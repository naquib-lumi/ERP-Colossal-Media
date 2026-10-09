@extends('layouts.app')
@section('title', 'Quotation ' . $quotation->quotation_number)
@section('content')
@include('inventory._styles')
@php($q = $quotation)

<div class="inv-wrap">
  <div class="inv-card">
    <div class="inv-hd">
      <div>
        <a href="{{ route('quotations.index') }}" class="inv-sub"><i class="bi bi-arrow-left"></i> Quotations</a>
        <div class="inv-title">{{ $q->quotation_number }}
          @if ($q->isConverted())<span class="inv-badge ok">Converted to order</span>@else<span class="inv-badge near">Pending</span>@endif
        </div>
        <div class="inv-sub">
          {{ $q->company->name }} · {{ $q->quotation_date?->format('d M Y') }} ·
          salesperson {{ $q->salesperson->name ?? '-' }} · created by {{ $q->creator->name ?? '-' }}
        </div>
      </div>
      <div class="inv-actions">
        @if ($canEdit)
          <a class="inv-btn inv-btn-dark" href="{{ route('quotations.edit', $q) }}"><i class="bi bi-pencil"></i> Edit</a>
        @endif
      </div>
    </div>

    @include('inventory._flash')
    @if (session('error'))<div class="inv-alert bad">{{ session('error') }}</div>@endif
    @if ($q->isConverted() && $q->order)
      <div class="inv-alert">Converted to order {{ $q->order->order_number }}{{ $q->converted_at ? ' on ' . $q->converted_at->timezone('Asia/Kuala_Lumpur')->format('d M Y') : '' }}. This quotation is locked.</div>
    @endif

    <div class="inv-table-wrap">
      <table class="inv-table">
        <tbody>
          <tr><th style="width:180px">Customer</th><td>{{ $q->lead->company_name ?? '-' }}</td></tr>
          <tr><th>Attn</th><td>{{ $q->attention ?: '-' }}</td></tr>
          <tr><th>Terms</th><td>{{ $q->terms ?: '-' }}</td></tr>
          <tr><th>P/O no.</th><td>{{ $q->po_number ?: '-' }}</td></tr>
        </tbody>
      </table>
    </div>

    <div class="inv-table-wrap">
      <table class="inv-table">
        <thead>
          <tr>
            <th style="width:50px">#</th>
            <th>Description</th>
            <th class="inv-num">Quantity</th>
            <th class="inv-num">Unit price (RM)</th>
            <th class="inv-num">Total (RM)</th>
          </tr>
        </thead>
        <tbody>
          @php($n = 0)
          @foreach ($q->products as $product)
            <tr>
              <td></td>
              <td colspan="4">
                <strong>{{ $product->product_name }}</strong>
                @if ($product->materials)<div>Material: {{ implode(' + ', $product->materials) }}</div>@endif
                @if ($product->description)<div class="inv-sub">{!! nl2br(e($product->description)) !!}</div>@endif
              </td>
            </tr>
            @foreach ($product->items as $item)
              @php($n++)
              <tr>
                <td>{{ $n }}</td>
                <td>
                  {{ $item->description }}
                  @if ($item->size_width || $item->size_height)
                    <span class="inv-sub">Size: {{ $item->size_width + 0 }}{{ $item->size_unit === 'piece' ? '' : $item->size_unit }}W × {{ $item->size_height + 0 }}{{ $item->size_unit === 'piece' ? '' : $item->size_unit }}H</span>
                  @endif
                </td>
                <td class="inv-num">{{ $item->quantity }} {{ $item->quantity_unit }}</td>
                <td class="inv-num">{{ number_format((float) $item->unit_price, 2) }}</td>
                <td class="inv-num">{{ number_format((float) $item->total, 2) }}</td>
              </tr>
            @endforeach
          @endforeach
        </tbody>
        <tfoot>
          <tr><td colspan="4" class="inv-num">Subtotal</td><td class="inv-num">{{ number_format((float) $q->subtotal, 2) }}</td></tr>
          @if ((float) $q->discount > 0)<tr><td colspan="4" class="inv-num">Discount</td><td class="inv-num">-{{ number_format((float) $q->discount, 2) }}</td></tr>@endif
          @if ((float) $q->tax > 0)<tr><td colspan="4" class="inv-num">Tax</td><td class="inv-num">{{ number_format((float) $q->tax, 2) }}</td></tr>@endif
          <tr><td colspan="4" class="inv-num"><strong>TOTAL (RM)</strong></td><td class="inv-num"><strong>{{ number_format((float) $q->grand_total, 2) }}</strong></td></tr>
        </tfoot>
      </table>
    </div>

    @if ($q->notes)
      <div class="inv-sub" style="margin-top:12px"><strong>Notes:</strong> {!! nl2br(e($q->notes)) !!}</div>
    @endif
  </div>
</div>
@endsection
