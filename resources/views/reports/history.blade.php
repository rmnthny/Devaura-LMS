
@extends('layouts.library')

@section('title', 'My Transactions')

@push('styles')
    <link rel="stylesheet"
          href="{{ asset('css/modules/history.css') }}">
@endpush

@section('content')

<div class="history-page">

    <!-- ==========================================
         PAGE HEADER
         ========================================== -->

    <header class="history-header">

        <div class="history-heading">

            <span class="history-heading-icon"
                  aria-hidden="true">
                <i class="bi bi-clock-history"></i>
            </span>

            <div class="history-heading-content">

                <span class="history-eyebrow">
                    PERSONAL LIBRARY RECORDS
                </span>

                <h1>My Transactions</h1>

                <p>
                    Track your borrowed books, due dates,
                    and return history in one place.
                </p>

            </div>

        </div>

        <a href="{{ route('dashboard.index') }}"
           class="history-back-link">

            <i class="bi bi-arrow-left"
               aria-hidden="true"></i>

            <span>Back to Dashboard</span>

        </a>

    </header>


    <!-- ==========================================
         TRANSACTION OVERVIEW
         ========================================== -->

    <section class="history-overview"
             aria-label="Transaction overview">

        <div class="history-overview-content">

            <span class="history-overview-icon"
                  aria-hidden="true">

                <i class="bi bi-journal-bookmark"></i>

            </span>

            <div class="history-overview-text">

                <span class="history-overview-eyebrow">
                    YOUR LIBRARY ACTIVITY
                </span>

                <h2>
                    Your borrowing records,
                    all in one place.
                </h2>

                <p>
                    Review your transactions and stay informed
                    about the status of your borrowed books.
                </p>

            </div>

        </div>

        <div class="history-overview-stat">

            <span>Total Transactions</span>

            <strong>
                {{ number_format($loans->total()) }}
            </strong>

            <small>
                Recorded borrowing transactions
            </small>

        </div>

    </section>


    <!-- ==========================================
         TRANSACTION HISTORY PANEL
         ========================================== -->

    <section class="history-card"
             aria-labelledby="history-list-heading">

        <!-- PANEL HEADER -->

        <header class="history-card-header">

            <div class="history-card-title">

                <span class="history-card-icon"
                      aria-hidden="true">

                    <i class="bi bi-journal-text"></i>

                </span>

                <div>

                    <span class="history-section-eyebrow">
                        TRANSACTION RECORDS
                    </span>

                    <h2 id="history-list-heading">
                        Borrowing History
                    </h2>

                    <p>
                        {{ number_format($loans->total()) }}
                        {{ $loans->total() === 1
                            ? 'transaction'
                            : 'transactions' }}
                        recorded
                    </p>

                </div>

            </div>

            @if($loans->total() > 0)

                <span class="history-count">

                    <i class="bi bi-collection"
                       aria-hidden="true"></i>

                    {{ number_format($loans->total()) }}
                    Total

                </span>

            @endif

        </header>


        <!-- ==========================================
             TRANSACTION TABLE
             ========================================== -->

        @if($loans->count() > 0)

            <div class="history-table-wrapper"
                 role="region"
                 aria-label="My borrowing transactions"
                 tabindex="0">

                <table class="history-table">

                    <thead>
                        <tr>
                            <th scope="col">
                                Reference
                            </th>

                            <th scope="col">
                                Book Details
                            </th>

                            <th scope="col">
                                Checked Out
                            </th>

                            <th scope="col">
                                Due Date
                            </th>

                            <th scope="col">
                                Returned
                            </th>

                            <th scope="col">
                                Status
                            </th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($loans as $loan)

                            @php
                                /*
                                |--------------------------------------------------------------------------
                                | Existing status behavior
                                |--------------------------------------------------------------------------
                                | Keep the backend status.
                                | Only calculate display overdue for
                                | an open borrowed transaction.
                                */

                                $displayStatus =
                                    !$loan->return_date
                                    && $loan->transaction_status === $loan::BORROWED
                                    && $loan->isOverdueOn($today)
                                        ? $loan::OVERDUE
                                        : $loan->transaction_status;

                                $statusKey = strtolower(
                                    (string) $displayStatus
                                );

                                $statusClass = match ($statusKey) {
                                    'returned' => 'is-returned',
                                    'borrowed' => 'is-borrowed',
                                    'overdue' => 'is-overdue',
                                    'lost' => 'is-lost',
                                    default => 'is-neutral',
                                };

                                $statusIcon = match ($statusKey) {
                                    'returned' => 'bi-check-circle',
                                    'borrowed' => 'bi-bookmark',
                                    'overdue' => 'bi-exclamation-circle',
                                    'lost' => 'bi-exclamation-triangle',
                                    default => 'bi-info-circle',
                                };
                            @endphp

                            <tr class="history-row">

                                <!-- REFERENCE -->

                                <td data-label="Reference">

                                    <span class="history-reference">

                                        <i class="bi bi-hash"
                                           aria-hidden="true"></i>

                                        {{ $loan->transaction_code }}

                                    </span>

                                </td>


                                <!-- BOOK DETAILS -->

                                <td data-label="Book Details">

                                    <div class="history-book">

                                        <span class="history-book-icon"
                                              aria-hidden="true">

                                            <i class="bi bi-book"></i>

                                        </span>

                                        <div class="history-book-info">

                                            <strong>
                                                {{ $loan->book->book_title }}
                                            </strong>

                                            <small>
                                                Library collection
                                            </small>

                                        </div>

                                    </div>

                                </td>


                                <!-- CHECKOUT DATE -->

                                <td data-label="Checked Out">

                                    <time
                                        datetime="{{ $loan->borrow_date->toDateString() }}">

                                        {{ $loan->borrow_date->format('M j, Y') }}

                                    </time>

                                </td>


                                <!-- DUE DATE -->

                                <td data-label="Due Date">

                                    <time
                                        datetime="{{ $loan->due_date->toDateString() }}"
                                        class="{{ $statusKey === 'overdue'
                                            ? 'history-due-overdue'
                                            : '' }}">

                                        {{ $loan->due_date->format('M j, Y') }}

                                    </time>

                                </td>


                                <!-- RETURN DATE -->

                                <td data-label="Returned">

                                    @if($loan->return_date)

                                        <time
                                            datetime="{{ $loan->return_date->toDateString() }}">

                                            {{ $loan->return_date->format('M j, Y') }}

                                        </time>

                                    @else

                                        <span class="history-no-return">
                                            Not yet returned
                                        </span>

                                    @endif

                                </td>


                                <!-- STATUS -->

                                <td data-label="Status">

                                    <span class="history-status {{ $statusClass }}">

                                        <i class="bi {{ $statusIcon }}"
                                           aria-hidden="true"></i>

                                        {{ $displayStatus }}

                                    </span>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        @else

            <!-- ==========================================
                 EMPTY STATE
                 ========================================== -->

            <div class="history-empty">

                <span class="history-empty-icon"
                      aria-hidden="true">

                    <i class="bi bi-journal-bookmark"></i>

                </span>

                <h3>
                    No transactions recorded.
                </h3>

                <p>
                    Your borrowing history will appear
                    here once you check out a library book.
                </p>

                <span class="history-empty-caption">
                    Your records will be available here
                    when transactions are recorded.
                </span>

            </div>

        @endif


        <!-- ==========================================
             PAGINATION
             ========================================== -->

        @if($loans->hasPages())

            <footer class="history-pagination">

                <span class="history-pagination-info">

                    Showing
                    {{ $loans->firstItem() }}
                    –
                    {{ $loans->lastItem() }}
                    of
                    {{ number_format($loans->total()) }}

                </span>

                {{ $loans->links('pagination::bootstrap-5') }}

            </footer>

        @endif

    </section>


    <!-- ==========================================
         INFORMATION NOTE
         ========================================== -->

    <div class="history-information">

        <i class="bi bi-info-circle"
           aria-hidden="true"></i>

        <p>
            Transaction information is based on your
            recorded library borrowing activity.
            For corrections or concerns, please contact
            your school librarian.
        </p>

    </div>

</div>

@endsection
