<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function login(Request $request, OtpService $otpService)
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'email', 'max:254'],
            'password' => ['required', 'string'],
        ], [], ['login' => 'email address']);

        $user = User::findForLogin(trim($credentials['login']));

        if (! $user || ! Hash::check($credentials['password'], $user->password_hash)) {
            throw ValidationException::withMessages(['login' => 'Invalid sign-in details.']);
        }

        if ($user->hasRole('visitor') || (! $user->isActive() && $user->account_status !== User::STATUS_PENDING)) {
            abort(403, 'Account is inactive or locked.');
        }

        if ($user->account_status === User::STATUS_PENDING) {
            $otpService->issue($user);
            $request->session()->put('otp_user_id', $user->getKey());

            return $request->expectsJson()
                ? response()->json(['message' => 'Please verify your email with the 6-digit OTP we just sent.'])
                : to_route('otp.form')->with('status', 'Enter the code sent to your email.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        activity('auth')
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('User logged in');

        if ($request->expectsJson()) {
            return new UserResource($user);
        }

        return $user->can('books.view') ? redirect()->intended(route('books.index')) : to_route('profile.edit');
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            activity('auth')
                ->causedBy($user)
                ->performedOn($user)
                ->withProperties(['ip' => $request->ip()])
                ->log('User logged out');
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $request->expectsJson() ? response()->json(['message' => 'You have been logged out.']) : to_route('login');
    }
}
