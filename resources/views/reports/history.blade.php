@extends('layouts.library')

@section('title', 'My transactions')

@section('content')
    <h1 class="h3 mb-4">My transactions</h1>
    <div class="card mb-3">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr>
                    <th scope="col">Reference</th>
                    <th scope="col">Book</th>
                    <th scope="col">Checked out</th>
                    <th scope="col">Due</th>
                    <th scope="col">Returned</th>
                    <th scope="col">Status</th>
                </tr></thead>
                <tbody>
                    @forelse($loans as $loan)
                        <tr>
                            <td>{{ $loan->transaction_code }}</td>
                            <td>{{ $loan->book->book_title }}</td>
                            <td>{{ $loan->borrow_date->toDateString() }}</td>
                            <td>{{ $loan->due_date->toDateString() }}</td>
                            <td>{{ $loan->return_date?->toDateString() ?? '—' }}</td>
                            <td>{{ !$loan->return_date && $loan->transaction_status === $loan::BORROWED && $loan->isOverdueOn($today) ? $loan::OVERDUE : $loan->transaction_status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No transactions recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $loans->links('pagination::bootstrap-5') }}
@endsection
