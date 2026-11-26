<?php

namespace App\Models;

use Database\Factories\UserAccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['username', 'email', 'password_hash', 'account_status', 'reference_number', 'user_qr_value'])]
#[Hidden(['password_hash'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserAccountFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable, Prunable, SoftDeletes;

    public const STATUS_ACTIVE = 'Active';
    public const STATUS_INACTIVE = 'Inactive';
    public const STATUS_LOCKED = 'Locked';
    public const STATUS_PENDING = 'Pending';

    protected $table = 'user_account';
    protected $primaryKey = 'account_id';
    protected $authPasswordName = 'password_hash';
    protected $rememberTokenName = null;

    public static function findForLogin(string $login): ?self
    {
        if (strlen($login) > 254 || filter_var($login, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return static::where('email', $login)->first();
    }

    protected static function newFactory(): UserAccountFactory
    {
        return UserAccountFactory::new();
    }

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            $user->user_qr_value ??= (string) str()->uuid();
        });
    }

    public function assignMemberQr(): void
    {
        $code = sprintf('UBNHS-%d-%04d', now()->year, $this->getKey());
        $this->update(['user_qr_value' => $code]);
    }

    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user_account')
            ->logOnly(['username', 'account_status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function prunable(): Builder
    {
        return static::onlyTrashed()->where('deleted_at', '<=', now()->subMonthsNoOverflow(6))
            ->whereNotIn('account_id', fn ($query) => $query->select('account_id')->from('library_member'))
            ->whereNotIn('account_id', fn ($query) => $query->select('account_id')->from('library_personnel'))
            ->withoutLibraryHistory();
    }

    public function scopeWithoutLibraryHistory(Builder $query): Builder
    {
        return $query
            ->whereNotIn('account_id', fn ($query) => $query->select('account_id')->from('attendance_log')->whereNotNull('account_id'))
            ->whereNotIn('account_id', fn ($query) => $query->select('borrower_id')->from('borrow_transaction'))
            ->whereNotIn('account_id', fn ($query) => $query->select('borrow_processed_by')->from('borrow_transaction'))
            ->whereNotIn('account_id', fn ($query) => $query->select('return_processed_by')->from('borrow_transaction')->whereNotNull('return_processed_by'))
            ->when($this->getConnection()->getSchemaBuilder()->hasTable('inventory_audit'), fn (Builder $query) => $query
                ->whereNotIn('account_id', fn ($audits) => $audits->select('conducted_by')->from('inventory_audit')));
    }

    public function member(): HasOne
    {
        return $this->hasOne(LibraryMember::class, 'account_id');
    }

    public function personnel(): HasOne
    {
        return $this->hasOne(LibraryPersonnel::class, 'account_id');
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
