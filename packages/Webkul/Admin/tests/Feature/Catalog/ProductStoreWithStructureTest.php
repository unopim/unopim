<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\VariantStructure;
use Webkul\User\Models\Admin;

it('returns the family variant structures for a configurable create', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $admin = Admin::factory()->create();
    $family = AttributeFamily::factory()->create();

    VariantStructure::create([
        'attribute_family_id' => $family->id,
        'code'                => 'blueprint',
        'name'                => 'Blueprint',
        'levels'              => 1,
    ]);

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.catalog.products.store'), [
            'type'                => 'configurable',
            'attribute_family_id' => $family->id,
            'sku'                 => 'CFG-1',
        ])
        ->assertOk()
        ->assertJsonPath('data.variant_structures.0.code', 'blueprint');
});

it('blocks configurable create when the family has no variant structures', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $admin = Admin::factory()->create();
    $family = AttributeFamily::factory()->create();

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.catalog.products.store'), [
            'type'                => 'configurable',
            'attribute_family_id' => $family->id,
            'sku'                 => 'CFG-3',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('attribute_family_id');
});

it('rejects a structure that does not belong to the family', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $admin = Admin::factory()->create();
    $family = AttributeFamily::factory()->create();
    $otherFamily = AttributeFamily::factory()->create();

    $foreign = VariantStructure::create([
        'attribute_family_id' => $otherFamily->id,
        'code'                => 'foreign',
        'name'                => 'Foreign',
        'levels'              => 1,
    ]);

    $this->actingAs($admin, 'admin')
        ->postJson(route('admin.catalog.products.store'), [
            'type'                 => 'configurable',
            'attribute_family_id'  => $family->id,
            'sku'                  => 'CFG-2',
            'variant_structure_id' => $foreign->id,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('variant_structure_id');
});
