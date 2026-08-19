<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAttribute;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

/**
 * Builds a 2-level (color/size) structure carrying a `variant`-level common
 * placement, plus a configurable already populated with `$leaves` leaf variants
 * spread across colour groups. Returns the configurable and the codes so the
 * caller can re-submit an update tree that exercises `updateVariant` per leaf.
 */
function makeConfigurableWithLeaves(int $leaves): array
{
    $colorCode = 'color_'.Str::random(8);
    $sizeCode = 'size_'.Str::random(8);
    $materialCode = 'material_'.Str::random(8);

    $color = Attribute::factory()->create(['code' => $colorCode, 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => $sizeCode, 'type' => 'select']);
    $material = Attribute::factory()->create(['code' => $materialCode, 'type' => 'text']);

    $family = AttributeFamily::factory()->create(['code' => 'fam_'.Str::random(8)]);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'bp_'.Str::random(8),
        'name'                => 'BP',
        'levels'              => 2,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $size->id, 'level' => 'level_2', 'position' => 0],
    ]);

    VariantStructureAttribute::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $material->id, 'level' => 'variant'],
    ]);

    $repository = app(ProductRepository::class);

    $configurable = $repository->create([
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'sku'                  => 'TEE-'.Str::random(8),
        'variant_structure_id' => $structure->id,
        'super_attributes'     => [$colorCode, $sizeCode],
    ]);

    $variantNodes = [];

    for ($i = 0; $i < $leaves; $i++) {
        $variantNodes['variant_'.$i] = [
            'sku'    => $configurable->sku.'-RED-'.$i,
            'values' => ['common' => [$sizeCode => 'size_'.$i]],
        ];
    }

    $repository->update([
        'sku'            => $configurable->sku,
        'channel'        => 'default',
        'locale'         => 'en_US',
        'variant_groups' => [
            'group_1' => [
                'group_axis_option' => 'red',
                'group_values'      => [],
                'sku'               => $configurable->sku.'-RED',
                'variants'          => $variantNodes,
            ],
        ],
    ], $configurable->id);

    return [$configurable->fresh(), $colorCode, $sizeCode, $materialCode];
}

/**
 * Re-submits every existing leaf through `update()` (the `updateVariant` path)
 * and returns how many SELECTs hit the structure-placement table.
 */
function countPlacementQueriesOnResubmit($configurable, string $sizeCode, string $materialCode): int
{
    $group = $configurable->variants()->where('type', 'variant_group')->first();
    $leaves = $group->variants()->where('type', 'simple')->orderBy('id')->get();

    $variantNodes = [];

    foreach ($leaves as $i => $leaf) {
        $variantNodes[(string) $leaf->id] = [
            'sku'    => $leaf->sku,
            'values' => ['common' => [$sizeCode => 'size_'.$i, $materialCode => 'linen_'.$i]],
        ];
    }

    $count = 0;

    DB::listen(function ($query) use (&$count): void {
        if (str_contains($query->sql, 'variant_structure_attributes')) {
            $count++;
        }
    });

    app(ProductRepository::class)->update([
        'sku'            => $configurable->sku,
        'channel'        => 'default',
        'locale'         => 'en_US',
        'variant_groups' => [
            (string) $group->id => [
                'group_axis_option' => 'red',
                'group_values'      => [],
                'sku'               => $group->sku,
                'variants'          => $variantNodes,
            ],
        ],
    ], $configurable->id);

    return $count;
}

it('resolves the variant structure placements once regardless of leaf count', function () {
    [$small, , $sizeS, $materialS] = makeConfigurableWithLeaves(2);
    [$large, , $sizeL, $materialL] = makeConfigurableWithLeaves(5);

    $smallQueries = countPlacementQueriesOnResubmit($small, $sizeS, $materialS);
    $largeQueries = countPlacementQueriesOnResubmit($large, $sizeL, $materialL);

    expect($largeQueries)->toBe($smallQueries)
        ->and($smallQueries)->toBeLessThanOrEqual(1);
});
