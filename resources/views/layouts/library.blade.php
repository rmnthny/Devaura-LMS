<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>@yield('title') | UBNHS Library</title>

    <!-- =========================================
         THEME AND SIDEBAR INITIALIZATION
         ========================================= -->

  <script type="text/javascript">
        (() => {
            const root = document.documentElement;

            let theme = 'light';
            let sidebarState = null;

            try {
                theme = localStorage.getItem('theme') === 'dark'
                    ? 'dark'
                    : 'light';

                sidebarState = localStorage.getItem('sidebar');
            } catch (error) {}

            root.dataset.theme = theme;
            root.dataset.bsTheme = theme;

            const compactScreen = window.matchMedia(
                '(min-width: 48rem) and (max-width: 79.99rem)'
            ).matches;

            const sidebarCollapsed =
                sidebarState === 'collapsed' ||
                (sidebarState !== 'expanded' && compactScreen);

            root.classList.toggle(
                'sidebar-collapsed',
                sidebarCollapsed
            );
        })();
    </script>

        <!-- LOCAL VENDOR STYLES -->

        <link rel="stylesheet"
            href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">

        <link rel="stylesheet"
            href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">

        <!-- WORKSPACE FOUNDATION -->

        <link rel="stylesheet"
            href="{{ asset('css/workspace.css') }}">

        <link rel="stylesheet"
            href="{{ asset('css/workspace-tokens.css') }}">

        <!-- WORKSPACE COMPONENTS -->

        <link rel="stylesheet"
            href="{{ asset('css/sidebar.css') }}">

        <link rel="stylesheet"
            href="{{ asset('css/topbar.css') }}">

        <link rel="stylesheet"
            href="{{ asset('css/responsive.css') }}">

        <!-- GLASS EFFECTS AND BRANDING -->

        <link rel="stylesheet"
            href="{{ asset('css/glass-effects.css') }}">

        <link rel="stylesheet"
            href="{{ asset('css/sidebar-branding.css') }}">

        <!-- REUSABLE FILE UPLOAD -->

        <link rel="stylesheet"
            href="{{ asset('css/file-upload.css') }}">

        <link rel="stylesheet"
            href="{{ asset('css/catalog-nav.css') }}">

        <!-- MODULE-SPECIFIC STYLES -->

        @stack('styles')

</head>

<body class="workspace-body">

    <a href="#workspaceContent" class="workspace-skip">
        Skip to content
    </a>

    <div class="workspace-shell">

        <!-- =====================================
             SIDEBAR
             ===================================== -->

        <aside class="workspace-sidebar d-print-none"
               id="workspaceSidebar">

            <!-- SCHOOL BRANDING -->

            <div class="sidebar-brand">

                <button type="button"
                        id="sidebarToggle"
                        class="sidebar-logo-button"
                        aria-label="Toggle sidebar"
                        aria-controls="workspaceSidebar"
                        title="Toggle navigation">

                    @if(file_exists(public_path('images/school-logo.png')))

                        <img
                            src="{{ asset('images/school-logo.png') }}"
                            alt=""
                            class="sidebar-school-logo"
                        >

                    @else

                        <span class="sidebar-logo-fallback">
                            UB
                        </span>

                    @endif

                </button>

                <div class="sidebar-brand-text">
                    <strong>UBNHS Library</strong>
                    <small>Management System</small>
                </div>

            </div>

            <!-- MAIN NAVIGATION -->

            <nav class="sidebar-navigation"
                 aria-label="Main navigation">

                <div class="sidebar-section-label">
                    Management
                </div>

                <!-- DASHBOARD -->

                @canany(['reports.generate', 'books.manage'])

                    <span class="sidebar-link sidebar-unavailable"
                          aria-disabled="true"
                          title="Dashboard — Coming in Sprint 5">

                        <i class="bi bi-grid-1x2"
                           aria-hidden="true"></i>

                        <span class="sidebar-link-text">
                            Dashboard
                        </span>

                        <small class="sidebar-soon">Soon</small>

                    </span>

                @endcanany

                <!-- BOOK MANAGEMENT -->

                @can('books.manage')

                    <a href="{{ route('books.index') }}"
                       title="Book Management"
                       aria-label="Book Management"
                       class="sidebar-link {{ request()->routeIs('books.*', 'catalog.*') ? 'is-active' : '' }}">

                        <i class="bi bi-book"
                           aria-hidden="true"></i>

                        <span class="sidebar-link-text">
                            Book Management
                        </span>

                    </a>

                @elsecan('books.view')

                    <a href="{{ route('books.index') }}"
                       title="Book Lookup"
                       aria-label="Book Lookup"
                       class="sidebar-link {{ request()->routeIs('books.*') ? 'is-active' : '' }}">

                        <i class="bi bi-search"
                           aria-hidden="true"></i>

                        <span class="sidebar-link-text">
                            Book Lookup
                        </span>

                    </a>

                @endcan

                <!-- MEMBER MANAGEMENT -->

                @can('members.manage')

                    <a href="{{ route('members.index') }}"
                       title="Member Management"
                       aria-label="Member Management"
                       class="sidebar-link {{ request()->routeIs('members.index', 'members.edit', 'members.create') ? 'is-active' : '' }}">

                        <i class="bi bi-people"
                           aria-hidden="true"></i>

                        <span class="sidebar-link-text">
                            Member Management
                        </span>

                    </a>

                @endcan

                <!-- PERSONNEL MANAGEMENT -->

                @canany(['roles.manage', 'schedules.view-own'])

                    <a href="{{ auth()->user()->hasAnyRole(['superadmin', 'admin']) ? route('accounts.index') : route('schedules.index') }}"
                       title="Personnel Management"
                       aria-label="Personnel Management"
                       class="sidebar-link {{ request()->routeIs('accounts.*', 'schedules.*') ? 'is-active' : '' }}">

                        <i class="bi bi-person-vcard"
                           aria-hidden="true"></i>

                        <span class="sidebar-link-text">
                            Personnel Management
                        </span>

                    </a>

                @endcanany

                <!-- INVENTORY MANAGEMENT -->

                @can('inventory.audit')

                    <a href="{{ route('inventory.index') }}"
                       title="Inventory Management"
                       aria-label="Inventory Management"
                       class="sidebar-link {{ request()->routeIs('inventory.*') ? 'is-active' : '' }}">

                        <i class="bi bi-box-seam"
                           aria-hidden="true"></i>

                        <span class="sidebar-link-text">
                            Inventory Management
                        </span>

                    </a>

                @endcan

                <!-- ARCHIVE -->

                @role('superadmin')

                    <a href="{{ route('archive.index') }}"
                       title="Archive"
                       aria-label="Archive"
                       class="sidebar-link {{ request()->routeIs('archive.*') ? 'is-active' : '' }}">

                        <i class="bi bi-archive"
                           aria-hidden="true"></i>

                        <span class="sidebar-link-text">
                            Archive
                        </span>

                    </a>

                @endrole

                <!-- LIBRARY OPERATIONS -->

                @canany(['circulation.process', 'reports.generate', 'audit.view'])

                    <div class="sidebar-section-label">
                        Library Operations
                    </div>

                @endcanany

                <!-- BOOK BORROWING AND RETURNS -->

                @can('circulation.process')

                    <span class="sidebar-link sidebar-unavailable"
                          aria-disabled="true"
                          title="Book Borrowing — Coming in Sprint 4">

                        <i class="bi bi-box-arrow-up-right"
                           aria-hidden="true"></i>

                        <span class="sidebar-link-text">
                            Book Borrowing
                        </span>

                        <small class="sidebar-soon">Soon</small>

                    </span>

                    <span class="sidebar-link sidebar-unavailable"
                          aria-disabled="true"
                          title="Book Returns — Coming in Sprint 4">

                        <i class="bi bi-arrow-return-left"
                           aria-hidden="true"></i>

                        <span class="sidebar-link-text">
                            Book Returns
                        </span>

                        <small class="sidebar-soon">Soon</small>

                    </span>

                @endcan

                <!-- OPERATIONAL REPORTS -->

                @can('reports.generate')

                    <span class="sidebar-link sidebar-unavailable"
                          aria-disabled="true"
                          title="Reports — Coming in Sprint 5">

                        <i class="bi bi-bar-chart"
                           aria-hidden="true"></i>

                        <span class="sidebar-link-text">
                            Operational Reports
                        </span>

                        <small class="sidebar-soon">Soon</small>

                    </span>

                @endcan

                <!-- AUDIT TRAIL -->

                @can('audit.view')

                    <span class="sidebar-link sidebar-unavailable"
                          aria-disabled="true"
                          title="Audit Trail — Coming in Sprint 5">

                        <i class="bi bi-clock-history"
                           aria-hidden="true"></i>

                        <span class="sidebar-link-text">
                            Audit Trail
                        </span>

                        <small class="sidebar-soon">Soon</small>

                    </span>

                @endcan

                <!-- ACCOUNT -->

                @can('qr.own')

                    <div class="sidebar-section-label">
                        Account
                    </div>

                    <a href="{{ route('members.my-qr-card') }}"
                       title="My QR Card"
                       aria-label="My QR Card"
                       class="sidebar-link {{ request()->routeIs('members.my-qr-card') ? 'is-active' : '' }}">

                        <i class="bi bi-qr-code"
                           aria-hidden="true"></i>

                        <span class="sidebar-link-text">
                            My QR Card
                        </span>

                    </a>

                @endcan

            </nav>

            <!-- SIDEBAR FOOTER -->

            <div class="sidebar-footer">

                <i class="bi bi-mortarboard"
                   aria-hidden="true"></i>

                <div class="sidebar-footer-text">
                    <strong>UBNHS Library</strong>
                    <small>Academic Workspace</small>
                </div>

            </div>

        </aside>

        <!-- MOBILE BACKDROP -->

        <button type="button"
                class="workspace-backdrop d-print-none"
                id="workspaceBackdrop"
                aria-label="Close sidebar"
                hidden>
        </button>

        <!-- =====================================
             MAIN WORKSPACE
             ===================================== -->

        <div class="workspace-main">

            <!-- FLOATING TOPBAR -->

            <header class="workspace-topbar d-print-none">

                <div class="workspace-topbar-left">

                    <!-- MOBILE LOGO TOGGLE -->

                    <button type="button"
                            class="mobile-brand-toggle"
                            id="mobileSidebarToggle"
                            aria-label="Open navigation"
                            aria-controls="workspaceSidebar">

                        @if(file_exists(public_path('images/school-logo.png')))

                            <img
                                src="{{ asset('images/school-logo.png') }}"
                                alt=""
                            >

                        @else

                            <span>UB</span>

                        @endif

                    </button>

                    <!-- BREADCRUMB -->

                    <div class="workspace-breadcrumb"
                         aria-label="Current location">

                        <span class="breadcrumb-root">
                            Workspace
                        </span>

                        <i class="bi bi-chevron-right"
                           aria-hidden="true"></i>

                        <span class="breadcrumb-current">
                            @yield('title', 'Library')
                        </span>

                    </div>

                </div>

                <!-- TOPBAR ACTIONS -->

                <div class="workspace-topbar-actions">

                    <!-- THEME TOGGLE -->

                    <button type="button"
                            class="workspace-icon-button"
                            id="workspaceThemeToggle"
                            aria-label="Switch color theme"
                            title="Switch color theme">

                        <i class="bi bi-moon"
                           aria-hidden="true"></i>

                    </button>

                    <div class="topbar-divider"
                         aria-hidden="true"></div>

                    <!-- PROFILE DROPDOWN -->

                    @auth

                        <details class="workspace-profile"
                                 id="workspaceProfile">

                            <summary class="workspace-profile-trigger"
                                     aria-label="Account menu">

                                <div class="workspace-avatar">

                                    {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr(auth()->user()->fullName(), 0, 1)) }}

                                </div>

                                <div class="workspace-profile-info">

                                    <strong>
                                        {{ auth()->user()->fullName() }}
                                    </strong>

                                    <small>
                                        {{ ucfirst(auth()->user()->getRoleNames()->first() ?? 'Member') }}
                                    </small>

                                </div>

                                <i class="bi bi-chevron-down profile-chevron"
                                   aria-hidden="true"></i>

                            </summary>

                            <!-- PROFILE MENU -->

                            <div class="workspace-profile-menu">

                                <div class="profile-menu-header">

                                    <strong>
                                        {{ auth()->user()->fullName() }}
                                    </strong>

                                    <small>
                                        {{ auth()->user()->email }}
                                    </small>

                                </div>

                                @can('profile.update')

                                    <a href="{{ route('profile.edit') }}"
                                       class="profile-menu-item">

                                        <i class="bi bi-person-circle"
                                           aria-hidden="true"></i>

                                        My Profile

                                    </a>

                                @endcan

                                <div class="profile-menu-divider"></div>

                                <!-- SINGLE LOGOUT CONTROL -->

                                <form method="POST"
                                      action="{{ route('api.v1.logout') }}">

                                    @csrf

                                    <button type="submit"
                                            class="profile-menu-item profile-logout">

                                        <i class="bi bi-box-arrow-right"
                                           aria-hidden="true"></i>

                                        Sign out

                                    </button>

                                </form>

                            </div>

                        </details>

                    @endauth

                </div>

            </header>

            <!-- =================================
                 MAIN CONTENT CARD
                 ================================= -->

            <main class="workspace-content"
                  id="workspaceContent"
                  tabindex="-1">

                <!-- SUCCESS MESSAGE -->

                @if(session('status'))

                    <div class="alert alert-success d-print-none"
                         role="status">

                        {{ session('status') }}

                    </div>

                @endif

                <!-- VALIDATION ERRORS -->

                @if($errors->any())

                    <div class="alert alert-danger"
                         role="alert">

                        <ul class="mb-0">

                            @foreach($errors->all() as $error)

                                <li>{{ $error }}</li>

                            @endforeach

                        </ul>

                    </div>

                @endif

                <!-- MODULE CONTENT -->

            {{-- Shared catalog navigation --}}

            @if(request()->routeIs('catalog.*'))
                @can('books.manage')
                    <div class="catalog-page-navigation d-print-none">
                        <x-catalog-nav :active="request()->segment(2)" />
                    </div>
                @endcan
            @endif

            @yield('content')

            </main>

            <!-- WORKSPACE FOOTER -->

        <footer class="workspace-footer d-print-none">

            <span>
                &copy; {{ date('Y') }}
                Upper Bicutan National High School
            </span>

            <div class="workspace-footer-brand">

                <span>Library Management System</span>

                <span aria-hidden="true">·</span>

                <span class="workspace-footer-credit">

                    Developed by

                    @if(file_exists(public_path('images/devaura-logo.png')))

                        <img
                            src="{{ asset('images/devaura-logo.png') }}"
                            alt="Devaura"
                            class="workspace-footer-logo"
                        >

                    @else

                        <strong>DEVAURA</strong>

                    @endif

                </span>

            </div>

        </footer>

        </div>

    </div>


        <!-- SHARED WORKSPACE INTERACTIONS -->

        <script src="{{ asset('js/workspace.js') }}"
                defer></script>

        <!-- REUSABLE FILE UPLOAD -->

        <script src="{{ asset('js/file-upload.js') }}"
                defer></script>

        <!-- MODULE-SPECIFIC SCRIPTS -->

        @stack('scripts')
</body>

</html>