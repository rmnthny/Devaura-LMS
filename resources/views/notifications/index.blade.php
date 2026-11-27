
@extends('layouts.library')

@section('title', 'Notifications')

@push('styles')
    <link rel="stylesheet"
          href="{{ asset('css/modules/notifications.css') }}">
@endpush

@section('content')
<div class="notification-page">

    <header class="notification-header">
        <div class="notification-heading">
            <span class="notification-page-icon">
                <i class="bi bi-bell" aria-hidden="true"></i>
            </span>

            <div>
                <span class="notification-eyebrow">
                    LIBRARY COMMUNICATIONS
                </span>

                <h1>Notifications</h1>

                <p>
                    View library updates and manage your notifications.
                </p>
            </div>
        </div>
    </header>

    <section class="notification-panel"
             aria-labelledby="notification-panel-title">

        <div class="notification-panel-header">
            <div>
                <h2 id="notification-panel-title">
                    Notification Inbox
                </h2>

                <p>
                    {{ $notifications->total() }}
                    {{ $notifications->total() === 1 ? 'notification' : 'notifications' }}
                </p>
            </div>

            <span class="notification-panel-label">
                <i class="bi bi-clock-history" aria-hidden="true"></i>
                Latest updates
            </span>
        </div>

        <div class="notification-list">
            @forelse($notifications as $notification)
                @php
                    $unread = is_null($notification->read_at);
                    $title = $notification->data['title'] ?? 'Library notification';
                    $message = $notification->data['message'] ?? '';
                @endphp

                <article class="notification-item {{ $unread ? 'is-unread' : 'is-read' }}">
                    <span class="notification-item-icon" aria-hidden="true">
                        <i class="bi {{ $unread ? 'bi-bell-fill' : 'bi-check2-circle' }}"></i>
                    </span>

                    <div class="notification-item-content">
                        <div class="notification-item-heading">
                            <h3>{{ $title }}</h3>

                            @if($unread)
                                <span class="notification-unread-badge">
                                    <span class="notification-unread-dot"></span>
                                    Unread
                                </span>
                            @else
                                <span class="notification-read-label">
                                    Read
                                </span>
                            @endif
                        </div>

                        @if($message !== '')
                            <p class="notification-message">
                                {{ $message }}
                            </p>
                        @endif

                        <div class="notification-item-meta">
                            <time datetime="{{ $notification->created_at->toIso8601String() }}">
                                <i class="bi bi-clock" aria-hidden="true"></i>
                                {{ $notification->created_at->copy()->timezone(config('attendance.timezone'))->format('M j, Y · g:i A') }}
                            </time>
                        </div>
                    </div>

                    @if($unread)
                        <form
                            action="{{ route('notifications.read', $notification->id) }}"
                            method="post"
                            class="notification-action"
                        >
                            @csrf
                            <button type="submit"
                                    class="btn btn-outline-primary">
                                <i class="bi bi-check2" aria-hidden="true"></i>
                                Mark as read
                            </button>
                        </form>
                    @endif
                </article>
            @empty
                <div class="notification-empty">
                    <span class="notification-empty-icon">
                        <i class="bi bi-bell-slash" aria-hidden="true"></i>
                    </span>

                    <h3>No notifications yet</h3>

                    <p>
                        You have no library notifications.
                        New updates will appear here when available.
                    </p>
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="notification-pagination">
                {{ $notifications->links('pagination::bootstrap-5') }}
            </div>
        @endif

    </section>

</div>
@endsection
