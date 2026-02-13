<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Core\Models\CoreConfig;
use Webkul\Core\Models\Currency;
use Webkul\Core\Models\Locale;
use Webkul\User\Models\Role;

/*
|--------------------------------------------------------------------------
| Wave 2 Tenant Isolation Tests
|--------------------------------------------------------------------------
|
| Proves tenant isolation for 6 Wave 2 Structural Models.
|
*/

it('isolates attribute families between tenants', function () {
    core()->setCurrentTenantId($this->tenantA->id);
    DB::table('attribute_families')->insert(['code' => 'fam-a-'.uniqid(), 'tenant_id' => $this->tenantA->id]);

    core()->setCurrentTenantId($this->tenantB->id);
    DB::table('attribute_families')->insert(['code' => 'fam-b-'.uniqid(), 'tenant_id' => $this->tenantB->id]);

    core()->setCurrentTenantId($this->tenantA->id);
    $countA = AttributeFamily::count();

    core()->setCurrentTenantId($this->tenantB->id);
    $countB = AttributeFamily::count();

    // Each tenant sees only its own families (fixture + test-created)
    expect($countA)->toBeGreaterThanOrEqual(1);
    expect($countB)->toBeGreaterThanOrEqual(1);

    // Cross-tenant: tenant A's test family not visible from tenant B
    core()->setCurrentTenantId($this->tenantB->id);
    expect(AttributeFamily::where('code', 'LIKE', 'fam-a-%')->count())->toBe(0);
});

it('isolates attribute groups between tenants', function () {
    core()->setCurrentTenantId($this->tenantA->id);
    DB::table('attribute_groups')->insert([
        'code'      => 'grp-a-'.uniqid(),
        'tenant_id' => $this->tenantA->id,
    ]);

    core()->setCurrentTenantId($this->tenantB->id);
    DB::table('attribute_groups')->insert([
        'code'      => 'grp-b-'.uniqid(),
        'tenant_id' => $this->tenantB->id,
    ]);

    core()->setCurrentTenantId($this->tenantA->id);
    expect(AttributeGroup::count())->toBe(1);

    core()->setCurrentTenantId($this->tenantB->id);
    expect(AttributeGroup::count())->toBe(1);

    core()->setCurrentTenantId(null);
    expect(AttributeGroup::count())->toBe(2);
});

it('isolates roles between tenants', function () {
    core()->setCurrentTenantId($this->tenantA->id);
    DB::table('roles')->insert([
        'name'            => 'Role A',
        'description'     => 'Test',
        'permission_type' => 'all',
        'permissions'     => json_encode([]),
        'tenant_id'       => $this->tenantA->id,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    core()->setCurrentTenantId($this->tenantB->id);
    DB::table('roles')->insert([
        'name'            => 'Role B',
        'description'     => 'Test',
        'permission_type' => 'all',
        'permissions'     => json_encode([]),
        'tenant_id'       => $this->tenantB->id,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);

    core()->setCurrentTenantId($this->tenantA->id);
    expect(Role::where('name', 'Role A')->count())->toBe(1);

    // Cross-tenant: Role B not visible from tenant A context
    expect(Role::where('name', 'Role B')->count())->toBe(0);

    core()->setCurrentTenantId($this->tenantB->id);
    expect(Role::where('name', 'Role B')->count())->toBe(1);
});

it('isolates locales between tenants', function () {
    core()->setCurrentTenantId($this->tenantA->id);
    DB::table('locales')->insert([
        'code'      => 'loc_a_'.uniqid(),
        'tenant_id' => $this->tenantA->id,
    ]);

    core()->setCurrentTenantId($this->tenantB->id);
    DB::table('locales')->insert([
        'code'      => 'loc_b_'.uniqid(),
        'tenant_id' => $this->tenantB->id,
    ]);

    core()->setCurrentTenantId($this->tenantA->id);
    expect(Locale::count())->toBe(1);

    core()->setCurrentTenantId(null);
    expect(Locale::count())->toBe(2);
});

it('isolates currencies between tenants', function () {
    core()->setCurrentTenantId($this->tenantA->id);
    DB::table('currencies')->insert([
        'code'      => 'TA'.rand(10,99),
        'tenant_id' => $this->tenantA->id,
    ]);

    core()->setCurrentTenantId($this->tenantB->id);
    DB::table('currencies')->insert([
        'code'      => 'TB'.rand(10,99),
        'tenant_id' => $this->tenantB->id,
    ]);

    core()->setCurrentTenantId($this->tenantA->id);
    expect(Currency::count())->toBe(1);

    core()->setCurrentTenantId(null);
    expect(Currency::count())->toBe(2);
});

it('isolates core_config between tenants', function () {
    core()->setCurrentTenantId($this->tenantA->id);
    DB::table('core_config')->insert([
        'code'      => 'test.config.a',
        'value'     => 'valueA',
        'tenant_id' => $this->tenantA->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    core()->setCurrentTenantId($this->tenantB->id);
    DB::table('core_config')->insert([
        'code'      => 'test.config.b',
        'value'     => 'valueB',
        'tenant_id' => $this->tenantB->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    core()->setCurrentTenantId($this->tenantA->id);
    expect(CoreConfig::count())->toBe(1);
    expect(CoreConfig::first()->value)->toBe('valueA');

    core()->setCurrentTenantId(null);
    expect(CoreConfig::count())->toBe(2);
});
