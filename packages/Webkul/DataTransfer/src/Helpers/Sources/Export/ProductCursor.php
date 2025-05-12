<?php

namespace Webkul\DataTransfer\Helpers\Sources\Export;

use Webkul\DataTransfer\Cursor\AbstractCursor;

class ProductCursor extends AbstractCursor
{
    protected int $offset = 0;

    public function __construct(
        protected array $filters,
        protected $source,
        protected int $batchSize = 100,
        protected array $options = []
    ) {}

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
        $query = $this->source->newQuery();

        // @TODO: Need to future
        // foreach ($this->filters as $field => $value) {
        //     $query->where($field, $value);
        // }

        $ids = $query->select('id')
            ->orderBy('id')
            ->offset($this->offset)
            ->limit($this->batchSize)
            ->pluck('id')
            ->map(fn ($id) => ['id' => $id])
            ->all();

        $this->offset += $this->batchSize;

        return $ids;
    }

    /**
     * Resolve and normalize options.
     */
    protected static function resolveOptions(array $options): array
    {
        $options['sort'] ??= [];
        $options['filters'] ??= [];

        return $options;
    }
}
