<!doctype html>
<html>
<body style="font-family:Arial,sans-serif;font-size:14px;color:#111827;line-height:1.5">
  <p>Dear {{ $do->order->leadName ?: 'Customer' }},</p>
  <p>
    Please find attached delivery order <strong>{{ $do->do_number }}</strong>
    for order {{ $do->order->order_number }}@if ($do->order->orderTitle) ({{ $do->order->orderTitle }})@endif.
  </p>
  <table style="border-collapse:collapse;margin:8px 0 16px">
    <tr><td style="padding:2px 12px 2px 0;color:#6b7280">Deliver to</td><td>{{ $do->location }}</td></tr>
    <tr><td style="padding:2px 12px 2px 0;color:#6b7280">Date</td><td>{{ $do->delivery_date ? $do->delivery_date->format('d M Y') : 'To be confirmed' }}</td></tr>
    <tr><td style="padding:2px 12px 2px 0;color:#6b7280">Items</td><td>{{ $do->lines->count() }} line(s), {{ $do->totalQuantity() }} unit(s)</td></tr>
  </table>
  <p>Please check the goods on receipt and contact us if anything is missing or damaged.</p>
  <p>Thank you,<br>{{ $company->name }}</p>
</body>
</html>
