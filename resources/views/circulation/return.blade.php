@extends('layouts.library')

@section('title', 'Book Returns')

@section('content')
    <h1 class="h3 mb-3">Book Returns</h1>
    @if($lookupError)
        <p class="alert alert-warning" role="alert">{{ $lookupError }}</p>
    @endif
    <div class="row g-3 align-items-start">
        <div class="col-lg-7">
            <form action="{{ route('circulation.return.form') }}" method="get" class="card card-body shadow-sm mb-3">
                <label for="return-qr" class="form-label">Book copy QR value</label>
                <input id="return-qr" name="book_qr_value" class="form-control"
                    value="{{ $bookQr }}" maxlength="50" autocomplete="off" required @unless($loan || $loanQuery !== '') autofocus @endunless>
                <div class="form-text mb-3">Without a QR, search for the open loan by reference, book title, or borrower.</div>
                <div><button class="btn btn-outline-primary">Find borrowing record</button></div>
            </form>

            @if($loan)
                <form action="{{ route('circulation.return') }}" method="post" class="card card-body shadow-sm">
                    @csrf
                    <input type="hidden" name="book_id" value="{{ $loan->book_id }}">
                    <input type="hidden" name="transaction_code" value="{{ $loan->transaction_code }}">
                    <h2 class="h5">{{ $loan->book->book_title }} · Copy #{{ $loan->book_id }}</h2>
                    <dl class="row small">
                        <dt class="col-sm-4">Reference</dt><dd class="col-sm-8">{{ $loan->transaction_code }}</dd>
                        <dt class="col-sm-4">Borrower</dt><dd class="col-sm-8">{{ $loan->borrower?->fullName() ?? 'Archived member' }}</dd>
                        <dt class="col-sm-4">Due date</dt><dd class="col-sm-8">{{ $loan->due_date->format('M j, Y') }}
                            @if($overdue)<span class="badge text-bg-warning">Overdue</span>@endif
                        </dd>
                    </dl>
                    <div class="mb-3">
                        <label for="return-condition" class="form-label">Return outcome</label>
                        <select id="return-condition" name="condition" class="form-select" required>
                            <option value="" disabled @selected(!in_array(old('condition'), ['Available', 'Damaged', 'Lost'], true))>Select an outcome</option>
                            <option value="Available" @selected(old('condition') === 'Available')>Fit for circulation</option>
                            <option value="Damaged" @selected(old('condition') === 'Damaged')>Damaged</option>
                            <option value="Lost" @selected(old('condition') === 'Lost')>Reported lost</option>
                        </select>
                    </div>
                    <div><button class="btn btn-primary">Record outcome</button></div>
                </form>
            @endif
        </div>
        <div class="col-lg-5">
            <section class="card card-body shadow-sm" aria-labelledby="loan-search-heading">
                <h2 class="h6 mb-0" id="loan-search-heading">Search open loans</h2>
                <form action="{{ route('circulation.return.form') }}" method="get" class="mt-3">
                    <label for="loan-query" class="form-label">Transaction reference, book title, or borrower</label>
                    <input id="loan-query" name="loan_query" class="form-control mb-3" value="{{ $loanQuery }}" maxlength="80" required>
                    <button class="btn btn-outline-primary">Search</button>
                </form>
                @if($loanQuery !== '')
                    <ul class="list-group mt-3">
                        @forelse($loans as $match)
                            <li class="list-group-item">
                                <a href="{{ route('circulation.return.form', ['transaction_code' => $match->transaction_code, 'loan_query' => $loanQuery]) }}">
                                    {{ $match->transaction_code }} · {{ $match->book->book_title }} · Copy #{{ $match->book_id }}
                                </a>
                                <small class="d-block text-body-secondary">{{ $match->borrower?->fullName() ?? 'Archived member' }}</small>
                            </li>
                        @empty
                            <li class="list-group-item">No matching open loans.</li>
                        @endforelse
                    </ul>
                @endif
            </section>
        </div>
    </div>
@endsection
