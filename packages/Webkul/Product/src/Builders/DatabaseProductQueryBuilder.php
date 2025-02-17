<?php

namespace Webkul\Product\Builders;

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Services\AttributeService;
use Webkul\Product\Filter\FilterRegistry;
use Webkul\Product\Traits\ProductQueryBuilder;
use Webkul\Product\Facades\DatabaseSearchQuery;

class DatabaseProductQueryBuilder extends DatabaseAbstractEntityQueryBuilder
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
        
        $filter->setQueryBuilder($this->getQueryBuilder());
        $filter->addAttributeFilter($attribute, $operator, $value, $locale, $channel, $context);

        return $this;
    }
}