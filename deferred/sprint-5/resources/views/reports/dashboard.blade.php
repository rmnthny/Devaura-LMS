@extends('layouts.library')

@section('title', 'Dashboard')

@section('content')
    <h1 class="h3 mb-3">Dashboard</h1>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-3">
        @foreach($summary as $label => $count)
            <div class="col"><div class="card card-body h-100">
                <div>{{ $label }}</div><strong class="fs-3">{{ $count }}</strong>
            </div></div>
        @endforeach
    </div>
    @if($operational)
        <section class="card card-body mt-4">
            <h2 class="h5">Last seven days in Manila</h2>
            @php($scale = max(1, max(array_column($trend, 'visits')), max(array_column($trend, 'checkouts'))))
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th scope="col">Date</th><th scope="col">Visits</th><th scope="col">Checkouts</th></tr></thead>
                    <tbody>
                        @foreach($trend as $day)
                            <tr>
                                <th scope="row">{{ $day['label'] }}</th>
                                <td><meter aria-hidden="true" min="0" max="{{ $scale }}" value="{{ $day['visits'] }}"></meter> {{ $day['visits'] }}</td>
                                <td><meter aria-hidden="true" min="0" max="{{ $scale }}" value="{{ $day['checkouts'] }}"></meter> {{ $day['checkouts'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
    <div class="d-flex flex-wrap gap-3 mt-4">
        <a href="{{ route('history.index') }}">My transactions</a>
        @if($operational)
            <a href="{{ route('reports.index') }}">Operational reports</a>
        @endif
    </div>
@endsection
