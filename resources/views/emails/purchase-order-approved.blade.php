<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>Purchase order approved</title></head>
<body style="margin:0;padding:24px;background:#F8F8F8;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;margin:auto;background:#ffffff;border-radius:10px;overflow:hidden;">
        <tr><td style="background:#03730B;padding:20px 24px;color:#ffffff;">
            <h1 style="margin:0;font-size:20px;">Purchase order approved</h1>
            <p style="margin:4px 0 0;font-size:13px;color:#A7F205;">{{ $order->reference_number }}</p>
        </td></tr>
        <tr><td style="padding:24px;">
            <p style="margin-top:0;">Hello,</p>
            <p>Purchase order <strong>{{ $order->reference_number }}</strong> for
               <strong>{{ $order->supplier_name }}</strong> has been approved by
               {{ $order->approvedBy?->name ?? 'a manager' }}.</p>
            @if ($note)
                <blockquote style="border-left:4px solid #1DBF04;margin:16px 0;padding:8px 16px;background:#f3faf1;">{{ $note }}</blockquote>
            @endif
            <p>Total amount: <strong>₱{{ number_format($order->total, 2) }}</strong></p>
            <p><a href="{{ $url }}" style="background:#0E5113;color:#ffffff;padding:10px 18px;border-radius:6px;text-decoration:none;display:inline-block;">View order</a></p>
        </td></tr>
    </table>
</body>
</html>
