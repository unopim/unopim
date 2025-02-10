<?php

namespace Webkul\Product\Query;

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Services\AttributeService;
use Webkul\ElasticSearch\AbstractEntityQueryBuilder;
use Webkul\ElasticSearch\Facades\SearchQuery;
use Webkul\Product\ElasticSearch\Filter\FilterRegistry;

class ProductQueryBuilder extends AbstractEntityQueryBuilder
{
    public function __construct(
        protected AttributeService $attributeService,
        protected FilterRegistry $filterRegistry
    ) {}

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
        $attribute = null;
        $filter = $this->filterRegistry->getFieldFilter($field, $operator);

        $filterType = 'field';

        if (! $filter) {
            $attribute = $this->attributeService->findAttributeByCode($field);
            if ($attribute) {
                $filterType = 'attribute';
                $filter = $this->filterRegistry->getAttributeFilter($attribute, $operator);
            }
        }

        if (! $filter) {
            throw new \Exception("No matching filter found for field: {$field}");
        }

        if (! $attribute) {
            $this->addFieldFilter($filter, $field, $operator, $value, $context);
        } else {
            $this->addAttributeFilter($filter, $attribute, $operator, $value, $context);
        }

        $this->rawFilters[] = [
            'field'    => $field,
            'operator' => $operator,
            'value'    => $value,
            'context'  => $context,
            'type'     => $filterType,
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
        $channel = $attribute->value_per_channel ? $context['channel'] : null;

        $filter->setQueryBuilder(new SearchQuery);
        $filter->addAttributeFilter($attribute, $operator, $value, $locale, $channel, $context);

        return $this;
    }
}
