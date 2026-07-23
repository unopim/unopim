<?php

namespace Webkul\Publication\Http\Middleware;

use Closure;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * IMPORTANT — verified by execution against this codebase, not merely read
 * from the framework source: a try/catch wrapping `$next($request)` in a
 * middleware CANNOT intercept an exception thrown further down the same
 * route's pipeline. `Illuminate\Routing\Pipeline` (used for both the global
 * and the route-specific middleware stacks) wraps EVERY pipe — including the
 * controller itself — in its own try/catch via `carry()`/`prepareDestination()`,
 * and on failure immediately renders the exception through the application's
 * global ExceptionHandler and returns that as a normal value. bootstrap/app.php
 * registers unconditional NotFoundHttpException/ThrottleRequestsException
 * render callbacks (-> admin::errors.index, which computes the admin support
 * email) before this package ever boots, so they always win that race. An
 * outer middleware's own catch block, this one included, therefore never
 * actually receives the exception — confirmed with instrumented logging
 * during development: `$next()` returned a rendered 404 response, never threw.
 *
 * The real fix is structural, not this middleware: EnsurePublicationEnabled,
 * PublicationRateLimit and PublicationController all RETURN a Response
 * directly instead of calling abort()/throwing, so no exception is ever
 * raised on this route group's own success/failure paths for those classes.
 * This middleware is kept for the narrow case of a genuinely unanticipated
 * exception thrown by ITS OWN code (none currently) and as a stable extension
 * point/alias for Task 6 and beyond — do not rely on its catch clauses to
 * intercept exceptions thrown by anything invoked via $next().
 */
class PublicationErrorBoundary
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (NotFoundHttpException) {
            return response()->view('publication::errors.404', [], 404);
        } catch (ThrottleRequestsException $e) {
            return response()->view('publication::errors.429', [], 429, $e->getHeaders());
        }
    }
}
