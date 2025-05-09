<?php

namespace Webkul\DataTransfer\Helpers\Sources\Export;

use Webkul\DataTransfer\Cursor\AbstractCursor;

class ProductCursor extends AbstractCursor
{
    private array $searchAfter = [];

    public function __construct(
        protected $elasticQuery,
        protected $source,
        protected int $size = 10,
        protected array $options = []
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        if (false === next($this->items)) {
            $this->position += count($this->items);
            $this->items = $this->getNextItems();
            reset($this->items);
        }
    }

    /**
     * Get next items from the source
     *
     * @param array $esQuery
     * @return array
     */
    public function getNextItems()
    {
        $ids = $this->getNextIds($this->elasticQuery, $this->size);
        
        return $ids;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->searchAfter = [];
        $this->items = $this->getNextItems();
        reset($this->items);
    }
    

    /**
     * Get next SKUs from the source
     *
     * @param array $esQuery
     * @param int|null $size
     * @return array
     */
    protected function getNextIds(array $esQuery = [], ?int $size = null): array
    {
        $options = self::resolveOptions($this->options);

        $ids = [];


        return $ids;
    }

    /**
     * @return array
     */
    protected static function resolveOptions(array $options)
    {
        $options['sort'] = $options['sort'] ?? [];
        $options['filters'] = $options['filters'] ?? [];

        return $options;
    }
}