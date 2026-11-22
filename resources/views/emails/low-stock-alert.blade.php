<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Low stock alert</title></head>
<body style="margin:0;padding:24px;background:#F8F8F8;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;margin:auto;background:#ffffff;border-radius:10px;overflow:hidden;">
        <tr><td style="background:#B45309;padding:20px 24px;color:#ffffff;">
            <h1 style="margin:0;font-size:20px;">Low stock alert</h1>
            <p style="margin:4px 0 0;font-size:13px;">{{ $product->name }}</p>
        </td></tr>
        <tr><td style="padding:24px;">
            <p style="margin-top:0;">Hello,</p>
            <p><strong>{{ $product->name }}</strong> (SKU {{ $product->sku }}) has
               <strong>{{ $product->quantity }} units</strong> left. The calculated reorder point is
               <strong>{{ $reorderPoint }} units</strong>.</p>
            <p>Suggested replenishment quantity: <strong>{{ number_format($suggestedQuantity) }} units</strong>.</p>
            <p><a href="{{ $url }}" style="background:#0E5113;color:#ffffff;padding:10px 18px;border-radius:6px;text-decoration:none;display:inline-block;">View product</a></p>
        </td></tr>
    </table>
</body>
</html>
