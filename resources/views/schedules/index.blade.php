@extends('layouts.library')
@section('title', 'Personnel Management')
@section('content')
    <h1 class="h2 mb-3">Personnel Management</h1>
    <ul class="nav nav-tabs mb-3">
        @can('roles.manage')
            <li class="nav-item">
                <a class="nav-link" href="{{ route('accounts.index') }}">Role Assignments</a>
            </li>
        @endcan
        <li class="nav-item">
            <a class="nav-link active" aria-current="page" href="{{ route('schedules.index') }}">Duty Schedules</a>
        </li>
    </ul>
    @canany(['schedules.manage-admin', 'schedules.manage-staff'])
        @if ($personnel->isEmpty())
            <p class="text-body-secondary">Add an active personnel profile before assigning a schedule.</p>
        @else
            <form class="card card-body mb-3" method="post" action="{{ route('schedules.store') }}">
                @csrf
                @include('schedules.fields', ['schedule' => null, 'prefix' => 'new'])
                <div><button class="btn btn-primary mt-2">Add schedule</button></div>
            </form>
        @endif
    @endcanany
    @if ($personnel->isNotEmpty() || $schedules->isNotEmpty())
        <form class="d-flex flex-wrap gap-2 mb-3" method="get" action="{{ route('schedules.index') }}">
            @if ($personnel->isNotEmpty())
                <select class="form-select w-auto" name="personnel_id" aria-label="Filter by personnel">
                    <option value="">All personnel</option>
                    @foreach ($personnel as $person)
                        <option value="{{ $person->getKey() }}" @selected(($personnelFilter ?? null) === $person->getKey())>{{ $person->fullName() }}</option>
                    @endforeach
                </select>
            @endif
            <select class="form-select w-auto" name="day_id" aria-label="Filter by day">
                <option value="">All days</option>
                @foreach ($days as $day)
                    <option value="{{ $day->getKey() }}" @selected(($dayFilter ?? null) === $day->getKey())>{{ $day->day_name }}</option>
                @endforeach
            </select>
            <button class="btn btn-outline-primary">Filter</button>
            @if (($dayFilter ?? null) || ($personnelFilter ?? null))
                <a class="btn btn-link" href="{{ route('schedules.index') }}">Clear</a>
            @endif
        </form>
    @endif
        @forelse ($schedules as $schedule)
            @php
                $key = [$schedule->personnel_id, $schedule->day_id, $schedule->period_id];
            @endphp
            <article class="card card-body mb-2">
                <h2 class="h6">{{ $schedule->personnel->fullName() }} · {{ $schedule->day->day_name }} · {{ $schedule->period->period_name }}</h2>
                @if ($personnel->contains('personnel_id', $schedule->personnel_id))
                <form method="post" action="{{ route('schedules.update', $key) }}">
                    @csrf
                    @method('PUT')
                    @include('schedules.fields', ['prefix' => implode('-', $key)])
                    <div><button class="btn btn-sm btn-outline-primary mt-2">Save</button></div>
                </form>
                <form method="post" action="{{ route('schedules.destroy', $key) }}" onsubmit="return confirm('Remove this schedule?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger mt-2">Remove</button>
                </form>
                @endif
            </article>
        @empty
            <p>No schedules have been assigned.</p>
        @endforelse
        {{ $schedules->links('pagination::bootstrap-5') }}
@endsection
