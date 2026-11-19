<?php

namespace App\Models;

use Database\Factories\LibraryMemberFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/** ERD: library_member — Student and Faculty profiles linked to user_account. */
#[Fillable([
    'account_id',
    'department_id',
    'member_role',
    'reference_number',
    'member_code',
    'last_name',
    'first_name',
    'middle_initial',
])]
class LibraryMember extends Model
{
    /** @use HasFactory<LibraryMemberFactory> */
    use HasFactory, LogsActivity, Prunable, SoftDeletes;

    protected $table = 'library_member';
    protected $primaryKey = 'member_id';

    public const ROLE_STUDENT = 'Student';
    public const ROLE_FACULTY = 'Faculty';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('library_member')
            ->logOnly(['member_role', 'reference_number', 'member_code', 'last_name', 'first_name', 'middle_initial'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function prunable(): Builder
    {
        return static::onlyTrashed()->where('deleted_at', '<=', now()->subMonths(6));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_id');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id', 'department_id');
    }

    public function fullName(): string
    {
        $mi = $this->middle_initial ? ' '.rtrim($this->middle_initial, '.').'.' : '';

        return trim("{$this->first_name}{$mi} {$this->last_name}");
    }

    /** Generates the next sequential UBNHS-YYYY-XXXX code (counting archived rows too). */
    public static function generateMemberCode(?int $year = null): string
    {
        $prefix = 'UBNHS-'.($year ?? now()->year).'-';

        $latest = static::withTrashed()
            ->where('member_code', 'like', $prefix.'%')
            ->orderByDesc('member_code')
            ->value('member_code');

        $next = $latest ? ((int) substr($latest, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
