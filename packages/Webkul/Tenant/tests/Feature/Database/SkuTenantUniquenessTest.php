<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Webkul\Tenant\Models\Tenant;

beforeEach(function () {
    Mail::fake();
});

it('allows same SKU for different tenants', function () {
    $tenant1 = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
    $tenant2 = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);

    // attribute_family_id is nullable in the products table
    DB::table('products')->insert([
        'sku'        => 'SHARED-SKU',
        'type'       => 'simple',
        'tenant_id'  => $tenant1->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('products')->insert([
        'sku'        => 'SHARED-SKU',
        'type'       => 'simple',
        'tenant_id'  => $tenant2->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $count = DB::table('products')->where('sku', 'SHARED-SKU')->count();
    expect($count)->toBe(2);
});

it('rejects duplicate SKU within the same tenant', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);

    DB::table('products')->insert([
        'sku'        => 'DUP-SKU',
        'type'       => 'simple',
        'tenant_id'  => $tenant->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    expect(fn () => DB::table('products')->insert([
        'sku'        => 'DUP-SKU',
        'type'       => 'simple',
        'tenant_id'  => $tenant->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});

it('has composite unique index on tenant_id and sku', function () {
    $driver = DB::getDriverName();

    if ($driver === 'sqlite') {
        $indexes = DB::select("PRAGMA index_list('products')");
        $indexNames = collect($indexes)->pluck('name')->toArray();
        expect($indexNames)->toContain('products_tenant_sku_unique');
    } else {
        $indexes = DB::select("SHOW INDEX FROM products WHERE Key_name = 'products_tenant_sku_unique'");
        expect($indexes)->not->toBeEmpty();
    }
});
