<?php

namespace Webkul\ElasticSearch\Traits;

use Illuminate\Support\Facades\DB;

trait ResolveTenantIndex
{
    /**
     * Tenant-specific index suffix.
     */
    private string $tenantIndexSuffix = '';

    /**
     * Resolve the tenant-specific index suffix for ES index isolation.
     */
    protected function resolveTenantIndexSuffix(): string
    {
        $tenantId = core()->getCurrentTenantId();

        if (is_null($tenantId)) {
            return '';
        }

        try {
            $uuid = DB::table('tenants')
                ->where('id', $tenantId)
                ->value('es_index_uuid');

            return $uuid ? "_tenant_{$uuid}" : "_tenant_{$tenantId}";
        } catch (\Throwable) {
            return "_tenant_{$tenantId}";
        }
    }

    /**
     * Get a tenant-aware index name for a given entity suffix.
     */
    protected function tenantAwareIndexName(string $entitySuffix): string
    {
        $prefix = $this->indexPrefix ?? config('elasticsearch.prefix');

        return strtolower($prefix.$this->tenantIndexSuffix.'_'.$entitySuffix);
    }

    /**
     * Initialize tenant index suffix (call in constructor).
     */
    protected function initTenantIndex(): void
    {
        $this->tenantIndexSuffix = $this->resolveTenantIndexSuffix();
    }
}
