@extends('layouts.library')

@section('title', $activity ? 'Audit Trail' : 'Operational Reports')

@section('content')
    <h1 class="h3 mb-3">{{ $activity ? 'Audit Trail' : 'Operational Reports' }}</h1>
    <form class="card card-body d-print-none mb-3" method="get" action="{{ route($activity ? 'audit.index' : 'reports.index') }}">
        <div class="row g-3 align-items-end">
            @if($activity)
                <div class="col-sm-3"><label for="report-area" class="form-label">Activity area</label>
                    <input id="report-area" name="area" class="form-control" maxlength="80" value="{{ $filters['area'] ?? '' }}">
                </div>
            @else
                <div class="col-sm-3"><label for="report-type" class="form-label">Report</label>
                    <select id="report-type" name="type" class="form-select">
                        @foreach(\App\Services\LibraryReports::TYPES as $key => $label)
                            @continue($key === 'activity' || ($key === 'inventory' && !auth()->user()->hasRole('superadmin')))
                            <option value="{{ $key }}" @selected($type === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-sm-3"><label for="report-from" class="form-label">From</label>
                <input type="date" id="report-from" name="from" class="form-control" value="{{ $filters['from'] }}" required>
            </div>
            <div class="col-sm-3"><label for="report-through" class="form-label">Through</label>
                <input type="date" id="report-through" name="through" class="form-control" value="{{ $filters['through'] }}" required>
            </div>
            <div class="col-sm-3"><button class="btn btn-primary">Apply filters</button></div>
        </div>
    </form>
    <div class="d-flex flex-wrap gap-2 d-print-none mb-3">
        <a class="btn btn-outline-primary" href="{{ route($activity ? 'audit.index' : 'reports.index', [...$filters, 'format' => 'pdf']) }}">Download PDF</a>
        <a class="btn btn-outline-primary" href="{{ route($activity ? 'audit.index' : 'reports.index', [...$filters, 'format' => 'xlsx']) }}">Download Excel</a>
        <button class="btn btn-outline-secondary" type="button" onclick="window.print()">Print current page</button>
    </div>
    <h2 class="h5">{{ $title }} · {{ $filters['from'] }} to {{ $filters['through'] }}</h2>
    <div class="table-responsive rounded border bg-white mb-3">
        <table class="table mb-0">
            <thead><tr>@foreach($headings as $heading)<th scope="col">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>@foreach($row as $value)<td>{{ $value ?? '—' }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="{{ count($headings) }}">No records match the filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-print-none">{{ $page->links() }}</div>
@endsection
