<?php

namespace Webkul\ElasticSearch\Contracts;

interface CursorFactoryInterface
{
    /**
     * Create the cursor with the correct implementation
     */
    public static function createCursor($queryBuilder, array $options = []);
}
