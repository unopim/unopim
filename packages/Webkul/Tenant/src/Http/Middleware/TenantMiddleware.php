<?php

namespace Webkul\Tenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\Tenant\Models\Tenant;

class TenantMiddleware
{
    /**
     * Statuses that are not operational — return 503.
     */
    private const UNAVAILABLE_STATUSES = [
        Tenant::STATUS_SUSPENDED,
        Tenant::STATUS_DELETING,
        Tenant::STATUS_DELETED,
    ];

    /**
     * Handle an incoming request.
     *
     * Resolution priority (D1 — Hybrid Strict):
     *   1. Subdomain extraction
     *   2. X-Tenant-ID header
     *   3. OAuth token → admin_id → tenant_id
     *   4. Session-based context (platform operator switching)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $this->resolveTenant($request);

        if ($tenant) {
            if (in_array($tenant->status, self::UNAVAILABLE_STATUSES, true)) {
                abort(503, 'Tenant is not available.');
            }

            core()->setCurrentTenantId($tenant->id);

            // Session tenant isolation: invalidate session if a tenant-scoped
            // user somehow has a session from a different tenant.
            // Platform operators (tenant_id = null) are exempt — they
            // legitimately switch between tenant contexts via the UI.
            $currentUser = auth('admin')->user() ?? auth('api')->user();
            $isPlatformOperator = $currentUser && is_null($currentUser->tenant_id);

            if (! $isPlatformOperator && session()->has('_tenant_id') && session('_tenant_id') !== $tenant->id) {
                session()->invalidate();
                session()->regenerateToken();
            }

            session(['_tenant_id' => $tenant->id]);

            return $next($request);
        }

        // No tenant resolved — let the request proceed without tenant context.
        // Routes that truly require a tenant should guard this themselves
        // or the caller should use the "tenant.required" middleware variant.
        return $next($request);
    }

    /**
     * Resolve tenant from the request using the priority chain.
     */
    protected function resolveTenant(Request $request): ?Tenant
    {
        return $this->resolveFromSubdomain($request)
            ?? $this->resolveFromHeader($request)
            ?? $this->resolveFromToken($request)
            ?? $this->resolveFromSession($request);
    }

    /**
     * Strategy 1: Extract subdomain from the request host.
     *
     * Given APP_URL = https://app.example.com,
     * a request to tenant-a.app.example.com yields subdomain "tenant-a".
     */
    protected function resolveFromSubdomain(Request $request): ?Tenant
    {
        $appHost = parse_url(config('app.url'), PHP_URL_HOST) ?: 'localhost';
        $requestHost = $request->getHost();

        // The request host must end with the app host and have an extra label.
        if ($requestHost === $appHost || ! str_ends_with($requestHost, '.'.$appHost)) {
            return null;
        }

        $subdomain = rtrim(str_replace('.'.$appHost, '', $requestHost), '.');

        if ($subdomain === '') {
            return null;
        }

        return Tenant::where('domain', $subdomain)->first();
    }

    /**
     * Strategy 2: Read the X-Tenant-ID request header.
     *
     * Security: Only accepts the header if the authenticated user
     * belongs to the requested tenant or is a platform operator.
     */
    protected function resolveFromHeader(Request $request): ?Tenant
    {
        $tenantId = $request->header('X-Tenant-ID');

        if (! $tenantId) {
            return null;
        }

        $tenant = Tenant::find((int) $tenantId);

        if (! $tenant) {
            return null;
        }

        // Validate the authenticated user is allowed to use this tenant context
        foreach (['api', 'admin'] as $guard) {
            $user = auth($guard)->user();

            if ($user) {
                // Platform operators (tenant_id = null) may switch into any tenant
                if (is_null($user->tenant_id)) {
                    return $tenant;
                }

                // Tenant users may only access their own tenant
                return $user->tenant_id === $tenant->id ? $tenant : null;
            }
        }

        // No authenticated user — reject header-based resolution
        return null;
    }

    /**
     * Strategy 3: Derive tenant from the authenticated admin's tenant_id.
     *
     * Works for both session-based (admin guard) and token-based (api guard) auth.
     */
    protected function resolveFromToken(Request $request): ?Tenant
    {
        // Try API guard first (token-based), then admin guard (session-based).
        foreach (['api', 'admin'] as $guard) {
            $user = auth($guard)->user();

            if ($user && $user->tenant_id) {
                return Tenant::find($user->tenant_id);
            }
        }

        return null;
    }

    /**
     * Strategy 4: Read tenant context from the session.
     *
     * Platform operators (tenant_id = NULL) can switch into a tenant's
     * context via the header switcher. The selected tenant ID is stored
     * in the session as 'tenant_context_id'.
     */
    protected function resolveFromSession(Request $request): ?Tenant
    {
        $sessionTenantId = session('tenant_context_id');

        if (! $sessionTenantId) {
            return null;
        }

        // Only allow session-based context for platform operators
        $admin = auth('admin')->user();

        if (! $admin || $admin->tenant_id) {
            return null;
        }

        return Tenant::find($sessionTenantId);
    }
}
