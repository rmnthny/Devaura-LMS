<?php

namespace App\Services;

use App\Mail\EmailOtpMail;
use App\Models\EmailOtp;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public const EXPIRY_MINUTES = 10;

    public function issue(User $user): EmailOtp
    {
        // Invalidate any prior unused codes for this user.
        $user->emailOtps()->whereNull('verified_at')->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = $user->emailOtps()->create([
            'code' => $code,
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ]);

        Mail::to($user->email)->send(new EmailOtpMail($user, $code, self::EXPIRY_MINUTES));

        return $otp;
    }

    public function verify(User $user, string $code): bool
    {
        $otp = $user->emailOtps()
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $otp || $otp->isExpired() || ! hash_equals($otp->code, trim($code))) {
            return false;
        }

        $otp->forceFill(['verified_at' => now()])->save();
        $user->forceFill(['email_verified_at' => now()])->save();

        return true;
    }
}
