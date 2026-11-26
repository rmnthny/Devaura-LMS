
@extends('layouts.library')

@section('title', 'Personnel Management')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/modules/personnel.css') }}">
    <link rel="stylesheet" href="{{ asset('css/modules/schedules.css') }}">
@endpush

@section('content')
@php
    $canManageSchedules = auth()->user()?->canAny([
        'schedules.manage-admin',
        'schedules.manage-staff'
    ]);

    $hasFilters = filled($personnelFilter ?? null)
        || filled($dayFilter ?? null);
@endphp

<section class="personnel-page schedules-page">

    <!-- PAGE HEADING -->

    <header class="personnel-header">
        <div class="personnel-heading">
            <span class="personnel-heading-icon" aria-hidden="true">
                <i class="bi bi-calendar-week"></i>
            </span>

            <div>
                <h1>Personnel Management</h1>
                <p>View and organize assigned library duty schedules.</p>
            </div>
        </div>
    </header>

    <!-- SHARED MODULE NAVIGATION -->

    <nav class="personnel-tabs" aria-label="Personnel sections">
        @can('roles.manage')
            <a class="personnel-tab"
               href="{{ route('accounts.index') }}">
                <i class="bi bi-people" aria-hidden="true"></i>
                Role Assignments
            </a>
        @endcan

        <a class="personnel-tab is-active"
           href="{{ route('schedules.index') }}"
           aria-current="page">
            <i class="bi bi-calendar3" aria-hidden="true"></i>
            Duty Schedules
        </a>
    </nav>

    <!-- ASSIGN SCHEDULE -->

    @if($canManageSchedules)
        <section class="schedules-create-card"
                 aria-labelledby="schedules-create-title">

            <div class="schedules-card-heading">
                <div>
                    <h2 id="schedules-create-title">Assign duty schedule</h2>
                    <p>Select an available personnel member, day, and duty period.</p>
                </div>
            </div>

            @if($personnel->isEmpty())
                <div class="schedules-notice">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    <span>
                        Add an active personnel profile before assigning a schedule.
                    </span>
                </div>
            @else
                <form method="POST"
                      action="{{ route('schedules.store') }}"
                      class="schedules-create-form">

                    @csrf

                    @include('schedules.fields', [
                        'schedule' => null,
                        'prefix' => 'new'
                    ])

                    <div class="schedules-create-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-lg" aria-hidden="true"></i>
                            Add schedule
                        </button>
                    </div>
                </form>
            @endif
        </section>
    @endif

    <!-- SCHEDULE DIRECTORY -->

    <section class="schedules-directory"
             aria-labelledby="schedules-directory-title">

        <header class="schedules-directory-header">
            <div>
                <h2 id="schedules-directory-title">Duty schedule directory</h2>
                <p>Review assigned days and duty periods.</p>
            </div>
        </header>

        <!-- EXISTING FILTERS -->

        @if($personnel->isNotEmpty() || $schedules->isNotEmpty())
            <form method="GET"
                  action="{{ route('schedules.index') }}"
                  class="schedules-filter-form">

                @if($personnel->isNotEmpty())
                    <div class="schedules-field">
                        <label for="schedule-personnel-filter"
                               class="form-label">
                            Personnel
                        </label>

                        <select id="schedule-personnel-filter"
                                class="form-select"
                                name="personnel_id">
                            <option value="">All personnel</option>

                            @foreach($personnel as $person)
                                <option
                                    value="{{ $person->getKey() }}"
                                    @selected(($personnelFilter ?? null) == $person->getKey())
                                >
                                    {{ $person->fullName() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="schedules-field">
                    <label for="schedule-day-filter"
                           class="form-label">
                        Day
                    </label>

                    <select id="schedule-day-filter"
                            class="form-select"
                            name="day_id">
                        <option value="">All days</option>

                        @foreach($days as $day)
                            <option
                                value="{{ $day->getKey() }}"
                                @selected(($dayFilter ?? null) == $day->getKey())
                            >
                                {{ $day->day_name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="schedules-filter-actions">
                    <button type="submit" class="btn btn-primary">
                        Filter
                    </button>

                    @if($hasFilters)
                        <a href="{{ route('schedules.index') }}"
                           class="btn btn-outline-secondary">
                            Clear
                        </a>
                    @endif
                </div>
            </form>
        @endif

        <!-- RESULTS SUMMARY -->

        <div class="schedules-results">
            @if($schedules->total() > 0)
                Showing
                {{ $schedules->firstItem() }}–{{ $schedules->lastItem() }}
                of {{ $schedules->total() }}
                {{ $schedules->total() === 1 ? 'schedule' : 'schedules' }}.
            @else
                No schedule records on this page.
            @endif

            @if($hasFilters)
                <span class="schedules-filter-chip">
                    Filters applied
                </span>
            @endif
        </div>

        <!-- COMPACT SCHEDULE ROWS -->

        <div class="schedules-list">
            @forelse($schedules as $schedule)
                @php
                    $key = [
                        $schedule->personnel_id,
                        $schedule->day_id,
                        $schedule->period_id
                    ];

                    $canEditThisSchedule = $personnel->contains(
                        'personnel_id',
                        $schedule->personnel_id
                    );
                @endphp

                <article class="schedules-record">

                    <div class="schedules-record-main">

                        <div class="schedules-person">
                            <span class="schedules-record-icon"
                                  aria-hidden="true">
                                <i class="bi bi-person"></i>
                            </span>

                            <div>
                                <h3>{{ $schedule->personnel->fullName() }}</h3>
                                <small>Assigned personnel</small>
                            </div>
                        </div>

                        <div class="schedules-record-detail">
                            <span class="schedules-detail-label">Day</span>
                            <strong>{{ $schedule->day->day_name }}</strong>
                        </div>

                        <div class="schedules-record-detail">
                            <span class="schedules-detail-label">Duty period</span>
                            <strong>{{ $schedule->period->period_name }}</strong>
                        </div>

                        @if($canEditThisSchedule)
                            <a class="schedules-manage-link"
                               href="#schedule-edit-{{ $loop->index }}"
                               data-schedule-open="{{ $loop->index }}"
                               aria-label="Manage schedule for {{ $schedule->personnel->fullName() }}">
                                <i class="bi bi-pencil" aria-hidden="true"></i>
                                Manage
                            </a>
                        @endif

                    </div>

                    <!-- ORIGINAL UPDATE AND DELETE ACTIONS -->

                    @if($canEditThisSchedule)
                        <details class="schedules-record-details"
                                 id="schedule-edit-{{ $loop->index }}">
                            <summary>
                                <span>Edit this schedule</span>
                                <i class="bi bi-chevron-down"
                                   aria-hidden="true"></i>
                            </summary>

                            <div class="schedules-edit-content">

                                <form method="POST"
                                      action="{{ route('schedules.update', $key) }}"
                                      class="schedules-edit-form">

                                    @csrf
                                    @method('PUT')

                                    @include('schedules.fields', [
                                        'prefix' => implode('-', $key)
                                    ])

                                    <div class="schedules-edit-actions">
                                        <button type="submit"
                                                class="btn btn-primary">
                                            Save changes
                                        </button>
                                    </div>
                                </form>

                                <div class="schedules-remove-row">
                                    <span>
                                        Remove this duty assignment
                                    </span>

                                    <form method="POST"
                                          action="{{ route('schedules.destroy', $key) }}"
                                          onsubmit="return confirm('Remove this schedule?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit"
                                                class="btn btn-outline-danger">
                                            Remove schedule
                                        </button>
                                    </form>
                                </div>

                            </div>
                        </details>
                    @endif

                </article>

            @empty
                <div class="schedules-empty">
                    <span class="schedules-empty-icon"
                          aria-hidden="true">
                        <i class="bi bi-calendar-x"></i>
                    </span>

                    <h3>No schedules found</h3>

                    <p>
                        @if($hasFilters)
                            Try changing or clearing your filters.
                        @else
                            Assigned duty schedules will appear here.
                        @endif
                    </p>

                    @if($hasFilters)
                        <a href="{{ route('schedules.index') }}"
                           class="btn btn-outline-primary">
                            Clear filters
                        </a>
                    @endif
                </div>
            @endforelse
        </div>

        @if($schedules->hasPages())
            <div class="schedules-pagination">
                {{ $schedules->links('pagination::bootstrap-5') }}
            </div>
        @endif

    </section>
</section>
@endsection

@push('scripts')
<script>
(() => {
    "use strict";

    document.querySelectorAll("[data-schedule-open]").forEach(link => {
        link.addEventListener("click", event => {
            const id = link.getAttribute("href")?.slice(1);
            const details = id ? document.getElementById(id) : null;

            if (!details) return;

            event.preventDefault();

            details.open = true;

            details.scrollIntoView({
                behavior: window.matchMedia(
                    "(prefers-reduced-motion: reduce)"
                ).matches ? "instant" : "smooth",
                block: "nearest"
            });

            details.querySelector("summary")?.focus();
        });
    });
})();
</script>
@endpush
