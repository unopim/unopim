<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;

uses(DatabaseTransactions::class);

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('returns a variant_group product through the configurable-products GET endpoint', function () {
    $family = AttributeFamily::factory()->create();
    $color = Attribute::factory()->create(['code' => 'vg_color_'.uniqid(), 'type' => 'select']);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'vg_structure_'.uniqid(),
        'levels'              => 2,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
    ]);

    $repository = app(ProductRepository::class);

    $configurable = $repository->create([
        'sku'                  => 'vg-config-'.uniqid(),
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'variant_structure_id' => $structure->id,
    ]);

    $group = $configurable->getTypeInstance()->createVariantGroup($configurable, [
        'sku' => 'vg-group', 'group_axis_code' => $color->code, 'group_axis_option' => 'red',
    ]);

    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.configurable_products.get', $group->sku))
        ->assertOK()
        ->assertJsonPath('sku', 'vg-group')
        ->assertJsonPath('type', 'variant_group')
        ->assertJsonPath('values.common.'.$color->code, 'red');
});

it('includes variant_group rows in the configurable-products listing', function () {
    $family = AttributeFamily::factory()->create();
    $repository = app(ProductRepository::class);

    $configurable = $repository->create([
        'sku' => 'vgl-config-'.uniqid(), 'type' => 'configurable', 'attribute_family_id' => $family->id,
    ]);

    Product::factory()->create([
        'parent_id' => $configurable->id, 'type' => 'variant_group', 'sku' => 'vgl-group',
        'values'    => ['common' => ['sku' => 'vgl-group']],
    ]);

    $filters = json_encode(['sku' => [['operator' => '=', 'value' => 'vgl-group']]]);

    $response = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.configurable_products.index', ['filters' => $filters]))
        ->assertOK();

    expect(collect($response->json('data'))->pluck('sku'))->toContain('vgl-group');
});

it('excludes simple products from the configurable-products listing', function () {
    $family = AttributeFamily::factory()->create();
    $repository = app(ProductRepository::class);

    $configurable = $repository->create([
        'sku' => 'vgs-config-'.uniqid(), 'type' => 'configurable', 'attribute_family_id' => $family->id,
    ]);

    Product::factory()->create([
        'parent_id' => $configurable->id, 'type' => 'simple', 'sku' => 'vgs-leaf',
        'values'    => ['common' => ['sku' => 'vgs-leaf']],
    ]);

    $filters = json_encode(['sku' => [['operator' => '=', 'value' => 'vgs-leaf']]]);

    $response = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.configurable_products.index', ['filters' => $filters]))
        ->assertOK();

    expect(collect($response->json('data'))->pluck('sku'))->not->toContain('vgs-leaf');
});

it('returns only variant_group rows when type=variant_group is requested', function () {
    $family = AttributeFamily::factory()->create();
    $repository = app(ProductRepository::class);

    $configurable = $repository->create([
        'sku' => 'vgt-config-'.uniqid(), 'type' => 'configurable', 'attribute_family_id' => $family->id,
    ]);

    Product::factory()->create([
        'parent_id' => $configurable->id, 'type' => 'variant_group', 'sku' => 'vgt-group',
        'values'    => ['common' => ['sku' => 'vgt-group']],
    ]);

    $groupFilter = json_encode(['sku' => [['operator' => '=', 'value' => 'vgt-group']]]);
    $configFilter = json_encode(['sku' => [['operator' => '=', 'value' => $configurable->sku]]]);

    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.configurable_products.index', ['filters' => $groupFilter, 'type' => 'variant_group']))
        ->assertOK()
        ->assertJsonPath('data.0.sku', 'vgt-group');

    $excluded = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.configurable_products.index', ['filters' => $configFilter, 'type' => 'variant_group']))
        ->assertOK();

    expect($excluded->json('data'))->toBeEmpty();
});

it('returns only configurable rows when type=configurable is requested', function () {
    $family = AttributeFamily::factory()->create();
    $repository = app(ProductRepository::class);

    $configurable = $repository->create([
        'sku' => 'vgtc-config-'.uniqid(), 'type' => 'configurable', 'attribute_family_id' => $family->id,
    ]);

    Product::factory()->create([
        'parent_id' => $configurable->id, 'type' => 'variant_group', 'sku' => 'vgtc-group',
        'values'    => ['common' => ['sku' => 'vgtc-group']],
    ]);

    $groupFilter = json_encode(['sku' => [['operator' => '=', 'value' => 'vgtc-group']]]);
    $configFilter = json_encode(['sku' => [['operator' => '=', 'value' => $configurable->sku]]]);

    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.configurable_products.index', ['filters' => $configFilter, 'type' => 'configurable']))
        ->assertOK()
        ->assertJsonPath('data.0.sku', $configurable->sku);

    $excluded = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.configurable_products.index', ['filters' => $groupFilter, 'type' => 'configurable']))
        ->assertOK();

    expect($excluded->json('data'))->toBeEmpty();
});

it('rejects an unsupported type value on the configurable-products listing', function () {
    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.configurable_products.index', ['type' => 'simple']))
        ->assertStatus(422);
});

it('lists a variant_group own simple children the same way a configurable lists its variants', function () {
    $family = AttributeFamily::factory()->create();
    $color = Attribute::factory()->create(['code' => 'vgc_color_'.uniqid(), 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => 'vgc_size_'.uniqid(), 'type' => 'select']);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id, 'code' => 'vgc_structure_'.uniqid(), 'levels' => 2,
    ]);
    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
        ['variant_structure_id' => $structure->id, 'attribute_id' => $size->id, 'level' => 'level_2', 'position' => 0],
    ]);

    $repository = app(ProductRepository::class);
    $configurable = $repository->create([
        'sku'                  => 'vgc-config-'.uniqid(), 'type' => 'configurable', 'attribute_family_id' => $family->id,
        'variant_structure_id' => $structure->id,
    ]);

    $typeInstance = $configurable->getTypeInstance();
    $group = $typeInstance->createVariantGroup($configurable, [
        'sku' => 'vgc-group', 'group_axis_code' => $color->code, 'group_axis_option' => 'red',
    ]);
    $typeInstance->createVariant($configurable, $configurable->super_attributes, [
        'parent_id' => $group->id, 'sku' => 'vgc-group-s', 'values' => ['common' => [$size->code => 's']],
    ]);

    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.configurable_products.get', 'vgc-group'))
        ->assertOK()
        ->assertJsonPath('variants.0.sku', 'vgc-group-s');
});

it('creates a variant_group via the configurable-products POST endpoint', function () {
    $family = AttributeFamily::factory()->create();
    $color = Attribute::factory()->create(['code' => 'vgp_color_'.uniqid(), 'type' => 'select']);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id, 'code' => 'vgp_structure_'.uniqid(), 'levels' => 2,
    ]);
    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
    ]);

    $repository = app(ProductRepository::class);
    $configurable = $repository->create([
        'sku'                  => 'vgp-config-'.uniqid(), 'type' => 'configurable', 'attribute_family_id' => $family->id,
        'variant_structure_id' => $structure->id,
    ]);

    $this->withHeaders($this->headers)->json('POST', route('admin.api.configurable_products.store'), [
        'type'   => 'variant_group',
        'parent' => $configurable->sku,
        'values' => ['common' => ['sku' => 'vgp-group', $color->code => $color->options->first()->code]],
    ])->assertStatus(201);

    expect(Product::where('sku', 'vgp-group')->first())
        ->not->toBeNull()
        ->type->toBe('variant_group');
});

it('rejects a variant_group create that collides with an existing sibling axis value', function () {
    $family = AttributeFamily::factory()->create();
    $color = Attribute::factory()->create(['code' => 'vgpc_color_'.uniqid(), 'type' => 'select']);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id, 'code' => 'vgpc_structure_'.uniqid(), 'levels' => 2,
    ]);
    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
    ]);

    $repository = app(ProductRepository::class);
    $configurable = $repository->create([
        'sku'                  => 'vgpc-config-'.uniqid(), 'type' => 'configurable', 'attribute_family_id' => $family->id,
        'variant_structure_id' => $structure->id,
    ]);

    $configurable->getTypeInstance()->createVariantGroup($configurable, [
        'sku' => 'vgpc-existing', 'group_axis_code' => $color->code, 'group_axis_option' => 'red',
    ]);

    $this->withHeaders($this->headers)->json('POST', route('admin.api.configurable_products.store'), [
        'type'   => 'variant_group',
        'parent' => $configurable->sku,
        'values' => ['common' => ['sku' => 'vgpc-new', $color->code => 'red']],
    ])->assertStatus(422);
});

it('rejects deleting a variant_group that still has children', function () {
    $family = AttributeFamily::factory()->create();
    $repository = app(ProductRepository::class);
    $configurable = $repository->create([
        'sku' => 'vgd-config-'.uniqid(), 'type' => 'configurable', 'attribute_family_id' => $family->id,
    ]);

    $group = Product::factory()->create([
        'parent_id' => $configurable->id, 'type' => 'variant_group', 'sku' => 'vgd-group',
    ]);
    Product::factory()->create([
        'parent_id' => $group->id, 'type' => 'simple', 'sku' => 'vgd-group-s',
    ]);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.configurable_products.delete', 'vgd-group'))
        ->assertStatus(422);

    expect(Product::where('sku', 'vgd-group')->exists())->toBeTrue();
});

it('deletes a childless variant_group', function () {
    $family = AttributeFamily::factory()->create();
    $repository = app(ProductRepository::class);
    $configurable = $repository->create([
        'sku' => 'vgd2-config-'.uniqid(), 'type' => 'configurable', 'attribute_family_id' => $family->id,
    ]);

    Product::factory()->create([
        'parent_id' => $configurable->id, 'type' => 'variant_group', 'sku' => 'vgd2-group',
    ]);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.configurable_products.delete', 'vgd2-group'))
        ->assertOK();

    expect(Product::where('sku', 'vgd2-group')->exists())->toBeFalse();
});

it('updates a variant_group own-level value via PUT without touching nested variants', function () {
    $family = AttributeFamily::factory()->create();
    $color = Attribute::factory()->create(['code' => 'vgu_color_'.uniqid(), 'type' => 'select']);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id, 'code' => 'vgu_structure_'.uniqid(), 'levels' => 2,
    ]);
    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
    ]);

    $repository = app(ProductRepository::class);
    $configurable = $repository->create([
        'sku'                  => 'vgu-config-'.uniqid(), 'type' => 'configurable', 'attribute_family_id' => $family->id,
        'variant_structure_id' => $structure->id,
    ]);

    $redOption = $color->options->first()->code;
    $greenOption = $color->options->last()->code;

    $group = $configurable->getTypeInstance()->createVariantGroup($configurable, [
        'sku' => 'vgu-group', 'group_axis_code' => $color->code, 'group_axis_option' => $redOption,
    ]);

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.configurable_products.update', 'vgu-group'), [
        'family' => $family->code,
        'values' => ['common' => ['sku' => 'vgu-group', $color->code => $greenOption]],
    ])->assertOK();

    expect($group->fresh()->values['common'][$color->code])->toBe($greenOption);
});

function vgLeafTree(string $prefix): array
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
        'sku'                  => $prefix.'-config-'.uniqid(), 'type' => 'configurable', 'attribute_family_id' => $family->id,
        'variant_structure_id' => $structure->id,
    ]);

    return [$family, $color, $size, $configurable];
}

it('creates a simple product under a variant_group and parents it to that group', function () {
    [$family, $color, $size, $configurable] = vgLeafTree('vgleaf');

    $group = $configurable->getTypeInstance()->createVariantGroup($configurable, [
        'sku' => 'vgleaf-group', 'group_axis_code' => $color->code, 'group_axis_option' => $color->options->first()->code,
    ]);

    $sizeOption = $size->options->first()->code;

    $this->withHeaders($this->headers)->json('POST', route('admin.api.products.store'), [
        'parent'  => 'vgleaf-group',
        'family'  => $family->code,
        'values'  => ['common' => ['sku' => 'vgleaf-s']],
        'variant' => ['attributes' => [$size->code => $sizeOption]],
    ])->assertStatus(201);

    $leaf = Product::where('sku', 'vgleaf-s')->first();

    expect($leaf)->not->toBeNull();
    expect($leaf->type)->toBe('simple');
    expect($leaf->parent_id)->toBe($group->id);
    expect($leaf->values['common'][$size->code])->toBe($sizeOption);
});

it('rejects a simple product under a variant_group that omits the leaf axis', function () {
    [$family, $color, $size, $configurable] = vgLeafTree('vgleafm');

    $configurable->getTypeInstance()->createVariantGroup($configurable, [
        'sku' => 'vgleafm-group', 'group_axis_code' => $color->code, 'group_axis_option' => $color->options->first()->code,
    ]);

    $response = $this->withHeaders($this->headers)->json('POST', route('admin.api.products.store'), [
        'parent' => 'vgleafm-group',
        'family' => $family->code,
        'values' => ['common' => ['sku' => 'vgleafm-s']],
    ])->assertStatus(422);

    expect($response->json('message'))->toContain($size->code);
    expect(Product::where('sku', 'vgleafm-s')->exists())->toBeFalse();
});

it('rejects a second simple product with the same leaf axis value in the same variant_group', function () {
    [$family, $color, $size, $configurable] = vgLeafTree('vgleafd');

    $configurable->getTypeInstance()->createVariantGroup($configurable, [
        'sku' => 'vgleafd-group', 'group_axis_code' => $color->code, 'group_axis_option' => $color->options->first()->code,
    ]);

    $sizeOption = $size->options->first()->code;

    $payload = [
        'parent'  => 'vgleafd-group',
        'family'  => $family->code,
        'values'  => ['common' => ['sku' => 'vgleafd-s1']],
        'variant' => ['attributes' => [$size->code => $sizeOption]],
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.products.store'), $payload)->assertStatus(201);

    $payload['values']['common']['sku'] = 'vgleafd-s2';

    $this->withHeaders($this->headers)->json('POST', route('admin.api.products.store'), $payload)->assertStatus(422);

    expect(Product::where('sku', 'vgleafd-s2')->exists())->toBeFalse();
});

it('allows the same leaf axis value in a different variant_group', function () {
    [$family, $color, $size, $configurable] = vgLeafTree('vgleafs');

    $typeInstance = $configurable->getTypeInstance();

    $typeInstance->createVariantGroup($configurable, [
        'sku' => 'vgleafs-group-a', 'group_axis_code' => $color->code, 'group_axis_option' => $color->options->first()->code,
    ]);
    $secondGroup = $typeInstance->createVariantGroup($configurable, [
        'sku' => 'vgleafs-group-b', 'group_axis_code' => $color->code, 'group_axis_option' => $color->options->last()->code,
    ]);

    $sizeOption = $size->options->first()->code;

    $this->withHeaders($this->headers)->json('POST', route('admin.api.products.store'), [
        'parent'  => 'vgleafs-group-a',
        'family'  => $family->code,
        'values'  => ['common' => ['sku' => 'vgleafs-s1']],
        'variant' => ['attributes' => [$size->code => $sizeOption]],
    ])->assertStatus(201);

    $this->withHeaders($this->headers)->json('POST', route('admin.api.products.store'), [
        'parent'  => 'vgleafs-group-b',
        'family'  => $family->code,
        'values'  => ['common' => ['sku' => 'vgleafs-s2']],
        'variant' => ['attributes' => [$size->code => $sizeOption]],
    ])->assertStatus(201);

    expect(Product::where('sku', 'vgleafs-s2')->first()->parent_id)->toBe($secondGroup->id);
});

it('keeps creating a variant directly under a single-level configurable parent', function () {
    $family = AttributeFamily::factory()->create();
    $color = Attribute::factory()->create(['code' => 'vgflat_color_'.uniqid(), 'type' => 'select']);

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily($family, $color);

    $configurable = app(ProductRepository::class)->create([
        'sku'                 => 'vgflat-config-'.uniqid(), 'type' => 'configurable', 'attribute_family_id' => $family->id,
        'super_attributes'    => [$color->code],
    ]);

    $colorOption = $color->options->first()->code;

    $this->withHeaders($this->headers)->json('POST', route('admin.api.products.store'), [
        'parent'  => $configurable->sku,
        'family'  => $family->code,
        'values'  => ['common' => ['sku' => 'vgflat-s1']],
        'variant' => ['attributes' => [$color->code => $colorOption]],
    ])->assertStatus(201);

    $leaf = Product::where('sku', 'vgflat-s1')->first();

    expect($leaf->parent_id)->toBe($configurable->id);
    expect($leaf->values['common'][$color->code])->toBe($colorOption);

    $this->withHeaders($this->headers)->json('POST', route('admin.api.products.store'), [
        'parent'  => $configurable->sku,
        'family'  => $family->code,
        'values'  => ['common' => ['sku' => 'vgflat-s2']],
        'variant' => ['attributes' => [$color->code => $colorOption]],
    ])->assertStatus(422);
});

it('rejects a duplicate sku on configurable create with 422 rather than a server error', function () {
    $family = AttributeFamily::factory()->create();

    app(ProductRepository::class)->create([
        'sku' => 'dup-config-sku', 'type' => 'configurable', 'attribute_family_id' => $family->id,
    ]);

    $this->withHeaders($this->headers)->json('POST', route('admin.api.configurable_products.store'), [
        'type'             => 'configurable',
        'family'           => $family->code,
        'super_attributes' => ['color'],
        'values'           => ['common' => ['sku' => 'dup-config-sku']],
    ])->assertStatus(422);
});

it('rejects a duplicate sku on variant_group create with 422 rather than a server error', function () {
    $family = AttributeFamily::factory()->create();
    $color = Attribute::factory()->create(['code' => 'dupvg_color_'.uniqid(), 'type' => 'select']);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id, 'code' => 'dupvg_structure_'.uniqid(), 'levels' => 2,
    ]);
    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
    ]);

    $configurable = app(ProductRepository::class)->create([
        'sku'                  => 'dupvg-config-'.uniqid(), 'type' => 'configurable', 'attribute_family_id' => $family->id,
        'variant_structure_id' => $structure->id,
    ]);

    Product::factory()->create([
        'parent_id' => $configurable->id, 'type' => 'variant_group', 'sku' => 'dup-group-sku',
        'values'    => ['common' => ['sku' => 'dup-group-sku']],
    ]);

    $this->withHeaders($this->headers)->json('POST', route('admin.api.configurable_products.store'), [
        'type'   => 'variant_group',
        'parent' => $configurable->sku,
        'values' => ['common' => ['sku' => 'dup-group-sku', $color->code => 'red']],
    ])->assertStatus(422);
});

it('announces a variant group created through the REST endpoint', function () {
    $family = AttributeFamily::factory()->create();
    $color = Attribute::factory()->create(['code' => 'vg_color_'.uniqid(), 'type' => 'select']);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'vg_structure_'.uniqid(),
        'levels'              => 2,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
    ]);

    $configurable = app(ProductRepository::class)->create([
        'sku'                  => 'vg-evt-config-'.uniqid(),
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'variant_structure_id' => $structure->id,
    ]);

    Event::fake(['catalog.product.create.after']);

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.configurable_products.store'), [
            'type'   => 'variant_group',
            'parent' => $configurable->sku,
            'values' => [
                'common' => [
                    'sku'         => 'vg-evt-group-'.uniqid(),
                    $color->code  => $color->options->first()->code,
                ],
            ],
        ])
        ->assertCreated();

    Event::assertDispatched(
        'catalog.product.create.after',
        fn ($event, $product): bool => $product->type === 'variant_group'
    );
});

it('purifies wysiwyg values when a variant group is created', function () {
    $family = AttributeFamily::factory()->create();
    $color = Attribute::factory()->create(['code' => 'vgp_color_'.uniqid(), 'type' => 'select']);
    $notes = Attribute::factory()->create([
        'code'              => 'vgp_notes_'.uniqid(),
        'type'              => 'textarea',
        'enable_wysiwyg'    => 1,
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ]);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'vgp_structure_'.uniqid(),
        'levels'              => 2,
    ]);

    VariantStructureAxis::insert([
        ['variant_structure_id' => $structure->id, 'attribute_id' => $color->id, 'level' => 'level_1', 'position' => 0],
    ]);

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    AttributeFamily::factory()->linkAttributesToFamily($family, Attribute::whereIn('id', [$color->id, $notes->id])->get());

    $configurable = app(ProductRepository::class)->create([
        'sku'                  => 'vgp-config-'.uniqid(),
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'variant_structure_id' => $structure->id,
    ]);

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.configurable_products.store'), [
            'type'   => 'variant_group',
            'parent' => $configurable->sku,
            'values' => ['common' => [
                'sku'          => 'vgp-group-'.uniqid(),
                $color->code   => $color->options->first()->code,
                $notes->code   => '<p>keep</p><script>alert(1)</script>',
            ]],
        ])
        ->assertStatus(201);

    $group = Product::where('type', 'variant_group')->latest('id')->first();

    expect($group->values['common'][$notes->code])->not->toContain('<script>')
        ->and($group->values['common'][$notes->code])->toContain('keep');
});
