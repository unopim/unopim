<?php

namespace Webkul\Product\Filter\Database\Field;

use Webkul\ElasticSearch\Contracts\FilterInterface;
use Webkul\ElasticSearch\Filter\Operators;
use Webkul\ElasticSearch\QueryString;
use Webkul\Product\Filter\AbstractFieldFilter;

/**
 * Sku filter for an Elasticsearch query
 */
class SkuFilter extends AbstractFieldFilter implements FilterInterface
{
    const FIELD = 'sku';

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
                $this->searchQueryBuilder->whereIn('products.sku', $value);

                break;
            case Operators::CONTAINS:
                $this->searchQueryBuilder->where(function ($query) use ($value) {
                    foreach ($value as $val) {
                        $escapedValue = QueryString::escapeValue($val);
                        $query->orWhere('products.sku', 'LIKE', "%{$escapedValue}%");
                    }
                });
                
                break;
        }

        return $this;
    }
}
