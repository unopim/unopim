<?php

namespace Webkul\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\Admin\Sso\OidcSettings;

/**
 * Closes password sign-in and the password reset flow while the OpenID Connect
 * SSO-only mode is active. The REST API authenticates through its own routes
 * and is not affected; unsetting UNOPIM_OIDC_ONLY reopens password sign-in.
 */
class EnforceSsoOnly
{
    public function __construct(protected OidcSettings $settings) {}

    /**
     * Refuse the request while SSO-only mode is active, otherwise pass it on.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->isSsoOnly()) {
            return $next($request);
        }

        $message = trans('admin::app.users.sessions.sso-only');

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], Response::HTTP_FORBIDDEN);
        }

        session()->flash('warning', $message);

        return redirect()->route('admin.session.create');
    }
}
