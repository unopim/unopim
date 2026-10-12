<?php

namespace Webkul\AdminApi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Flags a route as deprecated per RFC 8594. Optionally advertises the
 * successor URL so clients can migrate off the legacy path.
 */
class DeprecatedRoute
{
    public function handle(Request $request, Closure $next, ?string $successor = null): Response
    {
        $response = $next($request);

        $response->headers->set('Deprecation', 'true');

        if ($successor !== null) {
            $response->headers->set('Link', '<'.$successor.'>; rel="successor-version"');
        }

        return $response;
    }
}
