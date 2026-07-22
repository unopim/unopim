<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAttribute;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

/**
 * Builds a fresh 2-level (color/size) variant structure with a `sub_parent`
 * attribute, plus a configurable product carrying one `variant_group` child
 * and one nested `simple` leaf. Uses globally-unique, randomly-suffixed
 * attribute/family codes since this suite runs against a live/seeded DB.
 */
function makeTwoLevelConfigurableWithGroup(): array
{
    $colorCode = 'color_'.Str::random(8);
    $sizeCode = 'size_'.Str::random(8);
    $metaCode = 'meta_'.Str::random(8);

    $color = Attribute::factory()->create(['code' => $colorCode, 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => $sizeCode, 'type' => 'select']);
    $meta = Attribute::factory()->create(['code' => $metaCode, 'type' => 'textarea']);

    $family = AttributeFamily::factory()->create();

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
        ['variant_structure_id' => $structure->id, 'attribute_id' => $meta->id, 'level' => 'sub_parent'],
    ]);

    $configurable = app(ProductRepository::class)->create([
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'sku'                  => 'TEE-'.Str::random(8),
        'variant_structure_id' => $structure->id,
        'super_attributes'     => [$colorCode, $sizeCode],
    ]);

    $type = $configurable->getTypeInstance();

    $group = $type->createVariantGroup($configurable, [
        'group_axis_code'   => $colorCode,
        'group_axis_option' => 'red',
        'group_values'      => [$metaCode => 'Red group description'],
        'sku'               => $configurable->sku.'-red',
    ]);

    $type->createVariant($configurable, $configurable->super_attributes, [
        'parent_id' => $group->id,
        'sku'       => $configurable->sku.'-red-s',
        'values'    => ['common' => [$sizeCode => 's']],
    ]);

    return [$configurable, $group];
}

function makeOneLevelConfigurable(): Product
{
    $colorCode = 'color_'.Str::random(8);

    $color = Attribute::factory()->create(['code' => $colorCode, 'type' => 'select']);

    $family = AttributeFamily::factory()->create();

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'bp_'.Str::random(8),
        'name'                => 'BP',
        'levels'              => 1,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
    ]);

    return app(ProductRepository::class)->create([
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'sku'                  => 'TEE-'.Str::random(8),
        'variant_structure_id' => $structure->id,
        'super_attributes'     => [$colorCode],
    ]);
}

it('exposes the variant tree on the edit page for a 2-level variant structure', function () {
    $this->loginAsAdmin();

    [$configurable, $group] = makeTwoLevelConfigurableWithGroup();

    $response = $this->get(route('admin.catalog.products.edit', $configurable->id))
        ->assertOk();

    $content = $response->getContent();

    expect($content)
        ->toContain('window.__variantTree')
        ->toContain('"levels":2')
        ->toContain('"configurableId":'.$configurable->id)
        ->toContain('"totalVariants":1');

    // Ancestry-only: descendants are fetched on demand, never inlined.
    expect($content)->not->toContain('"'.$group->id.'":{');
});

it('exposes the variant tree for a 1-level variant structure', function () {
    $this->loginAsAdmin();

    $configurable = makeOneLevelConfigurable();

    $response = $this->get(route('admin.catalog.products.edit', $configurable->id))
        ->assertOk();

    expect($response->getContent())
        ->toContain('window.__variantTree')
        ->toContain('"levels":1');
});

it('keeps the legacy flat UI for a configurable product without a variant structure', function () {
    $this->loginAsAdmin();

    $configurable = Product::factory()->configurable()->withConfigurableAttributes()->create();

    expect($configurable->variant_structure_id)->toBeNull();

    $response = $this->get(route('admin.catalog.products.edit', $configurable->id))
        ->assertOk();

    expect($response->getContent())
        ->not->toContain('window.__variantTree')
        ->toContain('v-product-variations');
});

it('keeps firing the configurable type view render events for a structured configurable', function () {
    $this->loginAsAdmin();

    [$configurable] = makeTwoLevelConfigurableWithGroup();

    $fired = [];

    foreach (['before', 'after'] as $hook) {
        Event::listen(
            'unopim.admin.catalog.product.edit.form.types.configurable.'.$hook,
            function () use ($hook, &$fired) {
                $fired[] = $hook;
            }
        );
    }

    $this->get(route('admin.catalog.products.edit', $configurable->id))->assertOk();

    expect($fired)->toBe(['before', 'after']);
});
