<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\LibraryMember;
use App\Models\User;
use App\Services\OtpService;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OtpVerificationController extends Controller
{
    public function verify(Request $request, OtpService $otpService, QrCodeService $qrService)
    {
        $user = $this->pendingUser($request);

        if (! $user || ! $user->isActive() || $user->hasRole('visitor') || $user->email_verified_at !== null) {
            throw ValidationException::withMessages(['login' => 'Verification session expired. Please log in again.']);
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        if (! $otpService->verify($user, $validated['code'])) {
            throw ValidationException::withMessages(['code' => 'Invalid or expired 6-digit OTP code.']);
        }

        // Auto-generate member_code (UBNHS-YYYY-XXXX) and Member QR PNG upon verification.
        DB::transaction(function () use ($user, $qrService): void {
            $member = $user->member;

            if ($member && ! $member->member_code) {
                $code = LibraryMember::generateMemberCode();
                $member->forceFill(['member_code' => $code])->save();
                $user->forceFill(['user_qr_value' => $code])->save();
                $qrService->generateMemberQrPng($code);
            } elseif (! $user->user_qr_value) {
                $code = 'UBNHS-USR-'.str_pad((string) $user->id, 4, '0', STR_PAD_LEFT);
                $user->forceFill(['user_qr_value' => $code])->save();
                $qrService->generateMemberQrPng($code);
            }
        });

        $request->session()->forget('otp_user_id');
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['message' => 'Email verified! Your Member QR code is now ready.']);
    }

    public function resend(Request $request, OtpService $otpService)
    {
        $user = $this->pendingUser($request);

        if (! $user || ! $user->isActive() || $user->hasRole('visitor') || $user->email_verified_at !== null) {
            abort(422, 'Verification session expired.');
        }

        $otpService->issue($user);

        return response()->json(['message' => 'A new 6-digit verification code has been sent to '.$user->email.'.']);
    }

    private function pendingUser(Request $request): ?User
    {
        $userId = $request->session()->get('otp_user_id');

        return $userId ? User::find($userId) : null;
    }
}
