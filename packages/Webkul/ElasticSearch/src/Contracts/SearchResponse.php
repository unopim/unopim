<?php

declare(strict_types=1);

namespace Webkul\ElasticSearch\Contracts;

interface SearchResponse
{
    public function getResponseData(): mixed;
}
