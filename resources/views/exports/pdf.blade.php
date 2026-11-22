<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #1f2937; }
        h1 { color: #0E5113; font-size: 20px; margin-bottom: 2px; }
        p.subtitle { color: #6b7280; margin-top: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { background: #0E5113; color: #fff; text-align: left; padding: 7px; }
        td { border-bottom: 1px solid #e5e7eb; padding: 6px 7px; }
        tr:nth-child(even) td { background: #f7faf6; }
        footer { margin-top: 24px; color: #6b7280; font-size: 9px; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <p class="subtitle">ByteStock Automated Inventory Replenishment &middot; Generated {{ now()->format('M d, Y h:i A') }}</p>
    <table>
        <thead><tr>@foreach ($headers as $header)<th>{{ $header }}</th>@endforeach</tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>@foreach ((array) $row as $cell)<td>{{ $cell }}</td>@endforeach</tr>
            @empty
                <tr><td colspan="{{ count($headers) }}">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>
    <footer>Confidential &middot; ByteStock Inventory Management System</footer>
</body>
</html>
