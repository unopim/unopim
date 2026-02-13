<?php

use Webkul\Tenant\Tests\Stubs\TenantTestStub;

afterEach(function () {
    TenantTestStub::query()->withoutGlobalScopes()->delete();
});

it('applies WHERE tenant_id = ? when tenant context is set', function () {
    core()->setCurrentTenantId($this->tenantA->id);
    TenantTestStub::create(['name' => 'Item A']);

    core()->setCurrentTenantId($this->tenantB->id);
    TenantTestStub::create(['name' => 'Item B']);

    // Tenant A should only see its own items
    core()->setCurrentTenantId($this->tenantA->id);
    $results = TenantTestStub::all();

    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Item A');
});

it('is a no-op when tenant context is null (platform context)', function () {
    core()->setCurrentTenantId($this->tenantA->id);
    TenantTestStub::create(['name' => 'Item A']);

    core()->setCurrentTenantId($this->tenantB->id);
    TenantTestStub::create(['name' => 'Item B']);

    // Platform context (null) sees all items
    core()->setCurrentTenantId(null);
    $results = TenantTestStub::all();

    expect($results)->toHaveCount(2);
});

it('auto-sets tenant_id on model creation from current context', function () {
    core()->setCurrentTenantId($this->tenantA->id);
    $item = TenantTestStub::create(['name' => 'Auto-Set Item']);

    expect($item->tenant_id)->toBe($this->tenantA->id);
});

it('does not override explicitly set tenant_id on creation', function () {
    core()->setCurrentTenantId($this->tenantA->id);
    $item = TenantTestStub::create([
        'name'      => 'Explicit Tenant',
        'tenant_id' => $this->tenantB->id,
    ]);

    expect($item->tenant_id)->toBe($this->tenantB->id);
});

it('does not set tenant_id when context is null', function () {
    core()->setCurrentTenantId(null);
    $item = TenantTestStub::create(['name' => 'Platform Item']);

    expect($item->tenant_id)->toBeNull();
});

it('isolates queries between two tenants (cross-tenant proof)', function () {
    // Create items for tenant A
    core()->setCurrentTenantId($this->tenantA->id);
    TenantTestStub::create(['name' => 'A1']);
    TenantTestStub::create(['name' => 'A2']);

    // Create items for tenant B
    core()->setCurrentTenantId($this->tenantB->id);
    TenantTestStub::create(['name' => 'B1']);

    // Tenant A sees only its 2 items
    core()->setCurrentTenantId($this->tenantA->id);
    expect(TenantTestStub::count())->toBe(2);

    // Tenant B sees only its 1 item
    core()->setCurrentTenantId($this->tenantB->id);
    expect(TenantTestStub::count())->toBe(1);

    // Platform sees all 3
    core()->setCurrentTenantId(null);
    expect(TenantTestStub::count())->toBe(3);
});

it('scopes find() to the current tenant', function () {
    core()->setCurrentTenantId($this->tenantA->id);
    $itemA = TenantTestStub::create(['name' => 'A Item']);

    core()->setCurrentTenantId($this->tenantB->id);
    $itemB = TenantTestStub::create(['name' => 'B Item']);

    // Tenant A cannot find Tenant B's item
    core()->setCurrentTenantId($this->tenantA->id);
    expect(TenantTestStub::find($itemB->id))->toBeNull();
    expect(TenantTestStub::find($itemA->id))->not->toBeNull();
});

it('provides tenant() relationship on the model', function () {
    core()->setCurrentTenantId($this->tenantA->id);
    $item = TenantTestStub::create(['name' => 'Relatable']);

    expect($item->tenant)->not->toBeNull();
    expect($item->tenant->id)->toBe($this->tenantA->id);
});
