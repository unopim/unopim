<?php

namespace Webkul\Completeness\Repositories;

use Illuminate\Support\Facades\DB;
use Webkul\Completeness\Contracts\CompletenessSetting;
use Webkul\Core\Eloquent\Repository;

class CompletenessSettingsRepository extends Repository
{
    /**
     * Specify model class name.
     */
    public function model(): string
    {
        return CompletenessSetting::class;
    }

    /**
     * The given attribute ids that are not assigned to the family.
     *
     * Completeness settings are family-scoped, so a setting for an attribute the family
     * does not hold could never be satisfied and would pull every product's score down.
     *
     * @param  iterable<int|string>  $attributeIds
     * @return array<int, int>
     */
    public function attributeIdsOutsideFamily(int $familyId, iterable $attributeIds): array
    {
        $requested = collect($attributeIds)->map(fn ($id): int => (int) $id)->unique()->values();

        if ($requested->isEmpty()) {
            return [];
        }

        $assigned = DB::table('attribute_group_mappings')
            ->join(
                'attribute_family_group_mappings',
                'attribute_family_group_mappings.id',
                '=',
                'attribute_group_mappings.attribute_family_group_id'
            )
            ->where('attribute_family_group_mappings.attribute_family_id', $familyId)
            ->whereIn('attribute_group_mappings.attribute_id', $requested->all())
            ->pluck('attribute_group_mappings.attribute_id')
            ->map(fn ($id): int => (int) $id);

        return $requested->diff($assigned)->values()->all();
    }
}
