<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        config(['activitylog.activity_model' => \App\Models\Activity::class]);
    }

    public function boot(): void
    {
        // Count email case variants against one account's three attempts.
        RateLimiter::for('login', function (Request $request) {
            $input = $request->input('login');
            $login = is_string($input) ? trim($input) : '';
            $account = User::findForLogin($login);
            $key = $account ? 'account:'.$account->getKey() : 'identifier:'.Str::lower($login);

            return Limit::perMinute(3)->by($key.'|'.$request->ip())->response(function (Request $request, array $headers) {
                $message = 'Too many login attempts. Please try again in one minute.';

                return $request->expectsJson()
                    ? response()->json(['message' => $message], 429, $headers)
                    : response()->view('auth.login', ['rateLimitMessage' => $message], 429, $headers);
            });
        });

        RateLimiter::for('otp-verify', fn (Request $request) => Limit::perMinute(6)
            ->by($request->session()->has('otp_user_id') ? 'account:'.$request->session()->get('otp_user_id') : $request->ip()));
        RateLimiter::for('otp-resend', fn (Request $request) => Limit::perMinute(1)
            ->by($request->session()->has('otp_user_id') ? 'account:'.$request->session()->get('otp_user_id') : $request->ip())
            ->response(function (Request $request, array $headers) {
                $retryAfter = (int) ($headers['Retry-After'] ?? 60);
                $message = "Please wait {$retryAfter} seconds before requesting a new code.";

                return $request->expectsJson()
                    ? response()->json(['message' => $message], 429, $headers)
                    : to_route('otp.form')->withErrors(['code' => $message]);
            }));
    }
}
