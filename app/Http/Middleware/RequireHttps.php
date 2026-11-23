<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireHttps
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('app.require_https')) {
            return $next($request);
        }

        if (! $request->isSecure()) {
            abort(403, 'Use the HTTPS address to access the library.');
        }

        $response = $next($request);
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000');

        return $response;
    }
}
