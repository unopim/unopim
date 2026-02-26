<?php

namespace Webkul\DataTransfer\Helpers\Sources\Export;

use Webkul\DataTransfer\Cursor\AbstractCursor;

class ProductCursor extends AbstractCursor
{
    public function __construct(
        array $requestParams,
        mixed $source,
        int $batchSize = 100,
        protected array $options = []
    ) {
        $this->requestParams = $requestParams;
        $this->source = $source;
        $this->batchSize = $batchSize;
    }

    /**
     * Fetch a batch of product IDs from the source using offset-based pagination.
     */
    protected function fetchNextBatch(): array
    {
        $ids = [];
        $query = $this->source->newQuery();
        $filters = $this->requestParams['filters'] ?? [];

        foreach ($filters as $field => $value) {
            if ($field == 'status' && ! empty($value)) {
                $value = $value == 'enable' ? true : false;
                $query->where($field, $value);
            }
        }
        try {
            $ids = $query->select('id')
                ->orderBy('id')
                ->offset($this->offset)
                ->limit($this->batchSize)
                ->pluck('id')
                ->map(fn ($id) => ['id' => $id])
                ->all();

            $this->offset += $this->batchSize;
        } catch (\Throwable $e) {
            \Log::error('Elasticsearch search error: '.$e->getMessage());
            throw $e;
        }

        return $ids;
    }

    /**
     * Resolve and normalize options.
     */
    protected static function resolveOptions(array $options): array
    {
        $options['sort'] ??= [];

        return $options;
    }
}
