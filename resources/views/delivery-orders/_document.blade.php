{{-- One delivery order, A4. Shared by the print page and the PDF (dompdf: tables and inline CSS only). --}}
@php
  $client = $order->companyName ?: optional($order->lead)->company_name;
  $contact = $order->leadName ?: optional($order->lead)->name;
  $phone = $order->leadPhone ?: optional($order->lead)->phone;
  $email = $order->leadEmail ?: optional($order->lead)->email;
  $when = $do->delivery_date
      ? $do->delivery_date->format('d M Y') . ($do->delivery_time ? ', ' . \Carbon\Carbon::parse($do->delivery_time)->format('h:i A') : '')
      : 'To be confirmed';
@endphp
<div class="do">
  @if ($do->isCancelled())
    <div class="do-cancelled">CANCELLED</div>
  @endif

  <table class="do-head">
    <tr>
      <td class="do-company">
        @if ($logoSrc)
          <img src="{{ $logoSrc }}" alt="{{ $company['name'] }}" class="do-logo">
        @endif
        <div class="do-company-name">{{ $company['name'] }}@if ($company['reg_no']) <span class="do-muted">({{ $company['reg_no'] }})</span>@endif</div>
        @if ($company['address'])<div class="do-muted">{!! nl2br(e(str_replace('\n', "\n", $company['address']))) !!}</div>@endif
        <div class="do-muted">
          {{ collect([$company['phone'] ? 'Tel: ' . $company['phone'] : null, $company['email'], $company['website']])->filter()->implode(' · ') }}
        </div>
      </td>
      <td class="do-title-cell">
        <div class="do-title">DELIVERY ORDER</div>
        <table class="do-meta">
          <tr><th>DO No.</th><td>{{ $do->do_number }}</td></tr>
          <tr><th>Order No.</th><td>{{ $order->order_number }}</td></tr>
          <tr><th>Issued</th><td>{{ $do->created_at?->timezone('Asia/Kuala_Lumpur')->format('d M Y') }}</td></tr>
        </table>
      </td>
    </tr>
  </table>

  <table class="do-parties">
    <tr>
      <td>
        <div class="do-label">Deliver to</div>
        <div class="do-strong">{{ $client ?: '-' }}</div>
        @if ($contact)<div>Attn: {{ $contact }}</div>@endif
        <div class="do-address">{{ $do->location }}</div>
        <div class="do-muted">{{ collect([$phone, $email])->filter()->implode(' · ') }}</div>
      </td>
      <td>
        <div class="do-label">Delivery</div>
        <div><span class="do-muted">Date:</span> {{ $when }}</div>
        <div><span class="do-muted">Method:</span> {{ collect(explode(', ', (string) $do->methods))->filter()->map(fn ($m) => dd_method_label($m))->implode(', ') ?: '-' }}</div>
        <div><span class="do-muted">Job:</span> {{ $order->orderTitle ?: '-' }}</div>
      </td>
    </tr>
  </table>

  <table class="do-items">
    <thead>
      <tr>
        <th style="width:6%">No.</th>
        <th>Description</th>
        <th style="width:17%">Method</th>
        <th style="width:24%">Date / time</th>
        <th style="width:10%" class="do-num">Qty</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($do->lines as $i => $line)
        <tr>
          <td>{{ $i + 1 }}</td>
          <td>{{ $line->description }}</td>
          <td>{{ $line->method ? dd_method_label($line->method) : '-' }}</td>
          <td>{{ $line->delivery_date ? $line->delivery_date->format('d M Y') . ($line->delivery_time ? ', ' . \Carbon\Carbon::parse($line->delivery_time)->format('h:i A') : '') : '-' }}</td>
          <td class="do-num">{{ $line->quantity }}</td>
        </tr>
      @endforeach
    </tbody>
    <tfoot>
      <tr><td colspan="4" class="do-num do-strong">Total quantity</td><td class="do-num do-strong">{{ $do->totalQuantity() }}</td></tr>
    </tfoot>
  </table>

  <p class="do-note">Please check the goods on receipt. Any shortage or damage must be noted on this delivery order before signing.</p>

  <table class="do-sign">
    <tr>
      <td>
        <div class="do-label">Delivered by</div>
        <div class="do-sign-space"></div>
        <div class="do-sign-line">Name &amp; signature</div>
        <div class="do-sign-line">Date</div>
      </td>
      <td>
        <div class="do-label">Received in good order and condition by</div>
        <div class="do-sign-space"></div>
        <div class="do-sign-line">Name, IC no. &amp; signature</div>
        <div class="do-sign-line">Company chop &amp; date</div>
      </td>
    </tr>
  </table>

  <div class="do-foot">{{ $do->do_number }} · generated {{ now()->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') }}</div>
</div>
