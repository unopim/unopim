<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

it('does not prune variants when the configurable is saved without a variants payload', function () {
    $colorCode = 'color_'.Str::random(8);

    $color = Attribute::factory()->create(['code' => $colorCode, 'type' => 'select']);

    $family = AttributeFamily::factory()->create(['code' => 'fam_'.Str::random(8)]);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'bp_'.Str::random(8),
        'name'                => 'BP',
        'levels'              => 1,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
    ]);

    $configurable = app(ProductRepository::class)->create([
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'sku'                  => 'TEE-'.Str::random(8),
        'variant_structure_id' => $structure->id,
        'super_attributes'     => [$colorCode],
    ]);

    $optionCode = $color->options->first()->code;

    $leaf = $configurable->getTypeInstance()->createVariant($configurable, $configurable->super_attributes, [
        'parent_id' => $configurable->id,
        'sku'       => $configurable->sku.'-'.$optionCode,
        'values'    => ['common' => [$colorCode => $optionCode]],
    ]);

    expect(Product::where('parent_id', $configurable->id)->count())->toBe(1);

    $configurable->getTypeInstance()->update([
        'sku'    => $configurable->sku,
        'values' => $configurable->values,
    ], $configurable->id);

    expect(Product::where('parent_id', $configurable->id)->whereKey($leaf->id)->exists())->toBeTrue()
        ->and(Product::where('parent_id', $configurable->id)->count())->toBe(1);
});
