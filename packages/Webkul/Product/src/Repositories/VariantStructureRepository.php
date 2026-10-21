<?php

namespace Webkul\Product\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Webkul\Core\Eloquent\Repository;
use Webkul\Product\Contracts\VariantStructure;

class VariantStructureRepository extends Repository
{
    public function model(): string
    {
        return VariantStructure::class;
    }

    /**
     * Query builder with the axis and placement rows eager-loaded, so rendering
     * a page of structures never issues one query per row.
     *
     * @return Builder
     */
    public function queryBuilder()
    {
        return $this->with(['axes.attribute', 'placements.attribute']);
    }

    /**
     * Find one structure by its code within a single attribute family.
     *
     * Structure codes are unique per family only, so the family is part of the
     * lookup key rather than a check applied after the fact.
     */
    public function findByFamilyAndCode(int $attributeFamilyId, string $code): ?VariantStructure
    {
        return $this->queryBuilder()->findOneWhere([
            'attribute_family_id' => $attributeFamilyId,
            'code'                => $code,
        ]);
    }
}
