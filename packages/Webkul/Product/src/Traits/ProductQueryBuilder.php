<?php

namespace Webkul\Product\Traits;

use Illuminate\Support\Facades\DB;

trait ProductQueryBuilder
{

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
}