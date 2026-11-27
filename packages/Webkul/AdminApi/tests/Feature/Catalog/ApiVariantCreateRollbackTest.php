<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Enums\ProductType;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

beforeEach(function (): void {
    $this->headers = $this->getAuthenticationHeaders();
});

/**
 * Build a two-level tree and return its family, axes and the colour variant group.
 *
 * @return array{0: AttributeFamily, 1: Attribute, 2: Attribute, 3: Product}
 */
function apiVariantRollbackGroup(string $prefix): array
{
    $family = AttributeFamily::factory()->create();
    $color = Attribute::factory()->create(['code' => $prefix.'_color_'.uniqid(), 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => $prefix.'_size_'.uniqid(), 'type' => 'select']);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id, 'code' => $prefix.'_structure_'.uniqid(), 'levels' => 2,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $size->id, 'level' => 'level_2', 'position' => 0],
    ]);

    $configurable = app(ProductRepository::class)->create([
        'sku'                  => $prefix.'-config-'.uniqid(),
        'type'                 => ProductType::Configurable->value,
        'attribute_family_id'  => $family->id,
        'variant_structure_id' => $structure->id,
    ]);

    $group = $configurable->getTypeInstance()->createVariantGroup($configurable, [
        'sku' => $prefix.'-group', 'group_axis_code' => $color->code, 'group_axis_option' => $color->options->first()->code,
    ]);

    return [$family, $color, $size, $group];
}

/**
 * Build a leaf create payload for the given group, optionally overriding the group-owned colour.
 *
 * @param  array<string, string>  $extraCommon
 * @return array<string, mixed>
 */
function apiVariantRollbackPayload(string $sku, AttributeFamily $family, Product $group, Attribute $size, array $extraCommon = []): array
{
    return [
        'parent'  => $group->sku,
        'family'  => $family->code,
        'values'  => ['common' => array_merge(['sku' => $sku], $extraCommon)],
        'variant' => ['attributes' => [$size->code => $size->options->first()->code]],
    ];
}

/**
 * Record the SKU of every product announced through the create-after event.
 */
function apiVariantRollbackAnnouncedSkus(): ArrayObject
{
    $announced = new ArrayObject;

    Event::listen('catalog.product.create.after', function ($product) use ($announced): void {
        $announced[] = $product->sku;
    });

    return $announced;
}

it('rejects a leaf that changes the group-owned axis without saving the leaf', function (): void {
    [$family, $color, $size, $group] = apiVariantRollbackGroup('vrb_reject');

    $payload = apiVariantRollbackPayload('vrb-reject-leaf', $family, $group, $size, [$color->code => $color->options->last()->code]);

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.products.store'), $payload)
        ->assertStatus(422)
        ->assertJsonStructure(['errors' => ['immutable']]);

    $this->assertDatabaseMissing('products', ['sku' => 'vrb-reject-leaf']);
});

it('creates the leaf when the rejected request is retried without the group-owned axis', function (): void {
    [$family, $color, $size, $group] = apiVariantRollbackGroup('vrb_retry');

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.products.store'), apiVariantRollbackPayload('vrb-retry-leaf', $family, $group, $size, [$color->code => $color->options->last()->code]))
        ->assertStatus(422);

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.products.store'), apiVariantRollbackPayload('vrb-retry-leaf', $family, $group, $size))
        ->assertCreated();

    $leaf = Product::where('sku', 'vrb-retry-leaf')->first();

    expect($leaf)->not->toBeNull()
        ->and($leaf->parent_id)->toBe($group->id)
        ->and($leaf->values['common'][$size->code])->toBe($size->options->first()->code);
});

it('does not announce a leaf whose create was rejected', function (): void {
    [$family, $color, $size, $group] = apiVariantRollbackGroup('vrb_silent');

    $announced = apiVariantRollbackAnnouncedSkus();

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.products.store'), apiVariantRollbackPayload('vrb-silent-leaf', $family, $group, $size, [$color->code => $color->options->last()->code]))
        ->assertStatus(422);

    expect($announced->getArrayCopy())->not->toContain('vrb-silent-leaf');
});

it('announces a leaf once its create succeeds', function (): void {
    [$family, , $size, $group] = apiVariantRollbackGroup('vrb_announce');

    $announced = apiVariantRollbackAnnouncedSkus();

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.products.store'), apiVariantRollbackPayload('vrb-announce-leaf', $family, $group, $size))
        ->assertCreated();

    expect($announced->getArrayCopy())->toContain('vrb-announce-leaf');
});

it('keeps the group-owned axis value when the leaf repeats it unchanged', function (): void {
    [$family, $color, $size, $group] = apiVariantRollbackGroup('vrb_same');

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.products.store'), apiVariantRollbackPayload('vrb-same-leaf', $family, $group, $size, [$color->code => $color->options->first()->code]))
        ->assertCreated();

    expect(Product::where('sku', 'vrb-same-leaf')->exists())->toBeTrue();
});
