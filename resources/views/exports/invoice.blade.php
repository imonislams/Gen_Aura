<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Invoice {{ $order->order_number ?: $order->id }}</title>
<style>
  body { font-family: DejaVu Sans, sans-serif; color: #2b2233; font-size: 12px; }
  .brand { color: #8E3FD0; font-size: 22px; font-weight: bold; letter-spacing: 2px; }
  .muted { color: #6b6572; }
  .line { border-top: 1px solid #e5e1ea; margin: 12px 0; }
  table { width: 100%; border-collapse: collapse; }
  th { text-align: left; font-size: 10px; text-transform: uppercase; color: #8b8497; border-bottom: 1px solid #e5e1ea; padding: 6px; }
  td { padding: 7px 6px; border-bottom: 1px solid #f2eef6; vertical-align: top; }
  .right { text-align: right; }
  .total { font-size: 15px; font-weight: bold; }
  .badge { display: inline-block; padding: 3px 8px; background: #f3e8ff; color: #7a2fb8; font-size: 10px; font-weight: bold; }
  .box { background: #faf8fc; border: 1px solid #eee7f5; padding: 10px; }
</style>
</head>
<body>
  <table>
    <tr>
      <td style="width:60%">
        <div class="brand">{{ $settings['name'] }}</div>
        <div class="muted">{{ $settings['top'] }}</div>
      </td>
      <td class="right">
        <div style="font-size:18px;font-weight:bold">INVOICE</div>
        <div class="muted">{{ $order->order_number ?: '#' . $order->id }}</div>
        <div class="muted">{{ $order->created_at?->copy()->setTimezone(config('shop.timezone', 'Asia/Dhaka'))->format('d M Y, h:i A') }}</div>
      </td>
    </tr>
  </table>

  <div class="line"></div>

  <table>
    <tr>
      <td style="width:55%">
        <div class="muted" style="font-size:10px;text-transform:uppercase">Billed to</div>
        <b>{{ $order->name }}</b><br>
        {{ $order->phone }}<br>
        {{ $order->address }}<br>
        @if($order->delivery_area){{ $order->delivery_area }}<br>@endif
      </td>
      <td>
        <div class="muted" style="font-size:10px;text-transform:uppercase">Order</div>
        <span class="badge">{{ $order->status }}</span><br>
        Payment: {{ $order->payment_method }}<br>
        @if($order->note)Note: {{ $order->note }}@endif
      </td>
    </tr>
  </table>

  <div class="line"></div>

  <table>
    <thead>
      <tr>
        <th>Item</th>
        <th>Size</th>
        <th>Qty</th>
        <th class="right">Price</th>
        <th class="right">Line total</th>
      </tr>
    </thead>
    <tbody>
      @foreach($order->items as $item)
      <tr>
        <td>{{ $item->name }}</td>
        <td>{{ $item->size ?: '—' }}</td>
        <td>{{ $item->qty }}</td>
        <td class="right">BDT {{ number_format($item->price) }}</td>
        <td class="right">BDT {{ number_format($item->price * $item->qty) }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <table style="width:45%;margin-left:auto;margin-top:12px">
    <tr><td>Subtotal</td><td class="right">BDT {{ number_format($order->subtotal) }}</td></tr>
    <tr><td>Delivery</td><td class="right">BDT {{ number_format($order->ship) }}</td></tr>
    <tr><td class="total">Total</td><td class="total right">BDT {{ number_format($order->total) }}</td></tr>
  </table>

  <div class="line"></div>
  <div class="box muted">Thank you for shopping with {{ $settings['name'] }}. Questions? WhatsApp +{{ $settings['wa'] }}.</div>
</body>
</html>
