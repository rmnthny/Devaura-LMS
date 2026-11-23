@extends('layouts.library')
@section('title', 'Member Management')
@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <h1 class="h2 mb-0">Member Management</h1>
    @role('superadmin')
        <a href="{{ route('members.create') }}" class="btn btn-primary">Add member</a>
    @endrole
</div>
@role('superadmin')
    @include('imports.panel', ['kind' => 'members'])
@endrole
@php($hasPrintableCards = $members->contains(fn ($member) => filled($member->member_code)))
<form id="members-qr-sheet-form" method="post" action="{{ route('members.qr-sheet') }}" target="_blank">@csrf</form>
<section class="border rounded-3 bg-white mb-3" aria-labelledby="member-directory-title">
<div class="p-3 p-md-4">
<h2 id="member-directory-title" class="h5 mb-3">Member directory</h2>
<form class="row g-2 align-items-end" action="{{ route('members.index') }}">
    <div class="col-12 col-lg">
        <label class="form-label" for="member-search">Search members</label>
        <input type="search" class="form-control" id="member-search" name="q" value="{{ $search }}" placeholder="Name, reference number, QR code or email">
    </div>
    <div class="col-6 col-lg-auto">
        <label class="form-label" for="member-type-filter">Member type</label>
        <select class="form-select" id="member-type-filter" name="role">
            <option value="">All member types</option>
            @foreach (['Student', 'Faculty'] as $role)
                <option @selected($roleFilter === $role)>{{ $role }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-6 col-lg-auto">
        <label class="form-label" for="member-status-filter">Status</label>
        <select class="form-select" id="member-status-filter" name="status">
            <option value="">All statuses</option>
            @foreach ($allowedStatuses as $status)
                <option @selected(($statusFilter ?? '') === $status)>{{ $status }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-12 col-lg-auto">
        <button type="submit" class="btn btn-outline-primary">Search</button>
        @if ($search !== '' || $roleFilter || $statusFilter)
            <a class="btn btn-link" href="{{ route('members.index') }}">Clear filters</a>
        @endif
    </div>
</form>
@unless ($members->isEmpty())
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 border-top pt-3 mt-3">
        <p class="small text-body-secondary mb-0">
            Showing {{ $members->firstItem() }}–{{ $members->lastItem() }} of {{ $members->total() }} {{ $members->total() === 1 ? 'member' : 'members' }}
        </p>
        @if ($hasPrintableCards)
            <div class="d-flex align-items-center gap-2">
                <span class="small text-body-secondary" id="member-qr-count" role="status">0 selected</span>
                <button type="submit" form="members-qr-sheet-form" class="btn btn-sm btn-outline-secondary" id="members-qr-sheet-btn" disabled>
                    <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>Print QR cards
                </button>
            </div>
        @endif
    </div>
@endunless
</div>
@if ($members->isEmpty())
    <div class="border-top p-4 text-center">
        @if ($members->total() > 0)
            <p class="mb-2">There are no members on this page.</p>
            <a href="{{ $members->url(1) }}">Back to first page</a>
        @elseif ($search !== '' || $roleFilter || $statusFilter)
            <p class="mb-0 text-body-secondary">No members match these filters.</p>
        @else
            <p class="mb-0 text-body-secondary">No members have been added yet.</p>
        @endif
    </div>
@else
<div class="table-responsive border-top">
<table class="table align-middle mb-0">
    <thead>
        <tr>
            <th scope="col" class="ps-3">
                @if ($hasPrintableCards)
                    <input class="form-check-input" type="checkbox" id="members-select-all" aria-label="Select all QR cards on this page">
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
    @foreach ($members as $member)
        @php($dept = $member->user?->personnel?->department?->department_name)
        @php($status = $member->user?->account_status ?? 'Pending')
        @php($statusClass = match ($status) {
            'Active' => 'text-bg-success',
            'Pending' => 'text-bg-warning',
            'Inactive', 'Locked' => 'text-bg-danger',
            default => 'text-bg-secondary',
        })
        <tr>
            <td class="ps-3">
                @if (filled($member->member_code))
                    <input class="form-check-input member-qr-check" type="checkbox" name="member_ids[]" value="{{ $member->getKey() }}" form="members-qr-sheet-form" aria-label="Select {{ $member->fullName() }}">
                @endif
            </td>
            <td class="text-nowrap"><code>{{ $member->member_code ?? '—' }}</code></td>
            <td>
                <span class="fw-semibold">{{ $member->fullName() }}</span>
                <small class="d-block text-body-secondary">{{ $member->user?->email }}</small>
            </td>
            <td class="font-monospace">{{ $member->reference_number }}</td>
            <td>
                {{ $member->member_role }}
                @if ($dept)
                    <small class="d-block text-body-secondary">{{ $dept }}</small>
                @endif
            </td>
            <td><span class="badge {{ $statusClass }}">{{ $status }}</span></td>
            <td class="text-nowrap">
                @if (filled($member->member_code))
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('api.v1.members.qr-card', $member) }}" aria-label="QR card for {{ $member->fullName() }}">QR card</a>
                @endif
                @unless($member->user->hasRole('superadmin') || ($member->user->hasAnyRole(\App\Support\Rbac::PERSONNEL_ROLES) && auth()->user()->cannot('roles.manage')))
                    <a class="btn btn-sm btn-outline-primary" href="{{ route('members.edit', $member) }}">Edit</a>
                    @role('superadmin')
                        @if (! $member->user->is(auth()->user()))
                            <form class="d-inline" method="post" action="{{ route('api.v1.members.destroy', $member) }}" onsubmit="return confirm('Archive this member?')">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger">Archive</button>
                            </form>
                        @endif
                    @endrole
                @endunless
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</div>
@endif
</section>
{{ $members->links('pagination::bootstrap-5') }}
<script>
    (() => {
        const all = document.getElementById('members-select-all');
        const btn = document.getElementById('members-qr-sheet-btn');
        const countLabel = document.getElementById('member-qr-count');
        if (!all || !btn || !countLabel) return;
        const checks = Array.from(document.querySelectorAll('.member-qr-check'));
        const sync = () => {
            const count = checks.filter(c => c.checked).length;
            btn.disabled = count === 0;
            countLabel.textContent = `${count} selected`;
            all.checked = count === checks.length;
            all.indeterminate = count > 0 && count < checks.length;
        };
        all.addEventListener('change', () => { checks.forEach(c => c.checked = all.checked); sync(); });
        checks.forEach(c => c.addEventListener('change', sync));
        sync();
    })();
</script>
@endsection
