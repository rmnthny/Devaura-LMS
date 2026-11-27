
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <!-- DEVAURA BROWSER TAB ICON -->
    <link rel="icon"
          type="image/png"
          href="{{ asset('images/devaura-logo.png') }}">

    <link rel="apple-touch-icon"
          href="{{ asset('images/devaura-logo.png') }}">

    <title>@yield('title', 'Library Login') | UBNHS Library</title>

    <!-- APPLY SAVED THEME BEFORE PAGE RENDERS -->
    <script>
        try {
            document.documentElement.dataset.theme =
                localStorage.getItem('theme') === 'dark'
                    ? 'dark'
                    : 'light';
        } catch (e) {
            document.documentElement.dataset.theme = 'light';
        }
    </script>

    <!-- EXISTING STYLESHEETS -->
    <link rel="stylesheet"
          href="{{ asset('css/tokens.css') }}">

    <link rel="stylesheet"
          href="{{ asset('css/components.css') }}">

    <link rel="stylesheet"
          href="{{ asset('css/animations.css') }}">

    <link rel="stylesheet"
          href="{{ asset('css/auth.css') }}">
</head>

<body class="auth-body">

    <main class="auth-shell">

        <!-- UNIFIED AUTHENTICATION CONTAINER -->
        <div class="auth-unified-card">

            <!-- HEADER INSIDE THE MAIN CARD -->
            <header class="auth-header">

                <div class="auth-brand">

                    @if(file_exists(public_path('images/school-logo.png')))
                        <img
                            src="{{ asset('images/school-logo.png') }}"
                            alt="UBNHS school seal"
                            class="auth-brand-logo"
                        >
                    @else
                        <span class="auth-logo-fallback">
                            UB
                        </span>
                    @endif

                    <div class="auth-brand-text">

                        <strong>UBNHS Library</strong>

                        <small>
                            Upper Bicutan National High School
                        </small>

                        <small>
                            Library Management System
                        </small>

                    </div>

                </div>

                <!-- LIGHT / DARK THEME TOGGLE -->
                <button
                    type="button"
                    class="auth-theme-button"
                    id="authThemeToggle"
                    aria-label="Switch to dark theme"
                    aria-pressed="false"
                    title="Change color theme"
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
                            d="M20.9 13.35A9 9 0 0 1
                               10.65 3.1 9 9 0 1 0
                               20.9 13.35Z"
                        />
                    </svg>
                </button>

            </header>

            <!-- LOGIN OR EMAIL VERIFICATION CONTENT -->
            @yield('content')

            <!-- FOOTER INSIDE THE UNIFIED CARD -->
            <footer class="auth-footer">
                &copy; {{ date('Y') }}
                Upper Bicutan National High School.
                All rights reserved.
            </footer>

        </div>

    </main>

    <!-- THEME TOGGLE FUNCTIONALITY -->
    <script>
        (() => {
            const themeButton =
                document.getElementById('authThemeToggle');

            if (!themeButton) return;

            const root = document.documentElement;

            const moonIcon = `
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
                        d="M20.9 13.35A9 9 0 0 1
                           10.65 3.1 9 9 0 1 0
                           20.9 13.35Z"
                    />
                </svg>
            `;

            const sunIcon = `
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
                    <circle cx="12" cy="12" r="4"/>

                    <path
                        d="M12 2v2
                           m0 16v2
                           M4.93 4.93l1.41 1.41
                           m11.32 11.32 1.41 1.41
                           M2 12h2
                           m16 0h2
                           M4.93 19.07l1.41-1.41
                           M17.66 6.34l1.41-1.41"
                    />
                </svg>
            `;

            function updateThemeButton() {
                const isDark = root.dataset.theme === 'dark';

                themeButton.setAttribute(
                    'aria-pressed',
                    String(isDark)
                );

                themeButton.setAttribute(
                    'aria-label',
                    isDark
                        ? 'Switch to light theme'
                        : 'Switch to dark theme'
                );

                themeButton.setAttribute(
                    'title',
                    isDark
                        ? 'Switch to light theme'
                        : 'Switch to dark theme'
                );

                themeButton.innerHTML =
                    isDark ? sunIcon : moonIcon;
            }

            updateThemeButton();

            themeButton.addEventListener('click', () => {
                const next =
                    root.dataset.theme === 'dark'
                        ? 'light'
                        : 'dark';

                root.dataset.theme = next;

                try {
                    localStorage.setItem('theme', next);
                } catch (e) {
                    // Theme still works if storage is unavailable.
                }

                updateThemeButton();
            });
        })();
    </script>

    <!-- PRESERVE PAGE-SPECIFIC SCRIPTS -->
    @stack('scripts')

</body>
</html>
