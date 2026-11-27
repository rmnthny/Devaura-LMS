@extends('layouts.library')

@section('title', 'Dashboard')

@section('content')
    <header class="mb-4">
        <h1 class="h3">Dashboard</h1>
        <p class="text-body-secondary mb-0">{{ $today->format('F j, Y') }} · Manila time</p>
    </header>

    @if($operational)
        <section aria-labelledby="operations-heading" class="mb-4">
            <h2 class="h5 mb-3" id="operations-heading">Library overview</h2>
            <div class="row row-cols-2 row-cols-lg-4 g-3">
                @foreach($operational as $label => $count)
                    <div class="col">
                        <dl class="card card-body h-100 p-3 mb-0">
                            <dt class="text-body-secondary fw-medium">{{ $label }}</dt>
                            <dd class="fs-3 fw-bold mb-0">{{ number_format($count) }}</dd>
                        </dl>
                    </div>
                @endforeach
            </div>
        </section>

        <section aria-labelledby="trend-heading" class="mb-4">
            <h2 class="h5 mb-1" id="trend-heading">Last seven days in Manila</h2>
            <p class="text-body-secondary mb-3">{{ $trend[0]['label'] }} – {{ $trend[array_key_last($trend)]['label'] }}</p>
            <div class="row row-cols-1 row-cols-xl-2 g-3">
                @include('reports.trend', ['heading' => 'Attendance', 'series' => ['visits' => 'Visits']])
                @include('reports.trend', ['heading' => 'Circulation', 'series' => ['checkouts' => 'Checkouts', 'returns' => 'Returns']])
            </div>
        </section>
    @endif

    <section class="card card-body" aria-labelledby="personal-heading">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
            <h2 class="h5 mb-0" id="personal-heading">My borrowing</h2>
            <a href="{{ route('history.index') }}" class="btn btn-sm btn-outline-primary">My transactions</a>
        </div>
        <dl class="row row-cols-2 g-3 mb-0">
            @foreach($personal as $label => $count)
                <div class="col">
                    <dt class="text-body-secondary fw-medium">{{ $label }}</dt>
                    <dd class="fs-4 fw-bold mb-0">{{ number_format($count) }}</dd>
                </div>
            @endforeach
        </dl>
    </section>
@endsection
