<?php

namespace Webkul\Publication\Http\Middleware;

use Closure;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A try/catch here cannot intercept exceptions thrown further down the
 * pipeline: Illuminate\Routing\Pipeline renders NotFoundHttpException /
 * ThrottleRequestsException via the global ExceptionHandler at the exact pipe
 * that threw, before this middleware's catch ever runs — bootstrap/app.php's
 * unconditional callback always wins that race. The real fix is structural:
 * EnsurePublicationEnabled, PublicationRateLimit and PublicationController all
 * return a Response directly instead of throwing. This middleware only
 * catches genuinely unanticipated exceptions from its own future code — do
 * not rely on it to intercept exceptions thrown via $next().
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
