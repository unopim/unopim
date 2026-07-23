<?php

namespace Webkul\ElasticSearch\Filter;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\Date;
use Webkul\ElasticSearch\Contracts\Filter as FilterContract;
use Webkul\ElasticSearch\Facades\ElasticSearchQuery;

abstract class AbstractFilter implements FilterContract
{
    protected $dateFormat = 'Y-m-d H:i:s';

    /** @var ElasticSearchQuery */
    protected $queryBuilder;

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
    public function setQueryManager($queryBuilder): void
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

            $dateTime = Date::parse($value, $utcTimeZone);
        } catch (InvalidFormatException) {
            throw new \LogicException(
                sprintf(
                    'Invalid date format for field "%s", expected "Y-m-d H:i:s", but "%s" given',
                    $field,
                    $value
                )
            );
        }

        return $dateTime->setTimezone($utcTimeZone)->format($this->dateFormat);
    }
}
