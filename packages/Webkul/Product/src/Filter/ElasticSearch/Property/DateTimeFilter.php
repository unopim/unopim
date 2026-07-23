<?php

namespace Webkul\Product\Filter\ElasticSearch\Property;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Facades\Date;
use Webkul\ElasticSearch\Enums\FilterOperators;
use Webkul\Product\Filter\AbstractPropertyFilter;

/**
 * DateTime filter for an Elasticsearch query
 */
class DateTimeFilter extends AbstractPropertyFilter
{
    const CREATED_AT_PROPERTY = 'created_at';

    const UPDATED_AT_PROPERTY = 'updated_at';

    public function __construct(
        array $supportedProperties = [self::CREATED_AT_PROPERTY, self::UPDATED_AT_PROPERTY],
        array $allowedOperators = [
            FilterOperators::IN,
            FilterOperators::RANGE,
            FilterOperators::LESS_THAN,
            FilterOperators::GREATER_THAN,
        ]
    ) {
        $this->allowedOperators = $allowedOperators;
        $this->supportedProperties = $supportedProperties;
    }

    /**
     * {@inheritdoc}
     */
    public function applyPropertyFilter($property, $operator, $value, $locale = null, $channel = null, $options = []): static
    {
        throw_if($this->queryBuilder === null, \LogicException::class, 'The search query builder is not initialized in the filter.');

        if (! in_array($property, $this->supportedProperties)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unsupported property name for datetime filter, only "%s" are supported, "%s" given',
                    implode(',', $this->supportedProperties),
                    $property
                )
            );
        }

        switch ($operator) {
            case FilterOperators::IN:
                $clause = [
                    'terms' => [
                        $property => array_map(fn (string $data): string => $this->getFormattedDateTime($property, $data), $value),
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;

            case FilterOperators::RANGE:
                $values = array_values($value);
                $clause = [
                    'range' => [
                        $property => [
                            'gte' => $this->getFormattedDateTime($property, ($values[0] ?? '').' 00:00:01'),
                            'lte' => $this->getFormattedDateTime($property, ($values[1] ?? '').' 23:59:59'),
                        ],
                    ],
                ];

                $this->queryBuilder::where($clause);
                break;

            case FilterOperators::LESS_THAN:
                $this->queryBuilder::where([
                    'range' => [
                        $property => [
                            $operator->value => $this->getFormattedDateTime($property, $this->scalarValue($value).' 00:00:01'),
                        ],
                    ],
                ]);
                break;

            case FilterOperators::GREATER_THAN:
                $this->queryBuilder::where([
                    'range' => [
                        $property => [
                            $operator->value => $this->getFormattedDateTime($property, $this->scalarValue($value).' 23:59:59'),
                        ],
                    ],
                ]);
                break;
        }

        return $this;
    }

    /**
     * Format date time value according to elasticsearch mapping date format
     */
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

        return $dateTime->setTimezone($utcTimeZone)->toIso8601String();
    }
}
