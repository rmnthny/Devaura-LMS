<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>{{ $title }}</title>
<style>
    body { font: 11px DejaVu Sans, sans-serif; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #adb5bd; padding: 6px; text-align: left; }
    th { background: #e9ecef; }
</style></head>
<body>
    <h1>{{ $title }}</h1>
    <p>UBNHS Library · {{ $filters['from'] }} to {{ $filters['through'] }}</p>
    <table>
        <thead><tr>@foreach($headings as $heading)<th>{{ $heading }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse($rows as $row)
            <tr>@foreach($row as $value)<td>{{ $value ?? '—' }}</td>@endforeach</tr>
        @empty
            <tr><td colspan="{{ count($headings) }}">No records match the filters.</td></tr>
        @endforelse
        </tbody>
    </table>
</body></html>
