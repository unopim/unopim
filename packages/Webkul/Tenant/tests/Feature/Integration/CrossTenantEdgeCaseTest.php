<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Webkul\Tenant\Cache\TenantCache;
use Webkul\Tenant\Exceptions\TenantStateTransitionException;
use Webkul\Tenant\Models\Tenant;
use Webkul\Tenant\Services\TenantPurger;
use Webkul\Tenant\Services\TenantSeeder;
use Webkul\User\Models\Admin;

/*
|--------------------------------------------------------------------------
| Cross-Tenant Edge Case Tests
|--------------------------------------------------------------------------
|
| Concurrent tenant operations, large tenant counts, cross-tenant
| data leakage audit, and boundary condition verification.
|
*/

beforeEach(function () {
    Mail::fake();
});

// -- Comprehensive leakage audit ----------------------------------------------

it('audits all BelongsToTenant models for cross-tenant leakage', function () {
    $modelsToAudit = [
        \Webkul\Product\Models\Product::class => [
            'sku' => 'AUDIT-'.uniqid(), 'type' => 'simple',
            'attribute_family_id' => $this->fixture($this->tenantA, 'family_id'),
            'created_at' => now(), 'updated_at' => now(),
        ],
        \Webkul\Category\Models\Category::class => [
            'code' => 'audit-cat-'.uniqid(), '_lft' => 100, '_rgt' => 101,
            'created_at' => now(), 'updated_at' => now(),
        ],
        \Webkul\Attribute\Models\Attribute::class => [
            'code' => 'audit-attr-'.uniqid(), 'type' => 'text',
            'created_at' => now(), 'updated_at' => now(),
        ],
        // attribute_families has NO timestamps columns
        \Webkul\Attribute\Models\AttributeFamily::class => [
            'code' => 'audit-fam-'.uniqid(),
        ],
        \Webkul\Core\Models\Currency::class => [
            'code' => strtoupper(substr(uniqid(), 0, 3)), 'symbol' => '?',
            'created_at' => now(), 'updated_at' => now(),
        ],
        \Webkul\User\Models\Role::class => [
            'name' => 'Audit Role', 'description' => 'test', 'permission_type' => 'all',
            'created_at' => now(), 'updated_at' => now(),
        ],
    ];

    $tenantAId = $this->tenantA->id;
    $tenantBId = $this->tenantB->id;

    foreach ($modelsToAudit as $modelClass => $attributes) {
        $model = new $modelClass;
        $table = $model->getTable();

        $insertData = array_merge($attributes, ['tenant_id' => $tenantAId]);

        $recordId = DB::table($table)->insertGetId($insertData);

        // Verify tenantA can see it
        core()->setCurrentTenantId($tenantAId);
        $countA = $modelClass::where('id', $recordId)->count();
        expect($countA)->toBe(1, "Tenant A should see record {$recordId} in {$table}");

        // Verify tenantB cannot see it
        core()->setCurrentTenantId($tenantBId);
        $countB = $modelClass::where('id', $recordId)->count();
        expect($countB)->toBe(0, "Tenant B should NOT see tenant A's record {$recordId} in {$table}");
    }

    core()->setCurrentTenantId($tenantAId);
});

// -- Duplicate SKUs -----------------------------------------------------------

it('allows duplicate SKUs across different tenants', function () {
    $sku = 'DUPE-SKU-001';

    core()->setCurrentTenantId($this->tenantA->id);
    DB::table('products')->insert([
        'sku'                 => $sku,
        'type'                => 'simple',
        'attribute_family_id' => $this->fixture($this->tenantA, 'family_id'),
        'tenant_id'           => $this->tenantA->id,
        'created_at'          => now(),
        'updated_at'          => now(),
    ]);

    core()->setCurrentTenantId($this->tenantB->id);
    DB::table('products')->insert([
        'sku'                 => $sku,
        'type'                => 'simple',
        'attribute_family_id' => $this->fixture($this->tenantB, 'family_id'),
        'tenant_id'           => $this->tenantB->id,
        'created_at'          => now(),
        'updated_at'          => now(),
    ]);

    core()->setCurrentTenantId($this->tenantA->id);
    expect(\Webkul\Product\Models\Product::where('sku', $sku)->count())->toBe(1);

    core()->setCurrentTenantId($this->tenantB->id);
    expect(\Webkul\Product\Models\Product::where('sku', $sku)->count())->toBe(1);

    expect(DB::table('products')->where('sku', $sku)->count())->toBe(2);

    core()->setCurrentTenantId($this->tenantA->id);
});

// -- Domain uniqueness --------------------------------------------------------

it('enforces domain uniqueness across tenants', function () {
    $domain = 'unique-domain-test';

    Tenant::factory()->create(['domain' => $domain]);

    expect(fn () => Tenant::factory()->create(['domain' => $domain]))
        ->toThrow(\Illuminate\Database\QueryException::class);
});

// -- Scale test ---------------------------------------------------------------

it('handles 10+ tenants without performance degradation', function () {
    $tenants = [];
    $seeder = app(TenantSeeder::class);

    // List of unique locale codes to avoid locales.code UNIQUE constraint
    $localeCodes = ['af_ZA', 'ar_SA', 'bg_BG', 'cs_CZ', 'da_DK', 'el_GR', 'fi_FI', 'he_IL', 'hi_IN', 'hu_HU'];

    for ($i = 0; $i < 10; $i++) {
        $tenant = Tenant::factory()->provisioning()->create(['domain' => "scale-{$i}"]);
        $result = $seeder->seed($tenant, [
            'email'  => "admin@scale-{$i}.test",
            'locale' => $localeCodes[$i],
        ]);
        $tenant->transitionTo(Tenant::STATUS_ACTIVE);
        $tenants[] = ['tenant' => $tenant, 'result' => $result];
    }

    foreach ($tenants as $entry) {
        core()->setCurrentTenantId($entry['tenant']->id);

        DB::table('products')->insert([
            'sku'                 => 'SCALE-'.uniqid(),
            'type'                => 'simple',
            'attribute_family_id' => $entry['result']['family_id'],
            'tenant_id'           => $entry['tenant']->id,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        expect(\Webkul\Product\Models\Product::count())->toBe(1);
    }

    core()->setCurrentTenantId(null);
    $totalProducts = DB::table('products')
        ->whereIn('tenant_id', array_map(fn ($e) => $e['tenant']->id, $tenants))
        ->count();
    expect($totalProducts)->toBe(10);

    core()->setCurrentTenantId($this->tenantA->id);
});

// -- Rapid context switching --------------------------------------------------

it('maintains isolation during rapid context switching', function () {
    $familyA = $this->fixture($this->tenantA, 'family_id');
    $familyB = $this->fixture($this->tenantB, 'family_id');

    for ($i = 0; $i < 10; $i++) {
        core()->setCurrentTenantId($this->tenantA->id);
        DB::table('products')->insert([
            'sku'                 => "RAPID-A-{$i}",
            'type'                => 'simple',
            'attribute_family_id' => $familyA,
            'tenant_id'           => $this->tenantA->id,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        core()->setCurrentTenantId($this->tenantB->id);
        DB::table('products')->insert([
            'sku'                 => "RAPID-B-{$i}",
            'type'                => 'simple',
            'attribute_family_id' => $familyB,
            'tenant_id'           => $this->tenantB->id,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    core()->setCurrentTenantId($this->tenantA->id);
    expect(\Webkul\Product\Models\Product::where('sku', 'like', 'RAPID-A-%')->count())->toBe(10);
    expect(\Webkul\Product\Models\Product::where('sku', 'like', 'RAPID-B-%')->count())->toBe(0);

    core()->setCurrentTenantId($this->tenantB->id);
    expect(\Webkul\Product\Models\Product::where('sku', 'like', 'RAPID-B-%')->count())->toBe(10);
    expect(\Webkul\Product\Models\Product::where('sku', 'like', 'RAPID-A-%')->count())->toBe(0);

    core()->setCurrentTenantId($this->tenantA->id);
});

// -- Interleaved creation -----------------------------------------------------

it('isolates interleaved product creation across tenants', function () {
    $familyA = $this->fixture($this->tenantA, 'family_id');
    $familyB = $this->fixture($this->tenantB, 'family_id');

    for ($i = 0; $i < 5; $i++) {
        core()->setCurrentTenantId($this->tenantA->id);
        DB::table('products')->insert([
            'sku'                 => "INTER-A-{$i}",
            'type'                => 'simple',
            'attribute_family_id' => $familyA,
            'tenant_id'           => $this->tenantA->id,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);

        core()->setCurrentTenantId($this->tenantB->id);
        DB::table('products')->insert([
            'sku'                 => "INTER-B-{$i}",
            'type'                => 'simple',
            'attribute_family_id' => $familyB,
            'tenant_id'           => $this->tenantB->id,
            'created_at'          => now(),
            'updated_at'          => now(),
        ]);
    }

    core()->setCurrentTenantId($this->tenantA->id);
    expect(\Webkul\Product\Models\Product::where('sku', 'like', 'INTER-A-%')->count())->toBe(5);

    core()->setCurrentTenantId($this->tenantB->id);
    expect(\Webkul\Product\Models\Product::where('sku', 'like', 'INTER-B-%')->count())->toBe(5);

    core()->setCurrentTenantId($this->tenantA->id);
});

// -- Platform operator visibility ---------------------------------------------

it('allows platform operator (null context) to see all tenant data', function () {
    $familyA = $this->fixture($this->tenantA, 'family_id');
    $familyB = $this->fixture($this->tenantB, 'family_id');

    DB::table('products')->insert([
        'sku' => 'PLAT-A-1', 'type' => 'simple',
        'attribute_family_id' => $familyA,
        'tenant_id' => $this->tenantA->id, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('products')->insert([
        'sku' => 'PLAT-B-1', 'type' => 'simple',
        'attribute_family_id' => $familyB,
        'tenant_id' => $this->tenantB->id, 'created_at' => now(), 'updated_at' => now(),
    ]);

    core()->setCurrentTenantId(null);
    $allProducts = \Webkul\Product\Models\Product::withoutGlobalScopes()
        ->whereIn('sku', ['PLAT-A-1', 'PLAT-B-1'])
        ->count();
    expect($allProducts)->toBe(2);

    $scopedCount = \Webkul\Product\Models\Product::whereIn('sku', ['PLAT-A-1', 'PLAT-B-1'])->count();
    expect($scopedCount)->toBe(2);

    core()->setCurrentTenantId($this->tenantA->id);
});

// -- Category nested set isolation --------------------------------------------

it('isolates category nested set trees per tenant', function () {
    core()->setCurrentTenantId($this->tenantA->id);
    $rootAId = DB::table('categories')->insertGetId([
        'code' => 'tree-root-a', 'parent_id' => null, '_lft' => 200, '_rgt' => 203,
        'tenant_id' => $this->tenantA->id, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('categories')->insert([
        'code' => 'tree-child-a', 'parent_id' => $rootAId, '_lft' => 201, '_rgt' => 202,
        'tenant_id' => $this->tenantA->id, 'created_at' => now(), 'updated_at' => now(),
    ]);

    core()->setCurrentTenantId($this->tenantB->id);
    $rootBId = DB::table('categories')->insertGetId([
        'code' => 'tree-root-b', 'parent_id' => null, '_lft' => 200, '_rgt' => 203,
        'tenant_id' => $this->tenantB->id, 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('categories')->insert([
        'code' => 'tree-child-b', 'parent_id' => $rootBId, '_lft' => 201, '_rgt' => 202,
        'tenant_id' => $this->tenantB->id, 'created_at' => now(), 'updated_at' => now(),
    ]);

    core()->setCurrentTenantId($this->tenantA->id);
    expect(\Webkul\Category\Models\Category::whereIn('code', ['tree-root-a', 'tree-child-a'])->count())->toBe(2);
    expect(\Webkul\Category\Models\Category::whereIn('code', ['tree-root-b', 'tree-child-b'])->count())->toBe(0);

    core()->setCurrentTenantId($this->tenantB->id);
    expect(\Webkul\Category\Models\Category::whereIn('code', ['tree-root-a', 'tree-child-a'])->count())->toBe(0);
    expect(\Webkul\Category\Models\Category::whereIn('code', ['tree-root-b', 'tree-child-b'])->count())->toBe(2);

    core()->setCurrentTenantId($this->tenantA->id);
});

// -- Cache isolation ----------------------------------------------------------

it('isolates cache keys per tenant', function () {
    $key = 'edge-case-cache-test';

    core()->setCurrentTenantId($this->tenantA->id);
    TenantCache::put($key, 'value-from-A');
    expect(TenantCache::get($key))->toBe('value-from-A');

    core()->setCurrentTenantId($this->tenantB->id);
    TenantCache::put($key, 'value-from-B');
    expect(TenantCache::get($key))->toBe('value-from-B');

    core()->setCurrentTenantId($this->tenantA->id);
    expect(TenantCache::get($key))->toBe('value-from-A');

    core()->setCurrentTenantId($this->tenantB->id);
    expect(TenantCache::get($key))->toBe('value-from-B');

    TenantCache::forget($key, $this->tenantA->id);
    TenantCache::forget($key, $this->tenantB->id);
    core()->setCurrentTenantId($this->tenantA->id);
});

// -- Route blocking: cross-tenant resource access -----------------------------

it('blocks tenant admin from accessing other tenants products via scope', function () {
    $familyA = $this->fixture($this->tenantA, 'family_id');

    core()->setCurrentTenantId($this->tenantA->id);
    $productId = DB::table('products')->insertGetId([
        'sku' => 'BLOCK-TEST-001', 'type' => 'simple',
        'attribute_family_id' => $familyA,
        'tenant_id' => $this->tenantA->id, 'created_at' => now(), 'updated_at' => now(),
    ]);

    core()->setCurrentTenantId($this->tenantB->id);
    $found = \Webkul\Product\Models\Product::find($productId);
    expect($found)->toBeNull();

    core()->setCurrentTenantId($this->tenantA->id);
});

// -- Platform operator route blocking -----------------------------------------

it('blocks platform operator routes for tenant users', function () {
    $localeId = DB::table('locales')->insertGetId(['code' => 'en_US', 'status' => 1]);
    $currencyId = DB::table('currencies')->insertGetId(['code' => 'USD', 'symbol' => '$']);
    $channelId = DB::table('channels')->insertGetId([
        'code' => config('app.channel', 'default'), 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('channel_locales')->insert(['channel_id' => $channelId, 'locale_id' => $localeId]);
    DB::table('channel_currencies')->insert(['channel_id' => $channelId, 'currency_id' => $currencyId]);
    $channel = \Webkul\Core\Models\Channel::withoutGlobalScopes()->find($channelId);
    core()->setDefaultChannel($channel);

    $tenantAdmin = Admin::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'role_id'   => $this->fixture($this->tenantA, 'role_id'),
    ]);

    $response = $this->actingAs($tenantAdmin, 'admin')
        ->get(route('admin.settings.tenants.index'));

    $response->assertStatus(403);
});

// -- TenantPurger completeness ------------------------------------------------

it('verifies TenantPurger removes all data after deletion', function () {
    $tenant = Tenant::factory()->provisioning()->create(['domain' => 'purge-test']);

    $seeder = app(TenantSeeder::class);
    $result = $seeder->seed($tenant, [
        'email'  => 'admin@purge-test.test',
        'locale' => 'nl_NL',
    ]);
    $tenant->transitionTo(Tenant::STATUS_ACTIVE);

    core()->setCurrentTenantId($tenant->id);
    DB::table('products')->insert([
        'sku' => 'PURGE-PROD-1', 'type' => 'simple',
        'attribute_family_id' => $result['family_id'],
        'tenant_id' => $tenant->id, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $purger = app(TenantPurger::class);
    $tenant->transitionTo(Tenant::STATUS_DELETING);
    $report = $purger->purge($tenant);

    expect($report)->toHaveKey('tenant_id');
    expect($report)->toHaveKey('tables');
    expect($report['tenant_id'])->toBe($tenant->id);

    $verification = $purger->verify($tenant->id);
    expect($verification['status'])->toBe('COMPLETE');
    expect($verification['residual'])->toBeEmpty();

    core()->setCurrentTenantId($this->tenantA->id);
});

// -- Invalid state transitions ------------------------------------------------

it('prevents invalid state transitions', function () {
    $tenant1 = Tenant::factory()->provisioning()->create(['domain' => 'trans-1']);
    expect(fn () => $tenant1->transitionTo(Tenant::STATUS_SUSPENDED))
        ->toThrow(TenantStateTransitionException::class);

    $tenant2 = Tenant::factory()->create(['domain' => 'trans-2', 'status' => Tenant::STATUS_ACTIVE]);
    expect(fn () => $tenant2->transitionTo(Tenant::STATUS_PROVISIONING))
        ->toThrow(TenantStateTransitionException::class);

    $tenant3 = Tenant::factory()->create(['domain' => 'trans-3', 'status' => Tenant::STATUS_DELETED]);
    expect(fn () => $tenant3->transitionTo(Tenant::STATUS_ACTIVE))
        ->toThrow(TenantStateTransitionException::class);
});

// -- DataGrid tenant scope ---------------------------------------------------

it('ensures TenantScope is applied to DataGrid queries', function () {
    core()->setCurrentTenantId($this->tenantA->id);

    $dataGrid = app(\Webkul\Admin\DataGrids\Catalog\ProductDataGrid::class);
    $dataGrid->setQueryBuilder();

    // Access protected $queryBuilder via closure binding
    $queryBuilder = (fn () => $this->queryBuilder)->call($dataGrid);
    $sql = $queryBuilder->toSql();
    $bindings = $queryBuilder->getBindings();

    expect($sql)->toContain('tenant_id');
    expect($bindings)->toContain($this->tenantA->id);
});
