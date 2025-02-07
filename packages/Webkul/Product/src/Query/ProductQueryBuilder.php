<?php

namespace Webkul\Product\Query;

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Services\AttributeService; 
use Webkul\ElasticSearch\AbstractEntityQueryBuilder;
use Webkul\Product\ElasticSearch\Filter\FilterRegistry;
use Webkul\ElasticSearch\Facades\SearchQuery;

class ProductQueryBuilder extends AbstractEntityQueryBuilder
{
    public function __construct(
        protected AttributeService $attributeService,
        protected FilterRegistry $filterRegistry
    ) {
    }
    /**
     * Prepare query builder.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    public function prepareQueryBuilder()
    {
        $this->qb = DB::table('products')
            ->leftJoin('attribute_families as af', 'products.attribute_family_id', '=', 'af.id')
            ->leftJoin('products as parent_products', 'products.parent_id', '=', 'parent_products.id');

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addFilter($field, $operator, $value, array $context = [])
    {
        $attribute = $this->attributeService->findAttributeByCode($field);
        
        $filterType = 'field';

        if (!$attribute) {
            // $this->addFieldFilter($field, $operator, $value, $context);
        } else {
            $filterType = 'attribute';
            $filter = $this->filterRegistry->getAttributeFilter($attribute, $operator);
            $this->addAttributeFilter($filter, $attribute, $operator, $value, $context);
        }

        $this->rawFilters[] = [
            'field'    => $field,
            'operator' => $operator,
            'value'    => $value,
            'context'  => $context,
            'type'     => $filterType
        ];

        return $this;
    }

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
        $scope = $attribute->value_per_channel ? $context['scope'] : null;

        $filter->setQueryBuilder(new SearchQuery());
        $filter->addAttributeFilter($attribute, $operator, $value, $locale, $scope, $context);

        return $this;
    }
}