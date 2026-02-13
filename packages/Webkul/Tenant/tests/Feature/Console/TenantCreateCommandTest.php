<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Webkul\Tenant\Models\Tenant;

beforeEach(function () {
    Mail::fake();
});

it('provisions a new tenant end-to-end', function () {
    $this->artisan('tenant:create', [
        '--name'     => 'Acme Corp',
        '--domain'   => 'acme',
        '--email'    => 'admin@acme.test',
        '--locale'   => 'en_US',
        '--currency' => 'USD',
    ])->assertSuccessful();

    $tenant = Tenant::where('domain', 'acme')->first();

    expect($tenant)->not->toBeNull();
    expect($tenant->status)->toBe(Tenant::STATUS_ACTIVE);
    expect($tenant->name)->toBe('Acme Corp');

    // Verify seeded data exists
    expect(DB::table('admins')->where('tenant_id', $tenant->id)->exists())->toBeTrue();
    expect(DB::table('roles')->where('tenant_id', $tenant->id)->exists())->toBeTrue();
    expect(DB::table('categories')->where('tenant_id', $tenant->id)->exists())->toBeTrue();
    expect(DB::table('channels')->where('tenant_id', $tenant->id)->exists())->toBeTrue();
    expect(DB::table('locales')->where('tenant_id', $tenant->id)->exists())->toBeTrue();
    expect(DB::table('currencies')->where('tenant_id', $tenant->id)->exists())->toBeTrue();
    expect(DB::table('attribute_families')->where('tenant_id', $tenant->id)->exists())->toBeTrue();
    expect(DB::table('api_keys')->where('tenant_id', $tenant->id)->exists())->toBeTrue();
});

it('rejects duplicate domain', function () {
    Tenant::factory()->create(['domain' => 'taken']);

    $this->artisan('tenant:create', [
        '--name'   => 'Dup Corp',
        '--domain' => 'taken',
        '--email'  => 'admin@dup.test',
    ])->assertFailed();

    // Only one tenant with this domain
    expect(Tenant::where('domain', 'taken')->count())->toBe(1);
});

it('fails without required options', function () {
    $this->artisan('tenant:create', [
        '--name' => 'Missing',
    ])->assertFailed();
});

it('cleans up on provisioning failure', function () {
    // Bind a failing seeder to trigger cleanup
    $this->app->singleton(\Webkul\Tenant\Services\TenantSeeder::class, function () {
        return new class extends \Webkul\Tenant\Services\TenantSeeder
        {
            public function seed(\Webkul\Tenant\Models\Tenant $tenant, array $options = []): array
            {
                throw new \RuntimeException('Simulated failure');
            }
        };
    });

    $this->artisan('tenant:create', [
        '--name'   => 'FailCorp',
        '--domain' => 'fail',
        '--email'  => 'admin@fail.test',
    ])->assertFailed();

    // The tenant should be soft-deleted or in deleted state
    $tenant = Tenant::withTrashed()->where('domain', 'fail')->first();
    expect($tenant)->not->toBeNull();
    expect($tenant->deleted_at)->not->toBeNull();
});

it('outputs provisioning credentials on success', function () {
    $this->artisan('tenant:create', [
        '--name'   => 'Output Corp',
        '--domain' => 'output',
        '--email'  => 'admin@output.test',
    ])
        ->expectsOutputToContain('Tenant provisioned successfully!')
        ->expectsOutputToContain('admin@output.test')
        ->assertSuccessful();
});
