<?php

namespace Webkul\Publication\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The pre-routing kill switch (Config/publication.php `enabled`) — the only
 * gate before a Publication row is resolved. Never resolves channel scope
 * from the request: the per-channel enabled check happens later, in the
 * controller, against $publication->channel->code, never an unvalidated
 * `?channel=` query param.
 *
 * Returns the 404 view directly rather than abort(): a thrown exception is
 * rendered by Laravel's Pipeline via the global handler at the throwing pipe,
 * bypassing this middleware entirely (see PublicationErrorBoundary).
 */
class EnsurePublicationEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! (bool) config('publication.enabled')) {
            return response()->view('publication::errors.404', [], 404);
        }

        return $next($request);
    }
}
