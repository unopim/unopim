<?php

namespace Webkul\DataTransfer\Helpers\Sources\Export;

use Webkul\DataTransfer\Cursor\AbstractCursor;

class ProductCursor extends AbstractCursor
{
    protected int $offset = 0;

    public function __construct(
        array $requestParams,
        $source,
        int $batchSize = 100,
        protected array $options = []
    ) {
        $this->requestParams = $requestParams;
        $this->source = $source;
        $this->batchSize = $batchSize;
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        if (next($this->items) === false) {
            $this->items = $this->getNextItems();
            reset($this->items);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->offset = 0;
        $this->items = $this->getNextItems();
        reset($this->items);
    }

    /**
     * Get the next batch of items from the source.
     */
    protected function getNextItems(): array
    {
        return $this->fetchNextBatch();
    }

    /**
     * Fetch a batch of product IDs from the source using offset-based pagination.
     */
    protected function fetchNextBatch(): array
    {
        $ids = [];
        $query = $this->source->newQuery();
        $filters = $this->requestParams['filters'] ?? [];
        // @TODO: Need to future
        foreach ($filters as $field => $value) {
            if ($field == 'status' && ! empty($value)) {
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
