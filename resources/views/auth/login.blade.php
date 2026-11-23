@extends('layouts.library')
@section('title', 'Log In')
@section('content')
    <section class="card mx-auto" style="max-width: 28rem">
        <div class="card-body p-4">
            <h1 class="h3 mb-3">Log In</h1>
            @isset($rateLimitMessage)
                <div class="alert alert-warning" role="alert">{{ $rateLimitMessage }}</div>
            @endisset
            <form method="post" action="{{ route('api.v1.login.attempt') }}">
                @csrf
                <label class="form-label" for="login">Email address</label>
                <input class="form-control mb-3" type="email" id="login" name="login" value="{{ old('login') }}" autocomplete="username" maxlength="254" required autofocus>
                <label class="form-label" for="password">Password</label>
                <div class="input-group mb-3">
                    <input class="form-control" id="password" type="password" name="password" autocomplete="current-password" required>
                    <button class="btn btn-outline-secondary" type="button" id="toggle-login-password" aria-label="Show password">
                        <i class="bi bi-eye" aria-hidden="true"></i>
                    </button>
                </div>
                <button class="btn btn-primary" type="submit">Log In</button>
            </form>
        </div>
    </section>
    <script>
        (() => {
            const toggle = document.getElementById('toggle-login-password');
            const input = document.getElementById('password');
            toggle.addEventListener('click', () => {
                const show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                toggle.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        })();
    </script>
@endsection
