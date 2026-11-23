<?php

namespace App\Models;

use Database\Factories\LibraryPersonnelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

#[Fillable([
    'department_id',
    'account_id',
    'last_name',
    'first_name',
    'middle_initial',
])]
class LibraryPersonnel extends Model
{
    /** @use HasFactory<LibraryPersonnelFactory> */
    use HasFactory, LogsActivity, Prunable, SoftDeletes;

    protected $table = 'library_personnel';

    protected $primaryKey = 'personnel_id';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('library_personnel')
            ->logOnly(['department_id', 'last_name', 'first_name', 'middle_initial'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function prunable(): Builder
    {
        return static::onlyTrashed()->where('deleted_at', '<=', now()->subMonthsNoOverflow(6))
            ->whereDoesntHave('schedules')
            ->whereHas('user', fn (Builder $query) => $query->withTrashed()->withoutLibraryHistory());
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(PersonnelSchedule::class, 'personnel_id');
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
}
