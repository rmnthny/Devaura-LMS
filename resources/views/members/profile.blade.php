@extends('layouts.library')
@section('title', 'My profile')
@section('content')
<h1 class="h2">My profile</h1>
<p>{{ $account->email }} · {{ $account->reference_number }}</p>
<form method="post" action="{{ route('api.v1.profile.update') }}" class="card card-body">
    @csrf
    @method('PUT')
    <div class="row g-3">@include('members.names')</div>
    @if ($departments->isNotEmpty())
        <label class="form-label mt-3" for="department_id">Subject department</label>
        <select class="form-select" id="department_id" name="department_id" required>
            <option value="">Select a department</option>
            @foreach ($departments as $department)
                <option value="{{ $department->getKey() }}" @selected(old('department_id', $account->personnel?->department_id) == $department->getKey())>{{ $department->department_name }}</option>
            @endforeach
        </select>
    @endif
    <fieldset class="mt-4">
        <legend class="h5">Change password</legend>
        @foreach (['current_password' => 'Current password', 'password' => 'New password', 'password_confirmation' => 'Confirm new password'] as $field => $label)
            <label class="form-label mt-2" for="{{ $field }}">{{ $label }}</label>
            <div class="input-group">
                <input class="form-control" type="password" id="{{ $field }}" name="{{ $field }}" autocomplete="{{ $field === 'current_password' ? 'current-password' : 'new-password' }}"
                       @if ($field === 'password') minlength="8" maxlength="64" pattern="{{ \App\Support\MemberFieldRules::PASSWORD_PATTERN }}" aria-describedby="password-help" @endif>
                <button class="btn btn-outline-secondary" type="button" data-toggle-password="{{ $field }}" aria-label="Show {{ strtolower($label) }}">
                    <i class="bi bi-eye" aria-hidden="true"></i>
                </button>
            </div>
            @if ($field === 'password')
                <div class="form-text" id="password-help">{{ \App\Support\MemberFieldRules::PASSWORD_HINT }}</div>
            @endif
        @endforeach
    </fieldset>
    <div class="mt-3"><button class="btn btn-primary">Save profile</button></div>
</form>
<script>
    (() => {
        document.querySelectorAll('[data-toggle-password]').forEach((button) => {
            button.addEventListener('click', () => {
                const input = document.getElementById(button.dataset.togglePassword);
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                button.setAttribute('aria-label', (show ? 'Hide ' : 'Show ') + button.getAttribute('aria-label').replace(/^(Show|Hide) /, ''));
                button.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        });
    })();
</script>
@endsection
