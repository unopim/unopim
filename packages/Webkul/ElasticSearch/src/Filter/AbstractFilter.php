<?php

namespace Webkul\ElasticSearch\Filter;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Webkul\ElasticSearch\Contracts\Filter as FilterContract;
use Webkul\ElasticSearch\Facades\ElasticSearchQuery;

abstract class AbstractFilter implements FilterContract
{
    /** @var ElasticSearchQuery */
    protected $queryBuilder = null;

    /** @var array */
    protected $allowedOperators = [];

    /**
     * {@inheritdoc}
     */
    public function isOperatorAllowed($operator)
    {
        return in_array($operator, $this->allowedOperators);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedOperators()
    {
        return $this->allowedOperators;
    }

    /**
     * {@inheritdoc}
     */
    public function setQueryManager($queryBuilder)
    {
        if (! $queryBuilder instanceof ElasticSearchQuery) {
            throw new \InvalidArgumentException(
                sprintf('Query builder should be an instance of "%s"', ElasticSearchQuery::class)
            );
        }

        $this->queryBuilder = $queryBuilder;
    }

    protected function getFormattedDateTime(string $field, string $value): string
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
