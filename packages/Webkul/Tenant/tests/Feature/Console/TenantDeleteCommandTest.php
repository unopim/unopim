<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Webkul\Tenant\Models\Tenant;
use Webkul\Tenant\Services\TenantPurger;
use Webkul\Tenant\Services\TenantSeeder;

beforeEach(function () {
    Mail::fake();
});

it('deletes a tenant and purges all data', function () {
    // Provision a tenant first
    $tenant = Tenant::factory()->create([
        'status' => Tenant::STATUS_PROVISIONING,
        'domain' => 'del-test',
    ]);

    core()->setCurrentTenantId($tenant->id);
    app(TenantSeeder::class)->seed($tenant, ['email' => 'admin@del.test']);
    $tenant->transitionTo(Tenant::STATUS_ACTIVE);
    core()->setCurrentTenantId(null);

    // Verify data exists before deletion
    expect(DB::table('admins')->where('tenant_id', $tenant->id)->count())->toBeGreaterThan(0);
    expect(DB::table('channels')->where('tenant_id', $tenant->id)->count())->toBeGreaterThan(0);

    // Delete
    $this->artisan('tenant:delete', [
        '--tenant'  => $tenant->id,
        '--confirm' => true,
    ])->assertSuccessful();

    // Verify all data is purged
    expect(DB::table('admins')->where('tenant_id', $tenant->id)->count())->toBe(0);
    expect(DB::table('channels')->where('tenant_id', $tenant->id)->count())->toBe(0);
    expect(DB::table('roles')->where('tenant_id', $tenant->id)->count())->toBe(0);
    expect(DB::table('categories')->where('tenant_id', $tenant->id)->count())->toBe(0);

    // Tenant should be soft-deleted
    $tenant = Tenant::withTrashed()->find($tenant->id);
    expect($tenant->status)->toBe(Tenant::STATUS_DELETED);
    expect($tenant->deleted_at)->not->toBeNull();
});

it('refuses to delete a provisioning tenant', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_PROVISIONING]);

    $this->artisan('tenant:delete', [
        '--tenant'  => $tenant->id,
        '--confirm' => true,
    ])->assertFailed();
});

it('generates a COMPLETE deletion report', function () {
    $tenant = Tenant::factory()->create([
        'status' => Tenant::STATUS_PROVISIONING,
        'domain' => 'report-test',
    ]);

    core()->setCurrentTenantId($tenant->id);
    app(TenantSeeder::class)->seed($tenant, ['email' => 'admin@report.test']);
    $tenant->transitionTo(Tenant::STATUS_ACTIVE);
    core()->setCurrentTenantId(null);

    $this->artisan('tenant:delete', [
        '--tenant'  => $tenant->id,
        '--confirm' => true,
    ])
        ->expectsOutputToContain('Tenant Deletion Completeness Report')
        ->expectsOutputToContain('Status: COMPLETE')
        ->assertSuccessful();
});

it('verifies zero residual data after deletion', function () {
    $tenant = Tenant::factory()->create([
        'status' => Tenant::STATUS_PROVISIONING,
        'domain' => 'zero-test',
    ]);

    core()->setCurrentTenantId($tenant->id);
    app(TenantSeeder::class)->seed($tenant, ['email' => 'admin@zero.test']);
    $tenant->transitionTo(Tenant::STATUS_ACTIVE);
    core()->setCurrentTenantId(null);

    // Delete tenant
    $this->artisan('tenant:delete', [
        '--tenant'  => $tenant->id,
        '--confirm' => true,
    ])->assertSuccessful();

    // Manual verification via purger
    $purger = app(TenantPurger::class);
    $verification = $purger->verify($tenant->id);

    expect($verification['status'])->toBe('COMPLETE');
    expect($verification['residual'])->toBeEmpty();
});

it('finds all tenant-scoped tables', function () {
    $purger = app(TenantPurger::class);
    $tables = $purger->findTenantScopedTables();

    // Should find tables from all 3 waves + tenants table itself
    expect($tables)->toContain('products');
    expect($tables)->toContain('categories');
    expect($tables)->toContain('admins');
    expect($tables)->toContain('roles');
    expect($tables)->toContain('channels');
});

it('requires --tenant option', function () {
    $this->artisan('tenant:delete', ['--confirm' => true])->assertFailed();
});
