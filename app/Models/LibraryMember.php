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

#[Fillable([
    'account_id',
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
            ->logOnly(['account_id', 'last_name', 'first_name', 'middle_initial'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function prunable(): Builder
    {
        return static::onlyTrashed()->where('deleted_at', '<=', now()->subMonthsNoOverflow(6))
            ->whereHas('user', fn (Builder $query) => $query->withTrashed()->withoutLibraryHistory());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'account_id');
    }

    public function getDepartmentAttribute(): ?Department
    {
        return $this->user?->personnel?->department;
    }

    public function getDepartmentIdAttribute(): ?int
    {
        return $this->user?->personnel?->department_id;
    }

    public function getReferenceNumberAttribute(): ?string
    {
        return $this->user?->reference_number;
    }

    public function getMemberCodeAttribute(): ?string
    {
        return $this->user?->user_qr_value;
    }

    public function getMemberRoleAttribute(): string
    {
        return strlen((string) $this->reference_number) === 7
            ? self::ROLE_FACULTY
            : self::ROLE_STUDENT;
    }

    public function fullName(): string
    {
        $mi = $this->middle_initial ? ' '.rtrim($this->middle_initial, '.').'.' : '';

        return trim("{$this->first_name}{$mi} {$this->last_name}");
    }

}
