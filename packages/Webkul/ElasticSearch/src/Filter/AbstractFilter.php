<?php

namespace Webkul\ElasticSearch\Filter;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Webkul\ElasticSearch\Contracts\FilterInterface;
use Webkul\ElasticSearch\Facades\SearchQuery;

abstract class AbstractFilter implements FilterInterface
{
    /** @var SearchQuery */
    protected $searchQueryBuilder = null;

    /** @var array */
    protected $supportedOperators = [];

    /**
     * {@inheritdoc}
     */
    public function supportsOperator($operator)
    {
        return in_array($operator, $this->supportedOperators);
    }

    /**
     * {@inheritdoc}
     */
    public function getOperators()
    {
        return $this->supportedOperators;
    }

    /**
     * {@inheritdoc}
     */
    public function setQueryBuilder($searchQueryBuilder)
    {
        if (! $searchQueryBuilder instanceof SearchQuery) {
            throw new \InvalidArgumentException(
                sprintf('Query builder should be an instance of "%s"', SearchQuery::class)
            );
        }

        $this->searchQueryBuilder = $searchQueryBuilder;
    }

    protected function getFormattedDateTime($field, $value)
    {
        try {
            $utcTimeZone = 'UTC';

            $dateTime = Carbon::parse($value, $utcTimeZone);
        } catch (InvalidFormatException $e) {
            throw new \LogicException(
                sprintf(
                    'Invalid date format for field "%s", expected "Y-m-d H:i:s", but "%s" given',
                    $field,
                    $value
                )
            );
        }

        return $dateTime->setTimezone($utcTimeZone)->toIso8601String();
    }
}
