
@extends('layouts.library')

@section('title', 'Dashboard')

@push('styles')
    <link rel="stylesheet"
          href="{{ asset('css/modules/dashboard.css') }}">
@endpush

@section('content')
@php
    /*
    |--------------------------------------------------------------------------
    | Dashboard Presentation
    |--------------------------------------------------------------------------
    | Icons and colors only.
    | All statistics and dates come from the existing backend.
    */

    $kpiIcons = [
        'Available copies' => 'bi-book',
        'Open loans' => 'bi-journal-arrow-up',
        'Overdue loans' => 'bi-clock-history',
        'Visits today' => 'bi-people',
    ];

    $personalIcons = [
        'My open loans' => 'bi-bookmark-check',
        'My overdue loans' => 'bi-exclamation-circle',
    ];
@endphp

<div class="dashboard-page">

    <!-- ================================
         DASHBOARD PAGE HEADER
         ================================ -->

    <header class="dashboard-header">

        <div class="dashboard-heading">

            <span class="dashboard-heading-icon"
                  aria-hidden="true">
                <i class="bi bi-grid-1x2"></i>
            </span>

            <div class="dashboard-heading-copy">

                <span class="dashboard-eyebrow">
                    {{ $operational
                        ? 'LIBRARY ANALYTICS'
                        : 'PERSONAL LIBRARY SPACE' }}
                </span>

                <h1>
                    {{ $operational
                        ? 'Dashboard Overview'
                        : 'My Dashboard' }}
                </h1>

                <p>
                    {{ $operational
                        ? 'Monitor library performance and daily activity.'
                        : 'Stay updated on your library borrowing activity.' }}
                </p>

            </div>

        </div>

        <div class="dashboard-date-label">

            <i class="bi bi-calendar3"
               aria-hidden="true"></i>

            <div>
                <span class="dashboard-date-caption">
                    TODAY
                </span>

                <strong>
                    {{ $today->format('D, M j, Y') }}
                </strong>

                <small>Manila time</small>
            </div>

        </div>

    </header>


    <!-- ================================
         OPERATIONAL DASHBOARD
         Head Librarian / Admin
         ================================ -->

    @if($operational)

        <!-- KEY PERFORMANCE INDICATORS -->

        <section class="dashboard-section"
                 aria-labelledby="dashboard-kpi-heading">

            <div class="dashboard-section-heading">

                <div>
                    <span class="dashboard-section-eyebrow">
                        AT A GLANCE
                    </span>

                    <h2 id="dashboard-kpi-heading">
                        Library Overview
                    </h2>

                    <p>
                        Current status of library operations.
                    </p>
                </div>

                <span class="dashboard-section-note">
                    <i class="bi bi-arrow-repeat"
                       aria-hidden="true"></i>
                    Current records
                </span>

            </div>

            <div class="dashboard-kpi-grid">

                @foreach($operational as $label => $count)

                    @php
                        $cardType = match ($label) {
                            'Overdue loans' => 'is-warning',
                            'Visits today' => 'is-success',
                            default => 'is-blue',
                        };

                        $icon = $kpiIcons[$label]
                            ?? 'bi-bar-chart';
                    @endphp

                    <article class="dashboard-kpi {{ $cardType }}">

                        <div class="dashboard-kpi-top">

                            <span class="dashboard-kpi-icon"
                                  aria-hidden="true">

                                <i class="bi {{ $icon }}"></i>

                            </span>

                            <span class="dashboard-kpi-tag">
                                {{ $label === 'Visits today'
                                    ? 'Today'
                                    : 'Current' }}
                            </span>

                        </div>

                        <dl class="dashboard-kpi-data">

                            <dt>{{ $label }}</dt>

                            <dd>
                                {{ number_format($count) }}
                            </dd>

                        </dl>

                        <div class="dashboard-kpi-bottom"
                             aria-hidden="true">
                            <span class="dashboard-kpi-line"></span>
                        </div>

                    </article>

                @endforeach

            </div>

        </section>


        <!-- ================================
             SEVEN-DAY LIBRARY ACTIVITY
             ================================ -->

        <section class="dashboard-section"
                 aria-labelledby="dashboard-trend-heading">

            <div class="dashboard-section-heading">

                <div>

                    <span class="dashboard-section-eyebrow">
                        ACTIVITY INSIGHTS
                    </span>

                    <h2 id="dashboard-trend-heading">
                        Library Activity Trends
                    </h2>

                    <p>
                        Attendance and circulation activity
                        over the past 7 days.
                    </p>

                    <span class="visually-hidden">
                        Last seven days in Manila
                    </span>

                </div>

                @if(!empty($trend))
                    <span class="dashboard-period">

                        <i class="bi bi-calendar-week"
                           aria-hidden="true"></i>

                        {{ $trend[0]['label'] }}
                        –
                        {{ $trend[array_key_last($trend)]['label'] }}

                    </span>
                @endif

            </div>

            <div class="dashboard-chart-grid">

                <!-- ATTENDANCE -->

                @include('reports.trend', [
                    'heading' => 'Attendance',
                    'series' => [
                        'visits' => 'Visits',
                    ],
                ])

                <!-- CIRCULATION -->

                @include('reports.trend', [
                    'heading' => 'Circulation',
                    'series' => [
                        'checkouts' => 'Checkouts',
                        'returns' => 'Returns',
                    ],
                ])

            </div>

        </section>

    @endif


    <!-- ================================
         PERSONAL BORROWING SECTION
         Visible to eligible accounts
         ================================ -->

    <section class="dashboard-personal"
             aria-labelledby="dashboard-personal-heading">

        <header class="dashboard-personal-header">

            <div class="dashboard-section-heading">

                <span class="dashboard-personal-icon"
                      aria-hidden="true">

                    <i class="bi bi-bookmark-check"></i>

                </span>

                <div>

                    <span class="dashboard-section-eyebrow">
                        PERSONAL OVERVIEW
                    </span>

                    <h2 id="dashboard-personal-heading">
                        My Borrowing
                    </h2>

                    <p>
                        Your current borrowing status.
                    </p>

                </div>

            </div>

            <a href="{{ route('history.index') }}"
               class="btn btn-outline-primary dashboard-history-link">

                <i class="bi bi-clock-history"
                   aria-hidden="true"></i>

                <span>My Transactions</span>

                <i class="bi bi-arrow-right"
                   aria-hidden="true"></i>

            </a>

        </header>

        <dl class="dashboard-personal-grid">

            @foreach($personal as $label => $count)

                @php
                    $isOverdue = $label === 'My overdue loans';

                    $icon = $personalIcons[$label]
                        ?? 'bi-book';
                @endphp

                <div class="dashboard-personal-metric
                            {{ $isOverdue ? 'is-overdue' : '' }}">

                    <span class="dashboard-personal-metric-icon"
                          aria-hidden="true">

                        <i class="bi {{ $icon }}"></i>

                    </span>

                    <div class="dashboard-personal-metric-content">

                        <dt>
                            {{ $label }}
                        </dt>

                        <dd>
                            {{ number_format($count) }}
                        </dd>

                        <span class="dashboard-metric-caption">
                            {{ $isOverdue
                                ? 'Loans requiring attention'
                                : 'Currently borrowed books' }}
                        </span>

                    </div>

                </div>

            @endforeach

        </dl>

    </section>

</div>
@endsection
