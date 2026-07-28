<?php

use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\VariantStructure;

/**
 * The variant-structure grid used to render its edit/delete row actions
 * unconditionally, so a role with those permissions revoked still saw (and
 * could fire) the icons.
 */
function createVariantStructure(): array
{
    $attributeFamily = AttributeFamily::factory()->create();

    $structure = VariantStructure::create([
        'attribute_family_id' => $attributeFamily->id,
        'code'                => 'variant_structure_1',
        'levels'              => 1,
    ]);

    return [$attributeFamily, $structure];
}

it('should not expose the delete action when the delete permission is revoked', function () {
    $this->loginWithPermissions(permissions: [
        'catalog',
        'catalog.families',
        'catalog.families.variant-structures',
        'catalog.families.variant-structures.edit',
    ]);

    [$attributeFamily] = createVariantStructure();

    $response = $this->get(route('admin.catalog.families.variant-structures.index', [
        'id'       => $attributeFamily->id,
        'datagrid' => 1,
    ]))->assertOk();

    $actions = collect($response->json('meta.actions') ?? $response->json('actions') ?? [])
        ->pluck('index')
        ->all();

    expect($actions)->toContain('edit');
    expect($actions)->not->toContain('delete');
});

it('should not expose the edit action when the edit permission is revoked', function () {
    $this->loginWithPermissions(permissions: [
        'catalog',
        'catalog.families',
        'catalog.families.variant-structures',
        'catalog.families.variant-structures.delete',
    ]);

    [$attributeFamily] = createVariantStructure();

    $response = $this->get(route('admin.catalog.families.variant-structures.index', [
        'id'       => $attributeFamily->id,
        'datagrid' => 1,
    ]))->assertOk();

    $actions = collect($response->json('meta.actions') ?? $response->json('actions') ?? [])
        ->pluck('index')
        ->all();

    expect($actions)->toContain('delete');
    expect($actions)->not->toContain('edit');
});

it('should expose both actions when both permissions are granted', function () {
    $this->loginWithPermissions(permissions: [
        'catalog',
        'catalog.families',
        'catalog.families.variant-structures',
        'catalog.families.variant-structures.edit',
        'catalog.families.variant-structures.delete',
    ]);

    [$attributeFamily] = createVariantStructure();

    $response = $this->get(route('admin.catalog.families.variant-structures.index', [
        'id'       => $attributeFamily->id,
        'datagrid' => 1,
    ]))->assertOk();

    $actions = collect($response->json('meta.actions') ?? $response->json('actions') ?? [])
        ->pluck('index')
        ->all();

    expect($actions)->toContain('edit');
    expect($actions)->toContain('delete');
});
