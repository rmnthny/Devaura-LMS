
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Public Kiosk | UBNHS Library</title>

    <link rel="stylesheet"
          href="{{ asset('vendor/bootstrap/bootstrap.min.css') }}">
    <link rel="stylesheet"
          href="{{ asset('vendor/bootstrap-icons/bootstrap-icons.min.css') }}">
    <link rel="stylesheet"
          href="{{ asset('css/modules/kiosk.css') }}">
</head>

<body class="kiosk-body">
@php
    $kioskFeedback = session('kiosk_status');
    $kioskUnavailable = $unavailable ?? false;
    $kioskIsOpen = $open ?? false;
@endphp

<div class="kiosk-shell">

    <!-- SCHOOL HEADER -->
    <header class="kiosk-topbar">
        <div class="kiosk-brand">
            <div class="kiosk-brand-logo">
                @if(file_exists(public_path('images/school-logo.png')))
                    <img src="{{ asset('images/school-logo.png') }}"
                         alt="UBNHS school seal">
                @else
                    <span>UB</span>
                @endif
            </div>

            <div class="kiosk-brand-copy">
                <strong>UBNHS Library</strong>
                <small>Upper Bicutan National High School</small>
            </div>
        </div>

        <span class="kiosk-topbar-badge">
            <i class="bi bi-display" aria-hidden="true"></i>
            <span>Public Attendance Kiosk</span>
        </span>
    </header>

    <!-- MAIN UNIFIED CARD -->
    <main class="kiosk-main">
        <div class="kiosk-board">

            <!-- LEFT: WELCOME SECTION -->
            <section class="kiosk-welcome"
                     aria-labelledby="kiosk-welcome-title">

                @if(file_exists(public_path('images/school-sideview.jpg')))
                    <div class="kiosk-school-photo"
                         style="background-image: url('{{ asset('images/school-sideview.jpg') }}')"
                         aria-hidden="true"></div>
                @endif

                <div class="kiosk-welcome-content">

                    <div class="kiosk-welcome-intro">
                        <span class="kiosk-eyebrow">
                            UBNHS LIBRARY SERVICES
                        </span>

                        <div class="kiosk-welcome-icon">
                            <i class="bi bi-book-half"
                               aria-hidden="true"></i>
                        </div>

                        <h1 id="kiosk-welcome-title">
                            Welcome to<br>
                            the Library
                        </h1>

                        <p>
                            Your academic space for reading,
                            research, and learning. Record your
                            attendance to register your library visit.
                        </p>

                        <div class="kiosk-availability
                            {{ $kioskUnavailable || !$kioskIsOpen ? 'is-unavailable' : '' }}">

                            <span class="kiosk-availability-dot"></span>

                            @if($kioskUnavailable)
                                Attendance service unavailable
                            @elseif($kioskIsOpen)
                                Accepting check-ins
                            @else
                                Kiosk closed
                            @endif

                        </div>
                    </div>
                    @if($kioskIsOpen && !$kioskUnavailable && !$kioskFeedback)

                    <div class="kiosk-guidance">
                        <div class="kiosk-guidance-heading">
                            <i class="bi bi-info-circle"
                               aria-hidden="true"></i>
                            <strong>Before you begin</strong>
                        </div>

                        <div class="kiosk-guidance-row">
                            <span class="kiosk-step">01</span>
                            <div>
                                <strong>Registered members</strong>
                                <small>
                                    Enter your QR code value or reference ID.
                                </small>
                            </div>
                        </div>

                        <div class="kiosk-guidance-row">
                            <span class="kiosk-step">02</span>
                            <div>
                                <strong>Walk-in visitors</strong>
                                <small>
                                    Provide your required visitor details.
                                </small>
                            </div>
                        </div>

                        <div class="kiosk-guidance-row">
                            <span class="kiosk-step">03</span>
                            <div>
                                <strong>Before leaving</strong>
                                <small>
                                    Remember to record your Time Out.
                                </small>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </section>

            <!-- RIGHT: ATTENDANCE WORKSPACE -->
            <section class="kiosk-workspace"
                     aria-labelledby="kiosk-workspace-title">

                <div class="kiosk-workspace-heading">
                    <span class="kiosk-eyebrow">
                        LIBRARY ATTENDANCE
                    </span>

                    <h2 id="kiosk-workspace-title">
                        Record your visit
                    </h2>

                    <p>
                        Choose the appropriate attendance method.
                    </p>
                </div>

                @if($kioskUnavailable)

                    <section class="kiosk-state-card">
                        <div class="kiosk-state-icon kiosk-danger">
                            <i class="bi bi-wifi-off"
                               aria-hidden="true"></i>
                        </div>

                        <h3>
                            Public attendance is temporarily unavailable.
                        </h3>

                        <p>
                            Please try again when the staff application
                            is back online.
                        </p>

                        <a class="btn btn-primary kiosk-state-button"
                           href="{{ route('kiosk.index') }}">
                            <i class="bi bi-arrow-clockwise"
                               aria-hidden="true"></i>
                            Try again
                        </a>
                    </section>

                @elseif($kioskFeedback)

                    <section class="kiosk-state-card">
                        <div class="kiosk-state-icon kiosk-success">
                            <i class="bi bi-check-circle"
                               aria-hidden="true"></i>
                        </div>

                        <h3>Attendance recorded</h3>

                        <p role="status">
                            {{ $kioskFeedback }}
                        </p>

                        <a class="btn btn-primary kiosk-state-button"
                           href="{{ route('kiosk.index') }}">
                            <i class="bi bi-arrow-repeat"
                               aria-hidden="true"></i>
                            Resume
                        </a>
                    </section>

                @elseif($kioskIsOpen)

                    @if($errors->kiosk->any())
                        <div class="kiosk-alert" role="alert">
                            <i class="bi bi-exclamation-triangle"
                               aria-hidden="true"></i>
                            <div>
                                <strong>
                                    Unable to record attendance
                                </strong>

                                <ul>
                                    @foreach($errors->kiosk->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    @endif

                    <!-- EXISTING BACKEND FORMS -->
                    <div class="kiosk-services">
                        @include('attendance.forms', [
                            'actionUrl' => route('kiosk.record')
                        ])
                    </div>

                    <div class="kiosk-assistance">
                        <i class="bi bi-shield-check"
                           aria-hidden="true"></i>

                        <span>
                            New library member? Ask the Head Librarian
                            to register your account. Walk-in visitors
                            can use visitor attendance.
                        </span>
                    </div>

                @else

                    <section class="kiosk-state-card">
                        <div class="kiosk-state-icon kiosk-warning">
                            <i class="bi bi-clock-history"
                               aria-hidden="true"></i>
                        </div>

                        <h3>Kiosk closed</h3>

                        <p>
                            Public attendance opens when the Head Librarian
                            or someone assigned to the current shift
                            times in.
                        </p>

                        <a class="btn btn-primary kiosk-state-button"
                           href="{{ route('kiosk.index') }}">
                            <i class="bi bi-arrow-clockwise"
                               aria-hidden="true"></i>
                            Resume
                        </a>
                    </section>

                @endif

            </section>
        </div>
    </main>

    <!-- FOOTER -->
    <footer class="kiosk-footer">
        <span>
            &copy; {{ date('Y') }}
            Upper Bicutan National High School
        </span>

        <span>
            <i class="bi bi-shield-check"
               aria-hidden="true"></i>
            Library Attendance System
        </span>
    </footer>

</div>
</body>
</html>
