@extends('layouts.library')
@section('title', auth()->user()?->can('books.manage') ? 'Book Management' : 'Book Lookup')
@section('content')
    @hasanyrole('superadmin|admin')
        @include('imports.panel', ['kind' => 'books'])
    @endhasanyrole
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h1 class="h2 mb-0">@yield('title')</h1>
        <div class="d-flex flex-wrap gap-2">
            @can('books.qr')
                <button type="submit" form="books-qr-sheet-form" class="btn btn-outline-secondary" id="books-qr-sheet-btn" disabled>
                    <i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>Print selected QR (PDF)
                </button>
            @endcan
            @can('books.manage')
                <a class="btn btn-primary" href="{{ route('books.create') }}">Add book copy</a>
            @endcan
        </div>
    </div>
    @can('books.manage')
        <nav aria-label="Catalog lookups" class="d-flex flex-wrap gap-2 mb-3">
            @foreach (['genres' => 'Genres', 'authors' => 'Authors', 'publishers' => 'Publishers'] as $lookup => $label)
                <a class="btn btn-sm btn-outline-secondary" href="{{ route('catalog.index', $lookup) }}">{{ $label }}</a>
            @endforeach
        </nav>
    @endcan
    <form method="get" action="{{ route('books.index') }}" class="row g-2 bg-white border rounded p-3 mx-0 mb-3">
        <div class="col-sm-6 col-lg">
            <label for="title" class="form-label">Title</label>
            <input id="title" name="title" class="form-control" placeholder="Search title..." value="{{ $filters['title'] ?? '' }}">
        </div>
        <div class="col-sm-6 col-lg">
            <label for="isbn" class="form-label">ISBN</label>
            <input id="isbn" name="isbn" class="form-control" maxlength="17" placeholder="e.g. 978-971..." value="{{ $filters['isbn'] ?? '' }}">
        </div>
        @can('books.manage')
            <div class="col-sm-6 col-lg">
                <label for="qr" class="form-label">Copy QR</label>
                <input id="qr" name="qr" class="form-control" placeholder="e.g. BK-..." value="{{ $filters['qr'] ?? '' }}">
            </div>
        @endcan
        <div class="col-sm-6 col-lg">
            <label for="genre_id" class="form-label">Genre</label>
            <select id="genre_id" name="genre_id" class="form-select">
                <option value="">All genres</option>
                @foreach ($genres as $genre)
                    <option value="{{ $genre->getKey() }}" @selected(($filters['genre_id'] ?? null) == $genre->getKey())>{{ $genre->genre_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-6 col-lg">
            <label for="author_id" class="form-label">Author</label>
            <select id="author_id" name="author_id" class="form-select">
                <option value="">All authors</option>
                @foreach ($authors as $author)
                    <option value="{{ $author->getKey() }}" @selected(($filters['author_id'] ?? null) == $author->getKey())>{{ $author->fullName() }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-sm-6 col-lg">
            <label for="book_status" class="form-label">Status</label>
            <select id="book_status" name="book_status" class="form-select">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option @selected(($filters['book_status'] ?? '') === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-auto align-self-end"><button class="btn btn-outline-primary">Filter</button></div>
        <div class="col-auto align-self-end"><a href="{{ route('books.index') }}" class="btn btn-link">Clear</a></div>
    </form>
    @php($hasActions = auth()->user()?->canAny(['books.update', 'books.qr', 'books.delete']))
    @php($canPrintQr = auth()->user()?->can('books.qr'))
    @php($canManage = auth()->user()?->can('books.manage'))
    @php($hasFilters = collect($filters ?? [])->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty())
    @if ($canPrintQr)
        <form id="books-qr-sheet-form" method="post" action="{{ route('books.qr-sheet') }}" target="_blank">@csrf</form>
    @endif
    @if ($books->total() > 0)
        <p class="small text-body-secondary mb-2">
            Showing {{ $books->firstItem() }}–{{ $books->lastItem() }} of {{ $books->total() }} book {{ $books->total() === 1 ? 'copy' : 'copies' }}.
        </p>
    @endif
    <div class="table-responsive border rounded bg-white mb-3">
        <table class="table align-middle mb-0">
            <thead>
                <tr>
                    @if ($canPrintQr)
                        <th style="width: 2.25rem;">
                            <input class="form-check-input" type="checkbox" id="books-select-all" aria-label="Select all books" @disabled($books->isEmpty())>
                        </th>
                    @endif
                    @if ($canManage) <th>Copy QR</th> @endif
                    <th>Title</th>
                    <th>ISBN</th>
                    <th>Genre</th>
                    <th>Status</th>
                    <th>Copies</th>
                    @if ($hasActions) <th>Actions</th> @endif
                </tr>
            </thead>
            <tbody>
            @forelse ($books as $book)
                @php($statusClass = match ($book->book_status) {
                    'Available' => 'text-bg-success',
                    'Borrowed' => 'text-bg-warning',
                    'Lost', 'Damaged' => 'text-bg-danger',
                    default => 'text-bg-secondary',
                })
                <tr>
                    @if ($canPrintQr)
                        <td>
                            <input class="form-check-input book-qr-check" type="checkbox" name="book_ids[]" value="{{ $book->getKey() }}" form="books-qr-sheet-form" aria-label="Select {{ $book->book_title }}">
                        </td>
                    @endif
                    @if ($canManage)
                        <td class="text-nowrap"><code>{{ $book->book_qr_value }}</code></td>
                    @endif
                    <td>
                        <span class="fw-semibold">{{ $book->book_title }}</span>
                        @can('circulation.process')
                            <small class="d-block text-body-secondary">Copy #{{ $book->getKey() }}</small>
                        @endcan
                        <small class="d-block text-body-secondary">{{ $book->authors->map->fullName()->join(', ') }}</small>
                    </td>
                    <td>{{ $book->isbn ?? '—' }}</td>
                    <td>{{ $book->genre->genre_name }}</td>
                    <td><span class="badge {{ $statusClass }}">{{ $book->book_status }}</span></td>
                    <td>{{ $book->copies_count }}</td>
                    @if ($hasActions)
                        <td class="text-nowrap">
                            @can('books.update')
                                <a class="btn btn-sm btn-outline-primary" href="{{ route('books.edit', $book) }}">Edit</a>
                            @endcan
                            @can('books.qr')
                                <a class="btn btn-sm btn-outline-secondary" href="{{ route('books.label', $book) }}">Print QR</a>
                            @endcan
                            @can('books.delete')
                                <form class="d-inline" method="post" action="{{ route('books.destroy', $book) }}" onsubmit="return confirm('Archive this book copy?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger">Archive</button>
                                </form>
                            @endcan
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ 5 + ($hasActions ? 1 : 0) + ($canPrintQr ? 1 : 0) + ($canManage ? 1 : 0) }}" class="text-body-secondary">
                        {{ $hasFilters ? 'No book copies match these filters.' : 'No books in the catalog yet.' }}
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if ($canPrintQr)
        <script type="text/javascript">
            (() => {
                const all = document.getElementById('books-select-all');
                const btn = document.getElementById('books-qr-sheet-btn');
                const checks = Array.from(document.querySelectorAll('.book-qr-check'));
                const sync = () => {
                    const count = checks.filter(c => c.checked).length;
                    btn.disabled = count === 0;
                    btn.innerHTML = `<i class="bi bi-file-earmark-pdf me-1" aria-hidden="true"></i>Print selected QR${count ? ` (${count})` : ''} (PDF)`;
                    if (all) all.checked = checks.length > 0 && count === checks.length;
                };
                all?.addEventListener('change', () => { checks.forEach(c => c.checked = all.checked); sync(); });
                checks.forEach(c => c.addEventListener('change', sync));
                sync();
            })();
        </script>
    @endif
    {{ $books->links('pagination::bootstrap-5') }}
@endsection
