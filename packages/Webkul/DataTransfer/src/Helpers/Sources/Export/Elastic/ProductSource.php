<?php

namespace Webkul\DataTransfer\Helpers\Sources\Export\Elastic;


class ProductSource
{
    public function getResults(array $elasticQuery, $source, int $size = 10, array $options = [])
    {
        return new ProductCursor($elasticQuery, $source, $size, $options);
    }
}