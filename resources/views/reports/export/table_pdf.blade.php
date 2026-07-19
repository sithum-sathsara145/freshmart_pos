<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    body  { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1a; margin: 24px; }
    h1    { font-size: 16px; margin: 0 0 2px; }
    .meta { color: #666; font-size: 10px; margin-bottom: 14px; }
    table { width: 100%; border-collapse: collapse; }
    th    { text-align: left; background: #f0f0f0; border-bottom: 1.5px solid #999; padding: 5px 6px; font-size: 10px; text-transform: uppercase; letter-spacing: .4px; }
    td    { border-bottom: .5px solid #ddd; padding: 4px 6px; }
    tr:nth-child(even) td { background: #fafafa; }
    .num  { text-align: right; }
    .empty{ color: #888; padding: 18px 6px; text-align: center; }
    .foot { color: #999; font-size: 9px; margin-top: 12px; }
</style>
</head>
<body>
    <h1>{{ $title }}</h1>
    <div class="meta">{{ $period }} &middot; {{ $branch }} &middot; Generated {{ now()->format('Y-m-d H:i') }}</div>

    <table>
        <thead>
            <tr>
                @foreach($headers as $h)
                    <th @if(str_contains($h, 'Rs.') || in_array($h, ['Qty sold','On hand','Minimum','Quantity','Amount'])) class="num" @endif>{{ $h }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $i => $cell)
                        <td @if(str_contains($headers[$i] ?? '', 'Rs.') || in_array($headers[$i] ?? '', ['Qty sold','On hand','Minimum','Quantity','Amount'])) class="num" @endif>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td class="empty" colspan="{{ count($headers) }}">Nothing to report for this period.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="foot">FreshMart POS</div>
</body>
</html>
