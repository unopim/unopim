<?php

namespace Webkul\Publication\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The pre-routing kill switch — see Config/publication.php `enabled`. This is
 * deliberately the ONLY gate that runs before a Publication row is resolved,
 * and it never resolves channel scope from the request: a per-channel
 * `general.publication.settings.enabled` check (Task 7) happens later, inside
 * the controller, against $publication->channel->code — never against an
 * unvalidated `?channel=` query parameter.
 *
 * Returns the 404 view directly rather than calling abort(): a thrown
 * NotFoundHttpException is rendered by Illuminate\Routing\Pipeline via the
 * application's global ExceptionHandler at the exact pipe that threw it, and
 * bootstrap/app.php's own unconditional NotFoundHttpException callback
 * (admin::errors.index, which computes the admin support email) always wins
 * that race — no wrapping middleware, however early in the stack, can
 * intercept a downstream throw ahead of it. See PublicationErrorBoundary's
 * doc comment for the full explanation.
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
