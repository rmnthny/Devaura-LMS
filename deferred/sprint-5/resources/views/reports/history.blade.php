@extends('layouts.library')

@section('title', 'My transactions')

@section('content')
    <h1 class="h3 mb-3">My transactions</h1>
    <div class="table-responsive rounded border bg-white mb-3">
        <table class="table mb-0">
            <thead><tr><th scope="col">Reference</th><th scope="col">Book</th><th scope="col">Due</th><th scope="col">Returned</th><th scope="col">Status</th></tr></thead>
            <tbody>
            @forelse($loans as $loan)
                <tr>
                    <td>{{ $loan->transaction_code }}</td>
                    <td>{{ $loan->book->book_title }}</td>
                    <td>{{ $loan->due_date->toDateString() }}</td>
                    <td>{{ $loan->return_date?->toDateString() ?? '—' }}</td>
                    <td>{{ $loan->transaction_status === 'Borrowed' && $loan->due_date->toDateString() < now(config('attendance.timezone'))->toDateString() ? 'Overdue' : $loan->transaction_status }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No transactions recorded.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    {{ $loans->links() }}
@endsection
