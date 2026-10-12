<?php

use Webkul\Attribute\Models\AttributeFamily;

it('should open the completeness tab with only the completeness permission', function () {
    $this->loginWithPermissions(permissions: [
        'catalog',
        'catalog.families',
        'catalog.families.completeness',
    ]);

    $attributeFamily = AttributeFamily::factory()->create();

    $this->get(route('admin.catalog.families.edit', ['id' => $attributeFamily->id]).'?completeness')
        ->assertOk()
        ->assertSee('v-completeness-required-modal', false);
});

it('should not offer the completeness tab to an edit-only role', function () {
    $this->loginWithPermissions(permissions: [
        'catalog',
        'catalog.families',
        'catalog.families.edit',
    ]);

    $attributeFamily = AttributeFamily::factory()->create();

    $response = $this->get(route('admin.catalog.families.edit', ['id' => $attributeFamily->id]));

    $response->assertOk()
        ->assertDontSee('v-completeness-required-modal', false);
});

it('should deny the completeness datagrid route without the completeness permission', function () {
    $this->loginWithPermissions(permissions: [
        'catalog',
        'catalog.families',
        'catalog.families.edit',
    ]);

    $attributeFamily = AttributeFamily::factory()->create();

    $this->get(route('admin.catalog.families.completeness.edit', ['family_id' => $attributeFamily->id]))
        ->assertStatus(403);
});

it('should allow the completeness datagrid route with the completeness permission', function () {
    $this->loginWithPermissions(permissions: [
        'catalog',
        'catalog.families',
        'catalog.families.completeness',
    ]);

    $attributeFamily = AttributeFamily::factory()->create();

    $this->get(route('admin.catalog.families.completeness.edit', ['family_id' => $attributeFamily->id]))
        ->assertOk();
});
