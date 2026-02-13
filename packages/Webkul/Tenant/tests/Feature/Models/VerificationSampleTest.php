<?php

use Illuminate\Support\Facades\DB;
use Webkul\Notification\Models\Notification;
use Webkul\Product\Models\Product;

/*
|--------------------------------------------------------------------------
| Verification Sample Test (Story 1.9)
|--------------------------------------------------------------------------
|
| Demonstrates the enhanced TenantTestCase: creates a product in Tenant A,
| queries from Tenant B, asserts empty result. Also exercises the
| actingAsTenant() and assertTenantIsolated() helpers.
|
*/

it('demonstrates cross-tenant product isolation', function () {
    $familyA = $this->fixture($this->tenantA, 'family_id');

    // Create a product in Tenant A
    $this->actingAsTenant($this->tenantA);
    DB::table('products')->insert([
        'sku'                 => 'SAMPLE-'.uniqid(),
        'type'                => 'simple',
        'attribute_family_id' => $familyA,
        'tenant_id'           => $this->tenantA->id,
        'created_at'          => now(),
        'updated_at'          => now(),
    ]);

    // Tenant A sees the product
    expect(Product::count())->toBeGreaterThanOrEqual(1);

    // Switch to Tenant B — should NOT see Tenant A's product
    $this->actingAsTenant($this->tenantB);
    expect(Product::count())->toBe(0);
});

it('uses assertTenantIsolated helper for Notification', function () {
    $this->assertTenantIsolated(Notification::class, [
        'type'       => 'test-isolation',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

it('provides per-tenant fixtures (role, admin, family)', function () {
    expect($this->fixture($this->tenantA, 'role_id'))->toBeInt();
    expect($this->fixture($this->tenantA, 'admin_id'))->toBeInt();
    expect($this->fixture($this->tenantA, 'family_id'))->toBeInt();

    expect($this->fixture($this->tenantB, 'role_id'))->toBeInt();
    expect($this->fixture($this->tenantB, 'admin_id'))->toBeInt();
    expect($this->fixture($this->tenantB, 'family_id'))->toBeInt();

    // Fixtures are different between tenants
    expect($this->fixture($this->tenantA, 'role_id'))
        ->not->toBe($this->fixture($this->tenantB, 'role_id'));
});
