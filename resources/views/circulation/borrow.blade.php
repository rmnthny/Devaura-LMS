@extends('layouts.library')

@section('title', 'Book Borrowing')

@section('content')
    <h1 class="h3 mb-3">Book Borrowing</h1>
    @php
        $selection = array_filter(['member' => $member?->getKey(), 'copy' => $copy?->getKey(), 'member_query' => $memberQuery, 'book_query' => $bookQuery]);
        $previous = fn (string $field) => is_scalar(old($field)) ? old($field) : '';
    @endphp
    <div class="row g-3 align-items-start">
        <div class="col-lg-7">
            @if($memberUnavailable || $copyUnavailable)
                <div class="alert alert-warning" role="alert">
                    @if($memberUnavailable)<div>The selected member is no longer eligible. Select another member.</div>@endif
                    @if($copyUnavailable)<div>The selected copy is no longer available. Select another copy.</div>@endif
                </div>
            @endif
            <form action="{{ route('circulation.borrow') }}" method="post" class="card card-body shadow-sm">
                @csrf
                <input type="hidden" name="request_id" value="{{ $previous('request_id') ?: $checkoutId }}">
                @if($member)
                    <p>
                        <strong>Selected member</strong> {{ $member->member->fullName() }}
                        <a class="ms-2" href="{{ route('circulation.borrow.form', Arr::except($selection, 'member')) }}">Clear member</a>
                    </p>
                    <input type="hidden" name="borrower_account_id" value="{{ $member->getKey() }}">
                @else
                    <div class="mb-3">
                        <label for="borrower-identifier" class="form-label">Member ID (LRN or employee number) or QR value</label>
                        <input id="borrower-identifier" name="borrower_identifier" class="form-control"
                            value="{{ $memberUnavailable ? '' : $previous('borrower_identifier') }}" maxlength="50" autocomplete="off" required @if($memberQuery === '' && $bookQuery === '') autofocus @endif>
                    </div>
                @endif
                @if($copy)
                    <p>
                        <strong>Selected copy</strong> {{ $copy->book_title }} · Copy #{{ $copy->getKey() }}
                        <a class="ms-2" href="{{ route('circulation.borrow.form', Arr::except($selection, 'copy')) }}">Clear copy</a>
                    </p>
                    <input type="hidden" name="book_id" value="{{ $copy->getKey() }}">
                @else
                    <div class="mb-3">
                        <label for="borrow-qr" class="form-label">Book copy QR value</label>
                        <input id="borrow-qr" name="book_qr_value" class="form-control"
                            value="{{ $copyUnavailable ? '' : $previous('book_qr_value') }}" maxlength="50" autocomplete="off" required @if($member && $memberQuery === '' && $bookQuery === '') autofocus @endif>
                        <div class="form-text">Without a QR, search by title or ISBN and select the matching book.</div>
                    </div>
                @endif
                <p class="form-text">Books are due by the end of the day, {{ $loanDays }} calendar days after checkout (Manila time).</p>
                <div><button class="btn btn-primary">Check out book</button></div>
            </form>
        </div>
        <div class="col-lg-5">
            <details class="card card-body shadow-sm" open>
                <summary class="fw-semibold">Search for a member or book</summary>
                <form action="{{ route('circulation.borrow.form') }}" method="get" class="mt-3">
                    @if($member)
                        <input type="hidden" name="member" value="{{ $member->getKey() }}">
                    @endif
                    @if($copy)
                        <input type="hidden" name="copy" value="{{ $copy->getKey() }}">
                    @endif
                    <label for="member-query" class="form-label">Member name or reference number</label>
                    <input id="member-query" name="member_query" class="form-control mb-3" value="{{ $memberQuery }}" minlength="2" maxlength="80">
                    <label for="copy-query" class="form-label">Available book title or ISBN</label>
                    <input id="copy-query" name="book_query" class="form-control mb-3" value="{{ $bookQuery }}" maxlength="80">
                    <button class="btn btn-outline-primary">Search</button>
                </form>

                @if($memberQuery !== '')
                    <h2 class="h6 mt-3">Matching members</h2>
                    <ul class="list-group">
                        @forelse($members as $match)
                            <li class="list-group-item">
                                <a href="{{ route('circulation.borrow.form', [...$selection, 'member' => $match->getKey()]) }}">
                                    {{ $match->member->fullName() }} · ID ending {{ substr($match->reference_number, -4) }}
                                </a>
                            </li>
                        @empty
                            <li class="list-group-item">No eligible members found.</li>
                        @endforelse
                    </ul>
                @endif

                @if($bookQuery !== '')
                    <h2 class="h6 mt-3">Available copies</h2>
                    <ul class="list-group">
                        @forelse($books as $match)
                            <li class="list-group-item">
                                <a href="{{ route('circulation.borrow.form', [...$selection, 'copy' => $match->getKey()]) }}">
                                    {{ $match->book_title }} · Copy #{{ $match->getKey() }}
                                </a>
                            </li>
                        @empty
                            <li class="list-group-item">No available copies found.</li>
                        @endforelse
                    </ul>
                @endif
            </details>
        </div>
    </div>
@endsection
