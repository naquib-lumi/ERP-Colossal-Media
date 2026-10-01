<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $do->do_number }} – Delivery Order</title>
  @include('delivery-orders._document-styles')
  <style>
    body{margin:0;background:#e5e7eb;font-family:Arial,sans-serif}
    .bar{position:sticky;top:0;display:flex;gap:8px;align-items:center;flex-wrap:wrap;padding:10px 16px;background:#111827;color:#fff;z-index:5}
    .bar a,.bar button{border:0;border-radius:8px;padding:8px 12px;font-weight:600;font-size:13px;cursor:pointer;text-decoration:none;background:#374151;color:#fff}
    .bar .primary{background:#3B82F6}
    .bar .spacer{flex:1}
    .bar form{display:flex;gap:6px;align-items:center;margin:0}
    .bar input{height:34px;border-radius:8px;border:0;padding:0 10px;min-width:220px}
    .flash{padding:10px 16px;font-size:14px}
    .flash.ok{background:#ECFDF3;color:#067647}.flash.bad{background:#FEF3F2;color:#B42318}
    .sheet{width:210mm;max-width:100%;min-height:297mm;margin:16px auto;background:#fff;padding:18mm 14mm;box-sizing:border-box;box-shadow:0 4px 16px rgba(0,0,0,.12)}
    @media print{
      @page{size:A4;margin:18mm 14mm}
      body{background:#fff}
      .bar,.flash{display:none}
      .sheet{margin:0;padding:0;box-shadow:none;width:auto;min-height:0}
    }
  </style>
</head>
<body>
  <div class="bar">
    <a href="{{ url()->previous() }}">← Back</a>
    <strong>{{ $do->do_number }}</strong>
    <span class="spacer"></span>
    <button type="button" class="primary" onclick="window.print()">Print</button>
    <a href="{{ route('delivery-orders.pdf', $do) }}">Download PDF</a>
    @if ($canEmail && ! $do->isCancelled())
      <form method="POST" action="{{ route('delivery-orders.email', $do) }}" onsubmit="return confirm('Email {{ $do->do_number }} to ' + this.to.value + '?')">
        @csrf
        <input type="email" name="to" value="{{ old('to', $do->emailed_to ?: ($order->leadEmail ?: optional($order->lead)->email)) }}" placeholder="client@example.com" required aria-label="Client email">
        <button type="submit">Email PDF to client</button>
      </form>
    @endif
  </div>
  @if (session('success'))<div class="flash ok">{{ session('success') }}</div>@endif
  @if (session('error'))<div class="flash bad">{{ session('error') }}</div>@endif
  @if ($errors->any())<div class="flash bad">{{ $errors->first() }}</div>@endif
  @if ($do->emailed_at)
    <div class="flash {{ $do->changedSinceEmailed() ? 'bad' : 'ok' }}">
      Emailed to {{ $do->emailed_to }} on {{ $do->emailed_at->timezone('Asia/Kuala_Lumpur')->format('d M Y, h:i A') }}.
      @if ($do->changedSinceEmailed()) This delivery order changed since then. @endif
    </div>
  @endif

  <div class="sheet">
    @include('delivery-orders._document')
  </div>
</body>
</html>
