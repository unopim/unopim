<?php

use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

it('renders the product edit workspace chrome and section cards', function () {
    // Explicitly resolve the full-permission role rather than relying on
    // `loginAsAdmin()`'s default `Role::first()` fallback (used by sibling
    // tests): in this shared dev database, role id ordering isn't guaranteed
    // to put the "all" permission role first, which otherwise 302-redirects
    // every admin route with a permission error unrelated to this feature.
    $adminRole = Role::where('permission_type', 'all')->first() ?? Role::factory()->create(['permission_type' => 'all']);

    $this->loginAsAdmin(Admin::factory()->create(['role_id' => $adminRole->id]));

    $product = Product::factory()->withInitialValues()->create();

    $response = $this->get(route('admin.catalog.products.edit', $product->id));

    $response->assertOk();

    // The workspace chrome (`workspace.blade.php`) mounts the overlay component.
    $response->assertSee('v-product-workspace', false);

    // The Categories and Associations section cards open the workspace on click.
    $response->assertSee("\$productWorkspace.open('categories')", false);
    $response->assertSee("\$productWorkspace.open('associations')", false);

    // Each section's workspace panel is present, scoped by its `data-section-id`.
    $response->assertSee('product-workspace-panel', false);
    $response->assertSee('data-section-id="categories"', false);
    $response->assertSee('data-section-id="associations"', false);
});
