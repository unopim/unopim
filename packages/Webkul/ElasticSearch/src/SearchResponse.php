<?php

namespace Webkul\ElasticSearch;

use Webkul\ElasticSearch\Contracts\SearchResponse as SearchResponseContract;

class SearchResponse implements SearchResponseContract
{
    public function __construct(protected array $responseData) {}

    /**
     * Get the raw response data.
     */
    public function getResponseData(): mixed
    {
        return $this->responseData;
    }
}
