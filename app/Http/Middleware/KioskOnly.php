<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class KioskOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $kioskUrl = rtrim(config('attendance.kiosk_url'), '/').'/kiosk';
        $origin = parse_url($kioskUrl);
        $port = $origin['port'] ?? (($origin['scheme'] ?? '') === 'https' ? 443 : 80);
        $host = $origin['host'] ?? '';
        $onKioskOrigin = $host === $request->getHost() && $port === (int) $request->getPort();
        $onLocalKioskPort = in_array($host, ['localhost', '127.0.0.1', '[::1]'], true)
            && $port === (int) $request->server('SERVER_PORT');
        $isKioskPage = $request->is('kiosk');
        if (! $onKioskOrigin && ! $onLocalKioskPort && ! $isKioskPage) {
            return $next($request);
        }

        if (! $onKioskOrigin || ! $isKioskPage) {
            abort_unless($request->isMethodSafe(), 404);

            return redirect()->away($kioskUrl)->header('Cache-Control', 'no-store');
        }

        $cookie = config('session.cookie');
        $session = app('session')->driver();
        $name = $session->getName();
        config(['session.cookie' => $cookie.'_kiosk']);
        $session->setName($cookie.'_kiosk');
        try {
            $response = $next($request);
            $response->headers->removeCookie('XSRF-TOKEN', config('session.path'), config('session.domain'));
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('Referrer-Policy', 'no-referrer');
            $response->headers->set('Content-Security-Policy', "frame-ancestors 'none'; form-action 'self'; base-uri 'none'");

            return $response;
        } finally {
            config(['session.cookie' => $cookie]);
            $session->setName($name);
        }
    }
}
