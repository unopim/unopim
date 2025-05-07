<?php

namespace Webkul\ElasticSearch\Cursor;

abstract class AbstractElasticCursor
{
    protected $elasticQuery;
    protected $source;
    protected int $size = 100;
    protected ?array $lastSort = null;
    protected array $currentBatch = [];
    protected int $retrievedCount = 0;
    protected ?array $items = null;
    protected int $position = 0;
     
    abstract protected function getNextItems();

    public function current(): array
    {
        if (null === $this->items) {
            $this->rewind();
        }

        return current($this->items);
    }

    public function valid(): bool
    {
        if (null === $this->items) {
            $this->rewind();
        }

        return !empty($this->items);
    }
    

    public function count(): int
    {
        if (null === $this->items) {
            $this->rewind();
        }

        return $this->retrievedCount;
    }

    /**
     * {@inheritdoc}
     */
    public function key(): mixed
    {
        if (null === $this->items) {
            $this->rewind();
        }

        return key($this->items) + $this->position;
    }
}
