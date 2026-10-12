<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;

/*
 * Option endpoints are nested under {attribute_id}; they must resolve the option
 * within that attribute so a mismatched id cannot read, edit or delete an option
 * belonging to a different attribute (IDOR).
 */
it('does not edit an option belonging to a different attribute', function () {
    $this->loginWithPermissions('all', ['dashboard']);

    $attributeA = Attribute::factory()->create(['type' => 'select']);
    $attributeB = Attribute::factory()->create(['type' => 'select']);
    $optionB = AttributeOption::create(['code' => 'foreign', 'sort_order' => 1, 'attribute_id' => $attributeB->id]);

    getJson(route('admin.catalog.attributes.options.edit', ['attribute_id' => $attributeA->id, 'id' => $optionB->id]))
        ->assertStatus(404);
});

it('does not reorder options of a different attribute via updateSort', function () {
    $this->loginWithPermissions('all', ['dashboard']);

    $attributeA = Attribute::factory()->create(['type' => 'select']);
    $attributeB = Attribute::factory()->create(['type' => 'select']);

    $anchorA = AttributeOption::create(['code' => 'anchor_a', 'sort_order' => 1, 'attribute_id' => $attributeA->id]);
    $foreignB = AttributeOption::create(['code' => 'foreign_b', 'sort_order' => 7, 'attribute_id' => $attributeB->id]);

    $this->putJson(route('admin.catalog.attributes.options.update_sort', ['attribute_id' => $attributeA->id]), [
        'optionIds' => [$foreignB->id, $anchorA->id],
        'direction' => 'up',
        'toIndex'   => $anchorA->id,
    ])->assertOk();

    expect($foreignB->fresh()->sort_order)->toBe(7);
});

it('does not delete an option belonging to a different attribute', function () {
    $this->loginWithPermissions('all', ['dashboard']);

    $attributeA = Attribute::factory()->create(['type' => 'select']);
    $attributeB = Attribute::factory()->create(['type' => 'select']);
    $optionB = AttributeOption::create(['code' => 'foreign', 'sort_order' => 1, 'attribute_id' => $attributeB->id]);

    deleteJson(route('admin.catalog.attributes.options.delete', ['attribute_id' => $attributeA->id, 'id' => $optionB->id]))
        ->assertStatus(404);

    $this->assertDatabaseHas('attribute_options', ['id' => $optionB->id]);
});
