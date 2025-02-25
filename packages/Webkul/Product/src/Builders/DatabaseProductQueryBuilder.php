<?php

namespace Webkul\Product\Builders;

use Webkul\Attribute\Services\AttributeService;
use Webkul\Product\Filter\FilterManager;
use Webkul\Product\Traits\ProductQueryFilter;

class DatabaseProductQueryBuilder extends AbstractFilterableQueryBuilder
{
    use ProductQueryFilter;

    public function __construct(
        protected AttributeService $attributeService,
        protected FilterManager $filterManager
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

        $filter->setQueryManager($this->getQueryManager());

        if (! $filter->isOperatorAllowed($operator)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unsupported operator. Only "%s" are supported, but "%s" was given.',
                    implode(',', $filter->getAllowedOperators()),
                    $operator
                )
            );
        }

        $filter->addAttributeFilter($attribute, $operator, $value, $locale, $channel, $context);

        return $this;
    }
}
