<?php

namespace Webkul\ElasticSearch\Cursor;

abstract class AbstractElasticCursor
{
    protected $requestParams;

    protected $source;

    protected int $batchSize = 100;

    protected ?array $lastSort = null;

    protected array $currentBatch = [];

    protected int $retrievedCount = 0;

    protected ?array $items = null;

    abstract protected function getNextItems();

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
