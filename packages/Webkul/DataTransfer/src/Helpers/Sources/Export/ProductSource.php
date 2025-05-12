<?php

namespace Webkul\DataTransfer\Helpers\Sources\Export;

use Webkul\DataTransfer\Helpers\Sources\Export\Elastic\ProductCursor as ElasticProductCursor;

class ProductSource
{
    public function getResults(array $requestParam, $source, int $size = 100, array $options = [])
    {
        if (config('elasticsearch.enabled')) {
            return new ElasticProductCursor($requestParam, $source, $size, $options);
        }

        return new ProductCursor($requestParam, $source, $size, $options);
    }
}
