<?php

namespace Webkul\Product\Traits;

use Illuminate\Support\Facades\DB;

trait ProductQueryFilter
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
    public function applyFilter($property, $operator, $value, array $context = [])
    {
        $attribute = null;
        $filter = $this->filterManager->getPropertyFilter($property, $operator);

        $filterType = 'property';

        if (! $filter) {
            $attribute = $this->attributeService->findAttributeByCode($property);
            if ($attribute) {
                $filterType = 'attribute';
                $filter = $this->filterManager->getAttributeFilter($attribute, $operator);
            }
        }

        if (! $filter) {
            throw new \Exception("No matching filter found for property: {$property}");
        }

        if (! $attribute) {
            $this->applyPropertyFilter($filter, $property, $operator, $value, $context);
        } else {
            $this->addAttributeFilter($filter, $attribute, $operator, $value, $context);
        }

        $this->rawFilters[] = [
            'property'    => $property,
            'operator'    => $operator,
            'value'       => $value,
            'context'     => $context,
            'type'        => $filterType,
        ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function applySkuOrUnfilteredFilter($property, $operator, $value, array $context = [])
    {
        $filter = $this->filterManager->getSkuOrUnfilteredFilter();
        $this->applyUnfilteredFilter($filter, $property, $operator, $value, $context);

        return $this;
    }
}
