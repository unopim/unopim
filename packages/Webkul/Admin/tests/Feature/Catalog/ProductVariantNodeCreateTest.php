<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Models\VariantStructureAxis;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\User\Models\Admin;
use Webkul\Webhook\Jobs\SendProductWebhook;
use Webkul\Webhook\Models\Webhook;

uses(DatabaseTransactions::class);

/**
 * Builds a fresh 2-level (color/size) configurable + variant structure,
 * globally-unique/randomly-suffixed since this suite runs against a
 * live/seeded DB (attributes.code is globally unique).
 */
function makeConfigurableForVariantNodeCreate(): array
{
    $colorCode = 'color_'.Str::random(8);
    $sizeCode = 'size_'.Str::random(8);

    $color = Attribute::factory()->create(['code' => $colorCode, 'type' => 'select']);
    $size = Attribute::factory()->create(['code' => $sizeCode, 'type' => 'select']);

    $redOptionCode = $color->options->first()->code;
    $sizeOptionCode = $size->options->first()->code;

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

    return [$configurable, $colorCode, $sizeCode, $redOptionCode, $sizeOptionCode];
}

it('creates a variant_group node under a configurable', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    [$configurable, $colorCode, , $redOptionCode] = makeConfigurableForVariantNodeCreate();

    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')
        ->postJson(route('admin.catalog.products.variant_node.create', $configurable->id), [
            'parent_id' => null,
            'role'      => 'variant_group',
            'values'    => [$colorCode => $redOptionCode],
        ])
        ->assertOk();

    $newId = $response->json('data.id');

    expect($newId)->not->toBeNull();

    $response->assertJsonPath('data.redirect_url', route('admin.catalog.products.edit', $newId));

    $group = Product::find($newId);

    expect($group->type)->toBe('variant_group')
        ->and($group->parent_id)->toBe($configurable->id)
        ->and($group->values['common'][$colorCode] ?? null)->toBe($redOptionCode);
});

it('creates a simple node under a variant_group', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    [$configurable, $colorCode, $sizeCode, $redOptionCode, $sizeOptionCode] = makeConfigurableForVariantNodeCreate();

    $type = $configurable->getTypeInstance();

    $group = $type->createVariantGroup($configurable, [
        'group_values' => [$colorCode => $redOptionCode],
        'sku'          => $configurable->sku.'-'.$redOptionCode,
    ]);

    $admin = Admin::factory()->create();

    $response = $this->actingAs($admin, 'admin')
        ->postJson(route('admin.catalog.products.variant_node.create', $configurable->id), [
            'parent_id' => $group->id,
            'role'      => 'simple',
            'values'    => [$sizeCode => $sizeOptionCode],
        ])
        ->assertOk();

    $newId = $response->json('data.id');

    expect($newId)->not->toBeNull();

    $response->assertJsonPath('data.redirect_url', route('admin.catalog.products.edit', $newId));

    $leaf = Product::find($newId);

    expect($leaf->type)->toBe('simple')
        ->and($leaf->parent_id)->toBe($group->id)
        ->and($leaf->values['common'][$sizeCode] ?? null)->toBe($sizeOptionCode);
});

it('rejects a parent_id that does not belong to the configurable subtree', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    [$configurable, , $sizeCode, , $sizeOptionCode] = makeConfigurableForVariantNodeCreate();

    [$otherConfigurable] = makeConfigurableForVariantNodeCreate();

    $foreignGroup = $otherConfigurable->getTypeInstance()->createVariantGroup($otherConfigurable, [
        'group_values' => [],
        'sku'          => $otherConfigurable->sku.'-group',
    ]);

    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.catalog.products.variant_node.create', $configurable->id), [
            'parent_id' => $foreignGroup->id,
            'role'      => 'simple',
            'values'    => [$sizeCode => $sizeOptionCode],
        ])
        ->assertNotFound();
});

it('rejects an axis code that is not part of the configurable variant structure', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    [$configurable] = makeConfigurableForVariantNodeCreate();

    $foreignAxisCode = 'not_an_axis_'.Str::random(8);

    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.catalog.products.variant_node.create', $configurable->id), [
            'parent_id' => null,
            'role'      => 'variant_group',
            'values'    => [$foreignAxisCode => 'whatever'],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('values');
});

/**
 * Single level splitting on three axes at once (color + size + brand), the
 * shape Akeneo calls a "single variant" product model.
 */
function makeMultiAxisConfigurable(): array
{
    $codes = [];
    $options = [];
    $attributes = [];

    foreach (['color', 'size', 'brand'] as $name) {
        $attribute = Attribute::factory()->create(['code' => $name.'_'.Str::random(8), 'type' => 'select']);

        $attributes[] = $attribute;
        $codes[] = $attribute->code;
        $options[] = $attribute->options->first()->code;
    }

    $family = AttributeFamily::factory()->create(['code' => 'fam_'.Str::random(8)]);

    $structure = VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'bp_'.Str::random(8),
        'name'                => 'BP',
        'levels'              => 1,
    ]);

    VariantStructureAxis::insert(
        collect($attributes)->map(fn ($attribute, $position) => [
            'variant_structure_id' => $structure->id,
            'attribute_id'         => $attribute->id,
            'level'                => 'level_1',
            'position'             => $position,
        ])->all()
    );

    $configurable = app(ProductRepository::class)->create([
        'type'                 => 'configurable',
        'attribute_family_id'  => $family->id,
        'sku'                  => 'MULTI-'.Str::random(8),
        'variant_structure_id' => $structure->id,
        'super_attributes'     => $codes,
    ]);

    return [$configurable, $codes, $options];
}

it('creates a leaf fixed on every axis of a multi axis level', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    [$configurable, $codes, $options] = makeMultiAxisConfigurable();

    $admin = Admin::factory()->create();

    $newId = $this->actingAs($admin, 'admin')
        ->postJson(route('admin.catalog.products.variant_node.create', $configurable->id), [
            'parent_id' => null,
            'role'      => 'simple',
            'values'    => array_combine($codes, $options),
        ])
        ->assertOk()
        ->json('data.id');

    $leaf = Product::find($newId);

    expect($leaf->type)->toBe('simple')
        ->and($leaf->parent_id)->toBe($configurable->id);

    foreach ($codes as $index => $code) {
        expect($leaf->values['common'][$code] ?? null)->toBe($options[$index]);
    }
});

it('rejects a node that leaves one axis of a multi axis level unset', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    [$configurable, $codes, $options] = makeMultiAxisConfigurable();

    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.catalog.products.variant_node.create', $configurable->id), [
            'parent_id' => null,
            'role'      => 'simple',
            'values'    => [$codes[0] => $options[0]],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['values.'.$codes[1], 'values.'.$codes[2]]);
});

it('rejects an option that belongs to another attribute', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    [$configurable, $codes, $options] = makeMultiAxisConfigurable();

    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.catalog.products.variant_node.create', $configurable->id), [
            'parent_id' => null,
            'role'      => 'simple',
            'values'    => [
                $codes[0] => $options[1],
                $codes[1] => $options[1],
                $codes[2] => $options[2],
            ],
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('values.'.$codes[0]);
});

it('rejects a second node fixed on the same axis combination', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    [$configurable, $codes, $options] = makeMultiAxisConfigurable();

    $admin = Admin::factory()->create();

    $payload = [
        'parent_id' => null,
        'role'      => 'simple',
        'values'    => array_combine($codes, $options),
    ];

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.catalog.products.variant_node.create', $configurable->id), $payload)
        ->assertOk();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.catalog.products.variant_node.create', $configurable->id), $payload)
        ->assertStatus(422)
        ->assertJsonPath('message', trans('admin::app.catalog.products.edit.types.configurable.create.variant-already-exists'));
});

it('rejects a sku already taken by another product', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    [$configurable, $codes, $options] = makeMultiAxisConfigurable();

    $admin = Admin::factory()->create();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.catalog.products.variant_node.create', $configurable->id), [
            'parent_id' => null,
            'role'      => 'simple',
            'values'    => array_combine($codes, $options),
            'sku'       => $configurable->sku,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('sku');
});

it('announces a leaf variant created from the variations sidebar', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    [$configurable, $colorCode, $sizeCode, $redOptionCode, $sizeOptionCode] = makeConfigurableForVariantNodeCreate();

    $group = $configurable->getTypeInstance()->createVariantGroup($configurable, [
        'group_values' => [$colorCode => $redOptionCode],
        'sku'          => $configurable->sku.'-'.$redOptionCode,
    ]);

    Event::fake(['catalog.product.create.after']);

    $response = $this->actingAs(Admin::factory()->create(), 'admin')
        ->postJson(route('admin.catalog.products.variant_node.create', $configurable->id), [
            'parent_id' => $group->id,
            'role'      => 'simple',
            'values'    => [$sizeCode => $sizeOptionCode],
        ])
        ->assertOk();

    $newId = $response->json('data.id');

    Event::assertDispatched(
        'catalog.product.create.after',
        fn ($event, $product): bool => $product->id === $newId
    );

    Event::assertDispatchedTimes('catalog.product.create.after', 1);
});

it('queues exactly one product webhook for a leaf variant created from the sidebar', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    [$configurable, $colorCode, $sizeCode, $redOptionCode, $sizeOptionCode] = makeConfigurableForVariantNodeCreate();

    $group = $configurable->getTypeInstance()->createVariantGroup($configurable, [
        'group_values' => [$colorCode => $redOptionCode],
        'sku'          => $configurable->sku.'-'.$redOptionCode,
    ]);

    Webhook::create([
        'name'      => 'variant-probe-'.Str::random(6),
        'url'       => 'https://example.test/hook',
        'method'    => 'POST',
        'events'    => ['product.created'],
        'is_active' => 1,
    ]);

    Queue::fake();

    $response = $this->actingAs(Admin::factory()->create(), 'admin')
        ->postJson(route('admin.catalog.products.variant_node.create', $configurable->id), [
            'parent_id' => $group->id,
            'role'      => 'simple',
            'values'    => [$sizeCode => $sizeOptionCode],
        ])
        ->assertOk();

    Queue::assertPushed(SendProductWebhook::class, 1);

    expect(Product::find($response->json('data.id'))->type)->toBe('simple');
});

it('stays silent when the requested variant node already exists', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    [$configurable, $colorCode, $sizeCode, $redOptionCode, $sizeOptionCode] = makeConfigurableForVariantNodeCreate();

    $group = $configurable->getTypeInstance()->createVariantGroup($configurable, [
        'group_values' => [$colorCode => $redOptionCode],
        'sku'          => $configurable->sku.'-'.$redOptionCode,
    ]);

    $admin = Admin::factory()->create();

    $payload = [
        'parent_id' => $group->id,
        'role'      => 'simple',
        'values'    => [$sizeCode => $sizeOptionCode],
    ];

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.catalog.products.variant_node.create', $configurable->id), $payload)
        ->assertOk();

    Event::fake(['catalog.product.create.after']);

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.catalog.products.variant_node.create', $configurable->id), $payload)
        ->assertStatus(422);

    Event::assertNotDispatched('catalog.product.create.after');
});

it('announces a variant group created from the variations sidebar', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    [$configurable, $colorCode, $sizeCode, $redOptionCode, $sizeOptionCode] = makeConfigurableForVariantNodeCreate();

    Event::fake(['catalog.product.create.after']);

    $response = $this->actingAs(Admin::factory()->create(), 'admin')
        ->postJson(route('admin.catalog.products.variant_node.create', $configurable->id), [
            'parent_id' => $configurable->id,
            'role'      => 'variant_group',
            'values'    => [$colorCode => $redOptionCode],
        ])
        ->assertOk();

    $newId = $response->json('data.id');

    Event::assertDispatched(
        'catalog.product.create.after',
        fn ($event, $product): bool => $product->id === $newId && $product->type === 'variant_group'
    );
});
