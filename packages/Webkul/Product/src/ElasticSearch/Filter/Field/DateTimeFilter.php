<?php

namespace Webkul\Product\ElasticSearch\Filter\Field;

use Webkul\ElasticSearch\Contracts\FilterInterface;
use Webkul\ElasticSearch\Filter\Operators;

/**
 * DateTime filter for an Elasticsearch query
 */
class DateTimeFilter extends AbstractFieldFilter implements FilterInterface
{
    const CREATED_AT_FIELD = 'created_at';

    const UPDATED_AT_FIELD = 'updated_at';

    public function __construct(
        array $supportedFields = [self::CREATED_AT_FIELD, self::UPDATED_AT_FIELD],
        array $supportedOperators = [Operators::IN_LIST, Operators::BETWEEN]
    ) {
        $this->supportedOperators = $supportedOperators;
        $this->supportedFields = $supportedFields;

    }

    /**
     * {@inheritdoc}
     */
    public function addFieldFilter($field, $operator, $value, $locale = null, $channel = null, $options = [])
    {
        if ($this->searchQueryBuilder === null) {
            throw new \LogicException('The search query builder is not initialized in the filter.');
        }

        if (! in_array($field, $this->supportedFields)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unsupported field name for datetime filter, only "%s" are supported, "%s" given',
                    implode(',', $this->supportedFields),
                    $field
                )
            );
        }

        switch ($operator) {
            case Operators::IN_LIST:
                $clause = [
                    'terms' => [
                        $field => array_map(function ($data) use ($field) {
                            return $this->getFormattedDateTime($field, $data);
                        }, $value),
                    ],
                ];

                $this->searchQueryBuilder::addFilter($clause);
                break;

            case Operators::BETWEEN:
                $values = array_values($value);
                $clause = [
                    'range' => [
                        $field => [
                            'gte' => $this->getFormattedDateTime($field, $values[0]),
                            'lte' => $this->getFormattedDateTime($field, $values[1]),
                        ],
                    ],
                ];

                $this->searchQueryBuilder::addFilter($clause);
                break;
        }

        return $this;
    }
}
