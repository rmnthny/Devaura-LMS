<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\BorrowTransaction;
use App\Models\UserAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BorrowTransaction> */
class BorrowTransactionFactory extends Factory
{
    protected $model = BorrowTransaction::class;

    public function configure(): static
    {
        return $this->afterCreating(function (BorrowTransaction $transaction): void {
            // Keep the test book's status aligned with the transaction.
            $status = match ($transaction->transaction_status) {
                'Returned' => 'Available',
                'Lost' => 'Lost',
                default => 'Borrowed',
            };
            Book::query()->whereKey($transaction->book_id)->update(['book_status' => $status]);
        });
    }

    public function definition(): array
    {
        return [
            // Separate accounts, so the no-self-borrow CHECK passes.
            // Both have actual member/personnel profiles created via callbacks.
            'borrower_id' => UserAccount::factory()->withMemberProfile('student'),
            'borrow_processed_by' => UserAccount::factory()->withPersonnelProfile('staff'),
            'return_processed_by' => null,
            'book_id' => Book::factory(),
            'borrow_date' => today()->toDateString(),
            'return_date' => null,
            'due_date' => today()->addDays(3)->toDateString(),
            'transaction_status' => 'Borrowed',
            // Test-only code generator. Production must ensure collision-free codes.
            'transaction_code' => 'TRX-'.today()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function facultyBorrower(): static
    {
        return $this->state(fn (): array => [
            'borrower_id' => UserAccount::factory()->withMemberProfile('faculty'),
        ]);
    }

    public function personnelBorrower(): static
    {
        return $this->state(fn (): array => [
            'borrower_id' => UserAccount::factory()->withPersonnelProfile('admin'),
        ]);
    }

    public function returned(): static
    {
        return $this->state(fn (): array => [
            'return_processed_by' => UserAccount::factory()->withPersonnelProfile('staff'),
            'return_date' => today()->toDateString(),
            'transaction_status' => 'Returned',
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn (): array => [
            'borrow_date' => today()->subDays(5)->toDateString(),
            'due_date' => today()->subDays(2)->toDateString(),
            'return_date' => null,
            'return_processed_by' => null,
            'transaction_status' => 'Overdue',
        ]);
    }

    public function lost(): static
    {
        return $this->state(fn (): array => [
            'transaction_status' => 'Lost',
        ]);
    }
}
