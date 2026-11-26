<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffAppIsAvailable
{
    public function handle(Request $request, Closure $next): Response
    {
        $healthUrl = rtrim(config('app.url'), '/').'/up';

        try {
            $available = Http::connectTimeout(1)->timeout(2)->withoutRedirecting()->get($healthUrl)->ok();
        } catch (ConnectionException) {
            $available = false;
        }

        if ($available) {
            return $next($request);
        }

        return $request->expectsJson()
            ? response()->json(['message' => 'Public attendance is temporarily unavailable.'], 503)
            : response()->view('attendance.kiosk', ['unavailable' => true], 503);
    }
}
