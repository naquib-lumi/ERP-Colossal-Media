@extends('layouts.app')
@section('title', 'Delivery Orders – ' . $order->order_number)
@section('content')
@include('inventory._styles')

<div class="inv-wrap">
  <div class="inv-card">
    <div class="inv-hd">
      <div>
        <a href="{{ url()->previous() }}" class="inv-sub"><i class="bi bi-arrow-left"></i> Back</a>
        <div class="inv-title">Delivery orders – {{ $order->order_number }}</div>
        <div class="inv-sub">{{ $order->companyName ?: '-' }} · {{ $order->orderTitle ?: '-' }} · one delivery order per delivery location</div>
      </div>
      @if ($canGenerate && $canBuild && $hasRows && ($outOfDate || $issued->isEmpty()))
        <form method="POST" action="{{ route('orders.delivery-orders.sync', $order) }}">
          @csrf
          <button class="inv-btn inv-btn-dark" type="submit">
            <i class="bi bi-arrow-repeat"></i> {{ $issued->isEmpty() ? 'Create delivery orders' : 'Update delivery orders' }}
          </button>
        </form>
      @endif
    </div>

    @include('inventory._flash')
    @if (session('error'))<div class="inv-alert bad">{{ session('error') }}</div>@endif

    @if (! $canBuild)
      <div class="inv-alert bad">Delivery orders can be created once the order is submitted.</div>
    @elseif (! $hasRows)
      <div class="inv-alert bad">This order has no delivery locations yet. Add delivery details to its products first.</div>
    @elseif ($outOfDate && $issued->isNotEmpty())
      <div class="inv-alert bad">
        The order's delivery details changed since these delivery orders were made.
        @if ($canGenerate) Click <strong>Update delivery orders</strong> to bring them up to date. @else Ask dispatch or admin to update them. @endif
      </div>
    @endif

    <div class="inv-table-wrap">
      <table class="inv-table">
        <thead>
          <tr>
            <th>DO no.</th>
            <th>Deliver to</th>
            <th>Date</th>
            <th class="inv-num">Lines / qty</th>
            <th>Emailed</th>
            <th style="text-align:right">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($issued as $do)
            <tr>
              <td><strong>{{ $do->do_number }}</strong>@if ($do->isDelivered()) <span class="inv-badge ok">Delivered</span>@endif<div class="inv-sub">{{ collect(explode(', ', (string) $do->methods))->filter()->map(fn ($m) => dd_method_label($m))->implode(', ') }}</div></td>
              <td style="max-width:320px">{{ $do->location }}</td>
              <td style="white-space:nowrap">{{ $do->delivery_date ? $do->delivery_date->format('d M Y') : 'TBC' }}</td>
              <td class="inv-num">{{ $do->lines->count() }} / {{ $do->totalQuantity() }}</td>
              <td>
                @if ($do->emailed_at)
                  <span class="inv-badge {{ $do->changedSinceEmailed() ? 'low' : 'ok' }}">{{ $do->changedSinceEmailed() ? 'Changed since emailed' : 'Emailed' }}</span>
                  <div class="inv-sub">{{ $do->emailed_to }}, {{ $do->emailed_at->timezone('Asia/Kuala_Lumpur')->format('d M Y') }}</div>
                @else
                  <span class="inv-sub">Not yet</span>
                @endif
              </td>
              <td>
                <div class="inv-actions">
                  <a class="inv-btn inv-btn-primary" href="{{ route('delivery-orders.show', $do) }}" target="_blank"><i class="bi bi-printer"></i> {{ $canPrint ? 'Print' : 'View' }}{{ $canEmail ? ' / email' : '' }}</a>
                  @if ($canPrint)<a class="inv-btn inv-btn-ghost" href="{{ route('delivery-orders.pdf', $do) }}"><i class="bi bi-file-earmark-pdf"></i> PDF</a>@endif
                  <a class="inv-btn inv-btn-ghost" href="{{ route('delivery-orders.status', $do) }}"><i class="bi bi-truck"></i> {{ ! $do->isDelivered() && $canPrint ? 'Mark delivered' : 'Status' }}</a>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="inv-empty">No delivery orders yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($cancelled->isNotEmpty())
      <div class="inv-table-wrap">
        <div class="inv-sub" style="padding:4px 4px 0">Cancelled (location no longer on the order)</div>
        <table class="inv-table">
          <tbody>
            @foreach ($cancelled as $do)
              <tr>
                <td><span class="inv-badge neg">Cancelled</span> {{ $do->do_number }}</td>
                <td>{{ $do->location }}</td>
                <td><div class="inv-actions"><a class="inv-btn inv-btn-ghost" href="{{ route('delivery-orders.show', $do) }}" target="_blank">View</a></div></td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>
@endsection
