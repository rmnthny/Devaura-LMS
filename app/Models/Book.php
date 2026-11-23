<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable(['genre_id', 'isbn', 'book_title', 'book_status', 'date_published'])]
class Book extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    use Prunable {
        prune as private pruneRecord;
    }

    public const STATUSES = ['Available', 'Borrowed', 'Lost', 'Damaged'];
    public const AUDIT_CONDITIONS = ['Available', 'Lost', 'Damaged'];

    protected $table = 'book';
    protected $primaryKey = 'book_id';
    protected $attributes = ['book_status' => 'Available'];

    protected static function booted(): void
    {
        static::creating(function (self $book): void {
            if ($book->book_qr_value !== null) {
                return;
            }

            do {
                $token = Str::upper(Str::random(6));
                $value = 'BK-'.substr($token, 0, 4).'-'.substr($token, 4);
            } while (static::withTrashed()->where('book_qr_value', $value)->exists());

            $book->book_qr_value = $value;
        });
    }

    protected function casts(): array
    {
        return ['date_published' => 'date'];
    }

    public function genre(): BelongsTo
    {
        return $this->belongsTo(Genre::class, 'genre_id');
    }

    public function copies(): HasMany
    {
        return $this->hasMany(self::class, 'book_title', 'book_title');
    }

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'book_author', 'book_id', 'author_id');
    }

    public function publishers(): BelongsToMany
    {
        return $this->belongsToMany(Publisher::class, 'book_publisher', 'book_id', 'publisher_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->useLogName('book')
            ->logOnly(['genre_id', 'isbn', 'book_title', 'book_status', 'book_qr_value', 'date_published'])
            ->logOnlyDirty()->dontSubmitEmptyLogs();
    }

    public function prunable(): Builder
    {
        // The circulation history still needs its original book record.
        return static::onlyTrashed()
            ->where('deleted_at', '<=', now()->subMonthsNoOverflow(6))
            ->whereNotIn('book_id', fn ($query) => $query->select('book_id')->from('borrow_transaction'));
    }

    public function hasOpenLoan(): bool
    {
        return $this->getConnection()->table('borrow_transaction')
            ->where('book_id', $this->getKey())
            ->whereIn('transaction_status', ['Borrowed', 'Overdue'])
            ->exists();
    }

    public function prune()
    {
        return $this->getConnection()->transaction(function () {
            $this->authors()->detach();
            $this->publishers()->detach();

            return $this->pruneRecord();
        });
    }
}
