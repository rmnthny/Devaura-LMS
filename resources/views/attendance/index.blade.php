@extends('layouts.library')
@section('title', 'Attendance')
@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h1 class="h2 mb-0">Attendance</h1>
        @if ($open)
            <a href="{{ rtrim(config('attendance.kiosk_url'), '/').'/kiosk' }}" target="_blank" rel="noopener" class="btn btn-outline-primary">Open attendance kiosk</a>
        @else
            <button type="button" class="btn btn-outline-secondary" aria-describedby="kiosk-availability" disabled>Open attendance kiosk</button>
        @endif
    </div>
    <div class="card card-body mb-3">
        <p role="status" class="mb-2" id="kiosk-availability">
            {{ $open ? 'Kiosk open' : 'Kiosk closed until the Head Librarian or someone assigned to this shift times in.' }} · {{ $inside }} other open visits
        </p>
        @if ($lastOnDuty)
            <p class="alert alert-warning py-2">Timing out now will close the kiosk. Any open visits will stay in the attendance list.</p>
        @endif
        <form action="{{ route('attendance.own') }}" method="post">
            @csrf
            <button name="action" value="{{ $ownVisit ? 'out' : 'in' }}" class="btn btn-primary">{{ $ownVisit ? 'Time Out' : 'Time In' }}</button>
        </form>
    </div>
    <details class="mb-4">
        <summary class="mb-2 fw-semibold">Record assisted attendance</summary>
        @include('attendance.forms', ['actionUrl' => route('attendance.record')])
    </details>
    <form class="d-flex flex-wrap align-items-end gap-2 mb-3" method="get" action="{{ route('attendance.index') }}">
        <label>From <input type="date" name="date" value="{{ $date }}" class="form-control"></label>
        <label>Through <input type="date" name="through" value="{{ $filters['through'] ?? '' }}" class="form-control"></label>
        <label>Role
            <select name="role" class="form-select">
                <option value="">All roles</option>
                @foreach (['superadmin', 'admin', 'staff', 'student', 'faculty', 'maintenance', 'visitor'] as $role)
                    <option value="{{ $role }}" @selected(($filters['role'] ?? null) === $role)>{{ ucfirst($role) }}</option>
                @endforeach
            </select>
        </label>
        <label>Status
            <select name="status" class="form-select">
                <option value="">All visits</option>
                <option value="open" @selected(($filters['status'] ?? null) === 'open')>Currently inside</option>
                <option value="closed" @selected(($filters['status'] ?? null) === 'closed')>Timed out</option>
            </select>
        </label>
        <button class="btn btn-outline-primary">Filter</button>
        <a class="btn btn-link" href="{{ route('attendance.index', ['status' => 'open']) }}">All open visits</a>
    </form>
    <div class="table-responsive border rounded bg-white mb-3">
        <table class="table align-middle mb-0">
            <thead><tr><th scope="col">Name</th><th scope="col">Role</th><th scope="col">Time In</th><th scope="col">Time Out</th><th scope="col">Purpose</th></tr></thead>
            <tbody>
                @forelse ($visits as $visit)
                    <tr>
                        <td>{{ $visit->account?->fullName() ?? trim(($visit->visitor?->first_name ?? '').' '.($visit->visitor?->last_name ?? '')) }}</td>
                        <td>{{ ucfirst($visit->user_role) }}</td>
                        <td class="text-nowrap">{{ $visit->time_in_at->copy()->timezone($timezone)->format('M j, g:i A') }}</td>
                        <td>
                            {{ $visit->time_out_at?->copy()->timezone($timezone)->format('M j, g:i A') ?? 'Inside' }}
                            @if (! $visit->time_out_at && auth()->user()->hasAnyRole(['superadmin', 'admin']))
                                <details class="small">
                                    <summary>Force Time Out</summary>
                                    <form method="post" action="{{ route('attendance.force', $visit->getKey()) }}">
                                        @csrf
                                        <label>Reason <input class="form-control form-control-sm" name="reason" maxlength="255" required></label>
                                        <button class="btn btn-sm btn-outline-danger">Close visit</button>
                                    </form>
                                </details>
                            @endif
                        </td>
                        <td>{{ $visit->visit_purpose ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-body-secondary">No visits match these filters.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $visits->links('pagination::bootstrap-5') }}
@endsection
