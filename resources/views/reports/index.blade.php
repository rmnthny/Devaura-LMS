
@extends('layouts.library')

@section('title', $activity ? 'Audit Trail' : 'Operational Reports')

@push('styles')
    <link rel="stylesheet"
          href="{{ asset('css/modules/reporting.css') }}">
@endpush

@section('content')
@php
    $reportRoute = $activity ? 'audit.index' : 'reports.index';

    $reportOptions = $operationalFilterOptions ?? [];
    $auditOptions = $auditFilterOptions ?? [];

    // The same applied filters must be used for exports.
    $exportParams = $filters;

    // Keep date range when switching report types.
    $periodParams = [
        'from' => $filters['from'],
        'through' => $filters['through'],
    ];

    $auditSelects = [
        'area' => [
            'label' => 'Activity area',
            'placeholder' => 'All activity areas',
        ],
        'event' => [
            'label' => 'Event',
            'placeholder' => 'All events',
        ],
    ];
@endphp

<div class="reporting-page">

    <!-- PAGE HEADER -->
    <header class="reporting-header">

        <div class="reporting-heading">
            <span class="reporting-heading-icon">
                <i class="bi {{ $activity ? 'bi-shield-check' : 'bi-file-earmark-bar-graph' }}"
                   aria-hidden="true"></i>
            </span>

            <div>
                <span class="reporting-eyebrow">
                    {{ $activity ? 'SYSTEM ACCOUNTABILITY' : 'LIBRARY ANALYTICS' }}
                </span>

                <h1>
                    {{ $activity ? 'Audit Trail' : 'Operational Reports' }}
                </h1>

                <p>
                    {{ $activity
                        ? 'Review recorded system activities and trace transactions.'
                        : 'Explore library records and generate detailed reports.' }}
                </p>
            </div>
        </div>

        <span class="reporting-period-label">
            <i class="bi bi-calendar3" aria-hidden="true"></i>
            Selected period
        </span>

    </header>

    <!-- REPORT TYPE NAVIGATION -->
    @unless($activity)
        <nav class="reporting-type-nav d-print-none"
             aria-label="Report type">

            @foreach(($reportOptions['type'] ?? $types) as $key => $label)

                <a
                    href="{{ route('reports.index', [
                        ...$periodParams,
                        'type' => $key,
                    ]) }}"
                    class="reporting-type-link {{ $type === $key ? 'is-active' : '' }}"
                    @if($type === $key) aria-current="page" @endif
                >
                    <i class="bi {{
                        $key === 'attendance'
                            ? 'bi-people'
                            : ($key === 'circulation'
                                ? 'bi-arrow-left-right'
                                : 'bi-box-seam')
                    }}" aria-hidden="true"></i>

                    {{ $label }}
                </a>

            @endforeach

        </nav>
    @endunless

    <!-- VALIDATION ERRORS -->
    @if($errors->any())
        <div class="alert alert-danger reporting-alert d-print-none"
             role="alert">
            <i class="bi bi-exclamation-circle" aria-hidden="true"></i>

            <div>
                <strong>Please review the selected filters.</strong>
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <!-- FILTER PANEL -->
    <section class="reporting-card reporting-filters d-print-none"
             aria-labelledby="reporting-filters-heading">

        <div class="reporting-card-header">
            <div class="reporting-card-heading">
                <span class="reporting-card-icon">
                    <i class="bi bi-sliders" aria-hidden="true"></i>
                </span>

                <div>
                    <h2 id="reporting-filters-heading">
                        {{ $activity ? 'Audit Filters' : 'Report Filters' }}
                    </h2>
                    <p>
                        Narrow the records using the available criteria.
                    </p>
                </div>
            </div>
        </div>

        <form method="get"
              action="{{ route($reportRoute) }}"
              class="reporting-filter-form">

            @unless($activity)
                <div class="reporting-field reporting-field-type">
                    <label for="report-type" class="form-label">
                        Report type
                    </label>

                    <select id="report-type"
                            name="type"
                            class="form-select">

                        @foreach(($reportOptions['type'] ?? $types) as $key => $label)
                            <option value="{{ $key }}"
                                    @selected($type === $key)>
                                {{ $label }}
                            </option>
                        @endforeach

                    </select>
                </div>
            @endunless

            <div class="reporting-field">
                <label for="report-from" class="form-label">
                    From{{ $activity ? '' : ' (Manila)' }}
                </label>

                <input id="report-from"
                       type="date"
                       name="from"
                       class="form-control"
                       value="{{ $filters['from'] }}"
                       required>
            </div>

            <div class="reporting-field">
                <label for="report-through" class="form-label">
                    Through{{ $activity ? '' : ' (Manila)' }}
                </label>

                <input id="report-through"
                       type="date"
                       name="through"
                       class="form-control"
                       value="{{ $filters['through'] }}"
                       required>
            </div>

            <!-- AUDIT FILTERS -->
            @if($activity)

                @foreach($auditSelects as $name => $config)
                    @php
                        $choices = $auditOptions[$name] ?? [];
                        $selected = (string) ($filters[$name] ?? '');
                    @endphp

                    <div class="reporting-field">
                        <label for="report-{{ $name }}"
                               class="form-label">
                            {{ $config['label'] }}
                        </label>

                        <select id="report-{{ $name }}"
                                name="{{ $name }}"
                                class="form-select">

                            <option value="">
                                {{ $config['placeholder'] }}
                            </option>

                            @if($selected !== '' && !in_array($selected, $choices, true))
                                <option value="{{ $selected }}" selected>
                                    {{ $selected }}
                                </option>
                            @endif

                            @foreach($choices as $choice)
                                <option value="{{ $choice }}"
                                        @selected($selected === (string) $choice)>
                                    {{ str_replace('_', ' ', ucfirst($choice)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endforeach

                <div class="reporting-field">
                    <label for="report-action" class="form-label">
                        Action contains
                    </label>

                    <input id="report-action"
                           type="text"
                           name="action"
                           @if(empty($filters['area']))
                               list="report-action-options"
                           @endif
                           class="form-control"
                           placeholder="Search recorded actions"
                           maxlength="255"
                           value="{{ $filters['action'] ?? '' }}">

                    {{-- Suggestions span the date range, not the selected area.
                         Avoid displaying unrelated activity descriptions
                         when an area filter is active. Typed search still works. --}}
                    @if(empty($filters['area']))
                        <datalist id="report-action-options">
                            @foreach(($auditOptions['action'] ?? []) as $choice)
                                <option value="{{ $choice }}"></option>
                            @endforeach
                        </datalist>
                    @endif
                </div>

                <div class="reporting-field">
                    <label for="report-transaction" class="form-label">
                        Transaction reference
                    </label>

                    <input id="report-transaction"
                           type="text"
                           name="transaction"
                           list="report-transaction-options"
                           class="form-control"
                           placeholder="Select or enter reference"
                           maxlength="30"
                           value="{{ $filters['transaction'] ?? '' }}">

                    <datalist id="report-transaction-options">
                        @foreach(($auditOptions['transaction'] ?? []) as $choice)
                            <option value="{{ $choice }}"></option>
                        @endforeach
                    </datalist>
                </div>

            <!-- INVENTORY FILTERS -->
            @elseif($type === 'inventory')

                <div class="reporting-field">
                    <label for="report-title" class="form-label">
                        Book title contains
                    </label>

                    <input id="report-title"
                           name="book_title"
                           type="text"
                           list="report-title-options"
                           class="form-control"
                           placeholder="Search book title"
                           maxlength="255"
                           value="{{ $filters['book_title'] ?? '' }}">

                    <datalist id="report-title-options">
                        @foreach(($reportOptions['book_title'] ?? []) as $choice)
                            <option value="{{ $choice }}"></option>
                        @endforeach
                    </datalist>
                </div>

                <div class="reporting-field">
                    <label for="report-genre" class="form-label">
                        Genre
                    </label>

                    <select id="report-genre"
                            name="genre_id"
                            class="form-select">

                        <option value="">All genres</option>

                        @php
                            $selectedGenre = (string) ($filters['genre_id'] ?? '');
                            $genreOptions = $reportOptions['genre_id'] ?? [];
                        @endphp

                        @if($selectedGenre !== '' && !array_key_exists($selectedGenre, $genreOptions))
                            <option value="{{ $selectedGenre }}" selected>
                                Previously selected genre
                            </option>
                        @endif

                        @foreach($genreOptions as $id => $name)
                            <option value="{{ $id }}"
                                    @selected($selectedGenre === (string) $id)>
                                {{ $name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="reporting-field">
                    <label for="report-published-on" class="form-label">
                        Publication date
                    </label>

                    <input id="report-published-on"
                           type="date"
                           name="published_on"
                           class="form-control"
                           list="report-published-options"
                           value="{{ $filters['published_on'] ?? '' }}">

                    <datalist id="report-published-options">
                        @foreach(($reportOptions['published_on'] ?? []) as $choice)
                            <option value="{{ $choice }}"></option>
                        @endforeach
                    </datalist>
                </div>

            <!-- ATTENDANCE FILTERS -->
            @elseif($type === 'attendance')

                <div class="reporting-field">
                    <label for="report-role" class="form-label">
                        Role at time in
                    </label>

                    <select id="report-role"
                            name="role"
                            class="form-select">

                        <option value="">All roles</option>

                        @foreach(($reportOptions['role'] ?? []) as $key => $label)
                            <option value="{{ $key }}"
                                    @selected(($filters['role'] ?? '') === (string) $key)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

            <!-- CIRCULATION FILTERS -->
            @elseif($type === 'circulation')

                <div class="reporting-field reporting-field-wide">
                    <label for="report-circulation-activity"
                           class="form-label">
                        Circulation activity
                    </label>

                    <select id="report-circulation-activity"
                            name="circulation_activity"
                            class="form-select">

                        @foreach(($reportOptions['circulation_activity'] ?? []) as $key => $label)
                            <option value="{{ $key }}"
                                    @selected(($filters['circulation_activity'] ?? 'checkout') === (string) $key)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

            @endif

            <div class="reporting-filter-actions">
                <button type="submit"
                        class="btn btn-primary reporting-apply">
                    <i class="bi bi-funnel" aria-hidden="true"></i>
                    Apply Filters
                </button>

                <a class="btn btn-outline-secondary"
                   href="{{ route($reportRoute, $activity ? [] : ['type' => $type]) }}">
                    Clear
                </a>
            </div>

        </form>
    </section>

    <!-- RESULTS PANEL -->
    <section class="reporting-card reporting-results"
             aria-labelledby="reporting-results-heading">

        <div class="reporting-results-header">

            <div class="reporting-card-heading">
                <span class="reporting-card-icon">
                    <i class="bi {{ $activity ? 'bi-clock-history' : 'bi-table' }}"
                       aria-hidden="true"></i>
                </span>

                <div>
                    <h2 id="reporting-results-heading">
                        {{ $activity ? 'Activity Records' : $title . ' Report' }}
                    </h2>
                    <p>
                        Matching records based on the selected filters.
                    </p>
                </div>
            </div>

            <div class="reporting-export-actions d-print-none">
                <a class="btn btn-outline-primary"
                   href="{{ route($reportRoute, [...$exportParams, 'format' => 'pdf']) }}">
                    <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>
                    PDF
                </a>

                <a class="btn btn-outline-primary"
                   href="{{ route($reportRoute, [...$exportParams, 'format' => 'xlsx']) }}">
                    <i class="bi bi-file-earmark-excel" aria-hidden="true"></i>
                    Excel
                </a>

                <button type="button"
                        class="btn btn-outline-secondary"
                        onclick="window.print()">
                    <i class="bi bi-printer" aria-hidden="true"></i>
                    Print current page
                </button>
            </div>

        </div>

        <!-- SUMMARY -->
        <dl class="reporting-summary">
            @foreach($summary as $label => $value)
                <div class="reporting-summary-item">
                    <dt>{{ $label }}</dt>
                    <dd>{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        <!-- RECORD COUNT -->
        <div class="reporting-table-toolbar">
            <span>
                <i class="bi bi-list-ul" aria-hidden="true"></i>

                @if($page->count() > 0)
                    Showing {{ $page->firstItem() }}
                    – {{ $page->lastItem() }}
                    of {{ number_format($page->total()) }} records
                @else
                    No matching records
                @endif
            </span>
        </div>

        <!-- DATA TABLE -->
        <div class="reporting-table-scroll"
             role="region"
             aria-label="{{ $title }} results"
             tabindex="0">

            <table class="reporting-table">
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
                        <tr>
                            <td colspan="{{ count($headings) }}"
                                class="reporting-empty-cell">
                                <div class="reporting-empty">
                                    <i class="bi bi-inbox"
                                       aria-hidden="true"></i>
                                    <strong>No matching records</strong>
                                    <p>
                                        {{ \App\Services\LibraryReports::EMPTY_MESSAGE }}
                                    </p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- PAGINATION -->
        @if($page->hasPages())
            <div class="reporting-pagination d-print-none">
                {{ $page->links('pagination::bootstrap-5') }}
            </div>
        @endif

    </section>

</div>
@endsection
