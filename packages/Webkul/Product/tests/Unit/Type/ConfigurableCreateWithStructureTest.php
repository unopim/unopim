<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

it('persists the structure id and axis super_attributes on create', function () {
    // Codes are suffixed with a random string because this suite runs against a
    // seeded database that already has attributes named "color", "size", etc.
    $colorCode = 'color_'.Str::random(8);
    $sizeCode = 'size_'.Str::random(8);

    $color = Attribute::factory()->create(['code' => $colorCode, 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => $sizeCode, 'type' => 'select']);

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

    $product = app(ProductRepository::class)->create([
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'sku'                  => 'CFG-STRUCT-'.Str::random(8),
        'variant_structure_id' => $structure->id,
        'super_attributes'     => [$colorCode, $sizeCode],
    ]);

    expect($product->variant_structure_id)->toBe($structure->id)
        ->and($product->super_attributes->pluck('code')->sort()->values()->all())->toBe(collect([$colorCode, $sizeCode])->sort()->values()->all());
});
