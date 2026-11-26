<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Library Login') | UBNHS Library</title>

    <script>
        try {
            document.documentElement.dataset.theme =
                localStorage.getItem('theme') || 'light';
        } catch (e) {}
    </script>

    <link rel="stylesheet" href="{{ asset('css/tokens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/components.css') }}">
    <link rel="stylesheet" href="{{ asset('css/animations.css') }}">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>

<body class="auth-body">

    <main class="auth-shell">

        <header class="auth-header">
            <div class="auth-brand">

                @if(file_exists(public_path('images/school-logo.png')))
                    <img
                        src="{{ asset('images/school-logo.png') }}"
                        alt="UBNHS school seal"
                        class="auth-brand-logo"
                    >
                @else
                    <span class="auth-logo-fallback">UB</span>
                @endif

                <div>
                    <strong>UBNHS Library</strong>
                    <small>Upper Bicutan National High School</small>
                    <small>Library Management System</small>
                </div>

            </div>

            <button
                type="button"
                class="auth-theme-button"
                id="authThemeToggle"
                aria-label="Switch color theme"
            >
                <svg width="20" height="20" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="1.7" stroke-linecap="round"
                     stroke-linejoin="round">
                    <path d="M20.9 13.35A9 9 0 0 1
                             10.65 3.1 9 9 0 1 0
                             20.9 13.35Z"/>
                </svg>
            </button>
        </header>

        @yield('content')

        <footer class="auth-footer">
            &copy; {{ date('Y') }} Upper Bicutan National High School.
            All rights reserved.
        </footer>

    </main>

    <script>
        const themeButton = document.getElementById('authThemeToggle');

        themeButton.addEventListener('click', () => {
            const root = document.documentElement;
            const next = root.dataset.theme === 'dark'
                ? 'light'
                : 'dark';

            root.dataset.theme = next;

            try {
                localStorage.setItem('theme', next);
            } catch (e) {}
        });
    </script>

    @stack('scripts')

</body>
</html>