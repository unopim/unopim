<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Webkul\Admin\Http\Controllers\Catalog\ProductController;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAttribute;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

/**
 * Invokes the protected `buildVariantTree()` on a fresh ProductController
 * instance resolved from the container, mirroring the reflection pattern
 * already used for other protected-controller-method tests in this suite.
 */
function invokeBuildVariantTree(Product $product): ?array
{
    $controller = app(ProductController::class);

    $reflection = new ReflectionMethod($controller, 'buildVariantTree');
    $reflection->setAccessible(true);

    return $reflection->invoke($controller, $product);
}

it('returns the full variant tree blueprint for a 2-level variant structure', function () {
    $colorCode = 'color_'.Str::random(8);
    $sizeCode = 'size_'.Str::random(8);
    $metaCode = 'meta_'.Str::random(8);
    $descCode = 'desc_'.Str::random(8);

    $color = Attribute::factory()->create(['code' => $colorCode, 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => $sizeCode, 'type' => 'select']);
    $meta = Attribute::factory()->create(['code' => $metaCode, 'type' => 'textarea']);
    $desc = Attribute::factory()->create(['code' => $descCode, 'type' => 'text']);

    $redOptionCode = $color->options->first()->code;
    $sizeOptionCode = $size->options->first()->code;

    $family = AttributeFamily::factory()->create();

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily($family, [$color, $size, $meta, $desc]);

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
        ['variant_structure_id' => $structure->id, 'attribute_id' => $desc->id, 'level' => 'variant'],
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
        'group_axis_option' => $redOptionCode,
        'group_values'      => [$metaCode => 'Group description'],
        'sku'               => $configurable->sku.'-'.$redOptionCode,
    ]);

    $leaf = $type->createVariant($configurable, $configurable->super_attributes, [
        'parent_id' => $group->id,
        'sku'       => $configurable->sku.'-'.$redOptionCode.'-'.$sizeOptionCode,
        'values'    => ['common' => [$sizeCode => $sizeOptionCode, $descCode => 'Leaf description']],
    ]);

    $configurable->refresh();

    $tree = invokeBuildVariantTree($configurable);

    expect($tree)->not->toBeNull();

    expect($tree['levels'])->toBe(2)
        ->and($tree['axesByLevel'])->toBe([
            'level_1' => [$colorCode],
            'level_2' => [$sizeCode],
        ]);

    $attributesByCode = collect($tree['attributes'])->keyBy('code');

    expect($attributesByCode->has($colorCode))->toBeTrue()
        ->and($attributesByCode[$colorCode]['isAxis'])->toBeTrue()
        ->and($attributesByCode[$colorCode]['type'])->toBe('select')
        // Options are NEVER inlined — axis attributes can hold ~10k options each;
        // the axis dropdown fetches them async (searched + paginated). The
        // blueprint always carries `options => null` plus the attributeId.
        ->and($attributesByCode[$colorCode]['options'])->toBeNull()
        ->and($attributesByCode[$colorCode]['attributeId'])->not->toBeNull()
        ->and($attributesByCode[$sizeCode]['isAxis'])->toBeTrue()
        ->and($attributesByCode[$metaCode]['isAxis'])->toBeFalse()
        ->and($attributesByCode[$metaCode]['placement'])->toBe('sub_parent')
        ->and($attributesByCode[$descCode]['isAxis'])->toBeFalse()
        ->and($attributesByCode[$descCode]['placement'])->toBe('variant');

    // buildVariantTree() carries only the ancestry chain of the requested
    // node — invoked directly on the configurable, that's the configurable
    // alone. The group and leaf are fetched on demand via variantChildren().
    $nodes = $tree['nodes'];

    expect($nodes)->toHaveKey((string) $configurable->id)
        ->and($nodes)->not->toHaveKey((string) $group->id)
        ->and($nodes)->not->toHaveKey((string) $leaf->id);

    $configurableNode = $nodes[(string) $configurable->id];

    expect($configurableNode['role'])->toBe('configurable')
        ->and($configurableNode['parentId'])->toBeNull()
        ->and($configurableNode['sku'])->toBe($configurable->sku);
});

it('returns null for a configurable product without a variant structure (legacy)', function () {
    $configurable = Product::factory()->configurable()->withConfigurableAttributes()->create();

    expect($configurable->variant_structure_id)->toBeNull();

    expect(invokeBuildVariantTree($configurable))->toBeNull();
});

it('returns null for a non-configurable product', function () {
    $simple = Product::factory()->simple()->create();

    expect(invokeBuildVariantTree($simple))->toBeNull();
});

it('returns null for a simple variant whose parent configurable has no variant structure (legacy)', function () {
    $configurable = Product::factory()->configurable()->withConfigurableAttributes()->create();

    expect($configurable->variant_structure_id)->toBeNull();

    $leaf = Product::factory()->simple()->create(['parent_id' => $configurable->id]);

    expect(invokeBuildVariantTree($leaf))->toBeNull();
});

it('builds only the ancestry chain - configurable + group + leaf - never a sibling leaf, whether invoked on a variant_group or a simple leaf', function () {
    $colorCode = 'color_'.Str::random(8);
    $sizeCode = 'size_'.Str::random(8);

    $color = Attribute::factory()->create(['code' => $colorCode, 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => $sizeCode, 'type' => 'select']);

    $redOptionCode = $color->options->first()->code;
    $sizeOptionCode = $size->options->first()->code;
    $otherSizeOptionCode = $size->options->skip(1)->first()->code;

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
        'group_axis_option' => $redOptionCode,
        'group_values'      => [],
        'sku'               => $configurable->sku.'-'.$redOptionCode,
    ]);

    $leaf = $type->createVariant($configurable, $configurable->super_attributes, [
        'parent_id' => $group->id,
        'sku'       => $configurable->sku.'-'.$redOptionCode.'-'.$sizeOptionCode,
        'values'    => ['common' => [$sizeCode => $sizeOptionCode]],
    ]);

    // Sibling leaf under the same group — must never appear in either
    // node's ancestry chain, however many thousands of these exist.
    $sibling = $type->createVariant($configurable, $configurable->super_attributes, [
        'parent_id' => $group->id,
        'sku'       => $configurable->sku.'-'.$redOptionCode.'-'.$otherSizeOptionCode,
        'values'    => ['common' => [$sizeCode => $otherSizeOptionCode]],
    ]);

    $configurable->refresh();

    $groupTree = invokeBuildVariantTree($group->refresh());
    $leafTree = invokeBuildVariantTree($leaf->refresh());

    expect($groupTree)->not->toBeNull()
        ->and($leafTree)->not->toBeNull();

    expect($groupTree['configurableId'])->toBe($configurable->id)
        ->and($groupTree['currentNodeId'])->toBe($group->id)
        ->and($groupTree['levels'])->toBe(2)
        ->and($groupTree['nodes'])->toHaveKey((string) $configurable->id)
        ->and($groupTree['nodes'])->toHaveKey((string) $group->id)
        ->and($groupTree['nodes'])->not->toHaveKey((string) $leaf->id)
        ->and($groupTree['nodes'])->not->toHaveKey((string) $sibling->id);

    expect($leafTree['configurableId'])->toBe($configurable->id)
        ->and($leafTree['currentNodeId'])->toBe($leaf->id)
        ->and($leafTree['levels'])->toBe(2)
        ->and($leafTree['nodes'])->toHaveKey((string) $configurable->id)
        ->and($leafTree['nodes'])->toHaveKey((string) $group->id)
        ->and($leafTree['nodes'])->toHaveKey((string) $leaf->id)
        ->and($leafTree['nodes'])->not->toHaveKey((string) $sibling->id);
});
