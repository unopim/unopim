<?php

namespace Webkul\Product\Filter;

use Illuminate\Support\Facades\DB;
use Webkul\ElasticSearch\Contracts\PropertyFilter as PropertyFilterContract;

abstract class AbstractPropertyFilter extends AbstractFilter implements PropertyFilterContract
{
    protected array $supportedProperties = [];

    /**
     * {@inheritdoc}
     */
    public function supportsProperty($property): bool
    {
        return in_array($property, $this->supportedProperties);
    }

    /**
     * Retrieves the search table path based on the provided options.
     */
    protected function getSearchTablePath(array $options = []): string
    {
        return $options['search_table_path'] ?? 'products';
    }

    /**
     * Retrieves the IDs of parent products associated with the given SKUs.
     */
    protected function getParentIdsBySkus(array $skus, array $options = []): array
    {
        $table = $this->getSearchTablePath($options);

        return DB::table($table)
            ->select("$table.id")
            ->whereIn("$table.sku", $skus)
            ->Where("$table.type", config('product_types.configurable.key'))
            ->pluck('id')
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties(): array
    {
        return $this->supportedProperties;
    }
}
