<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Purchase order created</title></head>
<body style="margin:0;padding:24px;background:#F8F8F8;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;margin:auto;background:#ffffff;border-radius:10px;overflow:hidden;">
        <tr><td style="background:#0E5113;padding:20px 24px;color:#ffffff;">
            <h1 style="margin:0;font-size:20px;">ByteStock Procurement</h1>
            <p style="margin:4px 0 0;font-size:13px;color:#A7F205;">New purchase order created</p>
        </td></tr>
        <tr><td style="padding:24px;">
            <p style="margin-top:0;">Hello,</p>
            <p>A new draft purchase order <strong>{{ $order->reference_number }}</strong> was created for
               <strong>{{ $order->supplier_name }}</strong> and is awaiting review.</p>
            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;font-size:14px;">
                <tr style="background:#eaf7e8;"><th align="left">Product</th><th align="right">Qty</th><th align="right">Unit price</th></tr>
                @foreach ($order->items as $item)
                    <tr style="border-bottom:1px solid #e5e7eb;">
                        <td>{{ $item->product?->name ?? '—' }}</td>
                        <td align="right">{{ number_format($item->quantity) }}</td>
                        <td align="right">₱{{ number_format($item->unit_price, 2) }}</td>
                    </tr>
                @endforeach
                <tr><td colspan="2" align="right"><strong>Total</strong></td>
                    <td align="right"><strong>₱{{ number_format($order->total, 2) }}</strong></td></tr>
            </table>
            <p style="margin-top:24px;">
                <a href="{{ $url }}" style="background:#03730B;color:#ffffff;padding:10px 18px;border-radius:6px;text-decoration:none;display:inline-block;">Review order</a>
            </p>
            <p style="color:#6b7280;font-size:12px;margin-bottom:0;">This notification was queued automatically by the replenishment system.</p>
        </td></tr>
    </table>
</body>
</html>
