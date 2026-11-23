@extends('layouts.library')
@section('title', $book->exists ? 'Edit book copy' : 'Add book copy')
@section('content')
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h1 class="h2 mb-0">@yield('title')</h1>
            @if ($book->exists && $book->book_qr_value)
                <small class="text-body-secondary">Copy QR: <code>{{ $book->book_qr_value }}</code></small>
            @endif
        </div>
        @if ($book->exists)
            @can('books.qr')
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('books.label', $book) }}">Print QR</a>
            @endcan
        @endif
    </div>
    <form method="post" action="{{ $book->exists ? route('books.update', $book) : route('books.store') }}" class="card card-body">
        @csrf
        @if ($book->exists) @method('PUT') @endif
        <div class="row g-3">
            <div class="col-md-8">
                <label class="form-label" for="book_title">Title</label>
                <input class="form-control" id="book_title" name="book_title" maxlength="255" required value="{{ old('book_title', $book->book_title) }}">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="isbn">ISBN</label>
                <input class="form-control" id="isbn" name="isbn" maxlength="17" placeholder="978-971-011-123-4" value="{{ old('isbn', $book->isbn) }}">
                <div class="form-text">Optional. 10 or 13 digits (hyphens allowed).</div>
            </div>
            <div class="col-md-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label mb-0" for="genre_id">Genre</label>
                    <a class="small" href="{{ route('catalog.index', 'genres') }}">Manage genres</a>
                </div>
                <select class="form-select" id="genre_id" name="genre_id" required>
                    <option value="">Choose a genre</option>
                    @foreach ($genres as $genre)
                        <option value="{{ $genre->getKey() }}" @selected(old('genre_id', $book->genre_id) == $genre->getKey())>{{ $genre->genre_name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-{{ $book->exists ? '4' : '3' }}">
                <label class="form-label" for="book_status">Status</label>
                <select class="form-select" id="book_status" name="book_status" required>
                    @foreach ($statuses as $status)
                        <option @selected(old('book_status', $book->book_status) === $status)>{{ $status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-{{ $book->exists ? '4' : '3' }}">
                <label class="form-label" for="date_published">Publication date</label>
                <input class="form-control" id="date_published" type="date" name="date_published" value="{{ old('date_published', $book->date_published?->format('Y-m-d')) }}">
            </div>
            @unless ($book->exists)
                <div class="col-md-2">
                    <label class="form-label" for="copies">Copies</label>
                    <input class="form-control" id="copies" type="number" name="copies" min="1" max="50" value="{{ old('copies', 1) }}" required>
                </div>
            @endunless
            @php($selectedAuthors = (array) old('author_ids', $book->exists ? $book->authors->modelKeys() : []))
            <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label mb-0">Authors</label>
                    <a class="small" href="{{ route('catalog.index', 'authors') }}">Manage authors</a>
                </div>
                <div class="border rounded p-2 overflow-auto" style="max-height: 10rem;">
                    @forelse ($authors as $author)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="author_ids[]" value="{{ $author->getKey() }}"
                                   id="author-{{ $author->getKey() }}" @checked(in_array($author->getKey(), $selectedAuthors))>
                            <label class="form-check-label" for="author-{{ $author->getKey() }}">{{ $author->fullName() }}</label>
                        </div>
                    @empty
                        <span class="small text-body-secondary">No authors yet.</span>
                    @endforelse
                </div>
            </div>
            @php($selectedPublishers = (array) old('publisher_ids', $book->exists ? $book->publishers->modelKeys() : []))
            <div class="col-md-6">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label mb-0">Publishers</label>
                    <a class="small" href="{{ route('catalog.index', 'publishers') }}">Manage publishers</a>
                </div>
                <div class="border rounded p-2 overflow-auto" style="max-height: 10rem;">
                    @forelse ($publishers as $publisher)
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="publisher_ids[]" value="{{ $publisher->getKey() }}"
                                   id="publisher-{{ $publisher->getKey() }}" @checked(in_array($publisher->getKey(), $selectedPublishers))>
                            <label class="form-check-label" for="publisher-{{ $publisher->getKey() }}">{{ $publisher->publisher_name }}</label>
                        </div>
                    @empty
                        <span class="small text-body-secondary">No publishers yet.</span>
                    @endforelse
                </div>
            </div>
        </div>
        <div class="d-flex gap-2 mt-4">
            <button class="btn btn-primary">{{ $book->exists ? 'Save changes' : 'Save book copy' }}</button>
            <a href="{{ route('books.index') }}" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
@endsection
