<?php

namespace Webkul\Product\Builders;

use Webkul\Attribute\Services\AttributeService;
use Webkul\ElasticSearch\AbstractEntityQueryBuilder;
use Webkul\ElasticSearch\Facades\SearchQuery;
use Webkul\Product\Filter\FilterRegistry;
use Webkul\Product\Traits\ProductQueryBuilder;

class ElasticProductQueryBuilder extends AbstractEntityQueryBuilder
{
    use ProductQueryBuilder;

    public function __construct(
        protected AttributeService $attributeService,
        protected FilterRegistry $filterRegistry
    ) {}

    /**
     * Add a filter condition on an attribute
     */
    protected function addAttributeFilter(
        $filter,
        $attribute,
        $operator,
        $value,
        array $context
    ) {
        $locale = $attribute->value_per_locale ? $context['locale'] : null;
        $channel = $attribute->value_per_channel ? $context['channel'] : null;

        $filter->setQueryBuilder(new SearchQuery);

        if (! $filter->supportsOperator($operator)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unsupported operator. Only "%s" are supported, but "%s" was given.',
                    implode(',', $filter->getOperators()),
                    $operator
                )
            );
        }

        $filter->addAttributeFilter($attribute, $operator, $value, $locale, $channel, $context);

        return $this;
    }
}
