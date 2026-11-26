
@extends('layouts.library')

@section('title', 'Member Management')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/modules/members.css') }}">
@endpush

@section('content')
@php
    $hasPrintableCards = $members->contains(fn ($member) => filled($member->member_code));
    $hasFilters = $search !== '' || $roleFilter || $statusFilter;
@endphp

<section class="members-page">

    <!-- PAGE HEADER -->
    <header class="members-header">
        <div class="members-heading">
            <span class="members-heading-icon" aria-hidden="true">
                <i class="bi bi-people"></i>
            </span>

            <div>
                <h1>Member Management</h1>
                <p>Manage library members, account information, and QR cards.</p>
            </div>
        </div>

        @role('superadmin')
            <a href="{{ route('members.create') }}" class="btn btn-primary members-add-button">
                <i class="bi bi-plus-lg" aria-hidden="true"></i>
                Add member
            </a>
        @endrole
    </header>

    <!-- REUSABLE BULK IMPORT -->
    @role('superadmin')
        @include('imports.panel', ['kind' => 'members'])
    @endrole

    <!-- KEEP EXISTING QR SUBMISSION ROUTE -->
    <form id="members-qr-sheet-form"
          method="POST"
          action="{{ route('members.qr-sheet') }}"
          target="_blank">
        @csrf
    </form>

    <!-- DIRECTORY -->
    <section class="members-directory" aria-labelledby="member-directory-title">

        <header class="members-directory-header">
            <div class="members-section-heading">
                <h2 id="member-directory-title">Member directory</h2>
                <p>Search, review, and manage registered members.</p>
            </div>

            @if($hasPrintableCards)
                <button type="submit"
                        form="members-qr-sheet-form"
                        class="btn btn-outline-secondary members-print-button"
                        id="members-qr-sheet-btn"
                        disabled>
                    <i class="bi bi-printer" aria-hidden="true"></i>
                    <span id="members-print-label">Print QR cards</span>
                </button>
            @endif
        </header>

        <!-- SEARCH AND FILTERS -->
        <div class="members-filter-section">
            <form method="GET"
                  action="{{ route('members.index') }}"
                  class="members-filter-grid">

                <div class="members-field members-search-field">
                    <label class="form-label" for="member-search">Search members</label>

                    <div class="members-input-icon">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        <input type="search"
                               class="form-control"
                               id="member-search"
                               name="q"
                               value="{{ $search }}"
                               placeholder="Name, reference number, QR code or email">
                    </div>
                </div>

                <div class="members-field">
                    <label class="form-label" for="member-type-filter">Member type</label>
                    <select class="form-select" id="member-type-filter" name="role">
                        <option value="">All member types</option>
                        @foreach(['Student', 'Faculty'] as $role)
                            <option value="{{ $role }}" @selected($roleFilter === $role)>
                                {{ $role }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="members-field">
                    <label class="form-label" for="member-status-filter">Status</label>
                    <select class="form-select" id="member-status-filter" name="status">
                        <option value="">All statuses</option>
                        @foreach($allowedStatuses as $status)
                            <option value="{{ $status }}"
                                    @selected(($statusFilter ?? '') === $status)>
                                {{ $status }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="members-filter-actions">
                    <button type="submit" class="btn btn-primary">Search</button>

                    @if($hasFilters)
                        <a href="{{ route('members.index') }}"
                           class="btn btn-outline-secondary">Clear</a>
                    @endif
                </div>
            </form>
        </div>

        <!-- RESULT SUMMARY -->
        <div class="members-results-bar">
            <div class="members-results-info">
                @if(!$members->isEmpty())
                    <span>
                        Showing {{ $members->firstItem() }}–{{ $members->lastItem() }}
                        of {{ $members->total() }}
                        {{ $members->total() === 1 ? 'member' : 'members' }}
                    </span>
                @else
                    <span>No members on this page</span>
                @endif

                @if($hasFilters)
                    <span class="members-filter-indicator">Filters applied</span>
                @endif
            </div>

            @if($hasPrintableCards)
                <span class="members-selected-count"
                      id="member-qr-count"
                      role="status"
                      aria-live="polite">0 selected</span>
            @endif
        </div>

        @if($members->isEmpty())

            <!-- COMPACT EMPTY STATE -->
            <div class="members-empty-state">
                <span class="members-empty-icon" aria-hidden="true">
                    <i class="bi {{ $hasFilters ? 'bi-search' : 'bi-people' }}"></i>
                </span>

                @if($members->total() > 0)
                    <h3>No members on this page</h3>
                    <p>Return to the first page to see available records.</p>
                    <a href="{{ $members->url(1) }}"
                       class="btn btn-outline-primary">Back to first page</a>
                @elseif($hasFilters)
                <h3>No members match these filters.</h3>
                <p>Try changing your search or clearing the filters.</p>
                @else
                    <h3>No members registered yet</h3>
                    <p>Registered members will appear here.</p>
                @endif
            </div>

        @else

            <!-- MEMBER TABLE -->
            <div class="table-responsive members-table-wrapper">
                <table class="table align-middle members-table">
                    <thead>
                        <tr>
                            <th scope="col" class="members-checkbox-cell">
                                @if($hasPrintableCards)
                                    <input class="form-check-input"
                                           type="checkbox"
                                           id="members-select-all"
                                           aria-label="Select all QR cards on this page">
                                @endif
                            </th>

                            <th scope="col">Member QR</th>
                            <th scope="col">Name</th>
                            <th scope="col">Reference number</th>
                            <th scope="col">Member type</th>
                            <th scope="col">Status</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach($members as $member)
                            @php
                                $dept = $member->user?->personnel?->department?->department_name;
                                $status = $member->user?->account_status ?? 'Pending';

                                $statusClass = match ($status) {
                                    'Active' => 'text-bg-success',
                                    'Pending' => 'text-bg-warning',
                                    'Inactive', 'Locked' => 'text-bg-danger',
                                    default => 'text-bg-secondary',
                                };

                                $statusIcon = match ($status) {
                                    'Active' => 'bi-check-circle',
                                    'Pending' => 'bi-clock',
                                    'Inactive' => 'bi-dash-circle',
                                    'Locked' => 'bi-lock',
                                    default => 'bi-circle',
                                };

                                $initials = collect(
                                    preg_split('/\s+/u', trim($member->fullName()), -1, PREG_SPLIT_NO_EMPTY) ?: []
                                )
                                    ->take(2)
                                    ->map(fn ($part) => \Illuminate\Support\Str::upper(
                                        \Illuminate\Support\Str::substr($part, 0, 1)
                                    ))
                                    ->implode('');
                            @endphp

                            <tr>
                                <!-- QR SELECTION -->
                                <td class="members-checkbox-cell">
                                    @if(filled($member->member_code))
                                        <input class="form-check-input member-qr-check"
                                               type="checkbox"
                                               name="member_ids[]"
                                               value="{{ $member->getKey() }}"
                                               form="members-qr-sheet-form"
                                               aria-label="Select {{ $member->fullName() }}">
                                    @endif
                                </td>

                                <!-- QR IDENTIFIER -->
                                <td class="text-nowrap">
                                    <code class="members-qr-code">
                                        {{ $member->member_code ?? '—' }}
                                    </code>
                                </td>

                                <!-- IDENTITY -->
                                <td class="members-name-cell">
                                    <div class="members-identity">
                                        <span class="members-avatar {{ $member->member_role === 'Faculty' ? 'is-faculty' : 'is-student' }}"
                                              aria-hidden="true">
                                            {{ $initials ?: '—' }}
                                        </span>

                                        <div class="members-identity-details">
                                            <span class="members-name">{{ $member->fullName() }}</span>

                                            @if($member->user?->email)
                                                <small class="members-email">
                                                    {{ $member->user->email }}
                                                </small>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <!-- REFERENCE -->
                                <td class="members-reference">
                                    {{ $member->reference_number }}
                                </td>

                                <!-- MEMBER TYPE -->
                                <td>
                                    <div class="members-type">
                                        <span>{{ $member->member_role }}</span>
                                        @if($dept)
                                            <small>{{ $dept }}</small>
                                        @endif
                                    </div>
                                </td>

                                <!-- STATUS -->
                                <td>
                                    <span class="badge members-status {{ $statusClass }}">
                                        <i class="bi {{ $statusIcon }}" aria-hidden="true"></i>
                                        {{ $status }}
                                    </span>
                                </td>

                                <!-- EXISTING PERMISSION-PROTECTED ACTIONS -->
                                <td>
                                    <div class="members-row-actions">
                                        @if(filled($member->member_code))
                                            <a class="btn btn-sm btn-outline-secondary"
                                               href="{{ route('api.v1.members.qr-card', $member) }}"
                                               aria-label="QR card for {{ $member->fullName() }}">
                                                <i class="bi bi-qr-code" aria-hidden="true"></i>
                                                QR card
                                            </a>
                                        @endif

                                        @if($member->user)
                                            @unless(
                                                $member->user->hasRole('superadmin') ||
                                                (
                                                    $member->user->hasAnyRole(\App\Support\Rbac::PERSONNEL_ROLES) &&
                                                    auth()->user()->cannot('roles.manage')
                                                )
                                            )
                                                <a class="btn btn-sm btn-outline-primary"
                                                   href="{{ route('members.edit', $member) }}">
                                                    <i class="bi bi-pencil" aria-hidden="true"></i>
                                                    Edit
                                                </a>

                                                @role('superadmin')
                                                    @if(!$member->user->is(auth()->user()))
                                                        <form method="POST"
                                                              action="{{ route('api.v1.members.destroy', $member) }}"
                                                              onsubmit="return confirm('Archive this member?')">
                                                            @csrf
                                                            @method('DELETE')

                                                            <button type="submit"
                                                                    class="btn btn-sm btn-outline-danger">
                                                                <i class="bi bi-archive" aria-hidden="true"></i>
                                                                Archive
                                                            </button>
                                                        </form>
                                                    @endif
                                                @endrole
                                            @endunless
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <!-- PAGINATION -->
        @if($members->hasPages())
            <div class="members-pagination">
                {{ $members->links('pagination::bootstrap-5') }}
            </div>
        @endif

    </section>
</section>
@endsection

@push('scripts')
<script>
(() => {
    "use strict";

    const all = document.getElementById("members-select-all");
    const button = document.getElementById("members-qr-sheet-btn");
    const label = document.getElementById("members-print-label");
    const countLabel = document.getElementById("member-qr-count");

    if (!all || !button || !countLabel) return;

    const checks = Array.from(
        document.querySelectorAll(".member-qr-check")
    );

    function sync() {
        const count = checks.filter(checkbox => checkbox.checked).length;

        button.disabled = count === 0;
        countLabel.textContent = `${count} selected`;

        if (label) {
            label.textContent = count
                ? `Print QR cards (${count})`
                : "Print QR cards";
        }

        all.checked = checks.length > 0 && count === checks.length;
        all.indeterminate = count > 0 && count < checks.length;

        checks.forEach(checkbox => {
            checkbox.closest("tr")?.classList.toggle(
                "is-selected",
                checkbox.checked
            );
        });
    }

    all.addEventListener("change", () => {
        checks.forEach(checkbox => {
            checkbox.checked = all.checked;
        });
        sync();
    });

    checks.forEach(checkbox => {
        checkbox.addEventListener("change", sync);
    });

    window.addEventListener("pageshow", sync);
    sync();
})();
</script>
@endpush
