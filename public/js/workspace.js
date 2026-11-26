/* ==================================================
   UBNHS LIBRARY MANAGEMENT SYSTEM
   Modern Academic Glass 2.1
   Shared Workspace Interactions
   ================================================== */

(() => {

    "use strict";

    /* ==================================================
       01. ELEMENT REFERENCES
       ================================================== */

    const root = document.documentElement;

    const sidebar = document.getElementById(
        "workspaceSidebar"
    );

    const sidebarToggle = document.getElementById(
        "sidebarToggle"
    );

    const mobileToggle = document.getElementById(
        "mobileSidebarToggle"
    );

    const backdrop = document.getElementById(
        "workspaceBackdrop"
    );

    const themeToggle = document.getElementById(
        "workspaceThemeToggle"
    );

    const profile = document.getElementById(
        "workspaceProfile"
    );

    const mobileQuery = window.matchMedia(
        "(max-width: 47.99rem)"
    );

    const compactQuery = window.matchMedia(
        "(min-width: 48rem) and (max-width: 79.99rem)"
    );


    /* ==================================================
       02. STORAGE HELPERS
       ================================================== */

    function getPreference(key, fallback = null) {

        try {

            return localStorage.getItem(key) ?? fallback;

        } catch {

            return fallback;

        }

    }

    function savePreference(key, value) {

        try {

            localStorage.setItem(key, value);

        } catch {

            // Keep the interface functional
            // when local storage is unavailable.

        }

    }


    /* ==================================================
       03. PERSISTENT SIDEBAR STATE
       ================================================== */

    const savedSidebarState = getPreference("sidebar");

    let desktopCollapsed =

        savedSidebarState === "collapsed"

            ? true

            : savedSidebarState === "expanded"

                ? false

                : compactQuery.matches;


    function syncSidebarState() {

        const isMobile = mobileQuery.matches;

        const expanded = isMobile

            ? root.classList.contains("sidebar-mobile-open")

            : !desktopCollapsed;

        sidebarToggle?.setAttribute(
            "aria-expanded",
            String(expanded)
        );

        mobileToggle?.setAttribute(
            "aria-expanded",
            String(expanded)
        );

        const sidebarLabel = isMobile

            ? "Close navigation"

            : expanded

                ? "Collapse sidebar"

                : "Expand sidebar";

        sidebarToggle?.setAttribute(
            "aria-label",
            sidebarLabel
        );

        sidebarToggle?.setAttribute(
            "title",
            sidebarLabel
        );

        mobileToggle?.setAttribute(
            "aria-label",
            expanded
                ? "Close navigation"
                : "Open navigation"
        );

        if (sidebar) {

            sidebar.inert = isMobile && !expanded;

        }

    }


    /* ==================================================
       04. DESKTOP SIDEBAR TOGGLE
       ================================================== */

    function toggleDesktopSidebar() {

        desktopCollapsed = !desktopCollapsed;

        root.classList.toggle(
            "sidebar-collapsed",
            desktopCollapsed
        );

        savePreference(

            "sidebar",

            desktopCollapsed
                ? "collapsed"
                : "expanded"

        );

        syncSidebarState();

    }


    /* ==================================================
       05. MOBILE SIDEBAR
       ================================================== */

    function openMobileSidebar() {

        if (!mobileQuery.matches) return;

        root.classList.add(
            "sidebar-mobile-open"
        );

        if (backdrop) {

            backdrop.hidden = false;

        }

        if (profile) {

            profile.open = false;

        }

        syncSidebarState();

        sidebarToggle?.focus();

    }


    function closeMobileSidebar(restoreFocus = true) {

        const wasOpen = root.classList.contains(
            "sidebar-mobile-open"
        );

        if (wasOpen && restoreFocus && mobileQuery.matches) {

            mobileToggle?.focus();

        }

        root.classList.remove(
            "sidebar-mobile-open"
        );

        if (backdrop) {

            backdrop.hidden = true;

        }

        syncSidebarState();

    }


    /* ==================================================
       06. LOGO-BASED NAVIGATION CONTROL
       ================================================== */

    sidebarToggle?.addEventListener("click", () => {

        if (mobileQuery.matches) {

            closeMobileSidebar();

        } else {

            toggleDesktopSidebar();

        }

    });


    mobileToggle?.addEventListener("click", () => {

        const isOpen = root.classList.contains(
            "sidebar-mobile-open"
        );

        if (isOpen) {

            closeMobileSidebar();

        } else {

            openMobileSidebar();

        }

    });


    backdrop?.addEventListener("click", () => {

        closeMobileSidebar();

    });


    /* ==================================================
       07. MODULE NAVIGATION
       ================================================== */

    sidebar?.querySelectorAll("a.sidebar-link")

        .forEach(link => {

            link.addEventListener("click", () => {

                // Mobile overlay closes after navigation.
                // Desktop sidebar state remains unchanged.

                if (mobileQuery.matches) {

                    closeMobileSidebar();

                }

            });

        });


    /* ==================================================
       08. RESPONSIVE SIDEBAR BEHAVIOR
       ================================================== */

    function applyResponsiveSidebar() {

        closeMobileSidebar(false);

        root.classList.toggle(
            "sidebar-collapsed",
            desktopCollapsed
        );

        syncSidebarState();

    }

    // One breakpoint listener only.
    // Resizing does not overwrite the user's preference.

    mobileQuery.addEventListener(

        "change",

        applyResponsiveSidebar

    );


    /* ==================================================
       09. LIGHT AND DARK MODE
       ================================================== */

    function applyTheme(theme) {

        const selectedTheme = theme === "dark"

            ? "dark"

            : "light";

        const isDark = selectedTheme === "dark";

        root.dataset.theme = selectedTheme;

        root.dataset.bsTheme = selectedTheme;

        if (!themeToggle) return;

        const icon = themeToggle.querySelector("i");

        if (icon) {

            icon.className = isDark

                ? "bi bi-sun"

                : "bi bi-moon";

        }

        const themeLabel = isDark

            ? "Switch to light mode"

            : "Switch to dark mode";

        themeToggle.setAttribute(
            "aria-label",
            themeLabel
        );

        themeToggle.setAttribute(
            "title",
            themeLabel
        );

    }


    themeToggle?.addEventListener("click", () => {

        const nextTheme = root.dataset.theme === "dark"

            ? "light"

            : "dark";

        applyTheme(nextTheme);

        savePreference(
            "theme",
            nextTheme
        );

    });


    /* ==================================================
       10. PROFILE DROPDOWN
       ================================================== */

    document.addEventListener("click", event => {

        if (

            profile?.open &&

            !profile.contains(event.target)

        ) {

            profile.open = false;

        }

    });


    profile?.addEventListener("focusout", event => {

        if (

            event.relatedTarget &&

            !profile.contains(event.relatedTarget)

        ) {

            profile.open = false;

        }

    });


    /* ==================================================
       11. KEYBOARD ACCESSIBILITY
       ================================================== */

    document.addEventListener("keydown", event => {

        const sidebarOpen =

            mobileQuery.matches &&

            root.classList.contains("sidebar-mobile-open");


        /* ESCAPE KEY */

        if (event.key === "Escape") {

            if (sidebarOpen) {

                closeMobileSidebar();

                return;

            }

            if (profile?.open) {

                profile.open = false;

                profile.querySelector("summary")?.focus();

            }

        }


        /* MOBILE SIDEBAR FOCUS TRAP */

        if (

            event.key !== "Tab" ||

            !sidebarOpen ||

            !sidebar

        ) return;


        const focusable = Array.from(

            sidebar.querySelectorAll(

                'a[href], button:not([disabled]), [tabindex="0"]'

            )

        ).filter(element => {

            return element.getClientRects().length > 0;

        });


        if (!focusable.length) return;

        const first = focusable[0];

        const last = focusable[focusable.length - 1];

        const active = document.activeElement;


        if (!sidebar.contains(active)) {

            event.preventDefault();

            (event.shiftKey ? last : first).focus();

        } else if (

            event.shiftKey && active === first

        ) {

            event.preventDefault();

            last.focus();

        } else if (

            !event.shiftKey && active === last

        ) {

            event.preventDefault();

            first.focus();

        }

    });


    /* ==================================================
       12. ACTIVE NAVIGATION
       ================================================== */

    sidebar?.querySelectorAll("a.sidebar-link")

        .forEach(link => {

            if (link.classList.contains("is-active")) {

                link.setAttribute(
                    "aria-current",
                    "page"
                );

            } else {

                link.removeAttribute(
                    "aria-current"
                );

            }

        });


    /* ==================================================
       13. COLLAPSED NAVIGATION LABELS
       ================================================== */

    sidebar?.querySelectorAll("a.sidebar-link")

        .forEach(link => {

            const label =

                link.querySelector(".sidebar-link-text")

                    ?.textContent.trim();

            if (label) {

                link.setAttribute(
                    "title",
                    label
                );

                link.setAttribute(
                    "aria-label",
                    label
                );

            }

        });


    /* ==================================================
       14. INITIALIZATION
       ================================================== */

    applyTheme(

        getPreference(
            "theme",
            "light"
        )

    );

    applyResponsiveSidebar();

})();