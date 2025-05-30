<?php

namespace Webkul\ElasticSearch;

use Webkul\ElasticSearch\Contracts\ResultCursor as ResultCursorContract;
use Webkul\ElasticSearch\Contracts\SearchResponse;

class ResultIterator implements ResultCursorContract
{
    /** @var \ArrayIterator */
    private $idIterator;

    /** @var int */
    private $totalResults;

    /** @var SearchResponse */
    private $searchResponse;

    /**
     * Constructor.
     *
     * @param  array  $ids  The array of IDs to iterate over.
     * @param  int  $totalResults  The total number of results.
     * @param  SearchResponse  $searchResponse  The search response object.
     */
    public function __construct(array $ids, int $totalResults, SearchResponse $searchResponse)
    {
        $this->idIterator = new \ArrayIterator($ids);
        $this->totalResults = $totalResults;
        $this->searchResponse = $searchResponse;
    }

    /**
     * Get all IDs as an array.
     */
    public function getAllIds(): array
    {
        return $this->idIterator->getArrayCopy();
    }

    /**
     * Get the total number of results.
     */
    public function count(): int
    {
        return $this->totalResults;
    }

    /**
     * Get the current ID.
     */
    public function current(): mixed
    {
        return $this->idIterator->current();
    }

    /**
     * Get the current key.
     */
    public function key(): mixed
    {
        return $this->idIterator->key();
    }

    /**
     * Move to the next ID.
     */
    public function next(): void
    {
        $this->idIterator->next();
    }

    /**
     * Rewind the iterator to the first ID.
     */
    public function rewind(): void
    {
        $this->idIterator->rewind();
    }

    /**
     * Check if the current position is valid.
     */
    public function valid(): bool
    {
        return $this->idIterator->valid();
    }

    /**
     * Get the search response object.
     */
    public function getSearchResponse(): SearchResponse
    {
        return $this->searchResponse;
    }
}
