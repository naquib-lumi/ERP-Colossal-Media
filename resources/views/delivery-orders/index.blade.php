@extends('layouts.app')
@section('title', 'Delivery Orders')
@section('content')
@include('inventory._styles')

<div class="inv-wrap">
  <div class="inv-card">
    <div class="inv-hd">
      <div>
        <div class="inv-title">Delivery orders</div>
        <div class="inv-sub">Newest delivery date first. Open a delivery order to print it or email it to the client.</div>
      </div>
    </div>

    <form class="inv-toolbar" method="GET" action="{{ route('delivery-orders.index') }}">
      <input type="text" name="q" value="{{ $filters['q'] }}" placeholder="DO no., order no., client or location..." aria-label="Search delivery orders">
      <select name="status" aria-label="Status">
        <option value="issued" @selected($filters['status'] === 'issued')>Active</option>
        <option value="cancelled" @selected($filters['status'] === 'cancelled')>Cancelled</option>
        <option value="all" @selected($filters['status'] === 'all')>All</option>
      </select>
      <button class="inv-btn inv-btn-dark" type="submit"><i class="bi bi-search"></i> Filter</button>
    </form>

    <div class="inv-table-wrap">
      <table class="inv-table">
        <thead>
          <tr>
            <th>DO no.</th>
            <th>Order / client</th>
            <th>Deliver to</th>
            <th>Date</th>
            <th class="inv-num">Qty</th>
            <th style="text-align:right">Actions</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($dos as $do)
            <tr>
              <td>
                <strong>{{ $do->do_number }}</strong>
                @if ($do->isCancelled())<div><span class="inv-badge neg">Cancelled</span></div>
                @elseif ($do->isDelivered())<div><span class="inv-badge ok">Delivered</span></div>
                @elseif ($do->changedSinceEmailed())<div><span class="inv-badge low">Changed since emailed</span></div>
                @elseif ($do->emailed_at)<div><span class="inv-badge ok">Emailed</span></div>@endif
              </td>
              <td>
                <a href="{{ route('orders.delivery-orders', $do->order_id) }}">{{ $do->order->order_number ?? '-' }}</a>
                <div class="inv-sub">{{ $do->order->companyName ?? '' }}</div>
              </td>
              <td style="max-width:320px">{{ $do->location }}</td>
              <td style="white-space:nowrap">{{ $do->delivery_date ? $do->delivery_date->format('d M Y') : 'TBC' }}</td>
              <td class="inv-num">{{ (int) $do->lines_sum_quantity }}</td>
              <td>
                <div class="inv-actions">
                  <a class="inv-btn inv-btn-primary" href="{{ route('delivery-orders.show', $do) }}" target="_blank"><i class="bi bi-printer"></i> Open</a>
                  @if ($canPrint)<a class="inv-btn inv-btn-ghost" href="{{ route('delivery-orders.pdf', $do) }}"><i class="bi bi-file-earmark-pdf"></i> PDF</a>@endif
                  <a class="inv-btn inv-btn-ghost" href="{{ route('delivery-orders.status', $do) }}"><i class="bi bi-truck"></i> {{ ! $do->isDelivered() && ! $do->isCancelled() && $canPrint ? 'Mark delivered' : 'Status' }}</a>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="6" class="inv-empty">No delivery orders found.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($dos->hasPages())
      <div class="inv-pager">
        Page {{ $dos->currentPage() }} of {{ $dos->lastPage() }}
        @if ($dos->previousPageUrl())<a class="inv-btn inv-btn-ghost" href="{{ $dos->previousPageUrl() }}">Previous</a>@endif
        @if ($dos->nextPageUrl())<a class="inv-btn inv-btn-ghost" href="{{ $dos->nextPageUrl() }}">Next</a>@endif
      </div>
    @endif
  </div>
</div>
@endsection
