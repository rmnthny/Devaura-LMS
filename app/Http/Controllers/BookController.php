<?php

namespace App\Http\Controllers;

use App\Http\Requests\FilterBooksRequest;
use App\Http\Requests\SaveBookRequest;
use App\Models\Author;
use App\Models\Book;
use App\Models\Genre;
use App\Models\Publisher;
use App\Services\BookCatalog;
use App\Services\QrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class BookController extends Controller
{
    public function index(FilterBooksRequest $request, BookCatalog $catalog)
    {
        $filters = $request->validated();
        $books = $catalog->search($filters);

        return $request->expectsJson() ? $books : view('books.index', [
            ...$this->choices(),
            'books' => $books,
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        return view('books.form', [...$this->choices(), 'book' => new Book]);
    }

    public function edit(Book $book)
    {
        return view('books.form', [...$this->choices(), 'book' => $book->load(['authors', 'publishers'])]);
    }

    public function store(SaveBookRequest $request, BookCatalog $catalog)
    {
        $data = $request->validated();
        $copies = (int) ($data['copies'] ?? 1);
        $created = $catalog->createCopies($data, $copies);

        $message = $copies === 1 ? 'Book copy added.' : "{$copies} book copies added.";

        return $request->expectsJson()
            ? response()->json($copies === 1 ? $created->first() : $created, 201)
            : to_route('books.index')->with('status', $message);
    }

    public function update(SaveBookRequest $request, Book $book, BookCatalog $catalog)
    {
        $book = $catalog->save($book, $request->validated());

        return $request->expectsJson()
            ? response()->json($book)
            : to_route('books.index')->with('status', 'Book copy updated.');
    }

    public function qrSheet(Request $request, QrCodeService $qr)
    {
        $validated = $request->validate([
            'book_ids' => ['required', 'array', 'min:1'],
            'book_ids.*' => ['integer', 'distinct', 'exists:book,book_id'],
        ]);

        $books = Book::with('genre')
            ->whereIn('book_id', $validated['book_ids'])
            ->orderBy('book_title')
            ->orderBy('book_id')
            ->get();

        $items = $books->map(fn (Book $book) => [
            'title' => $book->book_title,
            'subtitle' => $book->genre?->genre_name,
            'code' => $book->book_qr_value,
            'footer' => $book->isbn ? 'ISBN: '.$book->isbn : null,
            'png' => $qr->getQrPngBase64($book->book_qr_value, 6),
        ])->all();

        return Pdf::loadView('pdf.qr-sheet', [
            'title' => 'Book QR Labels',
            'columns' => 3,
            'items' => $items,
        ])->setPaper('a4')->stream('book-qr-labels.pdf');
    }

    public function label(Book $book, QrCodeService $qr)
    {
        return view('books.label', ['book' => $book, 'qrSvg' => $qr->getQrSvg($book->book_qr_value)]);
    }

    public function png(Book $book, QrCodeService $qr)
    {
        $content = base64_decode($qr->getQrPngBase64($book->book_qr_value), true);
        abort_unless($content, 500, 'Could not generate the QR image.');

        return response($content)->header('Content-Type', 'image/png');
    }

    public function destroy(Request $request, Book $book, BookCatalog $catalog)
    {
        $catalog->archive($book);

        return $request->expectsJson()
            ? response()->noContent()
            : to_route('books.index')->with('status', 'Book copy archived.');
    }

    private function choices(): array
    {
        return [
            'genres' => Genre::orderBy('genre_name')->get(),
            'authors' => Author::orderBy('last_name')->orderBy('first_name')->get(),
            'publishers' => Publisher::orderBy('publisher_name')->get(),
            'statuses' => Book::STATUSES,
        ];
    }
}
