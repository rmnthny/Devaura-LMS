@extends('layouts.library')
@section('title', 'Inventory Management')
@section('content')
    <h1 class="h2 mb-3">Inventory Management</h1>

    <div class="row g-3 mb-4">
        @foreach ([
            'Total' => ['label' => 'Total Copies', 'class' => 'text-dark'],
            'Available' => ['label' => 'Available', 'class' => 'text-success'],
            'Borrowed' => ['label' => 'Borrowed', 'class' => 'text-warning'],
            'Lost' => ['label' => 'Lost', 'class' => 'text-danger'],
            'Damaged' => ['label' => 'Damaged', 'class' => 'text-danger'],
        ] as $key => $meta)
            <div class="col-6 col-md">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body py-3">
                        <div class="small text-body-secondary">{{ $meta['label'] }}</div>
                        <div class="h3 fw-bold mb-0 {{ $meta['class'] }}">{{ $counts[$key] ?? 0 }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row g-4 align-items-start">
        @can('inventory.audit')
            <div class="col-lg-5">
                <form method="post" action="{{ route('inventory.store') }}" class="card card-body shadow-sm border-0">
                    @csrf
                    <label for="book_qr_value" class="form-label">Book QR code</label>
                    <input class="form-control mb-3" id="book_qr_value" name="book_qr_value" maxlength="50" value="{{ old('book_qr_value') }}" placeholder="Enter a code (e.g. BK-XXXX-XX)" autocomplete="off" autofocus required>
                    <label for="condition" class="form-label">Condition</label>
                    <select class="form-select mb-3" id="condition" name="condition" required>
                        @foreach ($conditions as $condition)
                            <option @selected(old('condition') === $condition)>{{ $condition }}</option>
                        @endforeach
                    </select>
                    <div><button class="btn btn-primary">Record check</button></div>
                </form>
            </div>
        @endcan

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-semibold">Recent inventory checks</div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 small">
                        <thead>
                            <tr>
                                <th>Book &amp; QR</th>
                                <th>Status</th>
                                <th>Audited by</th>
                                <th>When</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse ($recentChecks as $log)
                            @php($prev = $log->properties->get('previous_status'))
                            @php($cond = $log->properties->get('condition'))
                            @php($badge = match ($cond) {
                                'Available' => 'text-bg-success',
                                'Lost', 'Damaged' => 'text-bg-danger',
                                default => 'text-bg-secondary',
                            })
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $log->subject?->book_title ?? 'Book copy' }}</div>
                                    <code class="text-body-secondary">{{ $log->properties->get('book_qr_value') }}</code>
                                </td>
                                <td class="text-nowrap">
                                    @if ($prev && $prev !== $cond)
                                        <span class="text-body-secondary">{{ $prev }} →</span>
                                    @endif
                                    <span class="badge {{ $badge }}">{{ $cond }}</span>
                                </td>
                                <td>{{ $log->causer?->fullName() ?? 'System' }}</td>
                                <td class="text-body-secondary text-nowrap">{{ $log->created_at?->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-body-secondary">No inventory checks recorded yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

@endsection
