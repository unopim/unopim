<?php

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAxis;

uses(DatabaseTransactions::class);

/**
 * A family whose variant structure uses one of its own attributes as the level 1 axis.
 */
function familyWithVariantAxis(): array
{
    $axis = Attribute::factory()->create(['code' => 'color_'.Str::random(8), 'type' => 'select']);

    $factory = AttributeFamily::factory();

    $family = $factory->create(['code' => 'fam_'.Str::random(8)]);

    $factory->linkAttributeGroupToFamily($family);

    $family->refresh();

    $factory->linkAttributesToFamily($family, $axis);

    $family->refresh();

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'vs_'.Str::random(8),
        'name'                => 'VS',
        'levels'              => 1,
    ]);

    VariantStructureAxis::create([
        'variant_structure_id' => $structure->id,
        'attribute_id'         => $axis->id,
        'level'                => 'level_1',
        'position'             => 1,
    ]);

    return [$family, $axis, $structure];
}

/**
 * The payload the family editor posts: every group with the attributes it keeps.
 */
function familyUpdatePayload(AttributeFamily $family, array $attributeIds): array
{
    $mapping = $family->attributeFamilyGroupMappings->first();

    return [
        'code'             => $family->code,
        'attribute_groups' => [
            $mapping->attribute_group_id => [
                'attribute_groups_mapping' => $mapping->id,
                'position'                 => 1,
                'custom_attributes'        => array_map(
                    fn (int $id, int $index): array => ['id' => $id, 'position' => $index + 1],
                    $attributeIds,
                    array_keys($attributeIds)
                ),
            ],
        ],
    ];
}

it('refuses to save a family that drops an attribute still used as a variant axis', function () {
    $this->loginAsAdmin();

    [$family, $axis] = familyWithVariantAxis();

    $this->put(
        route('admin.catalog.families.update', $family->id),
        familyUpdatePayload($family, [])
    )->assertSessionHasErrors('code');

    expect($family->fresh()->customAttributes()->pluck('attributes.id')->all())
        ->toContain($axis->id);
});

it('saves a family whose variant axis attributes are all still assigned', function () {
    $this->loginAsAdmin();

    [$family, $axis] = familyWithVariantAxis();

    $this->put(
        route('admin.catalog.families.update', $family->id),
        familyUpdatePayload($family, [$axis->id])
    )->assertSessionHasNoErrors();
});

it('checks the variant axis guard without binding one parameter per family attribute', function () {
    $this->loginAsAdmin();

    [$family, $axis] = familyWithVariantAxis();

    $factory = AttributeFamily::factory();

    $extraIds = [];

    foreach (range(1, 30) as $index) {
        $attribute = Attribute::factory()->create(['code' => 'extra_'.$index.'_'.Str::random(6)]);

        $factory->linkAttributesToFamily($family->fresh(), $attribute);

        $extraIds[] = $attribute->id;
    }

    $bindingCounts = [];

    DB::listen(function (QueryExecuted $query) use (&$bindingCounts): void {
        if (str_contains($query->sql, 'variant_structure_axes')) {
            $bindingCounts[] = count($query->bindings);
        }
    });

    $this->put(
        route('admin.catalog.families.update', $family->id),
        familyUpdatePayload($family, [$axis->id, ...$extraIds])
    )->assertSessionHasNoErrors();

    expect($bindingCounts)->not->toBeEmpty()
        ->and(max($bindingCounts))->toBeLessThan(10);
});
