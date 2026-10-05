@extends('layouts.app')
@section('title', 'Inventory: ' . $material->materialName)
@section('content')
@include('inventory._styles')
@php($m = $material)

<div class="inv-wrap">
  <div class="inv-card">
    <div class="inv-hd">
      <div>
        <a href="{{ route('inventory.index') }}" class="inv-sub"><i class="bi bi-arrow-left"></i> Inventory</a>
        <div class="inv-title">{{ $m->materialName }}</div>
        <div class="inv-sub">
          {{ $m->materialType->name ?? '-' }} ·
          <strong class="{{ $m->stock_quantity < 0 ? 'inv-neg' : '' }}">{{ number_format($m->stock_quantity) }} {{ $m->quantity_unit }}</strong> ·
          @if ($m->low_stock_quantity !== null)alert at {{ number_format($m->low_stock_quantity) }} ·@endif
          @include('inventory._status', ['m' => $m])
        </div>
        @if ($m->internal_ref || $m->materialDescription)
          <div class="inv-sub">
            @if ($m->internal_ref)Ref: {{ $m->internal_ref }}@endif
            @if ($m->internal_ref && $m->materialDescription) · @endif
            {{ $m->materialDescription }}
          </div>
        @endif
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
            <th class="inv-num">Change</th>
            <th class="inv-num">Balance after</th>
            <th>Reason</th>
            <th>Order</th>
            <th>By</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($movements as $mv)
            @php($q = $mv->quantity_change)
            <tr>
              <td style="white-space:nowrap">{{ $mv->created_at?->timezone('Asia/Kuala_Lumpur')->format('d M Y, H:i') }}</td>
              <td>{{ $mv->type->label() }}</td>
              <td class="inv-num {{ $q < 0 ? 'inv-neg' : 'inv-pos' }}">{{ sprintf('%+d', $q) }}</td>
              <td class="inv-num">{{ number_format($mv->quantity_after) }} {{ $m->quantity_unit }}</td>
              <td>{{ $mv->reason ?? '-' }}</td>
              <td style="white-space:nowrap">
                @if ($mv->order)<a href="{{ route('orders.materials', $mv->order) }}">{{ $mv->order->order_number }}</a>@else - @endif
              </td>
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
