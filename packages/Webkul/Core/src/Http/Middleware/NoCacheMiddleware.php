<?php

namespace Webkul\Core\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NoCacheMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        /**
         * Responses that deliberately opt into public caching keep their own
         * policy. Admin pages carry session-scoped data and must not be cached,
         * but static assets served through the app (the gravatar image proxy,
         * which a listing hits once per row) would otherwise be re-fetched on
         * every single page change.
         */
        if (str_contains((string) $response->headers->get('Cache-Control'), 'public')) {
            return $response;
        }

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}
