<?php

namespace Webkul\ElasticSearch;

use Webkul\ElasticSearch\Contracts\CursorInterface;
use Webkul\ElasticSearch\Contracts\ResultInterface;

class ResultCursor implements CursorInterface
{
    /** @var \ArrayIterator */
    private $ids;

    /** @var int */
    private $totalCount;

    /** @var ElasticsearchResult */
    private $result;

    public function __construct(array $ids, int $totalCount, ElasticsearchResult $result)
    {
        $this->ids = new \ArrayIterator($ids);
        $this->totalCount = $totalCount;
        $this->result = $result;
    }

    public function getIds(): mixed
    {
        return $this->ids->getArrayCopy();
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->totalCount;
    }

    /**
     * {@inheritdoc}
     */
    public function current(): mixed
    {
        return $this->ids->current();
    }

    /**
     * {@inheritdoc}
     */
    public function key(): mixed
    {
        return $this->ids->key();
    }

    /**
     * {@inheritdoc}
     */
    public function next(): void
    {
        $this->ids->next();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->ids->rewind();
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return $this->ids->valid();
    }

    /**
     * {@inheritdoc}
     */
    public function getResult(): ResultInterface
    {
        return $this->result;
    }
}