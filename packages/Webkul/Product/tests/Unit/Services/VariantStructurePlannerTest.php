<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Contracts\VariantStructurePlanner as PlannerContract;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAttribute;
use Webkul\Product\Models\VariantStructureAxis;

uses(DatabaseTransactions::class);

/**
 * @return array{structure: VariantStructure, colorCode: string, sizeCode: string, swatchCode: string, priceCode: string}
 */
function makeStructure(): array
{
    // Codes are suffixed with a random string because this suite runs against a
    // seeded database that already has attributes named "color", "size", etc.
    $colorCode = 'color_'.Str::random(8);
    $sizeCode = 'size_'.Str::random(8);
    $swatchCode = 'swatch_'.Str::random(8);
    $priceCode = 'price_'.Str::random(8);

    $color = Attribute::factory()->create(['code' => $colorCode, 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => $sizeCode, 'type' => 'select']);
    $image = Attribute::factory()->create(['code' => $swatchCode, 'type' => 'image']);
    $price = Attribute::factory()->create(['code' => $priceCode, 'type' => 'price']);

    $family = AttributeFamily::factory()->create();

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'blueprint_'.Str::random(8),
        'name'                => 'Blueprint',
        'levels'              => 2,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $size->id, 'level' => 'level_2', 'position' => 0],
    ]);

    VariantStructureAttribute::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $image->id, 'level' => 'sub_parent'],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $price->id, 'level' => 'variant'],
    ]);

    return [
        'structure'  => $structure->refresh(),
        'colorCode'  => $colorCode,
        'sizeCode'   => $sizeCode,
        'swatchCode' => $swatchCode,
        'priceCode'  => $priceCode,
    ];
}

it('reads axis codes per level', function () {
    $planner = app(PlannerContract::class);
    ['structure' => $s, 'colorCode' => $colorCode, 'sizeCode' => $sizeCode] = makeStructure();

    expect($planner->axisCodesByLevel($s))->toBe(['level_1' => [$colorCode], 'level_2' => [$sizeCode]])
        ->and($planner->allAxisCodes($s))->toBe([$colorCode, $sizeCode]);
});

it('reads a placement, defaulting to common', function () {
    $planner = app(PlannerContract::class);
    ['structure' => $s, 'swatchCode' => $swatchCode, 'priceCode' => $priceCode] = makeStructure();

    expect($planner->placementOf($s, $swatchCode))->toBe('sub_parent')
        ->and($planner->placementOf($s, $priceCode))->toBe('variant')
        ->and($planner->placementOf($s, 'brand'))->toBe('common');
});

it('lists attribute codes at a level', function () {
    $planner = app(PlannerContract::class);
    ['structure' => $s, 'swatchCode' => $swatchCode, 'priceCode' => $priceCode] = makeStructure();

    expect($planner->attributeCodesAtLevel($s, 'sub_parent'))->toBe([$swatchCode])
        ->and($planner->attributeCodesAtLevel($s, 'variant'))->toBe([$priceCode]);
});
