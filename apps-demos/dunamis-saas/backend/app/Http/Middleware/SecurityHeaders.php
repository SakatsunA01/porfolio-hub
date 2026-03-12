<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = config('security_headers', []);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', $headers['x_frame_options'] ?? 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', $headers['referrer_policy'] ?? 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', $headers['permissions_policy'] ?? 'geolocation=(), microphone=(), camera=()');

        $csp = $headers['csp'] ?? "default-src 'self'";
        $response->headers->set('Content-Security-Policy-Report-Only', $csp);

        if ($request->isSecure() && app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', $headers['hsts'] ?? 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
