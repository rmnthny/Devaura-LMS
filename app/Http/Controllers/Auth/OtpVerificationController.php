<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\MemberQrMail;
use App\Models\User;
use App\Services\OtpService;
use App\Services\QrCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OtpVerificationController extends Controller
{
    public function verify(Request $request, OtpService $otpService, QrCodeService $qrService)
    {
        $user = $this->pendingUser($request);

        if (! $user || $user->account_status !== User::STATUS_PENDING || $user->hasRole('visitor')) {
            throw ValidationException::withMessages(['login' => 'Verification session expired. Please log in again.']);
        }

        $validated = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        if (! $otpService->verify($user, (string) $validated['code'])) {
            throw ValidationException::withMessages(['code' => 'Invalid or expired 6-digit OTP code.']);
        }

        $user->refresh();
        try {
            $path = $qrService->generateMemberQrPng($user->user_qr_value);
            $png = Storage::disk('local')->get($path);
            if ($png !== null) {
                Mail::to($user->email)->send(new MemberQrMail($user->user_qr_value, $png));
            }
        } catch (\Throwable $error) {
            // The member can still sign in and retrieve their QR card after a mail failure.
            report($error);
        }

        $request->session()->forget(['otp_user_id', 'otp_resend_available_at']);
        Auth::login($user);
        $request->session()->regenerate();

        return $request->expectsJson()
            ? response()->json(['message' => 'Email verified! Your Member QR code is now ready.'])
            : redirect()->intended(route('books.index'));
    }

    public function resend(Request $request, OtpService $otpService)
    {
        $user = $this->pendingUser($request);

        if (! $user || $user->account_status !== User::STATUS_PENDING || $user->hasRole('visitor')) {
            abort(422, 'Verification session expired.');
        }

        $otpService->issue($user);
        $request->session()->put('otp_resend_available_at', now()->addMinute()->timestamp);

        return $request->expectsJson()
            ? response()->json(['message' => 'A new 6-digit verification code has been sent to '.$user->email.'.'])
            : to_route('otp.form')->with('status', 'A new code was sent to your email.');
    }

    private function pendingUser(Request $request): ?User
    {
        $userId = $request->session()->get('otp_user_id');

        return $userId ? User::find($userId) : null;
    }
}
