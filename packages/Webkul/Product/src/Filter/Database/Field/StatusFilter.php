<?php

namespace Webkul\Product\Filter\Database\Field;

use Webkul\ElasticSearch\Contracts\FilterInterface;
use Webkul\ElasticSearch\Filter\Operators;
use Webkul\Product\Filter\AbstractFieldFilter;

/**
 * Status filter for an Elasticsearch query
 */
class StatusFilter extends AbstractFieldFilter implements FilterInterface
{
    const FIELD = 'status';

    public function __construct(
        array $supportedFields = [self::FIELD],
        array $supportedOperators = [Operators::IN_LIST]
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
                    'Unsupported field name for sku filter, only "%s" are supported, "%s" given',
                    implode(',', $this->supportedFields),
                    $field
                )
            );
        }

        switch ($operator) {
            case Operators::IN_LIST:
                $this->searchQueryBuilder->whereIn(sprintf('products.%s', $field), $value);
                break;
        }

        return $this;
    }
}
