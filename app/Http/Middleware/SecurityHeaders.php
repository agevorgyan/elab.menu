<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request and attach hardening security headers.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Enforce anti-framing / clickjacking defense
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Enforce MIME-sniffing prevention
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Legacy XSS filter activation
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Protect referrer leakage on outbound navigation
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Restrict sensitive browser APIs
        $response->headers->set('Permissions-Policy', 'camera=(self), microphone=(), geolocation=()');

        // Remove server tech disclosure headers
        if (function_exists('header_remove')) {
            @header_remove('X-Powered-By');
        }
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
