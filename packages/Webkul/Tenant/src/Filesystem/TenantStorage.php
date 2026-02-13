<?php

namespace Webkul\Tenant\Filesystem;

class TenantStorage
{
    /**
     * Prefix a storage path with the current tenant context.
     * Returns the path unchanged when no tenant context is active (platform mode).
     *
     * Aligns with TenantPurger expectations:
     *   - General files: "tenant/{tenantId}/{originalPath}"
     *   - Imports:       "imports/tenant-{tenantId}/..."
     *   - Exports:       "exports/tenant-{tenantId}/..."
     */
    public static function path(string $path): string
    {
        $tenantId = core()->getCurrentTenantId();

        if (is_null($tenantId)) {
            return $path;
        }

        // Imports/exports use a different prefix pattern per TenantPurger
        if (str_starts_with($path, 'imports/')) {
            return 'imports/tenant-'.$tenantId.'/'.substr($path, 8);
        }

        if (str_starts_with($path, 'exports/')) {
            return 'exports/tenant-'.$tenantId.'/'.substr($path, 8);
        }

        return 'tenant/'.$tenantId.'/'.$path;
    }
}
