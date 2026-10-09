@extends('layouts.app')
@section('title', 'Delivery status – ' . $do->do_number)
@section('content')
@include('inventory._styles')
@php($order = $do->order)

<div class="inv-wrap">
  <div class="inv-card">
    <div class="inv-hd">
      <div>
        <a href="{{ route('orders.delivery-orders', $do->order_id) }}" class="inv-sub"><i class="bi bi-arrow-left"></i> Delivery orders – {{ $order->order_number }}</a>
        <div class="inv-title">{{ $do->do_number }}</div>
        <div class="inv-sub">{{ $order->companyName ?: '-' }} · {{ $do->location }} · {{ $do->lines->count() }} line(s), {{ $do->totalQuantity() }} pcs</div>
      </div>
      <div class="inv-actions">
        <a class="inv-btn inv-btn-ghost" href="{{ route('delivery-orders.show', $do) }}" target="_blank"><i class="bi bi-file-earmark-text"></i> {{ $canPrint ? 'Print' : 'View' }}</a>
      </div>
    </div>

    @include('inventory._flash')
    @if (session('error'))<div class="inv-alert bad">{{ session('error') }}</div>@endif

    @if ($do->isDelivered())
      <div class="inv-alert" style="background:var(--ok-bg);color:var(--ok)">
        <strong>Delivered</strong> on {{ $do->delivered_at->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') }}
        by {{ $do->deliveredBy->name ?? 'unknown' }}.
        @if ($do->delivery_remarks)<div style="margin-top:4px">{{ $do->delivery_remarks }}</div>@endif
      </div>
      @if ($do->signed_photo_path)
        <a href="{{ route('delivery-orders.photo', $do) }}" target="_blank">
          <img src="{{ route('delivery-orders.photo', $do) }}" alt="Signed {{ $do->do_number }}" style="max-width:100%;max-height:480px;border:1px solid #E5E7EB;border-radius:10px">
        </a>
      @endif
    @elseif ($do->isCancelled())
      <div class="inv-alert bad">This delivery order is cancelled; its location is no longer on the order.</div>
    @elseif ($canConfirm)
      <form class="inv-card" style="max-width:560px;padding:16px;margin:0" method="POST" action="{{ route('delivery-orders.deliver', $do) }}" enctype="multipart/form-data">
        @csrf
        <div class="inv-title" style="font-size:16px">Mark as delivered</div>
        <div class="inv-field">
          <label class="inv-label" for="dlvPhoto">Photo of the signed DO *</label>
          <input class="inv-control" type="file" name="signed_photo" id="dlvPhoto" accept="image/*" capture="environment" required>
        </div>
        <div class="inv-field">
          <label class="inv-label" for="dlvAt">Delivered on *</label>
          <input class="inv-control" type="datetime-local" name="delivered_at" id="dlvAt" required
                 value="{{ old('delivered_at', now()->timezone('Asia/Kuala_Lumpur')->format('Y-m-d\TH:i')) }}">
        </div>
        <div class="inv-field">
          <label class="inv-label" for="dlvRemarks">Remarks</label>
          <textarea class="inv-control" name="delivery_remarks" id="dlvRemarks" rows="3" maxlength="1000" placeholder="e.g. Received by store manager">{{ old('delivery_remarks') }}</textarea>
        </div>
        <button class="inv-btn inv-btn-dark" type="submit"><i class="bi bi-check2-circle"></i> Mark delivered</button>
      </form>
    @else
      <div class="inv-alert">Not delivered yet. Dispatch or delivery staff mark it delivered with a photo of the signed DO.</div>
    @endif

    <div class="inv-title" style="font-size:16px;margin-top:24px">History</div>
    <div class="inv-table-wrap">
      <table class="inv-table">
        <thead><tr><th>Date</th><th>What happened</th><th>By</th><th>Note</th></tr></thead>
        <tbody>
          @forelse ($do->events as $event)
            <tr>
              <td style="white-space:nowrap">{{ $event->created_at?->timezone('Asia/Kuala_Lumpur')->format('d M Y, H:i') }}</td>
              <td>{{ $event->label() }}</td>
              <td style="white-space:nowrap">{{ $event->user->name ?? 'System' }}</td>
              <td>{{ $event->note ?? '' }}</td>
            </tr>
          @empty
            <tr><td colspan="4" class="inv-empty">No history recorded yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>
@endsection
