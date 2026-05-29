<?php

namespace Webkul\AdminApi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class ScopeMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->getAclForCurrentRoute() && ! $this->hasPermission($this->getAclForCurrentRoute())) {
            return response()->json(['error' => 'This action is unauthorized'], 403);
        }

        return $next($request);
    }

    /**
     * Checks if user allowed or not for certain action
     */
    public function hasPermission(string $permission): bool
    {
        if (auth()->guard('api')->check()
        && auth()->guard('api')->user()->apiKey->permission_type == 'all') {
            return true;
        } elseif (! auth()->guard('api')->check()
        || ! auth()->guard('api')->user()->apiKey->hasPermission($permission)) {
            return false;
        }

        return true;
    }

    /**
     * Get current route.
     */
    public function getAclForCurrentRoute(): ?string
    {
        $acl = app('api-acl');

        if (! $acl) {
            return null;
        }

        return $acl->roles[str_replace('.get', '.index', Route::currentRouteName())] ?? null;
    }
}
