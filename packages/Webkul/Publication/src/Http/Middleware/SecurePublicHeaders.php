<?php

namespace Webkul\Publication\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applied to every registered publication type's public route group — there
 * is no CSP anywhere else in this application to fall back on, and every
 * payload field is attacker-reachable text the moment a catalog owner can
 * type into any attribute the payload builder surfaces.
 *
 * A per-request nonce lets the page carry a single trusted inline script (the
 * locale filter) without opening `script-src` to `'self'` or `'unsafe-inline'`.
 * The nonce is generated before the view renders so the template can stamp it.
 */
class SecurePublicHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Str::random(24);

        View::share('cspNonce', $nonce);

        $response = $next($request);

        $response->headers->set('Content-Security-Policy', $this->policy($nonce));
        $response->headers->set('Referrer-Policy', 'no-referrer');

        return $response;
    }

    private function policy(string $nonce): string
    {
        return "default-src 'none'; img-src 'self' data:; style-src 'self' 'unsafe-inline'; "
            ."script-src 'nonce-{$nonce}'; base-uri 'none'; form-action 'none'; frame-ancestors 'none'";
    }
}
