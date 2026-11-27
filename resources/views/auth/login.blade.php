
@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')

<section class="auth-card" aria-label="Library sign in">

    <!-- ========================================
         LEFT PANEL — SCHOOL BRANDING
         ======================================== -->

    <aside class="auth-visual">

        @if(file_exists(public_path('images/school-building.jpg')))
            <img
                src="{{ asset('images/school-building.jpg') }}"
                alt=""
                class="auth-school-image"
            >
        @endif

        <div
            class="auth-visual-overlay"
            aria-hidden="true"
        ></div>

        <div class="auth-visual-content">

            <span class="auth-visual-label">
                LIBRARY MANAGEMENT SYSTEM
            </span>

            <h1>
                Your library,<br>
                connected.
            </h1>

            <p>
                Discover knowledge, manage library resources,
                and support a better learning experience.
            </p>

            <div class="auth-school-caption">
                Upper Bicutan National High School
            </div>

        </div>

    </aside>

    <!-- ========================================
         RIGHT PANEL — SIGN-IN FORM
         ======================================== -->

    <div class="auth-form-panel">

        <div class="auth-form-content">

            <span class="auth-eyebrow">
                AUTHORIZED ACCESS
            </span>

            <h2>Welcome back!</h2>

            <p class="auth-description">
                Sign in to access your library account.
            </p>

            <!-- LOGIN RATE-LIMIT MESSAGE -->
            @if(!empty($rateLimitMessage))
                <div
                    class="auth-alert auth-alert-error"
                    role="alert"
                >
                    {{ $rateLimitMessage }}
                </div>
            @endif

            <!-- SUCCESS MESSAGE -->
            @if(session('status'))
                <div
                    class="auth-alert auth-alert-success"
                    role="status"
                >
                    {{ session('status') }}
                </div>
            @endif

            <!-- SESSION ERROR -->
            @if(session('error'))
                <div
                    class="auth-alert auth-alert-error"
                    role="alert"
                >
                    {{ session('error') }}
                </div>
            @endif

            <!-- VALIDATION ERRORS -->
            @if($errors->any())
                <div
                    class="auth-alert auth-alert-error"
                    role="alert"
                >
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            <!-- LOGIN FORM -->
            <form
                method="POST"
                action="{{ route('api.v1.login.attempt') }}"
                id="loginForm"
                class="auth-form"
                autocomplete="on"
            >

                @csrf

                <!-- EMAIL ADDRESS -->
                <div class="auth-field">

                    <label for="login">
                        Email address
                    </label>

                    <input
                        type="email"
                        id="login"
                        name="login"
                        value="{{ old('login') }}"
                        placeholder="Enter your email address"
                        autocomplete="username"
                        inputmode="email"
                        required
                        class="@error('login') auth-invalid @enderror"
                    >

                </div>

                <!-- PASSWORD -->
                <div class="auth-field">

                    <label for="password">
                        Password
                    </label>

                    <div class="auth-password-wrapper">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                            class="@error('password') auth-invalid @enderror"
                        >

                        <!-- SINGLE PASSWORD VISIBILITY CONTROL -->
                        <button
                            type="button"
                            id="passwordToggle"
                            class="auth-password-toggle"
                            aria-label="Show password"
                            aria-pressed="false"
                            aria-controls="password"
                        >
                            <svg
                                width="20"
                                height="20"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.7"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path
                                    d="M2 12s3.5-7 10-7
                                       10 7 10 7-3.5 7-10
                                       7-10-7-10-7Z"
                                />

                                <circle
                                    cx="12"
                                    cy="12"
                                    r="3"
                                />
                            </svg>
                        </button>

                    </div>

                </div>

                <!-- SIGN IN BUTTON -->
                <button
                    type="submit"
                    class="auth-submit"
                    id="loginButton"
                >
                    Sign In
                </button>

            </form>

            <!-- SUPPORT INFORMATION -->
            <div class="auth-divider"></div>

            <div class="auth-help">

                <strong>
                    Need assistance?
                </strong>

                <p>
                    Please contact your school librarian
                    for account-related concerns.
                </p>

            </div>

        </div>

    </div>

</section>

@endsection

@push('scripts')

<script>
    (() => {
        const password = document.getElementById('password');
        const passwordToggle = document.getElementById('passwordToggle');
        const loginForm = document.getElementById('loginForm');
        const loginButton = document.getElementById('loginButton');

        // PASSWORD VISIBILITY
        if (password && passwordToggle) {
            passwordToggle.addEventListener('click', () => {
                const visible = password.type === 'password';

                password.type = visible ? 'text' : 'password';

                passwordToggle.setAttribute(
                    'aria-label',
                    visible ? 'Hide password' : 'Show password'
                );

                passwordToggle.setAttribute(
                    'aria-pressed',
                    String(visible)
                );
            });
        }

        // PREVENT DUPLICATE FORM SUBMISSIONS
        if (loginForm && loginButton) {
            loginForm.addEventListener('submit', () => {
                loginButton.disabled = true;
                loginButton.setAttribute('aria-busy', 'true');
                loginButton.textContent = 'Signing in...';
            });

            // RESTORE BUTTON ON BACK NAVIGATION
            window.addEventListener('pageshow', () => {
                loginButton.disabled = false;
                loginButton.removeAttribute('aria-busy');
                loginButton.textContent = 'Sign In';
            });
        }
    })();
</script>

@endpush
