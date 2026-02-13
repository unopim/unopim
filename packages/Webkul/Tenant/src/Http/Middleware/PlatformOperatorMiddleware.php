<?php

namespace Webkul\Tenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlatformOperatorMiddleware
{
    /**
     * Only platform operators (tenant_id = null) may proceed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth('admin')->user();

        if (! $user || $user->tenant_id !== null) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Access denied. Platform operator privileges required.',
                ], 403);
            }

            abort(403, 'Access denied. Platform operator privileges required.');
        }

        return $next($request);
    }
}
