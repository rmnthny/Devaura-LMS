<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') | UBNHS Library</title>
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <style>
        input[type="password"]::-ms-reveal,
        input[type="password"]::-ms-clear { display: none; }
    </style>
    @stack('styles')
</head>
<body class="bg-body-tertiary">
    @unless(request()->routeIs('kiosk.*'))
    <header class="border-bottom bg-white d-print-none">
        <nav class="container py-3 d-flex align-items-center flex-wrap gap-3" aria-label="Main navigation">
            <a href="{{ route('books.index') }}" class="fw-semibold text-decoration-none text-dark">
                <i class="bi bi-book" aria-hidden="true"></i> UBNHS Library
            </a>
            @canany(['reports.generate', 'books.manage'])
                <span class="text-body-tertiary" title="Coming in Sprint 5" aria-disabled="true">Dashboard <span class="badge text-bg-light border fw-normal">Soon</span></span>
            @endcanany
            @can('attendance.view')
                <a href="{{ route('attendance.index') }}" class="text-decoration-none {{ request()->routeIs('attendance.*') ? 'fw-semibold text-dark' : 'text-secondary' }}">Attendance</a>
            @endcan
            @can('circulation.process')
                <span class="text-body-tertiary" title="Coming in Sprint 4" aria-disabled="true">Book Borrowing <span class="badge text-bg-light border fw-normal">Soon</span></span>
                <span class="text-body-tertiary" title="Coming in Sprint 4" aria-disabled="true">Book Returns <span class="badge text-bg-light border fw-normal">Soon</span></span>
            @endcan
            @can('books.manage')
                <a href="{{ route('books.index') }}" class="text-decoration-none {{ request()->routeIs('books.*', 'catalog.*') ? 'fw-semibold text-dark' : 'text-secondary' }}">Book Management</a>
            @elsecan('books.view')
                <a href="{{ route('books.index') }}" class="text-decoration-none {{ request()->routeIs('books.*') ? 'fw-semibold text-dark' : 'text-secondary' }}">Book Lookup</a>
            @endcan
            @can('members.manage')
                <a href="{{ route('members.index') }}" class="text-decoration-none {{ request()->routeIs('members.index', 'members.edit', 'members.create') ? 'fw-semibold text-dark' : 'text-secondary' }}">Member Management</a>
            @endcan
            @canany(['roles.manage', 'schedules.view-own'])
                <a href="{{ auth()->user()->hasAnyRole(['superadmin', 'admin']) ? route('accounts.index') : route('schedules.index') }}" class="text-decoration-none {{ request()->routeIs('accounts.*', 'schedules.*') ? 'fw-semibold text-dark' : 'text-secondary' }}">Personnel Management</a>
            @endcanany
            @can('inventory.audit')
                <a href="{{ route('inventory.index') }}" class="text-decoration-none {{ request()->routeIs('inventory.*') ? 'fw-semibold text-dark' : 'text-secondary' }}">Inventory Management</a>
            @endcan
            @can('reports.generate')
                <span class="text-body-tertiary" title="Coming in Sprint 5" aria-disabled="true">Operational Reports <span class="badge text-bg-light border fw-normal">Soon</span></span>
            @endcan
            @can('audit.view')
                <span class="text-body-tertiary" title="Coming in Sprint 5" aria-disabled="true">Audit Trail <span class="badge text-bg-light border fw-normal">Soon</span></span>
            @endcan
            @role('superadmin')
                <a href="{{ route('archive.index') }}" class="text-decoration-none {{ request()->routeIs('archive.*') ? 'fw-semibold text-dark' : 'text-secondary' }}">Archive</a>
            @endrole
            @can('profile.update')
                <a href="{{ route('profile.edit') }}" class="text-decoration-none {{ request()->routeIs('profile.*') ? 'fw-semibold text-dark' : 'text-secondary' }}">My Profile</a>
            @endcan
            @can('qr.own')
                <a href="{{ route('members.my-qr-card') }}" class="text-decoration-none {{ request()->routeIs('members.my-qr-card') ? 'fw-semibold text-dark' : 'text-secondary' }}">My QR Card</a>
            @endcan
            @auth
                <div class="ms-auto d-flex align-items-center gap-2">
                    <span class="small text-body-secondary">
                        {{ auth()->user()->fullName() }}
                        <span class="badge text-bg-light border text-capitalize ms-1">{{ auth()->user()->getRoleNames()->first() }}</span>
                    </span>
                    <form method="post" action="{{ route('api.v1.logout') }}">
                        @csrf
                        <button class="btn btn-sm btn-outline-secondary">Sign out</button>
                    </form>
                </div>
            @endauth
        </nav>
    </header>
    @endunless
    <main class="container py-4">
        @if (session('status'))
            <div class="alert alert-success d-print-none" role="status">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </main>
</body>
</html>
