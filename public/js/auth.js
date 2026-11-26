
document.addEventListener(
    'DOMContentLoaded',
    () => {
        const password =
            document.getElementById('password');

        const toggle =
            document.getElementById(
                'toggle-login-password'
            );

        /* Show / hide password */

        if (password && toggle) {
            toggle.addEventListener('click', () => {
                const show =
                    password.type === 'password';

                password.type = show
                    ? 'text'
                    : 'password';

                toggle.setAttribute(
                    'aria-label',
                    show
                        ? 'Hide password'
                        : 'Show password'
                );

                toggle.setAttribute(
                    'aria-pressed',
                    String(show)
                );

                const icon =
                    toggle.querySelector('i');

                if (icon) {
                    icon.className = show
                        ? 'bi bi-eye-slash'
                        : 'bi bi-eye';
                }
            });
        }

        /* Prevent duplicate submissions */

        const form =
            document.getElementById('login-form');

        const submit =
            document.getElementById('login-submit');

        if (form && submit) {
            form.addEventListener('submit', () => {
                submit.disabled = true;
                submit.setAttribute(
                    'aria-busy',
                    'true'
                );

                submit.textContent = 'Logging in…';
            });

            // Restore after browser back navigation.
            window.addEventListener('pageshow', () => {
                submit.disabled = false;
                submit.removeAttribute('aria-busy');

                submit.textContent = 'Log In';
            });
        }
    }
);
