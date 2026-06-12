<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateAdminFromApiGuard
{
    /**
     * Propagate the Passport-authenticated admin onto the session "admin"
     * guard so ACL checks (bouncer()) work for token-based MCP requests.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user('api');

        if ($user && ! auth()->guard('admin')->check()) {
            auth()->guard('admin')->setUser($user);
        }

        return $next($request);
    }
}
