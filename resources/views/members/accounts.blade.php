
@extends('layouts.library')

@section('title', 'Personnel Management')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/modules/personnel.css') }}">
@endpush

@section('content')
@php
    $hasFilters = $search !== '' || ($roleFilter ?? null);
@endphp

<section class="personnel-page">

    <!-- PAGE HEADER -->

    <header class="personnel-header">
        <div class="personnel-heading">
            <span class="personnel-heading-icon" aria-hidden="true">
                <i class="bi bi-person-badge"></i>
            </span>

            <div>
                <h1>Personnel Management</h1>
                <p>Manage library personnel access, roles, and responsibilities.</p>
            </div>
        </div>
    </header>

    <!-- MODULE NAVIGATION -->

    <nav class="personnel-tabs" aria-label="Personnel sections">
        <a
            class="personnel-tab is-active"
            href="{{ route('accounts.index') }}"
            aria-current="page"
        >
            <i class="bi bi-people" aria-hidden="true"></i>
            Role Assignments
        </a>

        @can('schedules.view-own')
            <a
                class="personnel-tab"
                href="{{ route('schedules.index') }}"
            >
                <i class="bi bi-calendar3" aria-hidden="true"></i>
                Duty Schedules
            </a>
        @endcan
    </nav>

    <!-- ACCOUNT DIRECTORY -->

    <section class="personnel-directory" aria-labelledby="personnel-directory-title">

        <div class="personnel-directory-top">
            <div>
                <h2 id="personnel-directory-title">Role assignments</h2>
                <p>View account roles and manage permitted changes.</p>
            </div>
        </div>

        <!-- FILTERS -->

        <form
            class="personnel-filters"
            method="GET"
            action="{{ route('accounts.index') }}"
        >
            <div class="personnel-field personnel-search">
                <label class="form-label" for="personnel-search">
                    Search accounts
                </label>

                <div class="personnel-search-box">
                    <i class="bi bi-search" aria-hidden="true"></i>

                    <input
                        id="personnel-search"
                        class="form-control"
                        type="search"
                        name="q"
                        value="{{ $search }}"
                        placeholder="{{ $isHead ? 'Name, ID, QR or email' : 'Student or staff name' }}"
                    >
                </div>
            </div>

            <div class="personnel-field personnel-role-filter">
                <label class="form-label" for="personnel-role">
                    Filter by role
                </label>

                <select
                    id="personnel-role"
                    class="form-select"
                    name="role"
                >
                    <option value="">All roles</option>

                    @foreach($filterableRoles as $role)
                        <option
                            value="{{ $role }}"
                            @selected(($roleFilter ?? '') === $role)
                        >
                            {{ ucfirst($role) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="personnel-filter-actions">
                <button type="submit" class="btn btn-primary">
                    Search
                </button>

                @if($hasFilters)
                    <a
                        class="btn btn-outline-secondary"
                        href="{{ route('accounts.index') }}"
                    >
                        Clear
                    </a>
                @endif
            </div>
        </form>

        <!-- RESULTS SUMMARY -->

        <div class="personnel-results">
            @if($users->total() > 0)
                <span>
                    Showing {{ $users->firstItem() }}–{{ $users->lastItem() }}
                    of {{ $users->total() }}
                    {{ $users->total() === 1 ? 'account' : 'accounts' }}.
                </span>
            @else
                <span>No matching accounts.</span>
            @endif

            @if($hasFilters)
                <span class="personnel-filter-chip">Filters applied</span>
            @endif
        </div>

        <!-- COMPACT ACCOUNT DIRECTORY -->

        <div class="personnel-list">
            @forelse($users as $account)
                @php
                    $statusClass = match ($account->account_status) {
                        'Active' => 'text-bg-success',
                        'Pending' => 'text-bg-warning',
                        'Inactive', 'Locked' => 'text-bg-danger',
                        default => 'text-bg-secondary',
                    };

                    $revokingStaff = $account->hasRole('staff');

                    $canManageRole =
                        $account->member &&
                        ($account->isActive() || $revokingStaff) &&
                        ! $account->hasRole('superadmin');

                    $faculty = $account->member?->member_role === 'Faculty';

                    $canTransfer =
                        $canManageRole &&
                        $isHead &&
                        $faculty &&
                        $account->personnel?->department_id;

                    $accountName = $account->member || $account->personnel
                        ? $account->fullName()
                        : $account->email;

                    $initials = collect(
                        preg_split('/\s+/u', trim($accountName), -1, PREG_SPLIT_NO_EMPTY) ?: []
                    )
                        ->take(2)
                        ->map(fn ($part) => \Illuminate\Support\Str::upper(
                            \Illuminate\Support\Str::substr($part, 0, 1)
                        ))
                        ->implode('');
                @endphp

                <article class="personnel-account">

                    <div class="personnel-account-main">

                        <!-- ACCOUNT IDENTITY -->

                        <div class="personnel-identity">
                            <span class="personnel-avatar" aria-hidden="true">
                                {{ $initials ?: '—' }}
                            </span>

                            <div class="personnel-identity-copy">
                                <h3>{{ $accountName }}</h3>

                                @if($isHead)
                                    <div class="personnel-meta">
                                        <span>{{ $account->email }}</span>

                                        @if($account->reference_number)
                                            <span class="personnel-reference">
                                                {{ $account->reference_number }}
                                            </span>
                                        @endif

                                        @if($account->user_qr_value)
                                            <code>{{ $account->user_qr_value }}</code>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- ACCOUNT ROLES -->

                        <div class="personnel-roles" aria-label="Assigned roles">
                            @foreach($account->getRoleNames() as $roleName)
                                <span class="personnel-role-chip">
                                    {{ ucfirst($roleName) }}
                                </span>
                            @endforeach
                        </div>

                        <!-- STATUS -->

                        <div class="personnel-status">
                            <span class="badge {{ $statusClass }}">
                                {{ $account->account_status }}
                            </span>
                        </div>

                        <!-- EXPAND TO MANAGE -->

                        @if($canManageRole)
                            <span class="personnel-manage-hint">
                                <i class="bi bi-chevron-down" aria-hidden="true"></i>
                                Manage below
                            </span>
                        @endif
                    </div>

                    <!-- FACULTY DEPARTMENT -->

                    @if(
                        $account->hasAnyRole(['faculty', 'admin', 'superadmin']) ||
                        $account->member?->member_role === 'Faculty'
                    )
                        <div class="personnel-department">
                            <i class="bi bi-building" aria-hidden="true"></i>
                            <span>
                                Subject department:
                                <strong>
                                    {{ $account->personnel?->department?->department_name ?? 'Not set' }}
                                </strong>
                            </span>
                        </div>
                    @endif

                    <!-- COLLAPSIBLE ROLE MANAGEMENT -->

                    @if($canManageRole)
                        <details class="personnel-details">
                            <summary>
                                <span>
                                    <i class="bi bi-shield-lock" aria-hidden="true"></i>
                                    Manage role assignment
                                </span>

                                <i class="bi bi-chevron-down personnel-chevron"
                                   aria-hidden="true"></i>
                            </summary>

                            <div class="personnel-details-body">

                                <!-- ORIGINAL ROLE CHANGE FORM -->

                                <form
                                    method="POST"
                                    action="{{ route('api.v1.admin.users.role', $account) }}"
                                    class="personnel-action-form"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <div class="personnel-action-heading">
                                        <h4>Role assignment</h4>
                                        <p>
                                            Confirm the change using your current password.
                                        </p>
                                    </div>

                                    <div class="personnel-action-grid">

                                        @if($isHead && $account->isActive())
                                            <div class="personnel-field">
                                                <label
                                                    class="form-label"
                                                    for="role-{{ $account->getKey() }}"
                                                >
                                                    Role
                                                </label>

                                                <select
                                                    id="role-{{ $account->getKey() }}"
                                                    name="role"
                                                    class="form-select"
                                                >
                                                    <option value="{{ $faculty ? 'faculty' : 'student' }}">
                                                        {{ $account->member->member_role }}
                                                    </option>

                                                    @if(!$faculty || $account->personnel?->department_id)
                                                        <option value="{{ $faculty ? 'admin' : 'staff' }}">
                                                            {{ $faculty ? 'Admin' : 'Staff' }}
                                                        </option>
                                                    @endif
                                                </select>
                                            </div>
                                        @else
                                            <input
                                                type="hidden"
                                                name="role"
                                                value="{{ $revokingStaff ? 'student' : 'staff' }}"
                                            >
                                        @endif

                                        <div class="personnel-field personnel-password-field">
                                            <label
                                                class="form-label"
                                                for="role-password-{{ $account->getKey() }}"
                                            >
                                                Your current password
                                            </label>

                                            <input
                                                class="form-control"
                                                id="role-password-{{ $account->getKey() }}"
                                                type="password"
                                                name="current_password"
                                                autocomplete="current-password"
                                                required
                                            >
                                        </div>
                                    </div>

                                    <div class="personnel-action-footer">
                                        <label class="personnel-confirm">
                                            <input
                                                class="form-check-input"
                                                type="checkbox"
                                                name="confirm"
                                                value="1"
                                                required
                                            >
                                            <span>Confirm role change</span>
                                        </label>

                                        <button type="submit" class="btn btn-primary">
                                            {{ $isHead && $account->isActive()
                                                ? 'Save role'
                                                : ($revokingStaff ? 'Revoke Staff role' : 'Assign Staff role') }}
                                        </button>
                                    </div>
                                </form>

                                <!-- ORIGINAL HEAD LIBRARIAN TRANSFER -->

                                @if($canTransfer)
                                    <form
                                        method="POST"
                                        action="{{ route('api.v1.admin.users.transfer', $account) }}"
                                        class="personnel-action-form personnel-transfer"
                                    >
                                        @csrf

                                        <div class="personnel-action-heading">
                                            <h4>Head Librarian transfer</h4>
                                            <p>
                                                This action changes Head Librarian access.
                                                Verify your password before proceeding.
                                            </p>
                                        </div>

                                        <div class="personnel-action-grid">
                                            <div class="personnel-field personnel-password-field">
                                                <label
                                                    class="form-label"
                                                    for="transfer-password-{{ $account->getKey() }}"
                                                >
                                                    Your current password
                                                </label>

                                                <input
                                                    class="form-control"
                                                    id="transfer-password-{{ $account->getKey() }}"
                                                    type="password"
                                                    name="current_password"
                                                    autocomplete="current-password"
                                                    required
                                                >
                                            </div>
                                        </div>

                                        <div class="personnel-action-footer">
                                            <label class="personnel-confirm">
                                                <input
                                                    class="form-check-input"
                                                    type="checkbox"
                                                    name="confirm"
                                                    value="1"
                                                    required
                                                >
                                                <span>Revert my account to Faculty</span>
                                            </label>

                                            <button
                                                type="submit"
                                                class="btn btn-outline-danger"
                                            >
                                                Transfer Head Librarian role
                                            </button>
                                        </div>
                                    </form>
                                @endif

                            </div>
                        </details>
                    @endif

                </article>

            @empty
                <div class="personnel-empty">
                    <span class="personnel-empty-icon" aria-hidden="true">
                        <i class="bi bi-people"></i>
                    </span>

                    <h3>No accounts found</h3>
                    <p>
                        @if($hasFilters)
                            Try adjusting or clearing your search filters.
                        @else
                            Account records will appear here when available.
                        @endif
                    </p>

                    @if($hasFilters)
                        <a href="{{ route('accounts.index') }}"
                           class="btn btn-outline-primary">
                            Clear filters
                        </a>
                    @endif
                </div>
            @endforelse
        </div>

        <!-- PAGINATION -->

        @if($users->hasPages())
            <div class="personnel-pagination">
                {{ $users->links('pagination::bootstrap-5') }}
            </div>
        @endif

    </section>
</section>
@endsection
