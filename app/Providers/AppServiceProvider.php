<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Brute-force protection: 3 login attempts per minute per login+IP (1-minute cooldown).
        RateLimiter::for('login', function (Request $request) {
            $key = Str::lower((string) $request->input('login')).'|'.$request->ip();

            return Limit::perMinute(3)->by($key)->response(fn () => back()
                ->withInput($request->only('login'))
                ->withErrors(['login' => 'Too many login attempts. Please try again in 1 minute.'])
                ->setStatusCode(429));
        });
    }
}
