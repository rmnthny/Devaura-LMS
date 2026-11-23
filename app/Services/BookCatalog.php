<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookCatalog
{
    public function search(array $filters): LengthAwarePaginator
    {
        return Book::with(['genre', 'authors', 'publishers'])->withCount('copies')
            ->when($filters['title'] ?? null, fn ($query, $title) => $query->where('book_title', 'like', "%{$title}%"))
            ->when($filters['isbn'] ?? null, fn ($query, $isbn) => $query->where('isbn', 'like', "%{$isbn}%"))
            ->when($filters['qr'] ?? null, fn ($query, $qr) => $query->where('book_qr_value', 'like', "%{$qr}%"))
            ->when($filters['genre_id'] ?? null, fn ($query, $id) => $query->where('genre_id', $id))
            ->when($filters['author_id'] ?? null, fn ($query, $id) => $query->whereHas('authors', fn ($authors) => $authors->whereKey($id)))
            ->when($filters['book_status'] ?? null, fn ($query, $status) => $query->where('book_status', $status))
            ->orderBy('book_title')->orderBy('book_id')->paginate(20)->withQueryString();
    }

    /** @return Collection<int, Book> */
    public function createCopies(array $data, int $copies = 1): Collection
    {
        $payload = Arr::except($data, ['copies']);

        return DB::transaction(function () use ($payload, $copies): Collection {
            $created = collect();
            for ($i = 0; $i < $copies; $i++) {
                $created->push($this->save(new Book, $payload));
            }

            return $created;
        });
    }

    public function save(Book $book, array $data): Book
    {
        return retry(5, function () use ($book, $data): Book {
            return DB::transaction(function () use ($book, $data): Book {
                if ($book->exists) {
                    $book = Book::lockForUpdate()->findOrFail($book->getKey());
                    if ($data['book_status'] !== 'Borrowed' && $book->hasOpenLoan()) {
                        throw ValidationException::withMessages(['book_status' => 'Resolve the active loan through circulation before changing this status.']);
                    }
                }

                $book->fill(Arr::except($data, ['author_ids', 'publisher_ids']))->save();
                $book->authors()->sync($data['author_ids'] ?? []);
                $book->publishers()->sync($data['publisher_ids'] ?? []);

                return $book->load(['genre', 'authors', 'publishers']);
            });
        }, 0, function (\Throwable $error) use ($book): bool {
            if (! $book->exists && $error instanceof UniqueConstraintViolationException) {
                $book->book_qr_value = null;

                return true;
            }

            return false;
        });
    }

    public function archive(Book $book): void
    {
        DB::transaction(function () use ($book): void {
            $book = Book::lockForUpdate()->findOrFail($book->getKey());
            if ($book->book_status === 'Borrowed' || $book->hasOpenLoan()) {
                throw ValidationException::withMessages(['book' => 'Resolve the active loan before archiving this copy.']);
            }

            $book->delete();
        });
    }
}
