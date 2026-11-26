
@extends('layouts.library')

@section('title', 'My profile')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/modules/profile.css') }}">
@endpush

@section('content')

@php
    $profileName = $account->member || $account->personnel
        ? $account->fullName()
        : $account->email;

    $initials = collect(
        preg_split('/\s+/u', trim($profileName), -1, PREG_SPLIT_NO_EMPTY) ?: []
    )
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(
            \Illuminate\Support\Str::substr($part, 0, 1)
        ))
        ->implode('');
@endphp

<section class="profile-page">

    <!-- PAGE HEADER -->

    <header class="profile-header">
        <span class="profile-header-icon" aria-hidden="true">
            <i class="bi bi-person-gear"></i>
        </span>

        <div>
            <h1>My Profile</h1>
            <p>Manage your personal information and account security.</p>
        </div>
    </header>

    <!-- EXISTING PROFILE UPDATE FORM -->

    <form
        id="profile-update-form"
        method="POST"
        action="{{ route('api.v1.profile.update') }}"
        class="profile-form"
    >
        @csrf
        @method('PUT')

        <div class="profile-grid">

            <!-- LEFT: ACCOUNT OVERVIEW -->

            <aside class="profile-overview-card"
                   aria-labelledby="profile-overview-title">

                <div class="profile-card-heading">
                    <h2 id="profile-overview-title">Account overview</h2>
                    <p>Your registered library account details.</p>
                </div>

                <div class="profile-identity">
                    <div class="profile-avatar" aria-hidden="true">
                        {{ $initials ?: '—' }}
                    </div>

                    <div class="profile-identity-details">
                        <strong>{{ $profileName }}</strong>
                        <span>UBNHS Library account</span>
                    </div>
                </div>

                <dl class="profile-account-details">

                    <div class="profile-account-row">
                        <dt>Email address</dt>
                        <dd>{{ $account->email }}</dd>
                    </div>

                    <div class="profile-account-row">
                        <dt>Reference number</dt>
                        <dd class="profile-reference">
                            {{ $account->reference_number ?? '—' }}
                        </dd>
                    </div>

                </dl>

                <div class="profile-overview-note">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    <span>
                        Account identifiers are displayed for reference.
                        Edit the available fields in the form.
                    </span>
                </div>
            </aside>

            <!-- RIGHT: EDITABLE SETTINGS -->

            <div class="profile-settings">

                <!-- PERSONAL INFORMATION -->

                <section class="profile-section"
                         aria-labelledby="profile-personal-title">

                    <header class="profile-card-heading">
                        <h2 id="profile-personal-title">
                            Personal information
                        </h2>
                        <p>Update your registered name and department.</p>
                    </header>

                    <div class="profile-name-grid row g-3">
                        @include('members.names')
                    </div>

                    @if($departments->isNotEmpty())

                        <div class="profile-department-field">
                            <label class="form-label"
                                   for="department_id">
                                Subject department
                            </label>

                            <select
                                class="form-select @error('department_id') is-invalid @enderror"
                                id="department_id"
                                name="department_id"
                                required
                            >
                                <option value="">Select a department</option>

                                @foreach($departments as $department)
                                    <option
                                        value="{{ $department->getKey() }}"
                                        @selected(
                                            old('department_id', $account->personnel?->department_id)
                                            == $department->getKey()
                                        )
                                    >
                                        {{ $department->department_name }}
                                    </option>
                                @endforeach
                            </select>

                            @error('department_id')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                    @endif
                </section>

                <!-- CHANGE PASSWORD -->

                <fieldset class="profile-section profile-security">
                    <legend class="visually-hidden">Change password</legend>

                    <div class="profile-card-heading">
                        <h2>Change password</h2>
                        <p>
                            Use the fields below when you want to update
                            your account password.
                        </p>
                    </div>

                    <div class="profile-password-fields">

                        @foreach([
                            'current_password' => 'Current password',
                            'password' => 'New password',
                            'password_confirmation' => 'Confirm new password'
                        ] as $field => $label)

                            <div class="profile-field">

                                <label class="form-label"
                                       for="{{ $field }}">
                                    {{ $label }}
                                </label>

                                <div class="input-group profile-password-group">

                                    <input
                                        class="form-control @error($field) is-invalid @enderror"
                                        type="password"
                                        id="{{ $field }}"
                                        name="{{ $field }}"
                                        autocomplete="{{ $field === 'current_password'
                                            ? 'current-password'
                                            : 'new-password' }}"
                                        @if($field === 'password')
                                            minlength="8"
                                            maxlength="64"
                                            pattern="{{ \App\Support\MemberFieldRules::PASSWORD_PATTERN }}"
                                            aria-describedby="password-help"
                                        @endif
                                    >

                                    <button
                                        class="btn btn-outline-secondary profile-password-toggle"
                                        type="button"
                                        data-toggle-password="{{ $field }}"
                                        aria-label="Show {{ strtolower($label) }}"
                                        aria-pressed="false"
                                    >
                                        <i class="bi bi-eye"
                                           aria-hidden="true"></i>
                                    </button>

                                </div>

                                @if($field === 'password')
                                    <div class="form-text"
                                         id="password-help">
                                        {{ \App\Support\MemberFieldRules::PASSWORD_HINT }}
                                    </div>
                                @endif

                                @error($field)
                                    <p class="profile-field-error"
                                       role="alert">
                                        {{ $message }}
                                    </p>
                                @enderror

                            </div>

                        @endforeach

                    </div>
                </fieldset>

                <!-- ONE SAVE ACTION -->

                <div class="profile-form-actions">
                    <span class="profile-save-note">
                        <i class="bi bi-shield-check" aria-hidden="true"></i>
                        Changes are saved to your existing account.
                    </span>

                    <button type="submit"
                            class="btn btn-primary profile-save-button">
                        Save profile
                    </button>
                </div>

            </div>

        </div>

    </form>
</section>

@endsection

@push('scripts')
<script>
(() => {
    "use strict";

    document
        .querySelectorAll("[data-toggle-password]")
        .forEach(button => {
            button.addEventListener("click", () => {
                const input = document.getElementById(
                    button.dataset.togglePassword
                );

                if (!input) return;

                const showing = input.type === "password";

                input.type = showing ? "text" : "password";

                button.setAttribute(
                    "aria-pressed",
                    String(showing)
                );

                const label = button.getAttribute("aria-label") || "";
                const fieldName = label.replace(/^(Show|Hide) /, "");

                button.setAttribute(
                    "aria-label",
                    `${showing ? "Hide" : "Show"} ${fieldName}`
                );

                const icon = button.querySelector("i");

                if (icon) {
                    icon.className = showing
                        ? "bi bi-eye-slash"
                        : "bi bi-eye";
                }
            });
        });
})();
</script>
@endpush
