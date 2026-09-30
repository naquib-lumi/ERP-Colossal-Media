@extends('layouts.app')
@section('title', 'Inventory: ' . $material->materialName)
@section('content')
@include('inventory._styles')
@php($sqft = \App\Services\MaterialStockService::class)
@php($m = $material)

<div class="inv-wrap">
  <div class="inv-card">
    <div class="inv-hd">
      <div>
        <a href="{{ route('inventory.index') }}" class="inv-sub"><i class="bi bi-arrow-left"></i> Inventory</a>
        <div class="inv-title">{{ $m->materialName }}</div>
        <div class="inv-sub">
          {{ $m->materialType->name ?? '-' }} ·
          <strong class="{{ (float) $m->stock_quantity < 0 ? 'inv-neg' : '' }}">{{ number_format((float) $m->stock_quantity, 2) }} {{ $m->quantity_unit }}</strong> ·
          <strong class="{{ (float) $m->stock_volume < 0 ? 'inv-neg' : '' }}">{{ number_format($sqft::toSqFt($m->stock_volume), 2) }} sq ft</strong> ·
          @include('inventory._status', ['m' => $m])
        </div>
      </div>
      <div class="inv-actions">@include('inventory._row-actions', ['m' => $m])</div>
    </div>

    @include('inventory._flash')

    <div class="inv-table-wrap">
      <table class="inv-table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Type</th>
            <th class="inv-num">Quantity</th>
            <th class="inv-num">Volume (sq ft)</th>
            <th class="inv-num">Balance after</th>
            <th>Reason</th>
            <th>By</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($movements as $mv)
            @php($q = (float) $mv->quantity_change)
            @php($v = $sqft::toSqFt($mv->volume_change))
            <tr>
              <td style="white-space:nowrap">{{ $mv->created_at?->timezone('Asia/Kuala_Lumpur')->format('d M Y, H:i') }}</td>
              <td>
                {{ $mv->type->label() }}
                @if ($mv->order)
                  <div class="inv-sub">{{ $mv->order->order_number }}</div>
                @endif
              </td>
              <td class="inv-num {{ $q < 0 ? 'inv-neg' : ($q > 0 ? 'inv-pos' : '') }}">{{ $q == 0 ? '-' : sprintf('%+.2f', $q) }}</td>
              <td class="inv-num {{ $v < 0 ? 'inv-neg' : ($v > 0 ? 'inv-pos' : '') }}">{{ $v == 0 ? '-' : sprintf('%+.2f', $v) }}</td>
              <td class="inv-num">
                {{ number_format((float) $mv->quantity_after, 2) }} {{ $m->quantity_unit }}<br>
                <span class="inv-sub">{{ number_format($sqft::toSqFt($mv->volume_after), 2) }} sq ft</span>
              </td>
              <td>{{ $mv->reason ?? '-' }}</td>
              <td style="white-space:nowrap">{{ $mv->user->name ?? 'System' }}</td>
            </tr>
          @empty
            <tr><td colspan="7" class="inv-empty">No stock changes yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($movements->hasPages())
      <div class="inv-pager">
        Page {{ $movements->currentPage() }} of {{ $movements->lastPage() }}
        @if ($movements->previousPageUrl())<a class="inv-btn inv-btn-ghost" href="{{ $movements->previousPageUrl() }}">Newer</a>@endif
        @if ($movements->nextPageUrl())<a class="inv-btn inv-btn-ghost" href="{{ $movements->nextPageUrl() }}">Older</a>@endif
      </div>
    @endif
  </div>
</div>

@include('inventory._modals')
@endsection
