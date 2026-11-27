@extends('layouts.library')

@section('title', 'Inventory audits')

@section('content')
    <h1 class="h3">Inventory audits</h1>
    <a href="{{ route('inventory.index') }}">Individual copy checks</a>
    <form method="post" action="{{ route('inventory.sessions.store') }}" class="card card-body my-3">
        @csrf
        <label for="audit-remarks" class="form-label">Audit remarks (optional)</label>
        <textarea id="audit-remarks" name="remarks" maxlength="1000" class="form-control mb-2"></textarea>
        <div><button class="btn btn-primary">Start inventory audit</button></div>
    </form>
    <ul class="list-group mb-3">
        @forelse($sessions as $session)
            <li class="list-group-item">
                <a href="{{ route('inventory.sessions.show', $session) }}">Audit #{{ $session->getKey() }}</a>
                · {{ $session->audit_status }} · {{ $session->items_count }} copies
            </li>
        @empty
            <li class="list-group-item">No inventory audits yet.</li>
        @endforelse
    </ul>
    {{ $sessions->links() }}
@endsection
