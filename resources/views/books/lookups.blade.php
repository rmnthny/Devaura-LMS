@extends('layouts.library')
@section('title', ucfirst($lookup))
@section('content')
    <h1 class="h2">{{ ucfirst($lookup) }}</h1>
    <form action="{{ route('catalog.index', $lookup) }}" method="get" class="d-flex gap-2 mb-3">
        <input class="form-control" type="search" name="q" aria-label="Search {{ $lookup }}" value="{{ $search }}" placeholder="Search {{ $lookup }}">
        <button class="btn btn-outline-primary">Search</button>
    </form>
    @can('books.manage')
        <form class="card card-body mb-3" method="post" action="{{ route('catalog.store', $lookup) }}">
            @csrf
            @include('books.lookup-fields', ['item' => null, 'prefix' => 'new'])
            <div><button class="btn btn-primary mt-2">Add {{ rtrim($lookup, 's') }}</button></div>
        </form>
    @endcan
    @forelse ($records as $item)
        <article class="card card-body mb-2">
            @can('books.update')
                <form method="post" action="{{ route('catalog.update', [$lookup, $item]) }}">
                    @csrf
                    @method('PUT')
                    @include('books.lookup-fields', ['prefix' => 'existing-'.$item->getKey()])
                    <div><button class="btn btn-sm btn-outline-primary mt-2">Save</button></div>
                </form>
            @else
                <p class="mb-0">{{ implode(' · ', array_filter($item->only(array_keys($fields)))) }}</p>
            @endcan
            @can('books.delete')
                <form method="post" action="{{ route('catalog.destroy', [$lookup, $item]) }}" onsubmit="return confirm('Delete this catalog entry?')">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger mt-2">Delete</button>
                </form>
            @endcan
        </article>
    @empty
        <p>No matching {{ $lookup }}.</p>
    @endforelse
    {{ $records->links('pagination::bootstrap-5') }}
    @if ($lookup === 'publishers')
        <script>
            (() => {
                const individualPattern = @json(\App\Http\Requests\SaveCatalogLookupRequest::INDIVIDUAL_PUBLISHER_PATTERN);
                const orgPattern = @json(\App\Http\Requests\SaveCatalogLookupRequest::PUBLISHER_PATTERN);

                document.querySelectorAll('[data-publisher-type]').forEach((select) => {
                    const input = document.querySelector(`[data-publisher-name="${select.dataset.publisherType}"]`);
                    if (!input) return;

                    const sync = () => {
                        const isIndividual = select.value === 'Individual';
                        input.pattern = isIndividual ? individualPattern : orgPattern;
                        input.placeholder = isIndividual ? 'e.g. Juan M. Dela Cruz' : 'e.g. Rex Book Store, Inc.';
                        input.title = isIndividual
                            ? 'Use letters, spaces, and initials only (e.g. Juan M. Dela Cruz).'
                            : "Use letters, numbers, spaces, and standard punctuation (. , & ' - /).";
                    };

                    input.addEventListener('input', () => {
                        if (select.value === 'Individual') {
                            input.value = input.value.replace(/[^\p{L}\p{M} .]/gu, '');
                        }
                    });
                    select.addEventListener('change', sync);
                    sync();
                });
            })();
        </script>
    @endif
@endsection
