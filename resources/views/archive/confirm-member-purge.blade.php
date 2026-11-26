
@extends('layouts.library')

@section('title', 'Review permanent deletion')

@push('styles')
    <link rel="stylesheet"
          href="{{ asset('css/modules/archive-purge.css') }}">
@endpush

@section('content')

<section class="purge-page">

    <!-- BACK NAVIGATION -->

    <nav aria-label="Archive navigation">
        <a class="purge-back"
           href="{{ route('archive.index', ['section' => 'members']) }}">
            <i class="bi bi-arrow-left" aria-hidden="true"></i>
            Back to archived members
        </a>
    </nav>

    <!-- DELETION REVIEW -->

    <section class="purge-card" aria-labelledby="purge-title">

        <header class="purge-header">
            <span class="purge-header-icon" aria-hidden="true">
                <i class="bi bi-shield-exclamation"></i>
            </span>

            <div>
                <h1 id="purge-title">
                    Permanently delete member
                </h1>

                <p>
                    Review the selected account before confirming.
                </p>
            </div>
        </header>

        <!-- ACTUAL MEMBER INFORMATION -->

        <div class="purge-member">
            <span class="purge-field-label">Selected member</span>

            <strong class="purge-member-name">
                {{ $member->fullName() }}
            </strong>

            <span class="purge-member-email">
                {{ $account->email }}
            </span>
        </div>

        @if($reason)

            <!-- DELETION NOT ALLOWED -->

            <div class="purge-blocked" role="alert">
                <i class="bi bi-exclamation-circle"
                   aria-hidden="true"></i>

                <div>
                    <strong>Deletion is unavailable</strong>
                    <p>{{ $reason }}</p>
                </div>
            </div>

            <div class="purge-actions">
                <a href="{{ route('archive.index', ['section' => 'members']) }}"
                   class="btn btn-outline-secondary">
                    Return to archive
                </a>
            </div>

        @else

            <!-- PERMANENT DELETION WARNING -->

            <div class="purge-warning" role="note">
                <i class="bi bi-exclamation-triangle"
                   aria-hidden="true"></i>

                <div>
                    <strong>This action cannot be undone.</strong>

                    <p>
                        This permanently removes the member profile
                        and linked account.
                    </p>
                </div>
            </div>

            <!-- ORIGINAL DELETE FORM -->

            <form
                id="purge-form"
                method="POST"
                action="{{ route('archive.members.purge', $member->getKey()) }}"
                class="purge-form"
            >
                @csrf
                @method('DELETE')

                <label class="form-label" for="purge-confirmation">
                    Type <strong>DELETE</strong> to confirm
                </label>

                <input
                    type="text"
                    class="form-control @error('confirmation') is-invalid @enderror"
                    id="purge-confirmation"
                    name="confirmation"
                    maxlength="6"
                    minlength="6"
                    pattern="DELETE"
                    autocomplete="off"
                    autocapitalize="characters"
                    spellcheck="false"
                    placeholder="Enter DELETE"
                    value="{{ old('confirmation') }}"
                    aria-describedby="purge-confirmation-help"
                    required
                >

                <p class="purge-help" id="purge-confirmation-help">
                    Confirmation must match DELETE exactly.
                </p>

                @error('confirmation')
                    <div class="invalid-feedback d-block" role="alert">
                        {{ $message }}
                    </div>
                @enderror

                <div class="purge-actions">
                    <a
                        href="{{ route('archive.index', ['section' => 'members']) }}"
                        class="btn btn-outline-secondary"
                    >
                        Cancel
                    </a>

                    <button
                        type="submit"
                        class="btn btn-danger purge-submit"
                        id="purge-submit"
                        disabled
                    >
                        <i class="bi bi-trash3" aria-hidden="true"></i>
                        Permanently delete member
                    </button>
                </div>

            </form>

        @endif

    </section>
</section>

@endsection

@push('scripts')
@if(!$reason)
<script>
(() => {
    "use strict";

    const input = document.getElementById("purge-confirmation");
    const submit = document.getElementById("purge-submit");
    const form = document.getElementById("purge-form");

    if (!input || !submit || !form) return;

    function syncConfirmation() {
        submit.disabled = input.value !== "DELETE";
    }

    input.addEventListener("input", syncConfirmation);

    window.addEventListener("pageshow", syncConfirmation);

    form.addEventListener("submit", event => {
        if (input.value !== "DELETE") {
            event.preventDefault();
            syncConfirmation();
            input.focus();
        }
    });

    syncConfirmation();
})();
</script>
@endif
@endpush
