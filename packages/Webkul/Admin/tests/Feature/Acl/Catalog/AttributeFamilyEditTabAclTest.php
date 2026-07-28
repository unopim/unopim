<?php

use Webkul\Attribute\Models\AttributeFamily;

/**
 * The variant structures grid is a tab of the family editor rather than a page
 * of its own, so the variant structure permission was unusable on its own: the
 * editor route 403'd before the tab could ever be reached.
 */
it('should open the family editor with only the variant structure permission', function () {
    $this->loginWithPermissions(permissions: [
        'catalog',
        'catalog.families',
        'catalog.families.variant-structures',
    ]);

    $attributeFamily = AttributeFamily::factory()->create();

    $this->get(route('admin.catalog.families.edit', ['id' => $attributeFamily->id]).'?variants')
        ->assertOk();
});

it('should still open the family editor with only the edit permission', function () {
    $this->loginWithPermissions(permissions: [
        'catalog',
        'catalog.families',
        'catalog.families.edit',
    ]);

    $attributeFamily = AttributeFamily::factory()->create();

    $this->get(route('admin.catalog.families.edit', ['id' => $attributeFamily->id]))
        ->assertOk();
});

it('should deny the family editor without either permission', function () {
    $this->loginWithPermissions(permissions: ['catalog', 'catalog.families']);

    $attributeFamily = AttributeFamily::factory()->create();

    $this->get(route('admin.catalog.families.edit', ['id' => $attributeFamily->id]))
        ->assertStatus(403);
});

it('should not offer the general tab to a variant structure only role', function () {
    $this->loginWithPermissions(permissions: [
        'catalog',
        'catalog.families',
        'catalog.families.variant-structures',
    ]);

    $attributeFamily = AttributeFamily::factory()->create();

    $response = $this->get(route('admin.catalog.families.edit', ['id' => $attributeFamily->id]));

    $response->assertOk()
        ->assertDontSee('attribute-family-edit-form')
        ->assertSee('v-variant-structure-list', false);
});
