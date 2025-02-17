<?php

namespace Webkul\Product\Filter\Database\Field;

use Webkul\ElasticSearch\Contracts\FilterInterface;
use Webkul\ElasticSearch\Filter\Operators;
use Webkul\Product\Filter\AbstractFieldFilter;

/**
 * Id filter for an Database query
 */
class IdFilter extends AbstractFieldFilter implements FilterInterface
{
    const FIELD = 'product_id';

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
                    'Unsupported field name for id filter, only "%s" are supported, "%s" given',
                    implode(',', $this->supportedFields),
                    $field
                )
            );
        }

        switch ($operator) {
            case Operators::IN_LIST:
                $this->searchQueryBuilder->whereIn('products.id', $value);
                break;
        }

        return $this;
    }
}
