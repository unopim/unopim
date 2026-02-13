<?php

namespace Webkul\Tenant\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Webkul\Tenant\Eloquent\TenantAwareBuilder;
use Webkul\Tenant\Models\Scopes\TenantScope;
use Webkul\Tenant\Models\TenantProxy;

/**
 * BelongsToTenant trait.
 *
 * MUST be the FIRST trait in any model's trait list (Pattern 2).
 * Registers the TenantScope global scope and auto-sets tenant_id on creation.
 */
trait BelongsToTenant
{
    /**
     * Boot the BelongsToTenant trait.
     *
     * Registers the TenantScope global scope and a `creating` event
     * listener that auto-sets tenant_id from the current tenant context.
     */
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            $tenantId = core()->getCurrentTenantId();

            if (! is_null($tenantId) && ! $model->isDirty('tenant_id')) {
                $model->tenant_id = $tenantId;
            }
        });
    }

    /**
     * Get the tenant that owns this model.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(TenantProxy::modelClass(), 'tenant_id');
    }

    /**
     * Create a new Eloquent query builder with scope bypass detection.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @return \Webkul\Tenant\Eloquent\TenantAwareBuilder
     */
    public function newEloquentBuilder($query)
    {
        return new TenantAwareBuilder($query);
    }
}
