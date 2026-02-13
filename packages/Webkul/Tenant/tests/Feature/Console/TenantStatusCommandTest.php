<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Webkul\Tenant\Models\Tenant;
use Webkul\Tenant\Services\TenantSeeder;

beforeEach(function () {
    Mail::fake();
});

it('shows all tenants in a table', function () {
    Tenant::factory()->create(['name' => 'Tenant Alpha', 'domain' => 'alpha', 'status' => Tenant::STATUS_ACTIVE]);
    Tenant::factory()->create(['name' => 'Tenant Beta', 'domain' => 'beta', 'status' => Tenant::STATUS_SUSPENDED]);

    $this->artisan('tenant:status')
        ->expectsOutputToContain('Tenant Alpha')
        ->expectsOutputToContain('Tenant Beta')
        ->assertSuccessful();
});

it('shows single tenant detail', function () {
    $tenant = Tenant::factory()->create([
        'name'   => 'Detail Corp',
        'domain' => 'detail',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $this->artisan('tenant:status', ['--tenant' => $tenant->id])
        ->expectsOutputToContain('Detail Corp')
        ->expectsOutputToContain('detail')
        ->expectsOutputToContain('active')
        ->assertSuccessful();
});

it('shows data counts for a provisioned tenant', function () {
    $tenant = Tenant::factory()->create([
        'status' => Tenant::STATUS_PROVISIONING,
        'domain' => 'counts-test',
    ]);

    core()->setCurrentTenantId($tenant->id);
    app(TenantSeeder::class)->seed($tenant, ['email' => 'admin@counts.test']);
    $tenant->transitionTo(Tenant::STATUS_ACTIVE);
    core()->setCurrentTenantId(null);

    $this->artisan('tenant:status', ['--tenant' => $tenant->id])
        ->expectsOutputToContain('Data Counts')
        ->expectsOutputToContain('Provisioning Completeness')
        ->assertSuccessful();
});

it('shows provisioning completeness checks', function () {
    $tenant = Tenant::factory()->create([
        'status' => Tenant::STATUS_PROVISIONING,
        'domain' => 'complete-test',
    ]);

    core()->setCurrentTenantId($tenant->id);
    app(TenantSeeder::class)->seed($tenant, ['email' => 'admin@complete.test']);
    $tenant->transitionTo(Tenant::STATUS_ACTIVE);
    core()->setCurrentTenantId(null);

    $this->artisan('tenant:status', ['--tenant' => $tenant->id])
        ->expectsOutputToContain('OK')
        ->assertSuccessful();
});

it('fails for nonexistent tenant', function () {
    $this->artisan('tenant:status', ['--tenant' => 99999])
        ->assertFailed();
});

it('shows no tenants message when empty', function () {
    // Remove all tenants (including the ones from TenantTestCase setUp)
    Tenant::query()->forceDelete();

    $this->artisan('tenant:status')
        ->expectsOutputToContain('No tenants found')
        ->assertSuccessful();
});
