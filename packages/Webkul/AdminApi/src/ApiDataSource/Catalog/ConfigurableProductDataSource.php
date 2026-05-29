<?php

declare(strict_types=1);

namespace Webkul\AdminApi\ApiDataSource\Catalog;

use Webkul\Product\Database\Eloquent\Builder;

class ConfigurableProductDataSource extends ProductDataSource
{
    /**
     * Prepares the query builder for API requests.
     *
     * @return Builder The query builder for the product repository.
     */
    #[\Override]
    public function prepareApiQueryBuilder(): mixed
    {
        [$queryBuilder] = $this->productRepository->queryBuilderFromDatabase([]);

        $this->addFilter('sku', [
            '=',
            'IN',
            'NOT IN',
        ]);

        $this->addFilter('categories', [
            'IN',
            'NOT IN',
        ]);

        return $queryBuilder;
    }

    /**
     * Sets default filters for the product query builder.
     *
     * This function adds a filter to the query builder to only retrieve simple products.
     *
     * @param  Builder  $queryBuilder  The query builder for the product repository.
     */
    #[\Override]
    public function setDefaultFilters(mixed $queryBuilder): mixed
    {
        $queryBuilder->where('products.type', config('product_types.configurable.key'));

        $this->queryBuilder = $queryBuilder;

        return $this->queryBuilder;
    }
}
