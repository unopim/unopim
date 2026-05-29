<?php

declare(strict_types=1);

namespace Webkul\ElasticSearch\Filter;

use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Webkul\ElasticSearch\Contracts\Filter as FilterContract;
use Webkul\ElasticSearch\Facades\ElasticSearchQuery;

abstract class AbstractFilter implements FilterContract
{
    protected string $dateFormat = 'Y-m-d H:i:s';

    /** @var ElasticSearchQuery */
    protected mixed $queryBuilder = null;

    protected array $allowedOperators = [];

    /**
     * {@inheritdoc}
     */
    public function isOperatorAllowed($operator): bool
    {
        return in_array($operator, $this->allowedOperators);
    }

    /**
     * {@inheritdoc}
     */
    public function getAllowedOperators(): array
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

            $dateTime = Carbon::parse($value, $utcTimeZone);
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
