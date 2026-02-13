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
        if ($this->getAclForCurrentRoute() && ! $this->hasPermission($this->getAclForCurrentRoute())) {
            return response()->json(['error' => 'This action is unauthorized'], 403);
        }

        return $next($request);
    }

    /**
     * Checks if user allowed or not for certain action
     *
     * @param  string  $permission
     * @return void
     */
    public function hasPermission($permission)
    {
        if (! auth()->guard('api')->check()) {
            return false;
        }

        $user = auth()->guard('api')->user();

        if ($user->apiKey->permission_type == 'all') {
            // Tenant API users with "all" still cannot access platform-reserved permissions (Story 5.5)
            $guard = app(\Webkul\Tenant\Auth\TenantPermissionGuard::class);

            return $guard->isAllowed($user, $permission);
        }

        return $user->apiKey->hasPermission($permission);
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

        return $acl->roles[str_replace('.get', '.index', Route::currentRouteName())] ?? null;
    }
}
