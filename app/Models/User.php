<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/** ERD: user_account — centralized login credentials for members and personnel. */
#[Fillable(['username', 'email', 'password', 'account_status', 'email_verified_at', 'user_qr_value'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable, Prunable, SoftDeletes;

    public const STATUS_ACTIVE = 'Active';
    public const STATUS_INACTIVE = 'Inactive';
    public const STATUS_LOCKED = 'Locked';

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user_account')
            ->logOnly(['username', 'email', 'account_status', 'user_qr_value'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function prunable(): Builder
    {
        return static::onlyTrashed()->where('deleted_at', '<=', now()->subMonths(6));
    }

    public function member(): HasOne
    {
        return $this->hasOne(LibraryMember::class, 'account_id');
    }

    public function personnel(): HasOne
    {
        return $this->hasOne(LibraryPersonnel::class, 'account_id');
    }

    public function emailOtps(): HasMany
    {
        return $this->hasMany(EmailOtp::class);
    }

    public function isActive(): bool
    {
        return $this->account_status === self::STATUS_ACTIVE;
    }

    public function fullName(): string
    {
        return $this->personnel?->fullName()
            ?? $this->member?->fullName()
            ?? $this->username;
    }
}
