<?php

namespace Webkul\Tenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Webkul\Tenant\Models\Tenant;

/**
 * Validates that API tokens belong to an active tenant and that
 * the tenant context is properly established. Rejects orphan tokens
 * (tokens whose tenant has been deleted or suspended).
 */
class TenantTokenValidator
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->guard('api')->user();

        if (! $user) {
            return $next($request);
        }

        $tenantId = $user->tenant_id;

        // Platform users (tenant_id = NULL) bypass tenant validation
        if (is_null($tenantId)) {
            // Log platform operator API access for audit
            $this->logPlatformAccess($user, $request);

            return $next($request);
        }

        // Validate tenant exists and is active
        $tenant = DB::table('tenants')
            ->where('id', $tenantId)
            ->whereNull('deleted_at')
            ->first();

        if (! $tenant) {
            return response()->json([
                'error' => 'Tenant not found. This API token has been orphaned.',
            ], 403);
        }

        if ($tenant->status !== Tenant::STATUS_ACTIVE) {
            return response()->json([
                'error' => 'Tenant is not active. Current status: '.$tenant->status,
            ], 403);
        }

        // Verify the OAuth client that issued the token belongs to the same tenant
        if ($user->token()) {
            $client = $user->token()->client;

            if ($client && $client->tenant_id && $user->tenant_id && $client->tenant_id !== $user->tenant_id) {
                return response()->json([
                    'error' => 'Token client tenant mismatch.',
                ], 403);
            }
        }

        // Set the tenant context for this request
        core()->setCurrentTenantId($tenantId);

        return $next($request);
    }

    /**
     * Log platform operator API access for audit trail.
     */
    protected function logPlatformAccess($user, Request $request): void
    {
        try {
            Log::channel('security')->info('Platform operator API access', [
                'user_id'  => $user->id,
                'email'    => $user->email,
                'method'   => $request->method(),
                'path'     => $request->path(),
                'ip'       => $request->ip(),
            ]);
        } catch (\Throwable) {
            // Logging must never block API requests
        }
    }
}
