<?php

namespace Webkul\Tenant\Eloquent;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class TenantAwareBuilder extends Builder
{
    /**
     * Remove a registered global scope — log + allow strategy (Decision D5).
     *
     * @param  \Illuminate\Database\Eloquent\Scope|string  $scope
     * @return $this
     */
    public function withoutGlobalScope($scope)
    {
        $scopeName = is_string($scope) ? $scope : get_class($scope);

        if ($scopeName === \Webkul\Tenant\Models\Scopes\TenantScope::class || $scopeName === 'tenant') {
            $this->logTenantScopeBypass('TenantScope bypass detected', [
                'scope'     => $scopeName,
                'model'     => get_class($this->getModel()),
                'tenant_id' => core()->getCurrentTenantId(),
            ]);
        }

        return parent::withoutGlobalScope($scope);
    }

    /**
     * Remove all registered global scopes — log if TenantScope would be affected.
     *
     * @return $this
     */
    public function withoutGlobalScopes(array $scopes = null)
    {
        $removingAll = is_null($scopes);
        $removingTenant = $removingAll || in_array(\Webkul\Tenant\Models\Scopes\TenantScope::class, $scopes ?? []);

        if ($removingTenant) {
            $this->logTenantScopeBypass('TenantScope bypass detected (bulk removal)', [
                'scopes'    => $scopes ?? 'ALL',
                'model'     => get_class($this->getModel()),
                'tenant_id' => core()->getCurrentTenantId(),
            ]);
        }

        return parent::withoutGlobalScopes($scopes);
    }

    /**
     * Log tenant scope bypass with graceful fallback.
     */
    protected function logTenantScopeBypass(string $message, array $context): void
    {
        try {
            Log::channel('security')->warning($message, $context);
        } catch (\Throwable) {
            // Fallback to default log channel if 'security' channel is not configured
            try {
                Log::warning('[TENANT-SECURITY] '.$message, $context);
            } catch (\Throwable) {
                // Silently continue — logging must never block queries
            }
        }
    }
}
