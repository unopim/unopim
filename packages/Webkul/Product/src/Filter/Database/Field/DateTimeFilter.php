<?php

namespace Webkul\Product\Filter\Database\Field;

use Webkul\ElasticSearch\Contracts\FilterInterface;
use Webkul\ElasticSearch\Filter\Operators;
use Webkul\Product\Filter\AbstractFieldFilter;

/**
 * DateTime filter for an Database query
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
                $this->searchQueryBuilder->whereIn(sprintf('%s.%s', $this->getSearchTablePath($options), $field), $value);

                break;

            case Operators::BETWEEN:
                $this->searchQueryBuilder->whereBetween(sprintf('%s.%s', $this->getSearchTablePath($options), $field), [
                    ($value[0] ?? '').' 00:00:01',
                    ($value[1] ?? '').' 23:59:59',
                ]);

                break;
        }

        return $this;
    }
}
