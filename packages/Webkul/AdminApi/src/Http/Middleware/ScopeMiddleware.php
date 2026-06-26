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
        if (
            auth()->guard('api')->check()
            && auth()->guard('api')->user()->apiKey->permission_type == 'all'
        ) {
            return true;
        } else {
            if (
                ! auth()->guard('api')->check()
                || ! auth()->guard('api')->user()->apiKey->hasPermission($permission)
            ) {
                return false;
            }
        }

        return true;
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
