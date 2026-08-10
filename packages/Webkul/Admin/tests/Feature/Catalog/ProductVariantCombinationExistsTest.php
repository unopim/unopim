<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

/**
 * A 2-level structure splitting on two axes (colour + brand) then one (size), so a
 * `variant_group` owns a pair of axis values and a `simple` leaf a single one. Codes
 * are randomly suffixed because this suite runs against a live, seeded database where
 * `attributes.code` is globally unique.
 */
function makeTwoAxisGroupConfigurable(): array
{
    $color = Attribute::factory()->create(['code' => 'color_'.Str::random(8), 'type' => 'select']);
    $brand = Attribute::factory()->create(['code' => 'brand_'.Str::random(8), 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => 'size_'.Str::random(8), 'type' => 'select']);

    $family = AttributeFamily::factory()->create(['code' => 'fam_'.Str::random(8)]);

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily($family, collect([$color, $brand, $size]));

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'vs_'.Str::random(8),
        'name'                => 'VS',
        'levels'              => 2,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $brand->id, 'level' => 'level_1', 'position' => 1],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $size->id, 'level' => 'level_2', 'position' => 0],
    ]);

    $configurable = app(ProductRepository::class)->create([
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'sku'                  => 'TEE-'.Str::random(8),
        'variant_structure_id' => $structure->id,
        'super_attributes'     => [$color->code, $brand->code, $size->code],
    ]);

    return [$configurable, $color, $brand, $size];
}

function makeGroupForCombinationTest(Product $configurable, array $groupValues, string $suffix): Product
{
    return $configurable->getTypeInstance()->createVariantGroup($configurable, [
        'group_values' => $groupValues,
        'sku'          => $configurable->sku.'-'.$suffix,
    ]);
}

function makeLeafForCombinationTest(Product $configurable, Product $group, array $leafValues, string $suffix): Product
{
    $leaf = $configurable->getTypeInstance()->createVariant($configurable, $configurable->super_attributes, [
        'parent_id' => $group->id,
        'sku'       => $group->sku.'-'.$suffix,
        'values'    => ['common' => $leafValues],
    ]);

    return Product::find($leaf->id);
}

function putCommonValuesForCombinationTest($test, Product $product, array $commonValues)
{
    return $test->put(route('admin.catalog.products.update', $product->id), [
        'sku'    => $product->sku,
        'values' => [
            'common' => array_merge(['sku' => $product->sku], $commonValues),
        ],
    ]);
}

it('reports the colliding combination as a variant group when a group is renamed onto a sibling', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->loginAsAdmin();

    [$configurable, $color, $brand] = makeTwoAxisGroupConfigurable();

    $black = $color->options->first()->code;
    $aurex = $brand->options->first()->code;
    $verano = $brand->options->get(1)->code;

    $groupA = makeGroupForCombinationTest($configurable, [$color->code => $black, $brand->code => $aurex], 'a');

    makeGroupForCombinationTest($configurable, [$color->code => $black, $brand->code => $verano], 'b');

    $expected = trans('admin::app.catalog.products.edit.types.configurable.variant-group-combination-exists', [
        'values' => $color->code.': '.$black.', '.$brand->code.': '.$verano,
    ]);

    putCommonValuesForCombinationTest($this, $groupA, [
        $color->code => $black,
        $brand->code => $verano,
    ])->assertSessionHas('warning', $expected);

    expect($expected)->toContain($black)
        ->and($expected)->toContain($verano)
        ->and($expected)->toContain('variant group');
});

it('reports the colliding combination as a variant when a leaf is renamed onto a sibling', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->loginAsAdmin();

    [$configurable, $color, $brand, $size] = makeTwoAxisGroupConfigurable();

    $black = $color->options->first()->code;
    $aurex = $brand->options->first()->code;
    $small = $size->options->first()->code;
    $large = $size->options->get(1)->code;

    $group = makeGroupForCombinationTest($configurable, [$color->code => $black, $brand->code => $aurex], 'a');

    $leaf = makeLeafForCombinationTest($configurable, $group, [$size->code => $small], 's');

    makeLeafForCombinationTest($configurable, $group, [$size->code => $large], 'l');

    $expected = trans('admin::app.catalog.products.edit.types.configurable.variant-combination-exists', [
        'values' => $size->code.': '.$large,
    ]);

    putCommonValuesForCombinationTest($this, $leaf, [$size->code => $large])
        ->assertSessionHas('warning', $expected);

    expect($expected)->toContain($large)
        ->and($expected)->not->toContain('variant group');
});

it('does not treat two groups differing only in the second level_1 axis as the same combination', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->loginAsAdmin();

    [$configurable, $color, $brand] = makeTwoAxisGroupConfigurable();

    $black = $color->options->first()->code;
    $aurex = $brand->options->first()->code;
    $verano = $brand->options->get(1)->code;
    $gamma = $brand->options->get(2)->code;

    $groupA = makeGroupForCombinationTest($configurable, [$color->code => $black, $brand->code => $aurex], 'a');

    makeGroupForCombinationTest($configurable, [$color->code => $black, $brand->code => $verano], 'b');

    putCommonValuesForCombinationTest($this, $groupA, [
        $color->code => $black,
        $brand->code => $gamma,
    ])
        ->assertSessionMissing('warning')
        ->assertSessionHas('success', trans('admin::app.catalog.products.update-success'));

    $groupA->refresh();

    expect($groupA->values['common'][$brand->code])->toBe($gamma)
        ->and($groupA->values['common'][$color->code])->toBe($black);
});

it('still refuses a group renamed onto an identical level_1 tuple', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->loginAsAdmin();

    [$configurable, $color, $brand] = makeTwoAxisGroupConfigurable();

    $black = $color->options->first()->code;
    $aurex = $brand->options->first()->code;
    $verano = $brand->options->get(1)->code;

    $groupA = makeGroupForCombinationTest($configurable, [$color->code => $black, $brand->code => $aurex], 'a');

    makeGroupForCombinationTest($configurable, [$color->code => $black, $brand->code => $verano], 'b');

    putCommonValuesForCombinationTest($this, $groupA, [
        $color->code => $black,
        $brand->code => $verano,
    ])->assertSessionMissing('success');

    $groupA->refresh();

    expect($groupA->values['common'][$brand->code])->toBe($aurex);
});
