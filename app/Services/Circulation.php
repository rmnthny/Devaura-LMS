<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BorrowTransaction;
use App\Models\LibraryMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Circulation
{
    public const LOAN_DAYS = 3;

    public function __construct(private LibraryAlerts $alerts) {}

    public function borrow(User $processor, array $data): BorrowTransaction
    {
        $borrowerField = isset($data['borrower_account_id']) ? 'borrower_account_id' : 'borrower_identifier';

        $result = retry(5, fn () => DB::transaction(function () use ($processor, $data, $borrowerField): ?BorrowTransaction {
            $book = $this->copies($data)->lockForUpdate()->first();
            if (! $book) {
                $this->fail($this->copyField($data), 'No book copy matches the QR value or selected record.');
            }

            $borrower = $this->borrower($data, $borrowerField);
            if ($borrower->is($processor)) {
                $this->fail($borrowerField, 'Another staff account must process your checkout.');
            }

            $existing = BorrowTransaction::where('checkout_request_id', $data['request_id'])->first();
            if ($existing) {
                if ($existing->borrower_id === $borrower->getKey() && $existing->book_id === $book->getKey()
                    && $existing->borrow_processed_by === $processor->getKey()) {
                    return $existing;
                }
                $this->fail('request_id', 'This checkout request was already used. Open a new checkout form.');
            }

            if ($book->trashed() || $book->book_status !== 'Available'
                || BorrowTransaction::open()->where('book_id', $book->getKey())->lockForUpdate()->first(['transaction_id'])) {
                $this->fail($this->copyField($data), 'This copy is unavailable for borrowing.');
            }

            $today = now(config('attendance.timezone'));
            $overdue = BorrowTransaction::where('borrower_id', $borrower->getKey())->overdueOn($today->toDateString())
                ->orderBy('due_date')->orderBy('transaction_id')->lockForUpdate()->first();
            if ($overdue) {
                $this->alerts->loan($borrower, $overdue, 'loan.overdue');

                return null;
            }

            $loan = BorrowTransaction::create([
                'borrower_id' => $borrower->getKey(),
                'borrow_processed_by' => $processor->getKey(),
                'book_id' => $book->getKey(),
                'borrow_date' => $today->toDateString(),
                'due_date' => $today->copy()->addDays(self::LOAN_DAYS)->toDateString(),
                'transaction_status' => BorrowTransaction::BORROWED,
                'transaction_code' => 'TRX-'.$today->format('Ymd').'-'.Str::upper(Str::random(4)),
                'checkout_request_id' => $data['request_id'],
            ]);
            $book->update(['book_status' => 'Borrowed']);
            $loan->setRelation('book', $book);
            $this->log($loan, $processor, 'checked_out');
            $this->alerts->loan($borrower, $loan, 'loan.pending');

            return $loan;
        }, 3), 0, function (\Throwable $error): bool {
            return $error instanceof UniqueConstraintViolationException
                && (str_contains($error->getMessage(), 'borrow_transaction_transaction_code_unique')
                    || str_contains($error->getMessage(), 'borrow_transaction_checkout_request_id_unique'));
        });

        return $result ?? $this->fail($borrowerField, 'This member has an overdue book. Process the return before checking out another copy.');
    }

    public function checkIn(User $processor, array $data): BorrowTransaction
    {
        return DB::transaction(function () use ($processor, $data): BorrowTransaction {
            $book = $this->copies($data)->lockForUpdate()->first();
            if (! $book) {
                $this->fail($this->copyField($data), 'No book copy matches the QR value or selected record.');
            }

            $loan = BorrowTransaction::where('book_id', $book->getKey())
                ->where('transaction_code', $data['transaction_code'])->lockForUpdate()->first();
            if (! $loan) {
                $this->fail('transaction_code', 'This checkout reference does not belong to the scanned copy.');
            }
            if ($loan->borrower_id === $processor->getKey()) {
                $this->fail('transaction_code', 'Another staff account must process your return.');
            }
            if (in_array($loan->transaction_status, [BorrowTransaction::RETURNED, BorrowTransaction::LOST], true)) {
                if (($loan->transaction_status === BorrowTransaction::LOST) !== ($data['condition'] === 'Lost')) {
                    $this->fail('condition', 'This borrowing record was already closed with a different outcome.');
                }

                return $loan;
            }
            if ($loan->return_date || ! in_array($loan->transaction_status, [BorrowTransaction::BORROWED, BorrowTransaction::OVERDUE], true)) {
                $this->fail('transaction_code', 'This loan is already closed.');
            }

            $today = now(config('attendance.timezone'))->toDateString();
            $wasOverdue = $loan->isOverdueOn($today);
            $lost = $data['condition'] === 'Lost';
            $loan->update($lost ? ['transaction_status' => BorrowTransaction::LOST] : [
                'return_date' => $today,
                'return_processed_by' => $processor->getKey(),
                'transaction_status' => BorrowTransaction::RETURNED,
            ]);
            $anotherOpenLoan = BorrowTransaction::open()->where('book_id', $book->getKey())
                ->lockForUpdate()->first(['transaction_id']);
            $book->update(['book_status' => $anotherOpenLoan ? 'Borrowed' : $data['condition']]);
            $this->log($loan, $processor, $lost ? 'lost' : 'returned', [
                'was_overdue' => $wasOverdue,
                'copy_status' => $book->book_status,
            ]);

            return $loan;
        }, 3);
    }

    private function copies(array $data): Builder
    {
        $field = $this->copyField($data);

        return Book::withTrashed()->where($field, $data[$field]);
    }

    private function copyField(array $data): string
    {
        return isset($data['book_id']) ? 'book_id' : 'book_qr_value';
    }

    private function borrower(array $data, string $field): User
    {
        $accountId = $data['borrower_account_id'] ?? null;
        $identifier = $data['borrower_identifier'] ?? null;
        if ($accountId === null) {
            $matches = User::where('reference_number', $identifier)->orWhere('user_qr_value', $identifier)
                ->limit(2)->pluck('account_id');
            if ($matches->count() !== 1) {
                $this->fail($field, 'Enter a valid library member reference number or QR value.');
            }
            $accountId = $matches->first();
        }

        // Use the same profile-before-account lock order as member archiving.
        $member = LibraryMember::where('account_id', $accountId)->lockForUpdate()->first();
        $user = User::lockForUpdate()->find($accountId);
        if (! $member || ! $user?->isActive() || ! $user->can('borrow.self')
            || ($identifier !== null && $user->reference_number !== $identifier
                && strcasecmp($user->user_qr_value ?? '', $identifier) !== 0)) {
            $this->fail($field, 'Only active library members can check out a book.');
        }

        return $user;
    }

    private function log(BorrowTransaction $loan, User $processor, string $event, array $details = []): void
    {
        activity('circulation')->performedOn($loan)->causedBy($processor)->event($event)
            ->withProperties(['reference' => $loan->transaction_code, 'book_id' => $loan->book_id] + $details)
            ->log(match ($event) {
                'returned' => 'Returned a library book',
                'lost' => 'Reported a library book lost',
                default => 'Checked out a library book',
            });
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
