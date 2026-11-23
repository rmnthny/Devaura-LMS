<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 10mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; margin: 0; }
        table.grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
        td.cell {
            width: 25%;
            border: 1px dashed #9ca3af;
            padding: 6mm 4mm;
            text-align: center;
            vertical-align: middle;
            page-break-inside: avoid;
        }
        td.cell-empty { width: 25%; border: none; }
        .qr-img { width: 28mm; height: 28mm; display: block; margin: 0 auto 2.5mm; }
        .qr-code { font-family: DejaVu Sans Mono, monospace; font-size: 9pt; font-weight: bold; margin: 0; letter-spacing: 0.03em; }
    </style>
</head>
<body>
    <table class="grid">
        @foreach (array_chunk($items, 4) as $row)
            <tr>
                @foreach ($row as $item)
                    <td class="cell">
                        <img class="qr-img" src="data:image/png;base64,{{ $item['png'] }}" alt="{{ $item['code'] }}">
                        <p class="qr-code">{{ $item['code'] }}</p>
                    </td>
                @endforeach
                @for ($i = count($row); $i < 4; $i++)
                    <td class="cell-empty"></td>
                @endfor
            </tr>
        @endforeach
    </table>
</body>
</html>
