<?php

namespace App\Services;

use App\Mail\EmailOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public const EXPIRY_MINUTES = 10;

    public function issue(User $user): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $key = "email_otp:{$user->getKey()}";

        Cache::lock("$key:lock", 10)->block(5, function () use ($user, $key, $code): void {
            Mail::to($user->email)->send(new EmailOtpMail($user, $code, self::EXPIRY_MINUTES));
            Cache::put($key, $this->digest($user, $code), now()->addMinutes(self::EXPIRY_MINUTES));
        });
    }

    public function verify(User $user, string $code): bool
    {
        $key = "email_otp:{$user->getKey()}";

        return Cache::lock("$key:lock", 10)->block(5, function () use ($user, $key, $code): bool {
            $digest = Cache::get($key);
            if (! is_string($digest) || ! hash_equals($digest, $this->digest($user, $code))) {
                return false;
            }

            $activated = User::whereKey($user->getKey())
                ->where('account_status', User::STATUS_PENDING)
                ->where('email', $user->email)
                ->update(['account_status' => User::STATUS_ACTIVE]);
            Cache::forget($key);

            return $activated === 1;
        });
    }

    private function digest(User $user, string $code): string
    {
        return hash_hmac('sha256', $user->getKey().'|'.$user->email.'|'.$code, config('app.key'));
    }
}
