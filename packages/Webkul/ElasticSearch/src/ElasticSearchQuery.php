<?php

namespace Webkul\ElasticSearch;

/**
 * This class is responsible for building Elasticsearch queries in a structured way.
 * It supports `must`, `must_not`, `should`, `filter`, `sort`, and `aggregation` clauses.
 */
class ElasticSearchQuery
{
    /** @var array */
    private $excludeConditions = [];

    /** @var array */
    private $filterConditions = [];

    /** @var array */
    private $orConditions = [];

    /** @var array */
    private $includeConditions = [];

    /** @var array */
    private $sortingConditions = [];

    /** @var array */
    private $aggregationConditions = [];

    /**
     * Add a `must_not` clause to the query.
     *
     * @return $this
     */
    public function whereNot(array $clause): self
    {
        $this->excludeConditions[] = $clause;

        return $this;
    }

    /**
     * Add a `filter` clause to the query.
     *
     * @return $this
     */
    public function where(array $clause): self
    {
        $this->filterConditions[] = $clause;

        return $this;
    }

    /**
     * Add a `should` clause to the query.
     *
     * @return $this
     */
    public function orWhere(array $clause): self
    {
        $this->orConditions[] = $clause;

        return $this;
    }

    /**
     * Add a `must` clause to the query.
     *
     * @return $this
     */
    public function must(array $clause): self
    {
        $this->includeConditions[] = $clause;

        return $this;
    }

    /**
     * Add a `sort` clause to the query.
     *
     * @return $this
     */
    public function orderBy(array $sort): self
    {
        $this->sortingConditions = array_merge($this->sortingConditions, $sort);

        return $this;
    }

    /**
     * Check if a sort clause exists for a specific field.
     */
    public function hasOrderBy(string $field): bool
    {
        return array_key_exists($field, $this->sortingConditions);
    }

    /**
     * Add an aggregation (facet) clause to the query.
     *
     * @return $this
     */
    public function addAggregation(string $name, string $field): self
    {
        $this->aggregationConditions[$name] = ['terms' => ['field' => $field]];

        return $this;
    }

    /**
     * Build and return the Elasticsearch query.
     */
    public function build(array $source = []): array
    {
        $searchQuery = [];

        if (! empty($this->filterConditions)) {
            $searchQuery['query']['constant_score']['filter']['bool']['filter'] = $this->filterConditions;
        }

        if (! empty($this->includeConditions)) {
            $searchQuery['query']['constant_score']['filter']['bool']['must'] = $this->includeConditions;
        }

        if (! empty($this->excludeConditions)) {
            $searchQuery['query']['constant_score']['filter']['bool']['must_not'] = $this->excludeConditions;
        }

        if (! empty($this->orConditions)) {
            $searchQuery['query']['constant_score']['filter']['bool']['should'] = $this->orConditions;
            $searchQuery['query']['constant_score']['filter']['bool']['minimum_should_match'] = 1;
        }

        if (! empty($this->sortingConditions)) {
            $searchQuery['sort'] = $this->sortingConditions;
        }

        if (! empty($this->aggregationConditions)) {
            $searchQuery['aggs'] = $this->aggregationConditions;
        }

        return $searchQuery;
    }
}
