<?php

namespace Webkul\DataTransfer\Helpers\Sources\Export;

use Webkul\DataTransfer\Cursor\AbstractCursor;
use Webkul\DataTransfer\Helpers\Sources\Export\Filters\ProductExportFilter;

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

    protected int $lastId = 0;

    /**
     * Fetch a batch of product IDs using keyset (seek) pagination. Offset
     * pagination is O(n^2) at depth; seeking on the ordered primary key keeps
     * each batch a constant-cost index range scan, which is what lets exports
     * scale to millions of products.
     */
    protected function fetchNextBatch(): array
    {
        if ($this->offset === 0) {
            $this->lastId = 0;
        }

        $query = $this->source->newQuery();
        $filters = $this->requestParams['filters'] ?? [];

        resolve(ProductExportFilter::class)->applyToQuery($query, $filters);

        try {
            $ids = $query->select('id')
                ->where('id', '>', $this->lastId)
                ->orderBy('id')
                ->limit($this->batchSize)
                ->pluck('id');

            if ($ids->isNotEmpty()) {
                $this->lastId = (int) $ids->last();
            }

            $this->offset += $this->batchSize;
        } catch (\Throwable $e) {
            \Log::error('Product export cursor error: '.$e->getMessage());
            throw $e;
        }

        return $ids->map(fn ($id): array => ['id' => $id])->all();
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
