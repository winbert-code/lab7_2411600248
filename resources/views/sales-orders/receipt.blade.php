<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $order->order_number }} — ByteStock</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 24px 12px;
            background: #eef1ee;
            color: #1b241b;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 13px;
            line-height: 1.4;
        }
        .actions {
            max-width: 420px;
            margin: 0 auto 14px;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        .actions a,
        .actions button {
            border: 1px solid #0e5113;
            border-radius: 6px;
            background: #fff;
            color: #0e5113;
            padding: 8px 14px;
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }
        .actions button { background: #0e5113; color: #fff; }
        @media print {
            body { background: #fff; padding: 0; }
            .actions { display: none; }
            .receipt { box-shadow: none; margin: 0 auto; padding: 18mm 0; }
        }
        .receipt {
            max-width: 420px;
            margin: 0 auto;
            padding: 28px 24px;
            background: #fff;
            box-shadow: 0 8px 24px rgba(0, 0, 0, .12);
        }
        .brand { text-align: center; border-bottom: 2px dashed #0e5113; padding-bottom: 16px; }
        .brand h1 { margin: 0; font-size: 22px; letter-spacing: 1px; color: #0e5113; }
        .brand p { margin: 4px 0 0; font-size: 11px; color: #5f6b5f; }
        .receipt-title { text-align: center; margin: 18px 0 4px; font-size: 16px; letter-spacing: 2px; }
        .receipt-number { text-align: center; margin: 0 0 20px; font-weight: 700; }
        .meta { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .meta td { padding: 3px 0; vertical-align: top; }
        .meta td.label { width: 38%; color: #5f6b5f; }
        .meta td.value { font-weight: 700; text-align: right; }
        table.items { width: 100%; border-collapse: collapse; }
        table.items th, table.items td { padding: 8px 4px; border-bottom: 1px dashed #c9d2c9; vertical-align: top; }
        table.items th { border-top: 1px dashed #0e5113; border-bottom: 1px dashed #0e5113; font-size: 11px; text-transform: uppercase; color: #5f6b5f; }
        .num { text-align: right; white-space: nowrap; }
        .item-name { font-weight: 700; }
        .item-sku { font-size: 11px; color: #5f6b5f; }
        .totals { margin-top: 14px; margin-left: auto; width: 58%; border-collapse: collapse; }
        .totals td { padding: 5px 0; }
        .totals .grand td { border-top: 2px solid #0e5113; font-size: 16px; font-weight: 700; padding-top: 10px; }
        .status { margin-top: 22px; text-align: center; padding: 9px; background: #e9f6e9; border: 1px solid #0e5113; color: #0e5113; font-weight: 700; letter-spacing: 1px; }
        .thanks { margin-top: 20px; text-align: center; font-size: 11px; color: #5f6b5f; }
    </style>
</head>
<body>
    <div class="actions">
        <a href="{{ route('receipts.index') }}">All receipts</a>
        <a href="{{ route('sales-orders.show', $order) }}">Back to order</a>
        <button type="button" onclick="window.print()">Print receipt</button>
    </div>

    <div class="receipt">
        <div class="brand">
            <h1>ByteStock</h1>
            <p>Computer Parts Shop · Inventory Management System</p>
        </div>

        <p class="receipt-title">Sales Receipt</p>
        <p class="receipt-number">{{ $order->order_number }}</p>

        <table class="meta">
            <tr>
                <td class="label">Customer</td>
                <td class="value">{{ $order->customer }}</td>
            </tr>
            <tr>
                <td class="label">Order date</td>
                <td class="value">{{ $order->order_date?->format('F d, Y') ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Completed at</td>
                <td class="value">{{ $order->updated_at?->format('F d, Y h:i A') ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Prepared by</td>
                <td class="value">{{ $order->user?->name ?? 'System' }}</td>
            </tr>
            <tr>
                <td class="label">Approved by</td>
                <td class="value">{{ $order->approvedBy?->name ?? '—' }}</td>
            </tr>
        </table>

        <table class="items">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="num">Qty</th>
                    <th class="num">Unit price</th>
                    <th class="num">Amount</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($order->items as $item)
                    <tr>
                        <td>
                            <span class="item-name">{{ $item->product?->name ?? 'Deleted product' }}</span><br>
                            <span class="item-sku">{{ $item->product?->sku ?? '—' }}</span>
                        </td>
                        <td class="num">{{ number_format($item->quantity) }}</td>
                        <td class="num">₱{{ number_format($item->unit_price, 2) }}</td>
                        <td class="num">₱{{ number_format($item->lineTotal(), 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="num">No items on this order.</td></tr>
                @endforelse
            </tbody>
        </table>

        <table class="totals">
            <tr>
                <td>Items</td>
                <td class="num">{{ $order->items->count() }} line(s)</td>
            </tr>
            <tr>
                <td>Units</td>
                <td class="num">{{ number_format($order->totalQuantity()) }}</td>
            </tr>
            <tr class="grand">
                <td>Total paid</td>
                <td class="num">₱{{ number_format($order->totalAmount(), 2) }}</td>
            </tr>
        </table>

        <div class="status">Completed · Paid in full</div>
        <p class="thanks">Thank you for shopping with ByteStock.</p>
    </div>
</body>
</html>
