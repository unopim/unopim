<?php

declare(strict_types=1);

namespace Webkul\ElasticSearch;

use Webkul\ElasticSearch\Contracts\ResultInterface;

class ElasticsearchResult implements ResultInterface
{
    /** @var array */
    private $rawResult;

    public function __construct(array $rawResult)
    {
        $this->rawResult = $rawResult;
    }

    public function getRawResult(): array
    {
        return $this->rawResult;
    }
}
