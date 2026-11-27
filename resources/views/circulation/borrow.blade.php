
@extends('layouts.library')

@section('title', 'Book Borrowing')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/modules/borrowing.css') }}">
@endpush

@section('content')
@php
    $selection = array_filter([
        'member' => $member?->getKey(),
        'copy' => $copy?->getKey(),
        'member_query' => $memberQuery,
        'book_query' => $bookQuery
    ]);

    $previous = fn (string $field) =>
        is_scalar(old($field)) ? old($field) : '';
@endphp

<div class="borrowing-page">

    <header class="borrowing-header">
        <div class="borrowing-heading">
            <span class="borrowing-heading-icon">
                <i class="bi bi-book-half" aria-hidden="true"></i>
            </span>
            <div>
                <span class="borrowing-eyebrow">LIBRARY CIRCULATION</span>
                <h1>Book Borrowing</h1>
                <p>Process book checkouts and verify eligible borrowers.</p>
            </div>
        </div>
    </header>

    @if($memberUnavailable || $copyUnavailable)
        <div class="alert alert-warning borrowing-alert" role="alert">
            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
            <div>
                @if($memberUnavailable)
                    <div>The selected member is no longer eligible. Select another member.</div>
                @endif
                @if($copyUnavailable)
                    <div>The selected copy is no longer available. Select another copy.</div>
                @endif
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger borrowing-alert" role="alert">
            <i class="bi bi-exclamation-circle" aria-hidden="true"></i>
            <div>
                <strong>Unable to complete checkout</strong>
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="borrowing-grid">

        <!-- MAIN CHECKOUT CARD -->
        <section class="borrowing-card borrowing-checkout">
            <div class="borrowing-card-heading">
                <span class="borrowing-section-icon">
                    <i class="bi bi-clipboard-check" aria-hidden="true"></i>
                </span>
                <div>
                    <h2>Checkout Details</h2>
                    <p>Identify the borrower and the exact book copy.</p>
                </div>
            </div>

            <form action="{{ route('circulation.borrow') }}"
                  method="post"
                  class="borrowing-form"
                  autocomplete="off">
                @csrf

                <input type="hidden"
                       name="request_id"
                       value="{{ $previous('request_id') ?: $checkoutId }}">

                <div class="borrowing-field-block">
                    <div class="borrowing-field-heading">
                        <span class="borrowing-step">01</span>
                        <strong>Select / Search Member</strong>
                    </div>

                    @if($member)
                        <div class="borrowing-selection">
                            <span class="borrowing-selection-icon">
                                <i class="bi bi-person-check" aria-hidden="true"></i>
                            </span>
                            <div class="borrowing-selection-content">
                                <small>Selected member</small>
                                <strong>{{ $member->member->fullName() }}</strong>
                            </div>
                            <a href="{{ route('circulation.borrow.form', Arr::except($selection, 'member')) }}">
                                Clear member
                            </a>
                        </div>

                        <input type="hidden"
                               name="borrower_account_id"
                               value="{{ $member->getKey() }}">
                    @else
                        <label for="borrower-identifier" class="form-label">
                            Member ID (LRN or employee number) or QR value
                        </label>
                        <input id="borrower-identifier"
                               name="borrower_identifier"
                               type="text"
                               class="form-control"
                               placeholder="Scan or enter member reference number"
                               value="{{ $memberUnavailable ? '' : $previous('borrower_identifier') }}"
                               maxlength="50"
                               autocomplete="off"
                               required
                               @if($memberQuery === '' && $bookQuery === '') autofocus @endif>
                    @endif
                </div>

                <div class="borrowing-field-block">
                    <div class="borrowing-field-heading">
                        <span class="borrowing-step">02</span>
                        <strong>Select / Search Book</strong>
                    </div>

                    @if($copy)
                        <div class="borrowing-selection">
                            <span class="borrowing-selection-icon">
                                <i class="bi bi-bookmark-check" aria-hidden="true"></i>
                            </span>
                            <div class="borrowing-selection-content">
                                <small>Selected copy</small>
                                <strong>{{ $copy->book_title }} · Copy #{{ $copy->getKey() }}</strong>
                            </div>
                            <a href="{{ route('circulation.borrow.form', Arr::except($selection, 'copy')) }}">
                                Clear copy
                            </a>
                        </div>
                        <input type="hidden"
                               name="book_id"
                               value="{{ $copy->getKey() }}">
                    @else
                        <label for="borrow-qr" class="form-label">
                            Book copy QR value
                        </label>
                        <input id="borrow-qr"
                               name="book_qr_value"
                               type="text"
                               class="form-control"
                               placeholder="Scan book copy QR code"
                               value="{{ $copyUnavailable ? '' : $previous('book_qr_value') }}"
                               maxlength="50"
                               autocomplete="off"
                               required
                               @if($member && $memberQuery === '' && $bookQuery === '') autofocus @endif>
                        <p class="borrowing-field-hint">
                            Without a QR, search by title or ISBN and select the matching book.
                        </p>
                    @endif
                </div>

                <div class="borrowing-due">
                    <i class="bi bi-calendar-check" aria-hidden="true"></i>
                    <div>
                        <strong>Borrowing period</strong>
                        <p>
                            Books are due by the end of the day,
                            {{ $loanDays }} calendar days after checkout
                            (Manila time).
                        </p>
                    </div>
                </div>

                <div class="borrowing-submit">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check2-circle" aria-hidden="true"></i>
                        Check out book
                    </button>
                </div>
            </form>
        </section>

        <!-- SEARCH AND GUIDELINES -->
        <div class="borrowing-side">

            <section class="borrowing-card borrowing-search">
                <div class="borrowing-card-heading">
                    <span class="borrowing-section-icon">
                        <i class="bi bi-search" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2>Search & Select</h2>
                        <p>Find eligible members and available copies.</p>
                    </div>
                </div>

                <form action="{{ route('circulation.borrow.form') }}"
                      method="get"
                      class="borrowing-search-form">

                    @if($member)
                        <input type="hidden"
                               name="member"
                               value="{{ $member->getKey() }}">
                    @endif

                    @if($copy)
                        <input type="hidden"
                               name="copy"
                               value="{{ $copy->getKey() }}">
                    @endif

                    <div>
                        <label for="member-query" class="form-label">
                            Member name or reference number
                        </label>
                        <input id="member-query"
                               name="member_query"
                               class="form-control"
                               placeholder="Search member"
                               value="{{ $memberQuery }}"
                               minlength="2"
                               maxlength="80">
                    </div>

                    <div>
                        <label for="copy-query" class="form-label">
                            Available book title or ISBN
                        </label>
                        <input id="copy-query"
                               name="book_query"
                               class="form-control"
                               placeholder="Search available books"
                               value="{{ $bookQuery }}"
                               maxlength="80">
                    </div>

                    <button class="btn btn-outline-primary"
                            type="submit">
                        <i class="bi bi-search" aria-hidden="true"></i>
                        Search
                    </button>
                </form>

                @if($memberQuery !== '')
                    <div class="borrowing-results">
                        <h3>Matching members</h3>
                        <ul>
                            @forelse($members as $match)
                                <li>
                                    <a href="{{ route('circulation.borrow.form', [...$selection, 'member' => $match->getKey()]) }}">
                                        <i class="bi bi-person" aria-hidden="true"></i>
                                        <span>
                                            {{ $match->member->fullName() }}
                                            <small>ID ending {{ substr($match->reference_number, -4) }}</small>
                                        </span>
                                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                    </a>
                                </li>
                            @empty
                                <li class="borrowing-no-results">No eligible members found.</li>
                            @endforelse
                        </ul>
                    </div>
                @endif

                @if($bookQuery !== '')
                    <div class="borrowing-results">
                        <h3>Available copies</h3>
                        <ul>
                            @forelse($books as $match)
                                <li>
                                    <a href="{{ route('circulation.borrow.form', [...$selection, 'copy' => $match->getKey()]) }}">
                                        <i class="bi bi-book" aria-hidden="true"></i>
                                        <span>
                                            {{ $match->book_title }}
                                            <small>Copy #{{ $match->getKey() }}</small>
                                        </span>
                                        <i class="bi bi-chevron-right" aria-hidden="true"></i>
                                    </a>
                                </li>
                            @empty
                                <li class="borrowing-no-results">No available copies found.</li>
                            @endforelse
                        </ul>
                    </div>
                @endif
            </section>

            <section class="borrowing-card borrowing-guidelines">
                <div class="borrowing-card-heading">
                    <span class="borrowing-section-icon">
                        <i class="bi bi-info-circle" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2>Circulation Guidelines</h2>
                        <p>Before completing a checkout</p>
                    </div>
                </div>

                <div class="borrowing-guideline">
                    <span>01</span>
                    <p>Confirm the borrower's registered library account.</p>
                </div>
                <div class="borrowing-guideline">
                    <span>02</span>
                    <p>Choose the correct available book copy.</p>
                </div>
                <div class="borrowing-guideline">
                    <span>03</span>
                    <p>Check the due date before confirming the transaction.</p>
                </div>
                <div class="borrowing-guideline">
                    <span>04</span>
                    <p>Complete the checkout and remind the borrower to return the book on time.</p>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
