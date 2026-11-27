@extends('layouts.library')

@section('title', 'Notifications')

@section('content')
    <h1 class="h3 mb-3">Notifications</h1>
    <ul class="list-group mb-3">
        @forelse($notifications as $notification)
            <li class="list-group-item d-flex flex-wrap justify-content-between align-items-start gap-2">
                <div>
                    <div class="fw-semibold">{{ $notification->data['title'] }}</div>
                    <p class="mb-1">{{ $notification->data['message'] }}</p>
                    <time class="small text-secondary" datetime="{{ $notification->created_at->toIso8601String() }}">
                        {{ $notification->created_at->copy()->timezone(config('attendance.timezone'))->format('M j, Y g:i a') }}
                    </time>
                </div>
                @unless($notification->read_at)
                    <form action="{{ route('notifications.read', $notification->id) }}" method="post">
                        @csrf
                        <button class="btn btn-sm btn-outline-secondary">Mark as read</button>
                    </form>
                @endunless
            </li>
        @empty
            <li class="list-group-item text-secondary">You have no library notifications.</li>
        @endforelse
    </ul>
    {{ $notifications->links() }}
@endsection
