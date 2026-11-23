@extends('layouts.library')
@section('title', 'Personnel Management')
@section('content')
<h1 class="h2 mb-3">Personnel Management</h1>
<ul class="nav nav-tabs mb-3">
    <li class="nav-item">
        <a class="nav-link active" aria-current="page" href="{{ route('accounts.index') }}">Role Assignments</a>
    </li>
    @can('schedules.view-own')
        <li class="nav-item">
            <a class="nav-link" href="{{ route('schedules.index') }}">Duty Schedules</a>
        </li>
    @endcan
</ul>
<form class="d-flex flex-wrap gap-2 mb-3" action="{{ route('accounts.index') }}">
    <input type="search" class="form-control w-auto" name="q" value="{{ $search }}" placeholder="{{ $isHead ? 'Name, ID, QR or email' : 'Student or staff name' }}" aria-label="Search accounts">
    <select class="form-select w-auto" name="role" aria-label="Filter by role">
        <option value="">All roles</option>
        @foreach ($filterableRoles as $role)
            <option value="{{ $role }}" @selected(($roleFilter ?? '') === $role)>{{ ucfirst($role) }}</option>
        @endforeach
    </select>
    <button class="btn btn-outline-primary">Search</button>
    @if ($search !== '' || ($roleFilter ?? null))
        <a class="btn btn-link" href="{{ route('accounts.index') }}">Clear</a>
    @endif
</form>
@if ($users->total() > 0)
    <p class="small text-body-secondary mb-2">
        Showing {{ $users->firstItem() }}–{{ $users->lastItem() }} of {{ $users->total() }} {{ $users->total() === 1 ? 'account' : 'accounts' }}.
    </p>
@endif
@forelse ($users as $account)
    @php($statusClass = match ($account->account_status) {
        'Active' => 'text-bg-success',
        'Pending' => 'text-bg-warning',
        'Inactive', 'Locked' => 'text-bg-danger',
        default => 'text-bg-secondary',
    })
    <article class="card card-body mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
            <div>
                <h2 class="h5 mb-1">{{ $account->member || $account->personnel ? $account->fullName() : $account->email }}</h2>
                <div class="small text-body-secondary">
                        @if ($isHead)
                        {{ $account->email }}
                    @if ($account->reference_number) · <span class="font-monospace">{{ $account->reference_number }}</span> @endif
                    @if ($account->user_qr_value) · <code>{{ $account->user_qr_value }}</code> @endif
                        @endif
                </div>
            </div>
            <div class="d-flex gap-1 align-items-center">
                @foreach ($account->getRoleNames() as $roleName)
                    <span class="badge text-bg-light border text-capitalize">{{ $roleName }}</span>
                @endforeach
                <span class="badge {{ $statusClass }}">{{ $account->account_status }}</span>
            </div>
        </div>
        @php($revokingStaff = $account->hasRole('staff'))
        @if ($account->member && ($account->isActive() || $revokingStaff) && ! $account->hasRole('superadmin'))
            @php($faculty = $account->member->member_role === 'Faculty')
            <form method="post" action="{{ route('api.v1.admin.users.role', $account) }}" class="d-flex flex-wrap align-items-end gap-2">
                @csrf
                @method('PATCH')
                @if ($isHead && $account->isActive())
                <label>Role
                    <select name="role" class="form-select">
                        <option value="{{ $faculty ? 'faculty' : 'student' }}">{{ $account->member->member_role }}</option>
                        @if (! $faculty || $account->personnel?->department_id)
                            <option value="{{ $faculty ? 'admin' : 'staff' }}">{{ $faculty ? 'Admin' : 'Staff' }}</option>
                        @endif
                    </select>
                </label>
                @else
                    <input type="hidden" name="role" value="{{ $revokingStaff ? 'student' : 'staff' }}">
                @endif
                <label>Your current password <input class="form-control" type="password" name="current_password" autocomplete="current-password" required></label>
                <label class="mb-2"><input type="checkbox" name="confirm" value="1" required> Confirm role change</label>
                <button class="btn btn-outline-primary">{{ $isHead && $account->isActive() ? 'Save role' : ($revokingStaff ? 'Revoke Staff role' : 'Assign Staff role') }}</button>
            </form>
            @if ($isHead && $faculty && $account->personnel?->department_id)
                <form method="post" action="{{ route('api.v1.admin.users.transfer', $account) }}" class="d-flex flex-wrap align-items-end gap-2 mt-3">
                    @csrf
                    <label>Your current password <input class="form-control" type="password" name="current_password" autocomplete="current-password" required></label>
                    <label class="mb-2"><input type="checkbox" name="confirm" value="1" required> Revert my account to Faculty</label>
                    <button class="btn btn-outline-danger">Transfer Head Librarian role</button>
                </form>
            @endif
        @endif
        @if ($account->hasAnyRole(['faculty', 'admin', 'superadmin']) || $account->member?->member_role === 'Faculty')
            <p class="mt-3 mb-0">Subject department: {{ $account->personnel?->department?->department_name ?? 'Not set' }}</p>
        @endif
    </article>
@empty
    <p>No accounts found.</p>
@endforelse
{{ $users->links('pagination::bootstrap-5') }}
@endsection
