@extends('layouts.library')
@section('title', 'Review permanent deletion')
@section('content')
    <a class="d-inline-block mb-3" href="{{ route('archive.index', ['section' => 'members']) }}">Back to archived members</a>
    <section class="card" style="max-width: 38rem" aria-labelledby="purge-title">
        <div class="card-body p-3 p-md-4">
            <h1 class="h4" id="purge-title">Delete member permanently</h1>
            <p class="fw-semibold mb-0">{{ $member->fullName() }}</p>
            <p class="text-body-secondary text-break mb-3">{{ $account->email }}</p>
            @if ($reason)
                <p class="alert alert-warning mb-0">{{ $reason }}</p>
            @else
                <p>This removes the member profile and linked account. This action cannot be undone.</p>
                <form method="post" action="{{ route('archive.members.purge', $member->getKey()) }}">
                    @csrf
                    @method('DELETE')
                    <label class="form-label" for="purge-confirmation">Type DELETE to confirm</label>
                    <input class="form-control mb-3" id="purge-confirmation" name="confirmation" maxlength="6" pattern="DELETE" autocomplete="off" required>
                    <button type="submit" class="btn btn-danger">Permanently delete member</button>
                </form>
            @endif
        </div>
    </section>
@endsection
