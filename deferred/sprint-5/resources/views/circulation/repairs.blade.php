@extends('layouts.library')

@section('title', 'Book repairs')

@section('content')
    <h1 class="h3 mb-3">Book repairs</h1>
    <ul class="list-group mb-3">
        @forelse($repairs as $repair)
            <li class="list-group-item">
                <strong>{{ $repair->loan->book->book_title }}</strong> · Copy #{{ $repair->loan->book_id }}
                <div>Scheduled for {{ $repair->scheduled_on }} · {{ $repair->outcome ?? 'Awaiting repair' }}</div>
                @if(!$repair->completed_at)
                    <form method="post" action="{{ route('circulation.repairs.complete', $repair) }}" class="d-flex flex-wrap gap-2 mt-2">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-sm btn-outline-primary" name="outcome" value="Available">Mark fit for circulation</button>
                        <button class="btn btn-sm btn-outline-secondary" name="outcome" value="Damaged">Keep out of circulation</button>
                    </form>
                @endif
            </li>
        @empty
            <li class="list-group-item">No book repairs scheduled.</li>
        @endforelse
    </ul>
    {{ $repairs->links() }}
@endsection
