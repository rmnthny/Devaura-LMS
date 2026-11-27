@extends('layouts.library')

@section('title', $activity ? 'Audit Trail' : 'Operational Reports')

@section('content')
    <h1 class="h3 mb-3">{{ $activity ? 'Audit Trail' : 'Operational Reports' }}</h1>
    @unless($activity)
        <nav class="nav nav-pills flex-wrap gap-2 mb-3 d-print-none" aria-label="Report type">
            @foreach($types as $key => $label)
                <a class="nav-link @if($type === $key) active @endif"
                   @if($type === $key) aria-current="page" @endif
                   href="{{ route('reports.index', ['type' => $key, 'from' => $filters['from'], 'through' => $filters['through']]) }}">{{ $label }}</a>
            @endforeach
        </nav>
    @endunless

    <form class="card card-body d-print-none mb-4" method="get" action="{{ route($activity ? 'audit.index' : 'reports.index') }}">
        @unless($activity)
            <input type="hidden" name="type" value="{{ $type }}">
        @endunless
        <div class="row g-3 align-items-end">
            <div class="col-sm-6 col-lg">
                <label for="report-from" class="form-label">From{{ $activity ? '' : ' (Manila)' }}</label>
                <input type="date" id="report-from" name="from" class="form-control" value="{{ $filters['from'] }}" required>
            </div>
            <div class="col-sm-6 col-lg">
                <label for="report-through" class="form-label">Through{{ $activity ? '' : ' (Manila)' }}</label>
                <input type="date" id="report-through" name="through" class="form-control" value="{{ $filters['through'] }}" required>
            </div>
            @if($activity)
                <div class="col-sm-6 col-lg">
                    <label for="report-area" class="form-label">Activity area</label>
                    <input id="report-area" name="area" class="form-control" maxlength="80" value="{{ $filters['area'] ?? '' }}">
                </div>
                <div class="col-sm-6 col-lg">
                    <label for="report-event" class="form-label">Event</label>
                    <input id="report-event" name="event" class="form-control" maxlength="80" placeholder="checked_out" value="{{ $filters['event'] ?? '' }}">
                </div>
                <div class="col-sm-6 col-lg">
                    <label for="report-action" class="form-label">Action contains</label>
                    <input id="report-action" name="action" class="form-control" maxlength="255" value="{{ $filters['action'] ?? '' }}">
                </div>
                <div class="col-sm-6 col-lg">
                    <label for="report-transaction" class="form-label">Transaction reference</label>
                    <input id="report-transaction" name="transaction" class="form-control" maxlength="30" value="{{ $filters['transaction'] ?? '' }}">
                </div>
            @elseif($type === 'inventory')
                <div class="col-sm-6 col-lg">
                    <label for="report-title" class="form-label">Book title contains</label>
                    <input id="report-title" name="book_title" class="form-control" maxlength="255" value="{{ $filters['book_title'] ?? '' }}">
                </div>
                <div class="col-sm-6 col-lg">
                    <label for="report-genre" class="form-label">Genre</label>
                    <select id="report-genre" name="genre_id" class="form-select">
                        <option value="">All genres</option>
                        @foreach($genres as $id => $name)
                            <option value="{{ $id }}" @selected((int) ($filters['genre_id'] ?? 0) === $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-sm-6 col-lg">
                    <label for="report-published-on" class="form-label">Publication date</label>
                    <input type="date" id="report-published-on" name="published_on" class="form-control" value="{{ $filters['published_on'] ?? '' }}">
                </div>
            @elseif($type === 'attendance')
                <div class="col-sm-6 col-lg">
                    <label for="report-role" class="form-label">Role at time in</label>
                    <select id="report-role" name="role" class="form-select">
                        <option value="">All roles</option>
                        @foreach(\App\Services\LibraryReports::ATTENDANCE_ROLES as $key => $label)
                            <option value="{{ $key }}" @selected(($filters['role'] ?? null) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @elseif($type === 'circulation')
                <div class="col-sm-6 col-lg-4">
                    <label for="report-circulation-activity" class="form-label">Circulation activity</label>
                    <select id="report-circulation-activity" name="circulation_activity" class="form-select">
                        @foreach(\App\Services\LibraryReports::CIRCULATION_ACTIVITIES as $key => $label)
                            <option value="{{ $key }}" @selected($filters['circulation_activity'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="col-sm-6 col-lg-auto">
                <button class="btn btn-primary" type="submit">Apply filters</button>
            </div>
        </div>
    </form>

    <div class="d-flex flex-wrap gap-2 d-print-none mb-4">
        <a class="btn btn-outline-primary" href="{{ route($activity ? 'audit.index' : 'reports.index', [...$filters, 'format' => 'pdf']) }}">Download PDF</a>
        <a class="btn btn-outline-primary" href="{{ route($activity ? 'audit.index' : 'reports.index', [...$filters, 'format' => 'xlsx']) }}">Download Excel</a>
        <button class="btn btn-outline-secondary" type="button" onclick="window.print()">Print current page</button>
    </div>
    @unless($activity)
        <h2 class="h5 mb-3">{{ $title }}</h2>
    @endunless

    <dl class="row gx-3 gy-2 mb-3">
        @foreach($summary as $label => $value)
            <div class="col-6 col-lg-auto">
                <dt class="small text-body-secondary">{{ $label }}</dt>
                <dd class="mb-0">{{ $value }}</dd>
            </div>
        @endforeach
    </dl>
    @if($page->hasPages() && $page->count() > 0)
        <p class="small text-body-secondary">Showing records {{ $page->firstItem() }}–{{ $page->lastItem() }} of {{ $page->total() }}.</p>
    @endif

    <div class="table-responsive mb-3" role="region" aria-label="{{ $title }} results" tabindex="0">
        <table class="table mb-0">
            <thead>
                <tr>
                    @foreach($headings as $heading)
                        <th scope="col">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        @foreach($row as $value)
                            <td>{{ $value ?? '—' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($headings) }}">{{ \App\Services\LibraryReports::EMPTY_MESSAGE }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="d-print-none">{{ $page->links('pagination::bootstrap-5') }}</div>
@endsection
