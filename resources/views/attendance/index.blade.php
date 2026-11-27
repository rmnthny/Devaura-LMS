@extends('layouts.library')

@section('title', 'Attendance Monitoring')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/modules/attendance.css') }}">
@endpush

@section('content')
<section class="attendance-page" aria-labelledby="attendance-page-title">
    {{-- Distinct page heading --}}
    <header class="attendance-heading">
        <div class="attendance-heading-copy">
            <span class="attendance-heading-icon" aria-hidden="true">
                <i class="bi bi-calendar2-check"></i>
            </span>
            <div>
                <p class="attendance-eyebrow">LIBRARY OPERATIONS</p>
                <h1 id="attendance-page-title">Attendance Monitoring</h1>
                <p>Monitor library presence, assist visitors, and manage daily records.</p>
            </div>
        </div>
        <span class="attendance-status {{ $open ? 'is-open' : 'is-closed' }}" role="status">
            <span class="attendance-status-dot" aria-hidden="true"></span>
            {{ $open ? 'Kiosk available' : 'Kiosk closed' }}
        </span>
    </header>

    {{-- Two equal-height operational cards; no disconnected tiny statistics --}}
    <div class="attendance-overview">
        <section class="attendance-card attendance-overview-card" aria-labelledby="attendance-kiosk-title">
            <div class="attendance-section-heading">
                <span class="attendance-icon" aria-hidden="true"><i class="bi bi-display"></i></span>
                <div>
                    <h2 id="attendance-kiosk-title">Public attendance kiosk</h2>
                    <p>Live access and library presence</p>
                </div>
            </div>

            <div class="attendance-kiosk-content">
                <div class="attendance-kiosk-availability" id="kiosk-availability">
                    <span class="attendance-chip {{ $open ? 'attendance-chip-success' : 'attendance-chip-danger' }}">
                        <i class="bi {{ $open ? 'bi-check-circle' : 'bi-lock' }}" aria-hidden="true"></i>
                        {{ $open ? 'Accepting check-ins' : 'Unavailable for check-in' }}
                    </span>
                    <p>{{ $open
                        ? 'Members and walk-in visitors can record their attendance.'
                        : 'The Head Librarian or assigned personnel must time in to open the kiosk.' }}</p>
                </div>
                <div class="attendance-count-panel">
                    <span>Other open visits</span>
                    <strong>{{ $inside }}</strong>
                    <small>Currently recorded inside</small>
                </div>
            </div>


                <div class="attendance-overview-footer">

                    @if ($open)

                        <a class="btn btn-primary attendance-button"
                        href="{{ rtrim(config('attendance.kiosk_url'), '/') . '/kiosk' }}" target="_blank"
                        rel="noopener noreferrer">

                            <i class="bi bi-box-arrow-up-right"
                            aria-hidden="true"></i>

                            Open attendance kiosk

                        </a>

                    @else

                        <button
                            class="btn btn-outline-secondary attendance-button"
                            type="button"
                            aria-describedby="kiosk-availability"
                            disabled>Open attendance kiosk</button>

                    @endif

                    <a class="attendance-text-link"
                    href="{{ route('attendance.index', ['status' => 'open']) }}">
                        View all open visits
                    </a>

                </div>

        </section>

        <section class="attendance-card attendance-overview-card" aria-labelledby="attendance-duty-title">
            <div class="attendance-section-heading">
                <span class="attendance-icon" aria-hidden="true"><i class="bi bi-person-badge"></i></span>
                <div>
                    <h2 id="attendance-duty-title">My duty attendance</h2>
                    <p>Record your personnel Time In or Time Out</p>
                </div>
            </div>

            <div class="attendance-duty-content">
                <div>
                    <span class="attendance-label">CURRENT SESSION</span>
                    <div class="attendance-duty-status">
                        <span class="attendance-chip {{ $ownVisit ? 'attendance-chip-success' : 'attendance-chip-neutral' }}">
                            <i class="bi {{ $ownVisit ? 'bi-person-check' : 'bi-clock' }}" aria-hidden="true"></i>
                            {{ $ownVisit ? 'Currently timed in' : 'Not timed in' }}
                        </span>
                    </div>
                    <p>{{ $ownVisit
                        ? 'Your current duty session is active. Record Time Out when your shift ends.'
                        : 'Start your duty session to record your attendance.' }}</p>
                </div>
            </div>

            @if ($lastOnDuty)
                <div class="attendance-duty-warning" role="alert">
                    <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                    <span>Timing out now will close the kiosk. Any open visits will stay in the attendance list.</span>
                </div>
            @endif

            <form method="POST" action="{{ route('attendance.own') }}" class="attendance-duty-form">
                @csrf
                <button class="btn {{ $ownVisit ? 'btn-outline-primary' : 'btn-primary' }} attendance-button"
                        type="submit" name="action" value="{{ $ownVisit ? 'out' : 'in' }}">
                    <i class="bi {{ $ownVisit ? 'bi-box-arrow-right' : 'bi-box-arrow-in-right' }}" aria-hidden="true"></i>
                    {{ $ownVisit ? 'Record Time Out' : 'Record Time In' }}
                </button>
            </form>
        </section>
    </div>

    {{-- Expand only when needed; full-width forms preserve original validation and route --}}
    <details class="attendance-card attendance-assist" @if ($errors->any()) open @endif>
        <summary class="attendance-assist-summary">
            <span class="attendance-section-heading">
                <span class="attendance-icon" aria-hidden="true"><i class="bi bi-person-plus"></i></span>
                <span class="attendance-assist-copy">
                    <strong>Assisted attendance</strong>
                    <small>Check in or check out a registered account or walk-in visitor.</small>
                </span>
            </span>
            <span class="attendance-summary-action">
                <span>Open forms</span><i class="bi bi-chevron-down" aria-hidden="true"></i>
            </span>
        </summary>
        <div class="attendance-assist-body">
            @include('attendance.forms', ['actionUrl' => route('attendance.record')])
        </div>
    </details>

    {{-- Single full-width records workspace: filters are part of the same card --}}
    <section class="attendance-card attendance-records" aria-labelledby="attendance-records-title">
        <div class="attendance-records-heading">
            <div class="attendance-section-heading">
                <span class="attendance-icon" aria-hidden="true"><i class="bi bi-journal-text"></i></span>
                <div>
                    <h2 id="attendance-records-title">Attendance records</h2>
                    <p>Review check-ins, check-outs, and visit purposes.</p>
                </div>
            </div>
            <span class="attendance-total" aria-label="{{ $visits->total() }} matching records">
                <strong>{{ $visits->total() }}</strong> {{ $visits->total() === 1 ? 'record' : 'records' }}
            </span>
        </div>

        <form class="attendance-filters" method="GET" action="{{ route('attendance.index') }}">
            <div class="attendance-filter-field">
                <label for="attendance-date">From</label>
                <input id="attendance-date" type="date" class="form-control" name="date" value="{{ $date }}">
            </div>
            <div class="attendance-filter-field">
                <label for="attendance-through">Through</label>
                <input id="attendance-through" type="date" class="form-control" name="through" value="{{ $filters['through'] ?? '' }}">
            </div>
            <div class="attendance-filter-field">
                <label for="attendance-role">Role</label>
                <select id="attendance-role" name="role" class="form-select">
                    <option value="">All roles</option>
                    @foreach (['superadmin', 'admin', 'staff', 'student', 'faculty', 'maintenance', 'visitor'] as $role)
                        <option value="{{ $role }}" @selected(($filters['role'] ?? null) === $role)>{{ ucfirst($role) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="attendance-filter-field">
                <label for="attendance-filter-status">Status</label>
                <select id="attendance-filter-status" name="status" class="form-select">
                    <option value="">All visits</option>
                    <option value="open" @selected(($filters['status'] ?? null) === 'open')>Currently inside</option>
                    <option value="closed" @selected(($filters['status'] ?? null) === 'closed')>Timed out</option>
                </select>
            </div>
            <div class="attendance-filter-actions">
                <button class="btn btn-primary" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i> Filter</button>
                <a class="btn btn-outline-secondary" href="{{ route('attendance.index') }}">Clear</a>
            </div>
        </form>

        <div class="table-responsive attendance-table-wrap">
            <table class="table align-middle attendance-table">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Role</th>
                        <th scope="col">Time In</th>
                        <th scope="col">Time Out / Actions</th>
                        <th scope="col">Purpose</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($visits as $visit)
                        <tr>
                            <td class="attendance-person">
                                {{ $visit->account?->fullName() ?? (trim(($visit->visitor?->first_name ?? '') . ' ' . ($visit->visitor?->last_name ?? '')) ?: 'Visitor') }}
                            </td>
                            <td><span class="attendance-role">{{ ucfirst($visit->user_role) }}</span></td>
                            <td class="text-nowrap">{{ $visit->time_in_at->copy()->timezone($timezone)->format('M j, g:i A') }}</td>
                            <td>
                                @if ($visit->time_out_at)
                                    <span class="text-nowrap">{{ $visit->time_out_at->copy()->timezone($timezone)->format('M j, g:i A') }}</span>
                                @else
                                    <span class="attendance-chip attendance-chip-success">Inside</span>
                                    @if (auth()->user()->hasAnyRole(['superadmin', 'admin']))
                                        <details class="attendance-force">
                                            <summary>Force Time Out</summary>
                                            <form method="POST" action="{{ route('attendance.force', $visit->getKey()) }}">
                                                @csrf
                                                <label for="attendance-force-{{ $visit->getKey() }}">Reason</label>
                                                <input id="attendance-force-{{ $visit->getKey() }}" name="reason"
                                                       class="form-control form-control-sm" maxlength="255" required>
                                                <button class="btn btn-sm btn-outline-danger" type="submit">Close visit</button>
                                            </form>
                                        </details>
                                    @endif
                                @endif
                            </td>
                            <td class="attendance-purpose">{{ $visit->visit_purpose ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="attendance-empty">
                                    <span class="attendance-empty-icon" aria-hidden="true"><i class="bi bi-calendar-x"></i></span>
                                    <strong>No visits match your filters</strong>
                                    <p>Try adjusting the date, role, or attendance status.</p>
                                    <a href="{{ route('attendance.index') }}" class="btn btn-sm btn-outline-primary">Reset filters</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($visits->hasPages())
            <div class="attendance-pagination">
                {{ $visits->links('pagination::bootstrap-5') }}
            </div>
        @endif
    </section>
</section>
@endsection
