<?php

namespace Webkul\Product\Filter;

abstract class AbstractFieldFilter extends AbstractFilter
{
    /** @var array */
    protected $supportedFields = [];

    /**
     * {@inheritdoc}
     */
    public function supportsField($field)
    {
        return in_array($field, $this->supportedFields);
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
        $parentQuery = clone $this->searchQueryBuilder;

        return $parentQuery
            ->select("$table.id")
            ->whereIn("$table.sku", $skus)
            ->orWhere("$table.type", config('product_types.configurable.key'))
            ->pluck('id')
            ->toArray();
    }
}
