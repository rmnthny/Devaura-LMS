
(() => {
    const root = document.documentElement;
    const storageKey = 'devaura-theme';

    function readTheme() {
        try {
            return localStorage.getItem(storageKey)
                === 'dark' ? 'dark' : 'light';
        } catch {
            return 'light';
        }
    }

    function applyTheme(theme) {
        root.dataset.theme = theme;

        // Keep Bootstrap components consistent.
        root.dataset.bsTheme = theme;

        const toggle =
            document.getElementById('theme-toggle');

        if (!toggle) return;

        const icon = toggle.querySelector('i');
        const isDark = theme === 'dark';

        toggle.setAttribute(
            'aria-pressed',
            String(isDark)
        );

        toggle.setAttribute(
            'aria-label',
            isDark
                ? 'Switch to Light Mode'
                : 'Switch to Dark Mode'
        );

        if (icon) {
            icon.className = isDark
                ? 'bi bi-sun'
                : 'bi bi-moon-stars';
        }
    }

    // Apply early to prevent a light-mode flash.
    applyTheme(readTheme());

    document.addEventListener(
        'DOMContentLoaded',
        () => {
            applyTheme(readTheme());

            const toggle =
                document.getElementById(
                    'theme-toggle'
                );

            if (!toggle) return;

            toggle.addEventListener('click', () => {
                const nextTheme =
                    root.dataset.theme === 'dark'
                        ? 'light'
                        : 'dark';

                try {
                    localStorage.setItem(
                        storageKey,
                        nextTheme
                    );
                } catch {
                    // Theme still works for this page.
                }

                applyTheme(nextTheme);

                const icon = toggle.querySelector('i');

                if (icon) {
                    icon.classList.add(
                        'lms-icon-reveal'
                    );
                }
            });
        }
    );
})();
