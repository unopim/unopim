<?php

namespace Webkul\AdminApi\ApiDataSource\Catalog;

use Webkul\Product\Database\Eloquent\Builder;

class ConfigurableProductDataSource extends ProductDataSource
{
    /**
     * Prepares the query builder for API requests.
     *
     * @return Builder The query builder for the product repository.
     */
    public function prepareApiQueryBuilder()
    {
        $queryMethodName = core()->getConfigData('catalog.products.storefront.search_mode') == 'elastic' ? 'queryBuilderFromElastic' : 'queryBuilderFromDatabase';

        [$queryBuilder] = $this->productRepository->$queryMethodName([]);

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
     * @return void
     */
    public function setDefaultFilters($queryBuilder)
    {
        $queryBuilder->where('products.type', config('product_types.configurable.key'));

        $this->queryBuilder = $queryBuilder;
    }
}
