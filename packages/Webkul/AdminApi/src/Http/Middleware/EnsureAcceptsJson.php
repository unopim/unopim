<?php

namespace Webkul\AdminApi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureAcceptsJson
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->header('Accept') !== 'application/json') {
            return response()->json(['error' => 'Accept header must be application/json'], 406);
        }

        return $next($request);
    }
}
