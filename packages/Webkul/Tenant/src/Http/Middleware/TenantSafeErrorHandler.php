<?php

namespace Webkul\Tenant\Http\Middleware;

use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TenantSafeErrorHandler
{
    /**
     * Minimum response time in milliseconds to prevent timing attacks.
     */
    protected const TIMING_FLOOR_MS = 50;

    public function handle(Request $request, Closure $next)
    {
        $start = hrtime(true);

        try {
            $response = $next($request);
        } catch (ModelNotFoundException|NotFoundHttpException $e) {
            $this->enforceTimingFloor($start);

            return response()->json([
                'error'   => 'not_found',
                'message' => 'The requested resource was not found.',
            ], 404);
        } catch (AuthorizationException $e) {
            $this->enforceTimingFloor($start);

            return response()->json([
                'error'   => 'not_found',
                'message' => 'The requested resource was not found.',
            ], 404);
        }

        return $response;
    }

    /**
     * Enforce a minimum response time to prevent timing-based tenant enumeration.
     */
    protected function enforceTimingFloor(int $startNanos): void
    {
        $elapsed = (hrtime(true) - $startNanos) / 1_000_000; // Convert to ms

        if ($elapsed < self::TIMING_FLOOR_MS) {
            usleep((int) ((self::TIMING_FLOOR_MS - $elapsed) * 1_000));
        }
    }
}
