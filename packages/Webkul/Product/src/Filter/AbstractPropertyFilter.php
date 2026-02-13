<?php

namespace Webkul\Product\Filter;

use Illuminate\Support\Facades\DB;
use Webkul\ElasticSearch\Contracts\PropertyFilter as PropertyFilterContract;

abstract class AbstractPropertyFilter extends AbstractFilter implements PropertyFilterContract
{
    /** @var array */
    protected $supportedProperties = [];

    /**
     * {@inheritdoc}
     */
    public function supportsProperty($property)
    {
        return in_array($property, $this->supportedProperties);
    }

    /**
     * Retrieves the search table path based on the provided options.
     */
    protected function getSearchTablePath(array $options = [])
    {
        return $options['search_table_path'] ?? 'products';
    }

    /**
     * Retrieves the IDs of parent products associated with the given SKUs.
     */
    protected function getParentIdsBySkus(array $skus, array $options = [])
    {
        $table = $this->getSearchTablePath($options);

        $query = DB::table($table)
            ->select("$table.id")
            ->whereIn("$table.sku", $skus)
            ->where("$table.type", config('product_types.configurable.key'));

        $tenantId = core()->getCurrentTenantId();

        if (! is_null($tenantId)) {
            $query->where("$table.tenant_id", $tenantId);
        }

        return $query->pluck('id')->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties()
    {
        return $this->supportedProperties;
    }
}
