<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryAudit
{
    public function record(User $actor, string $qr, string $condition): Book
    {
        return DB::transaction(function () use ($actor, $qr, $condition): Book {
            $book = Book::where('book_qr_value', $qr)->lockForUpdate()->first();
            if (! $book) {
                throw ValidationException::withMessages(['book_qr_value' => 'No active book copy has this QR code.']);
            }

            if ($book->book_status === 'Borrowed' || $book->hasOpenLoan()) {
                throw ValidationException::withMessages(['book_qr_value' => 'Resolve the active loan through circulation before auditing this copy.']);
            }

            $previous = $book->book_status;
            $book->update(['book_status' => $condition]);

            // Record the check even when the book's condition stays the same.
            activity('inventory')->causedBy($actor)->performedOn($book)->event('checked')
                ->withProperties(['book_qr_value' => $book->book_qr_value, 'previous_status' => $previous, 'condition' => $condition])
                ->log('Checked a book copy during inventory');

            return $book;
        });
    }
}
