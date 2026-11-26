
@extends('layouts.library')

@section('title', $book->exists ? 'Edit book copy' : 'Add book copy')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/modules/book-form.css') }}">
@endpush

@section('content')

@php
    $isEditing = $book->exists;

    $selectedAuthors = (array) old(
        'author_ids',
        $isEditing ? $book->authors->modelKeys() : []
    );

    $selectedPublishers = (array) old(
        'publisher_ids',
        $isEditing ? $book->publishers->modelKeys() : []
    );
@endphp

<section class="book-form-page">

    <!-- PAGE HEADER -->

    <header class="book-form-header">
        <div class="book-form-heading">
            <span class="book-form-heading-icon" aria-hidden="true">
                <i class="bi {{ $isEditing ? 'bi-pencil-square' : 'bi-book' }}"></i>
            </span>

            <div>
                <h1>@yield('title')</h1>
                <p>
                    {{ $isEditing
                        ? 'Update the information and classification of this book copy.'
                        : 'Register a book title and physical copies in the library catalog.' }}
                </p>
            </div>
        </div>

        @if($isEditing && $book->book_qr_value)
            <div class="book-form-header-actions">
                <span class="book-form-qr-label">
                    Copy QR
                    <code>{{ $book->book_qr_value }}</code>
                </span>

                @can('books.qr')
                    <a class="btn btn-outline-secondary"
                       href="{{ route('books.label', $book) }}">
                        <i class="bi bi-printer" aria-hidden="true"></i>
                        Print QR
                    </a>
                @endcan
            </div>
        @endif
    </header>

    <!-- VALIDATION ERRORS -->

    @if($errors->any())
        <div class="alert alert-danger book-form-alert" role="alert">
            <strong>Please review the following:</strong>

            <ul class="mb-0 mt-2">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- EXISTING FORM AND ROUTES -->

    <form
        method="POST"
        action="{{ $isEditing
            ? route('books.update', $book)
            : route('books.store') }}"
        class="book-editor"
    >
        @csrf

        @if($isEditing)
            @method('PUT')
        @endif

        <div class="book-editor-grid">

            <!-- LEFT: BOOK DETAILS -->

            <section class="book-editor-card book-editor-details"
                     aria-labelledby="book-details-title">

                <div class="book-editor-card-header">
                    <h2 id="book-details-title">Book information</h2>
                    <p>Enter the title, catalog details, and condition.</p>
                </div>

                <div class="book-editor-fields">

                    <!-- TITLE -->

                    <div class="book-editor-field book-title-field">
                        <label class="form-label" for="book_title">
                            Book title
                            <span class="book-required">*</span>
                        </label>

                        <input
                            type="text"
                            class="form-control @error('book_title') is-invalid @enderror"
                            id="book_title"
                            name="book_title"
                            maxlength="255"
                            value="{{ old('book_title', $book->book_title) }}"
                            placeholder="Enter the book title"
                            required
                        >

                        @error('book_title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- ISBN -->

                    <div class="book-editor-field book-isbn-field">
                        <label class="form-label" for="isbn">ISBN</label>

                        <input
                            type="text"
                            class="form-control @error('isbn') is-invalid @enderror"
                            id="isbn"
                            name="isbn"
                            maxlength="17"
                            placeholder="978-971-011-123-4"
                            value="{{ old('isbn', $book->isbn) }}"
                            aria-describedby="book-isbn-help"
                        >

                        <div class="form-text" id="book-isbn-help">
                            Optional · 10 or 13 digits; hyphens allowed.
                        </div>

                        @error('isbn')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- GENRE -->

                    <div class="book-editor-field">
                        <div class="book-editor-label-row">
                            <label class="form-label" for="genre_id">
                                Genre
                                <span class="book-required">*</span>
                            </label>

                            <a href="{{ route('catalog.index', 'genres') }}"
                               class="book-editor-manage-link">
                                Manage genres
                            </a>
                        </div>

                        <select
                            class="form-select @error('genre_id') is-invalid @enderror"
                            id="genre_id"
                            name="genre_id"
                            required
                        >
                            <option value="">Choose a genre</option>

                            @foreach($genres as $genre)
                                <option
                                    value="{{ $genre->getKey() }}"
                                    @selected(old('genre_id', $book->genre_id) == $genre->getKey())
                                >
                                    {{ $genre->genre_name }}
                                </option>
                            @endforeach
                        </select>

                        @error('genre_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- STATUS -->

                    <div class="book-editor-field">
                        <label class="form-label" for="book_status">
                            Status
                            <span class="book-required">*</span>
                        </label>

                        <select
                            class="form-select @error('book_status') is-invalid @enderror"
                            id="book_status"
                            name="book_status"
                            required
                        >
                            @foreach($statuses as $status)
                                <option
                                    value="{{ $status }}"
                                    @selected(old('book_status', $book->book_status) === $status)
                                >
                                    {{ $status }}
                                </option>
                            @endforeach
                        </select>

                        @error('book_status')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- PUBLICATION DATE -->

                    <div class="book-editor-field">
                        <label class="form-label" for="date_published">
                            Publication date
                        </label>

                        <input
                            class="form-control @error('date_published') is-invalid @enderror"
                            id="date_published"
                            type="date"
                            name="date_published"
                            value="{{ old('date_published', $book->date_published?->format('Y-m-d')) }}"
                        >

                        @error('date_published')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- COPIES: ADD ONLY -->

                    @unless($isEditing)
                        <div class="book-editor-field">
                            <label class="form-label" for="copies">
                                Copies
                                <span class="book-required">*</span>
                            </label>

                            <input
                                class="form-control @error('copies') is-invalid @enderror"
                                id="copies"
                                type="number"
                                name="copies"
                                min="1"
                                max="50"
                                value="{{ old('copies', 1) }}"
                                required
                            >

                            @error('copies')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endunless

                </div>
            </section>

            <!-- RIGHT: AUTHORS AND PUBLISHERS -->

            <div class="book-editor-related">

                <!-- AUTHORS -->

                <fieldset class="book-editor-card book-editor-list-card">
                    <legend class="visually-hidden">Authors</legend>

                    <div class="book-editor-card-header book-editor-list-heading">
                        <div>
                            <h2>Authors</h2>
                            <p>Select all authors associated with this book.</p>
                        </div>

                        <a href="{{ route('catalog.index', 'authors') }}"
                           class="book-editor-manage-link">
                            Manage authors
                        </a>
                    </div>

                    <div class="book-editor-options" role="group"
                         aria-label="Select authors">

                        @forelse($authors as $author)
                            <label class="book-editor-option"
                                   for="author-{{ $author->getKey() }}">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="author_ids[]"
                                    value="{{ $author->getKey() }}"
                                    id="author-{{ $author->getKey() }}"
                                    @checked(in_array($author->getKey(), $selectedAuthors))
                                >

                                <span>{{ $author->fullName() }}</span>
                            </label>
                        @empty
                            <p class="book-editor-empty-options">
                                No authors yet.
                            </p>
                        @endforelse

                    </div>

                    @error('author_ids')
                        <p class="book-editor-error" role="alert">
                            {{ $message }}
                        </p>
                    @enderror

                    @error('author_ids.*')
                        <p class="book-editor-error" role="alert">
                            {{ $message }}
                        </p>
                    @enderror
                </fieldset>

                <!-- PUBLISHERS -->

                <fieldset class="book-editor-card book-editor-list-card">
                    <legend class="visually-hidden">Publishers</legend>

                    <div class="book-editor-card-header book-editor-list-heading">
                        <div>
                            <h2>Publishers</h2>
                            <p>Select the publishing organizations.</p>
                        </div>

                        <a href="{{ route('catalog.index', 'publishers') }}"
                           class="book-editor-manage-link">
                            Manage publishers
                        </a>
                    </div>

                    <div class="book-editor-options" role="group"
                         aria-label="Select publishers">

                        @forelse($publishers as $publisher)
                            <label class="book-editor-option"
                                   for="publisher-{{ $publisher->getKey() }}">
                                <input
                                    class="form-check-input"
                                    type="checkbox"
                                    name="publisher_ids[]"
                                    value="{{ $publisher->getKey() }}"
                                    id="publisher-{{ $publisher->getKey() }}"
                                    @checked(in_array($publisher->getKey(), $selectedPublishers))
                                >

                                <span>{{ $publisher->publisher_name }}</span>
                            </label>
                        @empty
                            <p class="book-editor-empty-options">
                                No publishers yet.
                            </p>
                        @endforelse

                    </div>

                    @error('publisher_ids')
                        <p class="book-editor-error" role="alert">
                            {{ $message }}
                        </p>
                    @enderror

                    @error('publisher_ids.*')
                        <p class="book-editor-error" role="alert">
                            {{ $message }}
                        </p>
                    @enderror
                </fieldset>
            </div>
        </div>

        <!-- FORM ACTIONS -->

        <div class="book-editor-actions">
            <span class="book-editor-actions-note">
                <i class="bi bi-info-circle" aria-hidden="true"></i>
                Required fields are marked with *
            </span>

            <div class="book-editor-buttons">
                <a
                    href="{{ route('books.index') }}"
                    class="btn btn-outline-secondary"
                >
                    Cancel
                </a>

                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg" aria-hidden="true"></i>
                    {{ $isEditing ? 'Save changes' : 'Save book copy' }}
                </button>
            </div>
        </div>

    </form>
</section>

@endsection
