<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 32pt 40pt 44pt; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9pt; line-height: 1.4; color: #21262c; }
        h1 { font-size: 19pt; line-height: 1.25; margin: 9pt 0 12pt; }
        .letterhead { padding-bottom: 7pt; text-align: center; border-bottom: 1pt solid #394c60; }
        .school { font-size: 12pt; font-weight: bold; }
        .library { color: #515960; font-size: 9pt; }
        table { width: 100%; border-collapse: collapse; }
        .summary { margin-bottom: 16pt; }
        .summary th, .summary td { padding: 3pt 8pt; border-bottom: .5pt solid #d5d9dd; text-align: left; vertical-align: top; }
        .summary th { width: 150pt; font-weight: normal; color: #465362; }
        .records { table-layout: fixed; }
        .records th, .records td { padding: 4pt 6pt; line-height: 1.25; text-align: left; vertical-align: top; overflow-wrap: break-word; }
        .records th { border-top: 1pt solid #596777; border-bottom: 1pt solid #596777; background: #eef1f4; }
        .records td { border-bottom: .5pt solid #d5d9dd; }
        .records thead { display: table-header-group; }
        .records tr { page-break-inside: avoid; }
        .empty { color: #515960; font-style: italic; }
    </style>
</head>
<body>
    <header class="letterhead">
        <div class="school">Upper Bicutan National High School</div>
        <div class="library">Library Services</div>
    </header>
    <h1>{{ $title }}</h1>
    <table class="summary" aria-label="Report criteria">
        @foreach($summary as $label => $value)
            <tr><th scope="row">{{ $label }}</th><td>{{ $value }}</td></tr>
        @endforeach
    </table>
    <table class="records" aria-label="{{ $title }} records">
        @php($widths = match ($type) {
            'attendance' => [13, 29, 29, 29],
            'circulation' => [16, 9, 27, 12, 12, 12, 12],
            'inventory' => [17, 9, 26, 16, 16, 16],
            'activity' => [16, 10, 18, 13, 28, 15],
        })
        <thead>
            <tr>
                @foreach($headings as $index => $heading)
                    <th scope="col" style="width: {{ $widths[$index] }}%">{{ $heading }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($row as $value)
                        <td>{{ $value ?? '-' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td class="empty" colspan="{{ count($headings) }}">{{ \App\Services\LibraryReports::EMPTY_MESSAGE }}</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
