<?php

namespace Webkul\AdminApi\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class ScopeMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $acl = $this->getAclForCurrentRoute();

        if (! $acl || ! $this->hasPermission($acl)) {
            return response()->json(['error' => 'This action is unauthorized'], 403);
        }

        return $next($request);
    }

    /**
     * Checks if user allowed or not for certain action
     *
     * @param  string  $permission
     */
    public function hasPermission($permission): bool
    {
        $user = auth()->guard('api')->check() ? auth()->guard('api')->user() : null;

        if (! $user || ! $user->apiKey) {
            return false;
        }

        if ($user->apiKey->revoked || ! $user->status) {
            return false;
        }

        if ($user->apiKey->permission_type == 'all') {
            return true;
        }

        return (bool) $user->apiKey->hasPermission($permission);
    }

    /**
     * Get current route.
     *
     * @return string|null
     */
    public function getAclForCurrentRoute()
    {
        $acl = app('api-acl');

        if (! $acl) {
            return;
        }

        $routeName = Route::currentRouteName();

        return $acl->roles[$routeName]
            ?? $acl->roles[str_replace('.get', '.index', $routeName)]
            ?? null;
    }
}
