@extends('layouts.library')

@section('title', auth()->user()?->can('books.manage')

    ? 'Book Management'

    : 'Book Lookup')

@push('styles')

    <link rel="stylesheet" href="{{ asset('css/modules/books.css') }}">

@endpush

@section('content')

@php

    $hasActions = auth()->user()?->canAny([

        'books.update',

        'books.qr',

        'books.delete'

    ]);

    $canPrintQr = auth()->user()?->can('books.qr');

    $canManage = auth()->user()?->can('books.manage');

    $hasFilters = collect($filters ?? [])

        ->filter(fn ($v) => $v !== null && $v !== '')

        ->isNotEmpty();

@endphp

<section class="books-page">

    <!-- PAGE HEADER -->

    <header class="books-header">

        <div class="books-heading">

            <span class="books-heading-icon">

                <i class="bi bi-book" aria-hidden="true"></i>

            </span>

            <div>

                <h1>@yield('title')</h1>

                <p>

                    {{ $canManage

                        ? 'Manage book records, individual copies, and availability.'

                        : 'Search and explore the library collection.' }}

                </p>

            </div>

        </div>

        @can('books.manage')

            <a class="btn btn-primary books-add-button"

               href="{{ route('books.create') }}">

                <i class="bi bi-plus-lg" aria-hidden="true"></i>

                Add book copy

            </a>

        @endcan

    </header>

    <!-- PERSISTENT CATALOG NAVIGATION -->

    @if($canManage || $canPrintQr)

        <div class="books-toolbar">

            @can('books.manage')

                <x-catalog-nav active="books" />

            @endcan

            @can('books.qr')

                <button

                    type="submit"

                    form="books-qr-sheet-form"

                    class="btn btn-outline-secondary books-print-button"

                    id="books-qr-sheet-btn"

                    disabled

                >

                    <i class="bi bi-printer" aria-hidden="true"></i>

                    <span id="books-print-label">

                        Print selected QR (PDF)

                    </span>

                </button>

            @endcan

        </div>

    @endif

    <!-- SHARED IMPORT PANEL -->

    @hasanyrole('superadmin|admin')

        @include('imports.panel', ['kind' => 'books'])

    @endhasanyrole

    <!-- COLLECTION FILTERS -->

    <section class="books-filter-card">

        <div class="books-section-heading">

            <div>

                <h2>Collection filters</h2>

                <p>Find specific books and individual copies.</p>

            </div>

        </div>

        <form

            method="GET"

            action="{{ route('books.index') }}"

            class="books-filter-grid"

        >

            <!-- Title -->

            <div class="books-field books-title-field">

                <label for="title" class="form-label">

                    Title

                </label>

                <div class="books-input-icon">

                    <i class="bi bi-search" aria-hidden="true"></i>

                    <input

                        id="title"

                        name="title"

                        class="form-control"

                        placeholder="Search title..."

                        value="{{ $filters['title'] ?? '' }}"

                    >

                </div>

            </div>

            <!-- ISBN -->

            <div class="books-field">

                <label for="isbn" class="form-label">

                    ISBN

                </label>

                <div class="books-input-icon">

                    <i class="bi bi-upc" aria-hidden="true"></i>

                    <input

                        id="isbn"

                        name="isbn"

                        class="form-control"

                        maxlength="17"

                        placeholder="e.g. 978-971..."

                        value="{{ $filters['isbn'] ?? '' }}"

                    >

                </div>

            </div>

            <!-- Copy QR -->

            @can('books.manage')

                <div class="books-field">

                    <label for="qr" class="form-label">

                        Copy QR

                    </label>

                    <div class="books-input-icon">

                        <i class="bi bi-qr-code" aria-hidden="true"></i>

                        <input

                            id="qr"

                            name="qr"

                            class="form-control"

                            placeholder="e.g. BK-..."

                            value="{{ $filters['qr'] ?? '' }}"

                        >

                    </div>

                </div>

            @endcan

            <!-- Genre -->

            <div class="books-field">

                <label for="genre_id" class="form-label">

                    Genre

                </label>

                <select

                    id="genre_id"

                    name="genre_id"

                    class="form-select"

                >

                    <option value="">All genres</option>

                    @foreach($genres as $genre)

                        <option

                            value="{{ $genre->getKey() }}"

                            @selected(($filters['genre_id'] ?? null) == $genre->getKey())

                        >

                            {{ $genre->genre_name }}

                        </option>

                    @endforeach

                </select>

            </div>

            <!-- Author -->

            <div class="books-field">

                <label for="author_id" class="form-label">

                    Author

                </label>

                <select

                    id="author_id"

                    name="author_id"

                    class="form-select"

                >

                    <option value="">All authors</option>

                    @foreach($authors as $author)

                        <option

                            value="{{ $author->getKey() }}"

                            @selected(($filters['author_id'] ?? null) == $author->getKey())

                        >

                            {{ $author->fullName() }}

                        </option>

                    @endforeach

                </select>

            </div>

            <!-- Status -->

            <div class="books-field">

                <label for="book_status" class="form-label">

                    Status

                </label>

                <select

                    id="book_status"

                    name="book_status"

                    class="form-select"

                >

                    <option value="">All statuses</option>

                    @foreach($statuses as $status)

                        <option

                            value="{{ $status }}"

                            @selected(($filters['book_status'] ?? '') === $status)

                        >

                            {{ $status }}

                        </option>

                    @endforeach

                </select>

            </div>

            <!-- Filter actions -->

            <div class="books-filter-actions">

                <button type="submit" class="btn btn-primary">

                    <i class="bi bi-funnel" aria-hidden="true"></i>

                    Filter

                </button>

                <a

                    href="{{ route('books.index') }}"

                    class="btn btn-outline-secondary"

                >

                    Clear

                </a>

            </div>

        </form>

    </section>

    <!-- BULK QR PRINTING FORM -->

    @if($canPrintQr)

        <form

            id="books-qr-sheet-form"

            method="POST"

            action="{{ route('books.qr-sheet') }}"

            target="\_blank"

        >

            @csrf

        </form>

    @endif

    <!-- BOOK COLLECTION -->

    <section class="books-collection-card">

        <header class="books-collection-header">

            <div class="books-section-heading">

                <div>

                    <h2>Book collection</h2>

                    @if($books->total() > 0)

                        <p>

                            Showing

                            {{ $books->firstItem() }}–{{ $books->lastItem() }}

                            of {{ $books->total() }}

                            book {{ $books->total() === 1 ? 'copy' : 'copies' }}.

                        </p>

                    @else

                        <p>No matching book copies.</p>

                    @endif

                </div>

            </div>

            <div class="books-collection-meta">

                @if($hasFilters)

                    <span class="books-filter-indicator">

                        Filters applied

                    </span>

                @endif

                @if($canPrintQr)

                    <span

                        class="books-selected-count"

                        id="books-selected-count"

                        aria-live="polite"

                    >

                        0 selected

                    </span>

                @endif

            </div>

        </header>

        <!-- DATA TABLE -->

        <div class="table-responsive books-table-wrapper">

            <table class="table align-middle books-table">

                <thead>

                    <tr>

                        @if($canPrintQr)

                            <th class="books-checkbox-cell" scope="col">

                                <input

                                    class="form-check-input"

                                    type="checkbox"

                                    id="books-select-all"

                                    aria-label="Select all books on this page"

                                    @disabled($books->isEmpty())

                                >

                            </th>

                        @endif

                        @if($canManage)

                            <th scope="col">Copy QR</th>

                        @endif

                        <th scope="col">Title</th>

                        <th scope="col">ISBN</th>

                        <th scope="col">Genre</th>

                        <th scope="col">Status</th>

                        <th scope="col">Copies</th>

                        @if($hasActions)

                            <th scope="col">Actions</th>

                        @endif

                    </tr>

                </thead>

                <tbody>

                    @forelse($books as $book)

                        @php

                            $statusClass = match ($book->book_status) {

                                'Available' => 'text-bg-success',

                                'Borrowed' => 'text-bg-warning',

                                'Lost', 'Damaged' => 'text-bg-danger',

                                default => 'text-bg-secondary',

                            };

                            $statusIcon = match ($book->book_status) {

                                'Available' => 'bi-check-circle',

                                'Borrowed' => 'bi-clock',

                                'Lost', 'Damaged' => 'bi-exclamation-circle',

                                default => 'bi-circle',

                            };

                        @endphp

                        <tr>

                            @if($canPrintQr)

                                <td class="books-checkbox-cell">

                                    <input

                                        class="form-check-input book-qr-check"

                                        type="checkbox"

                                        name="book_ids[]"

                                        value="{{ $book->getKey() }}"

                                        form="books-qr-sheet-form"

                                        aria-label="Select {{ $book->book_title }}"

                                    >

                                </td>

                            @endif

                            @if($canManage)

                                <td class="text-nowrap">

                                    <code class="books-qr-code">

                                        {{ $book->book_qr_value }}

                                    </code>

                                </td>

                            @endif

                            <!-- Book title and author -->

                            <td class="books-title-cell">

                                <div class="books-title-entry">

                                    <div class="books-record-details">

                                        <span class="books-record-title">

                                            {{ $book->book_title }}

                                        </span>

                                        {{-- Sprint 4: Identify a specific physical copy during circulation. --}}
                                        @can('circulation.process')
                                            <small class="books-record-author">
                                                Copy #{{ $book->getKey() }}
                                            </small>
                                        @endcan

                                        @if($book->authors->isNotEmpty())

                                            <small class="books-record-author">

                                                {{ $book->authors->map->fullName()->join(', ') }}

                                            </small>

                                        @endif

                                    </div>

                                </div>

                            </td>

                            <!-- ISBN -->

                            <td class="books-isbn">

                                {{ $book->isbn ?? '—' }}

                            </td>

                            <!-- Genre -->

                            <td>

                                {{ $book->genre->genre_name }}

                            </td>

                            <!-- Status -->

                            <td>

                                <span class="badge books-status {{ $statusClass }}">

                                    <i class="bi {{ $statusIcon }}"

                                       aria-hidden="true"></i>

                                    {{ $book->book_status }}

                                </span>

                            </td>

                            <!-- Copy count -->

                            <td>

                                {{ $book->copies_count }}

                            </td>

                            <!-- Row actions -->

                            @if($hasActions)

                                <td>

                                    <div class="books-row-actions">

                                        @can('books.update')

                                            <a

                                                class="btn btn-sm btn-outline-primary"

                                                href="{{ route('books.edit', $book) }}"

                                            >

                                                <i class="bi bi-pencil"

                                                   aria-hidden="true"></i>

                                                Edit

                                            </a>

                                        @endcan

                                        @can('books.qr')

                                            <a

                                                class="btn btn-sm btn-outline-secondary"

                                                href="{{ route('books.label', $book) }}"

                                            >

                                                <i class="bi bi-printer"

                                                   aria-hidden="true"></i>

                                                Print QR

                                            </a>

                                        @endcan

                                        @can('books.delete')

                                            <form

                                                method="POST"

                                                action="{{ route('books.destroy', $book) }}"

                                                onsubmit="return confirm('Archive this book copy?')"

                                            >

                                                @csrf

                                                @method('DELETE')

                                                <button

                                                    type="submit"

                                                    class="btn btn-sm btn-outline-danger"

                                                >

                                                    <i class="bi bi-archive"

                                                       aria-hidden="true"></i>

                                                    Archive

                                                </button>

                                            </form>

                                        @endcan

                                    </div>

                                </td>

                            @endif

                        </tr>

                    @empty

                        <tr>

                            <td colspan="{{ 5 + ($hasActions ? 1 : 0) + ($canPrintQr ? 1 : 0) + ($canManage ? 1 : 0) }}">

                                <div class="books-empty-state">

                                    <span class="books-empty-icon">

                                        <i

                                            class="bi {{ $hasFilters ? 'bi-search' : 'bi-book' }}"

                                            aria-hidden="true"

                                        ></i>

                                    </span>

                                    <h3>

                                        {{ $hasFilters

                                            ? 'No matching books'

                                            : 'No books in the catalog yet' }}

                                    </h3>

                                    <p>

                                        {{ $hasFilters

                                            ? 'Try adjusting or clearing your search filters.'

                                            : 'Book records will appear here when added to the collection.' }}

                                    </p>

                                    @if($hasFilters)

                                        <a

                                            class="btn btn-outline-primary"

                                            href="{{ route('books.index') }}"

                                        >

                                            Clear filters

                                        </a>

                                    @endif

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        <!-- PAGINATION -->

        @if($books->hasPages())

            <div class="books-pagination">

                {{ $books->links('pagination::bootstrap-5') }}

            </div>

        @endif

    </section>

</section>

@endsection

@push('scripts')

@if(auth()->user()?->can('books.qr'))

    <script type="text/javascript">

(() => {

    "use strict";

    const all = document.getElementById("books-select-all");

    const button = document.getElementById("books-qr-sheet-btn");

    const label = document.getElementById("books-print-label");

    const selectedCount = document.getElementById("books-selected-count");

    const checks = Array.from(

        document.querySelectorAll(".book-qr-check")

    );

    if (!button) return;

    function sync() {

        const count = checks.filter(

            checkbox => checkbox.checked

        ).length;

        button.disabled = count === 0;

        if (label) {

            label.textContent = count

                ? `Print selected QR (${count}) (PDF)`

                : "Print selected QR (PDF)";

        }

        if (selectedCount) {

            selectedCount.textContent = `${count} selected`;

        }

        if (all) {

            all.checked =

                checks.length > 0 &&

                count === checks.length;

            all.indeterminate =

                count > 0 &&

                count < checks.length;

        }

        checks.forEach(checkbox => {

            checkbox.closest("tr")?.classList.toggle(

                "is-selected",

                checkbox.checked

            );

        });

    }

    all?.addEventListener("change", () => {

        checks.forEach(checkbox => {

            checkbox.checked = all.checked;

        });

        sync();

    });

    checks.forEach(checkbox => {

        checkbox.addEventListener("change", sync);

    });

    window.addEventListener("pageshow", sync);

    sync();

})();

</script>

@endif

@endpush
