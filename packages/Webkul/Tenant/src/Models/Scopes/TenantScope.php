<?php

namespace Webkul\Tenant\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * Apply the tenant scope to the given Eloquent query builder.
     *
     * When core()->getCurrentTenantId() returns null (platform context),
     * no filtering is applied — the query returns all tenants' data.
     */
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = core()->getCurrentTenantId();

        if (! is_null($tenantId)) {
            $builder->where($model->getTable().'.tenant_id', $tenantId);
        }
    }
}
