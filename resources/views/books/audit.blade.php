
@extends('layouts.library')

@section('title', 'Inventory Management')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/modules/inventory.css') }}">
@endpush

@section('content')

<section class="inventory-page">

    <!-- PAGE HEADER -->

    <header class="inventory-header">
        <div class="inventory-heading">
            <span class="inventory-heading-icon" aria-hidden="true">
                <i class="bi bi-box-seam"></i>
            </span>

            <div>
                <h1>Inventory Management</h1>
                <p>Monitor book conditions and record physical inventory checks.</p>
            </div>
        </div>
    </header>

    <!-- ACTUAL INVENTORY COUNTS -->

    <section class="inventory-overview" aria-label="Inventory summary">

        @foreach([
            'Total' => [
                'label' => 'Total Copies',
                'icon' => 'bi-collection',
                'tone' => 'total',
            ],
            'Available' => [
                'label' => 'Available',
                'icon' => 'bi-check-circle',
                'tone' => 'available',
            ],
            'Borrowed' => [
                'label' => 'Borrowed',
                'icon' => 'bi-arrow-left-right',
                'tone' => 'borrowed',
            ],
            'Lost' => [
                'label' => 'Lost',
                'icon' => 'bi-exclamation-circle',
                'tone' => 'lost',
            ],
            'Damaged' => [
                'label' => 'Damaged',
                'icon' => 'bi-tools',
                'tone' => 'damaged',
            ],
        ] as $key => $meta)

            <article class="inventory-stat inventory-stat-{{ $meta['tone'] }}">
                <div class="inventory-stat-top">
                    <span class="inventory-stat-label">
                        {{ $meta['label'] }}
                    </span>

                    <span class="inventory-stat-icon" aria-hidden="true">
                        <i class="bi {{ $meta['icon'] }}"></i>
                    </span>
                </div>

                <strong class="inventory-stat-value">
                    {{ number_format($counts[$key] ?? 0) }}
                </strong>
            </article>

        @endforeach

    </section>

    <!-- INVENTORY WORKSPACE -->

    <div class="inventory-workspace">

        <!-- RECORD CHECK -->

        @can('inventory.audit')

            <section class="inventory-check-card"
                     aria-labelledby="inventory-check-title">

                <div class="inventory-card-heading">
                    <h2 id="inventory-check-title">
                        Record inventory check
                    </h2>

                    <p>
                        Scan or enter a book copy's QR code and select its condition.
                    </p>
                </div>

                <form method="POST"
                      action="{{ route('inventory.store') }}"
                      class="inventory-check-form">

                    @csrf

                    <div class="inventory-field">
                        <label for="book_qr_value" class="form-label">
                            Book QR code
                        </label>

                        <div class="inventory-qr-input">
                            <i class="bi bi-qr-code"
                               aria-hidden="true"></i>

                            <input
                                type="text"
                                class="form-control"
                                id="book_qr_value"
                                name="book_qr_value"
                                maxlength="50"
                                value="{{ old('book_qr_value') }}"
                                placeholder="e.g. BK-XXXX-XX"
                                autocomplete="off"
                                autofocus
                                required
                            >
                        </div>

                        @error('book_qr_value')
                            <p class="inventory-field-error" role="alert">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="inventory-field">
                        <label for="condition" class="form-label">
                            Condition
                        </label>

                        <select
                            class="form-select"
                            id="condition"
                            name="condition"
                            required
                        >
                            @foreach($conditions as $condition)
                                <option
                                    value="{{ $condition }}"
                                    @selected(old('condition') === $condition)
                                >
                                    {{ $condition }}
                                </option>
                            @endforeach
                        </select>

                        @error('condition')
                            <p class="inventory-field-error" role="alert">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="btn btn-primary inventory-check-submit">
                        <i class="bi bi-check2-circle"
                           aria-hidden="true"></i>
                        Record check
                    </button>

                </form>

                <div class="inventory-check-note">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    <p>
                        Select the observed condition carefully.
                        Recording a check uses the existing inventory audit process.
                    </p>
                </div>

            </section>

        @endcan

        <!-- RECENT INVENTORY CHECKS -->

        <section class="inventory-history-card"
                 aria-labelledby="inventory-history-title">

            <header class="inventory-history-header">
                <div class="inventory-card-heading">
                    <h2 id="inventory-history-title">
                        Recent inventory checks
                    </h2>

                    <p>
                        Latest recorded book condition checks.
                    </p>
                </div>
            </header>

            <div class="table-responsive inventory-table-wrap">

                <table class="table align-middle inventory-table">
                    <thead>
                        <tr>
                            <th scope="col">Book &amp; QR</th>
                            <th scope="col">Condition</th>
                            <th scope="col">Audited by</th>
                            <th scope="col">When</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($recentChecks as $log)

                            @php
                                $prev = $log->properties->get('previous_status');
                                $cond = $log->properties->get('condition');

                                $badge = match ($cond) {
                                    'Available' => 'text-bg-success',
                                    'Lost', 'Damaged' => 'text-bg-danger',
                                    default => 'text-bg-secondary',
                                };

                                $conditionIcon = match ($cond) {
                                    'Available' => 'bi-check-circle',
                                    'Lost' => 'bi-exclamation-circle',
                                    'Damaged' => 'bi-tools',
                                    default => 'bi-circle',
                                };
                            @endphp

                            <tr>
                                <td class="inventory-book-cell">
                                    <strong class="inventory-book-title">
                                        {{ $log->subject?->book_title ?? 'Book copy' }}
                                    </strong>

                                    <code class="inventory-book-code">
                                        {{ $log->properties->get('book_qr_value') }}
                                    </code>
                                </td>

                                <td>
                                    <div class="inventory-condition-change">
                                        @if($prev && $prev !== $cond)
                                            <span class="inventory-previous-status">
                                                {{ $prev }}
                                                <i class="bi bi-arrow-right"
                                                   aria-hidden="true"></i>
                                            </span>
                                        @endif

                                        <span class="badge inventory-condition-badge {{ $badge }}">
                                            <i class="bi {{ $conditionIcon }}"
                                               aria-hidden="true"></i>
                                            {{ $cond }}
                                        </span>
                                    </div>
                                </td>

                                <td class="inventory-auditor">
                                    {{ $log->causer?->fullName() ?? 'System' }}
                                </td>

                                <td class="inventory-date">
                                    {{ $log->created_at?->diffForHumans() }}
                                </td>
                            </tr>

                        @empty

                            <tr>
                                <td colspan="4">
                                    <div class="inventory-empty">
                                        <span class="inventory-empty-icon"
                                              aria-hidden="true">
                                            <i class="bi bi-clipboard-check"></i>
                                        </span>

                                        <strong>No inventory checks yet</strong>

                                        <p>
                                            Recorded inventory checks will appear here.
                                        </p>
                                    </div>
                                </td>
                            </tr>

                        @endforelse
                    </tbody>
                </table>

            </div>

        </section>

    </div>

</section>

@endsection
