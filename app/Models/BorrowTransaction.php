<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'borrower_id', 'borrow_processed_by', 'return_processed_by', 'book_id',
    'borrow_date', 'due_date', 'return_date', 'transaction_status',
    'transaction_code', 'checkout_request_id',
])]
class BorrowTransaction extends Model
{
    use HasFactory;

    public const BORROWED = 'Borrowed';
    public const OVERDUE = 'Overdue';
    public const RETURNED = 'Returned';
    public const LOST = 'Lost';

    protected $table = 'borrow_transaction';
    protected $primaryKey = 'transaction_id';

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('return_date')
            ->whereIn('transaction_status', [self::BORROWED, self::OVERDUE]);
    }

    public function scopeOverdueOn(Builder $query, string $date): Builder
    {
        return $query->open()->where(fn (Builder $loan) => $loan
            ->where('transaction_status', self::OVERDUE)->orWhere('due_date', '<', $date));
    }

    public function isOverdueOn(string $date): bool
    {
        return $this->transaction_status === self::OVERDUE || $this->due_date->toDateString() < $date;
    }

    public function borrower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'borrower_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'book_id')->withTrashed();
    }

    protected function casts(): array
    {
        return [
            'borrow_date' => 'date',
            'due_date' => 'date',
            'return_date' => 'date',
        ];
    }
}
