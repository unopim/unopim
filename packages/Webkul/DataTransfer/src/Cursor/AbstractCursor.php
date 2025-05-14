<?php

namespace Webkul\DataTransfer\Cursor;

abstract class AbstractCursor
{
    protected $requestParams;

    protected $source;

    protected int $offset = 0;

    protected int $batchSize = 100;

    protected ?array $lastSort = null;

    protected array $currentBatch = [];

    protected int $retrievedCount = 0;

    protected ?array $items = null;

    abstract protected function fetchNextBatch();

    public function next(): void
    {
        if (next($this->items) === false) {
            $this->items = $this->getNextItems();
            reset($this->items);
        }
    }

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

    public function current(): array
    {
        if ($this->items === null) {
            $this->rewind();
        }

        return current($this->items);
    }

    public function valid(): bool
    {
        if ($this->items === null) {
            $this->rewind();
        }

        return ! empty($this->items);
    }

    public function count(): int
    {
        if ($this->items === null) {
            $this->rewind();
        }

        return $this->retrievedCount;
    }
}
