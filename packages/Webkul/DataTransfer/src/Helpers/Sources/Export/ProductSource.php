<?php

declare(strict_types=1);

namespace Webkul\DataTransfer\Helpers\Sources\Export;

use Webkul\DataTransfer\Cursor\AbstractCursor;
use Webkul\DataTransfer\Helpers\Sources\Export\Elastic\ProductCursor as ElasticProductCursor;

class ProductSource
{
    public function getResults(array $requestParams, mixed $source, int $size = 100, array $options = []): AbstractCursor
    {
        if (config('elasticsearch.enabled')) {
            return new ElasticProductCursor($requestParams, $source, $size, $options);
        }

        return new ProductCursor($requestParams, $source, $size, $options);
    }
}
