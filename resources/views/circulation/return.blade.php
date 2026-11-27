
@extends('layouts.library')

@section('title', 'Book Returns')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/modules/returns.css') }}">
@endpush

@section('content')
<div class="returns-page">

    <!-- PAGE HEADER -->
    <header class="returns-header">
        <div class="returns-heading">
            <span class="returns-heading-icon">
                <i class="bi bi-arrow-return-left" aria-hidden="true"></i>
            </span>
            <div>
                <span class="returns-eyebrow">LIBRARY CIRCULATION</span>
                <h1>Book Returns</h1>
                <p>Locate active loans, inspect returned copies, and record outcomes.</p>
            </div>
        </div>
    </header>

    @if($lookupError)
        <div class="alert alert-warning returns-alert" role="alert">
            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
            <span>{{ $lookupError }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger returns-alert" role="alert">
            <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
            <div>
                <strong>Please review the return information.</strong>
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="returns-grid">

        <!-- LEFT: RETURN PROCESSING -->
        <div class="returns-main-column">

            <section class="returns-card returns-lookup">
                <div class="returns-card-heading">
                    <span class="returns-card-icon">
                        <i class="bi bi-qr-code-scan" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2>Find Book Copy</h2>
                        <p>Scan the QR code to find its current borrowing record.</p>
                    </div>
                </div>

                <form action="{{ route('circulation.return.form') }}"
                      method="get"
                      class="returns-lookup-form"
                      autocomplete="off">

                    <div class="returns-field">
                        <label for="return-qr" class="form-label">
                            Book copy QR value
                        </label>
                        <input
                            id="return-qr"
                            name="book_qr_value"
                            type="text"
                            class="form-control"
                            placeholder="Scan or enter the book copy QR value"
                            value="{{ $bookQr }}"
                            maxlength="50"
                            autocomplete="off"
                            required
                            @unless($loan || $loanQuery !== '') autofocus @endunless
                        >
                        <p class="returns-hint">
                            Without a QR, search for the open loan by reference,
                            book title, or borrower.
                        </p>
                    </div>

                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        Find borrowing record
                    </button>
                </form>
            </section>

            @if($loan)
                <!-- SELECTED LOAN -->
                <section class="returns-card returns-record">

                    <div class="returns-card-heading">
                        <span class="returns-card-icon">
                            <i class="bi bi-clipboard-check" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h2>Return Details</h2>
                            <p>Review the active loan before recording the return.</p>
                        </div>
                    </div>

                    <form action="{{ route('circulation.return') }}"
                          method="post"
                          class="returns-record-form">
                        @csrf

                        <input type="hidden"
                               name="book_id"
                               value="{{ $loan->book_id }}">

                        <input type="hidden"
                               name="transaction_code"
                               value="{{ $loan->transaction_code }}">

                        <div class="returns-book">
                            <div class="returns-book-icon">
                                <i class="bi bi-book" aria-hidden="true"></i>
                            </div>
                            <div>
                                <small>Selected book copy</small>
                                <h3>
                                    {{ $loan->book->book_title }}
                                </h3>
                                <span>Copy #{{ $loan->book_id }}</span>
                            </div>
                        </div>

                        <dl class="returns-details">
                            <div>
                                <dt>Transaction reference</dt>
                                <dd>{{ $loan->transaction_code }}</dd>
                            </div>
                            <div>
                                <dt>Borrower</dt>
                                <dd>
                                    {{ $loan->borrower?->fullName() ?? 'Archived member' }}
                                </dd>
                            </div>
                            <div>
                                <dt>Due date</dt>
                                <dd>
                                    {{ $loan->due_date->format('M j, Y') }}

                                    @if($overdue)
                                        <span class="returns-overdue">
                                            Overdue
                                        </span>
                                    @endif
                                </dd>
                            </div>
                        </dl>

                        <div class="returns-condition">
                            <label for="return-condition" class="form-label">
                                Return outcome
                            </label>

                            <select id="return-condition"
                                    name="condition"
                                    class="form-select"
                                    required>
                                <option value=""
                                        disabled
                                        @selected(!in_array(old('condition'), ['Available', 'Damaged', 'Lost'], true))>
                                    Select an outcome
                                </option>

                                <option value="Available"
                                        @selected(old('condition') === 'Available')>
                                    Fit for circulation
                                </option>

                                <option value="Damaged"
                                        @selected(old('condition') === 'Damaged')>
                                    Damaged
                                </option>

                                <option value="Lost"
                                        @selected(old('condition') === 'Lost')>
                                    Reported lost
                                </option>
                            </select>

                            <p class="returns-hint">
                                Inspect the returned copy and select its
                                actual condition.
                            </p>
                        </div>

                        <div class="returns-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check2-circle" aria-hidden="true"></i>
                                Record outcome
                            </button>
                        </div>
                    </form>

                </section>
            @else
                <section class="returns-card returns-awaiting"
                         aria-label="Return processing instructions">
                    <div class="returns-awaiting-icon">
                        <i class="bi bi-journal-check" aria-hidden="true"></i>
                    </div>

                    <div>
                        <h2>Ready to process a return</h2>
                        <p>
                            Scan a book copy above or select a matching open loan.
                            Its borrowing details and return condition form
                            will appear here.
                        </p>
                    </div>
                </section>
            @endif

        </div>

        <!-- RIGHT: SEARCH AND GUIDELINES -->
        <aside class="returns-side">

            <section class="returns-card returns-search">
                <div class="returns-card-heading">
                    <span class="returns-card-icon">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2 id="loan-search-heading">Search open loans</h2>
                        <p>Find a borrowing record without scanning a QR.</p>
                    </div>
                </div>

                <form action="{{ route('circulation.return.form') }}"
                      method="get"
                      class="returns-search-form"
                      aria-labelledby="loan-search-heading">

                    <label for="loan-query" class="form-label">
                        Transaction reference, book title, or borrower
                    </label>

                    <input
                        id="loan-query"
                        name="loan_query"
                        type="text"
                        class="form-control"
                        placeholder="Search active borrowing records"
                        value="{{ $loanQuery }}"
                        maxlength="80"
                        required
                    >

                    <button class="btn btn-outline-primary" type="submit">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        Search
                    </button>
                </form>

                @if($loanQuery !== '')
                    <div class="returns-results">
                        <h3>Matching open loans</h3>

                        <ul>
                            @forelse($loans as $match)
                                <li>
                                    <a href="{{ route('circulation.return.form', [
                                        'transaction_code' => $match->transaction_code,
                                        'loan_query' => $loanQuery
                                    ]) }}">
                                        <span class="returns-result-icon">
                                            <i class="bi bi-book" aria-hidden="true"></i>
                                        </span>

                                        <span class="returns-result-copy">
                                            <strong>{{ $match->book->book_title }}</strong>
                                            <small>
                                                {{ $match->transaction_code }}
                                                · Copy #{{ $match->book_id }}
                                            </small>
                                            <small>
                                                {{ $match->borrower?->fullName() ?? 'Archived member' }}
                                            </small>
                                        </span>

                                        <i class="bi bi-chevron-right"
                                           aria-hidden="true"></i>
                                    </a>
                                </li>
                            @empty
                                <li class="returns-empty-results">
                                    No matching open loans.
                                </li>
                            @endforelse
                        </ul>
                    </div>
                @endif
            </section>

            <section class="returns-card returns-guidelines">
                <div class="returns-card-heading">
                    <span class="returns-card-icon">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2>Return Guidelines</h2>
                        <p>Follow the circulation workflow.</p>
                    </div>
                </div>

                <div class="returns-guideline">
                    <span>01</span>
                    <p>Locate the correct open borrowing record.</p>
                </div>

                <div class="returns-guideline">
                    <span>02</span>
                    <p>Verify the book copy and borrower details.</p>
                </div>

                <div class="returns-guideline">
                    <span>03</span>
                    <p>Inspect the book and select its return condition.</p>
                </div>

                <div class="returns-guideline">
                    <span>04</span>
                    <p>Record the outcome to complete the return.</p>
                </div>
            </section>

        </aside>

    </div>
</div>
@endsection
