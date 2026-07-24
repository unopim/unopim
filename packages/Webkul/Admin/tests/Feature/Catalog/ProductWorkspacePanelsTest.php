<?php

use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

function editProductResponse()
{
    // Explicitly resolve the full-permission role rather than relying on
    // `loginAsAdmin()`'s default `Role::first()` fallback (used by sibling
    // tests): in this shared dev database, role id ordering isn't guaranteed
    // to put the "all" permission role first, which otherwise 302-redirects
    // every admin route with a permission error unrelated to this feature.
    $adminRole = Role::where('permission_type', 'all')->first() ?? Role::factory()->create(['permission_type' => 'all']);

    test()->loginAsAdmin(Admin::factory()->create(['role_id' => $adminRole->id]));

    $product = Product::factory()->withInitialValues()->create();

    return test()->get(route('admin.catalog.products.edit', $product->id));
}

it('renders the slim section store and drawer component, not the old workspace frame', function () {
    $response = editProductResponse();

    $response->assertOk();

    $response->assertSee('$productWorkspace', false);
    $response->assertSee('v-product-section-drawer', false);

    $response->assertDontSee('v-product-workspace', false);
    $response->assertDontSee('product-workspace-frame', false);
    $response->assertDontSee('product-workspace-panel', false);
    $response->assertDontSee('setWorkspaceBounds', false);
});

it('renders Categories and Associations as contained drawers with intact submission contract', function () {
    $response = editProductResponse();

    $response->assertOk();

    // Both sections mount a section-drawer instance.
    $response->assertSee('id="categories"', false);
    $response->assertSee('id="associations"', false);

    // Association count is scoped by this wrapper; publishState() depends on it.
    $response->assertSee('data-section-id="associations"', false);
    $response->assertSee('v-product-links', false);
});

it('never reintroduces a clickable div add-control', function () {
    editProductResponse()
        ->assertOk()
        ->assertDontSee('<div'."\n".'                                class="secondary-button', false);
});
