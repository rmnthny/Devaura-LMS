@extends('layouts.auth')

@section('title', 'Verify Email')

@section('content')

<section class="auth-card">

    <!-- LEFT PANEL -->

    <aside class="auth-visual">

        @if(file_exists(public_path('images/school-building.jpg')))
            <img
                src="{{ asset('images/school-building.jpg') }}"
                alt=""
                class="auth-school-image"
            >
        @endif

        <div class="auth-visual-overlay"></div>

        <div class="auth-visual-content">

            @if(file_exists(public_path('images/school-logo.png')))
                <img
                    src="{{ asset('images/school-logo.png') }}"
                    alt="UBNHS school seal"
                    class="auth-school-logo"
                >
            @else
                <span class="auth-logo-fallback auth-logo-large">
                    UB
                </span>
            @endif

            <span class="auth-visual-label">
                ACCOUNT SECURITY
            </span>

            <h1>A safer library<br>experience.</h1>

            <p>
                Verify your identity to keep your account
                and library information protected.
            </p>

            <div class="auth-school-caption">
                Upper Bicutan National High School
            </div>

        </div>

    </aside>

    <!-- RIGHT PANEL -->

    <div class="auth-form-panel">

        <div class="auth-form-content">

            <div class="auth-verification-icon">

                <svg
                    width="26"
                    height="26"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.6"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <rect x="2" y="4" width="20"
                          height="16" rx="3"/>

                    <path d="m3 6 9 7 9-7"/>
                </svg>

            </div>

            <span class="auth-eyebrow">
                EMAIL VERIFICATION
            </span>

            <h2>Verify your email</h2>

            <p class="auth-description">
                Enter the six-digit verification code
                sent to your email address.
            </p>

            <!-- SERVER MESSAGES -->

            @if(session('status'))
                <div class="auth-alert auth-alert-success"
                     role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if(session('error'))
                <div class="auth-alert auth-alert-error"
                     role="alert">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="auth-alert auth-alert-error"
                     role="alert">

                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach

                </div>
            @endif

            <!-- OTP VERIFICATION FORM -->

            <form
                method="POST"
                action="{{ route('api.v1.otp.verify') }}"
                class="auth-form"
                id="otpForm"
            >

                @csrf

                <div class="auth-field">

                    <label for="code">
                        Verification code
                    </label>

                    <input
                        type="text"
                        id="code"
                        name="code"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        pattern="[0-9]{6}"
                        maxlength="6"
                        minlength="6"
                        placeholder="000000"
                        required
                        class="auth-otp-input @error('code') auth-invalid @enderror"
                        aria-describedby="otpHint"
                    >

                    <small id="otpHint"
                           class="auth-field-hint">
                        Enter all six digits of your code.
                    </small>

                </div>

                <button
                    type="submit"
                    class="auth-submit"
                    id="verifyButton"
                >
                    Verify Email
                </button>

            </form>

            <!-- RESEND COOLDOWN -->

            @php
                $resendWait = max(
                    0,
                    (int) session('otp_resend_available_at', 0)
                    - now()->timestamp
                );
            @endphp

            <div class="auth-resend-section">

                <p>Didn't receive the code?</p>

                <form
                    method="POST"
                    action="{{ route('api.v1.otp.resend') }}"
                >

                    @csrf

                    <button
                        type="submit"
                        class="auth-resend-button"
                        id="otp-resend-btn"
                        data-wait="{{ $resendWait }}"
                        @disabled($resendWait > 0)
                    >
                        {{ $resendWait > 0
                            ? "Resend code ({$resendWait}s)"
                            : 'Resend code' }}
                    </button>

                </form>

            </div>

            <div class="auth-divider"></div>

            <div class="auth-security-note">

                <svg
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.7"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    aria-hidden="true"
                >
                    <rect x="5" y="10" width="14"
                          height="11" rx="2"/>

                    <path d="M8 10V7a4 4 0 0 1 8 0v3"/>
                </svg>

                <span>
                    Never share your verification code
                    with anyone.
                </span>

            </div>

        </div>

    </div>

</section>

@endsection

@push('scripts')

<script>
(() => {

    const codeInput = document.getElementById('code');
    const otpForm = document.getElementById('otpForm');
    const verifyButton = document.getElementById('verifyButton');
    const resendButton = document.getElementById('otp-resend-btn');

    // Allow numeric characters only.
    codeInput.addEventListener('input', () => {

        codeInput.value = codeInput.value
            .replace(/[^0-9]/g, '')
            .slice(0, 6);

    });

    // Verification loading state.
    otpForm.addEventListener('submit', () => {

        verifyButton.disabled = true;
        verifyButton.textContent = 'Verifying...';

    });

    // Preserve the backend-provided resend cooldown.
    const wait = Number(resendButton.dataset.wait || 0);
    const deadline = Date.now() + wait * 1000;

    function updateCooldown() {

        const remaining = Math.max(
            0,
            Math.ceil((deadline - Date.now()) / 1000)
        );

        resendButton.disabled = remaining > 0;

        resendButton.textContent = remaining > 0
            ? `Resend code (${remaining}s)`
            : 'Resend code';

        if (remaining > 0) {
            setTimeout(updateCooldown, 250);
        }

    }

    updateCooldown();

    resendButton.form.addEventListener('submit', () => {

        resendButton.disabled = true;
        resendButton.textContent = 'Sending...';

    });

    window.addEventListener('pageshow', () => {

        verifyButton.disabled = false;
        verifyButton.textContent = 'Verify Email';

    });

})();
</script>

@endpush