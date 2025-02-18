<?php

namespace Webkul\ElasticSearch;

/** *
 *
 * This stateful class holds the multiple parts of an Elastic Search search query.
 *
 * In two different arrays, it keeps track of the conditions where:
 * - a property should be equal to a value (ES filter clause)
 * - a property should *not* be equal to a value (ES must_not clause)
 *
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl.html
 * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-bool-query.html
 */
class SearchQueryBuilder
{
    /** @var array */
    private $mustNotClauses = [];

    /** @var array */
    private $filterClauses = [];

    /** @var array */
    private $shouldClauses = [];

    /** @var array */
    private $sortClauses = [];

    /** @var array */
    private $aggsClauses = [];

    /**
     * Adds a must_not clause to the query
     *
     *
     * @return SearchQueryBuilder
     */
    public function addMustNot(array $clause)
    {
        $this->mustNotClauses[] = $clause;

        return $this;
    }

    /**
     * Adds a filter clause to the query
     *
     *
     * @return SearchQueryBuilder
     */
    public function addFilter(array $clause)
    {
        $this->filterClauses[] = $clause;

        return $this;
    }

    /**
     * Adds a should clause to the query
     *
     * Warning: in the context of the PIM, a request containing a should clause is subject to a lot of side effects.
     * For instance, in one filter you want to filter on the property A with 2 possible values: A = 1 || A = 2
     * you could do the following:
     * `sqb->addShould([
     *  [
     *   'terms' => [
     *      'A' => 1
     *    ]
     *  ],
     *  [
     *   'terms' => [
     *      'A' => 2
     *    ]
     *  ]);`
     *
     * Later on, with another filter but the same sqb, you want to filter on property B: B = 1 || B =2
     * again, you would do the following:
     * `sqb->addShould([
     *  [
     *   'terms' => [
     *      'B' => 1
     *    ]
     *  ],
     *  [
     *   'terms' => [
     *      'B' => 2
     *    ]
     *  ]);`
     *
     * The resulting logical request looks like this: A = 1 || A = 2 || B = 1 || B = 2 where in fact what the user meant
     * was (A = 1 || A = 2) && (B = 1 || B = 2)
     *
     *
     *
     * @return SearchQueryBuilder
     */
    public function addShould(array $clause)
    {
        $this->shouldClauses[] = $clause;

        return $this;
    }

    /**
     * Adds a sort clause to the query
     *
     *
     * @return $this
     */
    public function addSort(array $sort)
    {
        $this->sortClauses = array_merge($this->sortClauses, $sort);

        return $this;
    }

    public function hasSort(string $field): bool
    {
        return \array_key_exists($field, $this->sortClauses);
    }

    public function addFacet(string $name, string $field): self
    {
        $this->aggsClauses[$name] = ['terms' => ['field' => $field]];

        return $this;
    }

    /**
     * Returns an Elastic search Query
     */
    public function getQuery(array $source = []): array
    {

        $searchQuery = [];

        if (! empty($this->filterClauses)) {
            $searchQuery['query']['constant_score']['filter']['bool']['filter'] = $this->filterClauses;
        }

        if (! empty($this->mustNotClauses)) {
            $searchQuery['query']['constant_score']['filter']['bool']['must_not'] = $this->mustNotClauses;
        }

        if (! empty($this->shouldClauses)) {
            $searchQuery['query']['constant_score']['filter']['bool']['should'] = $this->shouldClauses;
            $searchQuery['query']['constant_score']['filter']['bool']['minimum_should_match'] = 1;
        }

        if (! empty($this->sortClauses)) {
            $searchQuery['sort'] = $this->sortClauses;
        }

        if (! empty($this->aggsClauses)) {
            $searchQuery['aggs'] = $this->aggsClauses;
        }

        return $searchQuery;
    }
}
