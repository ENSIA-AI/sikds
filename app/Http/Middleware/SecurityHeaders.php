<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Generate the per-request CSP nonce BEFORE the view renders: @vite
        // stamps it on its tags automatically, and the few remaining inline
        // <script> blocks carry nonce="{{ Vite::cspNonce() }}".
        $nonce = Vite::useCspNonce();

        /** @var Response $response */
        $response = $next($request);

        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        // The app is never embedded: deny all framing (mirrors `frame-ancestors 'none'` in the CSP).
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');

        if (app()->isProduction()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        if (! $headers->has('Content-Security-Policy')) {
            $headers->set('Content-Security-Policy', $this->buildCsp($nonce));
        }

        return $response;
    }

    private function buildCsp(string $nonce): string
    {
        // Scripts: no 'unsafe-inline' — page logic ships as Vite modules
        // (resources/js/pages/*) and the few remaining inline blocks carry the
        // per-request nonce. 'unsafe-eval' must stay while the standard
        // Alpine.js build is used (it compiles x-* expressions with
        // new Function); swap to @alpinejs/csp to drop it.
        // Styles keep 'unsafe-inline': Blade views still use style="" and
        // <style> blocks (e.g. dynamic tag colors).
        $directives = [
            "default-src 'self'",
            "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval'",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com",
            "img-src 'self' data: blob: https:",
            "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
            "connect-src 'self'",
            "media-src 'self'",
            "object-src 'none'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        if (app()->environment('local')) {
            // Vite HMR + dev server: it serves scripts, stylesheets, fonts and
            // images from http://localhost:5173, so every fetching directive
            // needs the dev-server origins — not just script/connect.
            $directives[1] = "script-src 'self' 'nonce-{$nonce}' 'unsafe-eval' http://localhost:* http://127.0.0.1:*";
            $directives[2] = "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com http://localhost:* http://127.0.0.1:*";
            $directives[3] = "img-src 'self' data: blob: https: http://localhost:* http://127.0.0.1:*";
            $directives[4] = "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com http://localhost:* http://127.0.0.1:*";
            $directives[5] = "connect-src 'self' ws://localhost:* ws://127.0.0.1:* http://localhost:* http://127.0.0.1:*";
        }

        return implode('; ', $directives);
    }
}
