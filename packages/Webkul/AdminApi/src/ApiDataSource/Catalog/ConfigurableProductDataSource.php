<?php

namespace Webkul\AdminApi\ApiDataSource\Catalog;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
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
        $this->registerDateFilters();

        return $queryBuilder;
    }

    /**
     * Restricts this endpoint to the two types it serves: `configurable` roots and the
     * `variant_group` nodes of a 2-level variant tree, which are configurable products
     * in their own right. Applied to the listing and to `getByCode()` alike, so a
     * variant group is both enumerable and addressable here.
     *
     * @param  Builder  $queryBuilder  The query builder for the product repository.
     * @return void
     */
    public function setDefaultFilters($queryBuilder)
    {
        $queryBuilder->whereIn('products.type', $this->requestedTypes());

        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Narrows the endpoint's two served types down to the one named by the optional
     * `type` query parameter, mirroring how `limit` and `page` are read straight off
     * the request rather than through the `filters` JSON payload. Omitting the
     * parameter returns both types; naming a type this endpoint does not serve is a
     * client error rather than a silently empty page.
     *
     * @return array<int, string>
     */
    protected function requestedTypes(): array
    {
        $supported = [
            config('product_types.configurable.key'),
            config('product_types.variant_group.key'),
        ];

        $requested = request()->input('type');

        if ($requested === null || $requested === '') {
            return $supported;
        }

        if (! is_string($requested) || ! in_array($requested, $supported, true)) {
            throw new UnprocessableEntityHttpException(
                sprintf(
                    'Type filter value %s is not supported by this endpoint. Use one of: %s.',
                    json_encode($requested),
                    implode(', ', $supported)
                )
            );
        }

        return [$requested];
    }
}
