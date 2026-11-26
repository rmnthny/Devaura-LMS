<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BorrowTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CirculationLookup
{
    public function members(User $processor, string $term): Collection
    {
        return $this->eligibleMembers($processor)
            ->where(fn (Builder $accounts) => $this->matchMember($accounts, $term))
            ->orderBy('account_id')->limit(12)->get();
    }

    public function member(User $processor, int $id): ?User
    {
        return $this->eligibleMembers($processor)->find($id);
    }

    public function books(string $term): Collection
    {
        return $this->availableBooks()
            ->where(fn (Builder $books) => $this->matchBook($books, $term))
            ->orderBy('book_title')->orderBy('book_id')->limit(12)->get();
    }

    public function book(int $id): ?Book
    {
        return $this->availableBooks()->find($id);
    }

    public function openLoan(array $filters): BorrowTransaction
    {
        $field = isset($filters['transaction_code']) ? 'transaction_code' : (isset($filters['book_id']) ? 'book_id' : 'book_qr_value');
        $query = $this->openLoans();
        if ($field === 'transaction_code') {
            $query->where($field, $filters[$field]);
        } elseif ($field === 'book_id') {
            $query->where($field, $filters[$field]);
        } else {
            $query->whereHas('book', fn (Builder $books) => $books->where($field, $filters[$field]));
        }

        $matches = $query->limit(2)->get();
        if ($matches->count() === 1) {
            return $matches->first();
        }

        throw ValidationException::withMessages([$field => $matches->isEmpty()
            ? 'No open loan matches that QR value or transaction reference.'
            : 'More than one open loan matches this copy. Search and select the original transaction.']);
    }

    public function loans(string $term): Collection
    {
        return $this->openLoans()
            ->where(fn (Builder $loans) => $loans
                ->where('transaction_code', 'like', $this->contains($term))
                ->orWhereHas('book', fn (Builder $books) => $this->matchBook($books, $term))
                ->orWhereHas('borrower', fn (Builder $accounts) => $this->matchMember($accounts, $term)))
            ->orderBy('due_date')->orderBy('transaction_id')->limit(12)->get();
    }

    private function openLoans(): Builder
    {
        return BorrowTransaction::open()->with(['book', 'borrower.member', 'borrower.personnel']);
    }

    private function availableBooks(): Builder
    {
        return Book::where('book_status', 'Available')
            ->whereNotExists(BorrowTransaction::open()
                ->whereColumn('borrow_transaction.book_id', 'book.book_id')
                ->selectRaw('1')->toBase());
    }

    private function eligibleMembers(User $processor): Builder
    {
        return User::permission('borrow.self')->where('account_status', User::STATUS_ACTIVE)
            ->whereKeyNot($processor->getKey())->whereHas('member')->with('member');
    }

    private function matchMember(Builder $query, string $term): Builder
    {
        return $query->where('reference_number', $term)
            ->orWhereHas('member', function (Builder $members) use ($term): void {
                foreach (preg_split('/\s+/u', $term, -1, PREG_SPLIT_NO_EMPTY) as $word) {
                    $pattern = $this->contains($word);
                    $members->where(fn (Builder $name) => $name
                        ->where('first_name', 'like', $pattern)->orWhere('last_name', 'like', $pattern));
                }
            });
    }

    private function matchBook(Builder $query, string $term): Builder
    {
        return $query->where('book_title', 'like', $this->contains($term))
            ->orWhere('isbn', $term)
            ->orWhere('book_qr_value', $term)
            ->when(ctype_digit($term), fn (Builder $books) => $books->orWhere('book_id', $term));
    }

    private function contains(string $term): string
    {
        return '%'.addcslashes($term, '\\%_').'%';
    }
}
