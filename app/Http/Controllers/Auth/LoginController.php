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
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($credentials['login']);
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $user = User::where($field, $login)->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['login' => 'Invalid username/email or password.']);
        }

        if (! $user->isActive() || $user->hasRole('visitor')) {
            abort(403, 'Account is inactive or locked.');
        }

        if ($user->email_verified_at === null) {
            $otpService->issue($user);
            $request->session()->put('otp_user_id', $user->id);

            return response()->json(['message' => 'Please verify your email with the 6-digit OTP we just sent.']);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        activity('auth')
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties(['ip' => $request->ip()])
            ->log('User logged in');

        return new UserResource($user);
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

        return response()->json(['message' => 'You have been logged out.']);
    }
}
