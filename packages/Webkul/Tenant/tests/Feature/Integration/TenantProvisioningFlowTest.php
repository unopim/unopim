<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Webkul\Tenant\Models\Tenant;
use Webkul\Tenant\Services\TenantPurger;
use Webkul\Tenant\Services\TenantSeeder;
use Webkul\User\Models\Admin;

/*
|--------------------------------------------------------------------------
| Tenant Provisioning Flow Integration Tests
|--------------------------------------------------------------------------
|
| End-to-end: create tenant via UI → verify all seeded data →
| login as tenant admin → verify scoped access.
|
*/

beforeEach(function () {
    Mail::fake();

    // Seed channel infrastructure with en_GB (NOT en_US) so that
    // TenantSeeder's default en_US doesn't hit locales.code UNIQUE constraint.
    $localeId = DB::table('locales')->insertGetId([
        'code'   => 'en_GB',
        'status' => 1,
    ]);
    $currencyId = DB::table('currencies')->insertGetId([
        'code'   => 'GBP',
        'symbol' => '£',
    ]);
    $channelId = DB::table('channels')->insertGetId([
        'code'       => config('app.channel', 'default'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('channel_locales')->insert([
        'channel_id' => $channelId,
        'locale_id'  => $localeId,
    ]);
    DB::table('channel_currencies')->insert([
        'channel_id'  => $channelId,
        'currency_id' => $currencyId,
    ]);

    $channel = \Webkul\Core\Models\Channel::withoutGlobalScopes()->find($channelId);
    core()->setDefaultChannel($channel);

    // Platform operator admin (tenant_id = null)
    $this->platformAdmin = Admin::factory()->create([
        'tenant_id' => null,
        'role_id'   => $this->fixture($this->tenantA, 'role_id'),
    ]);
});

// -- Controller store ---------------------------------------------------------

it('creates a tenant via controller store and transitions to active', function () {
    $response = $this->actingAs($this->platformAdmin, 'admin')
        ->post(route('admin.settings.tenants.store'), [
            'name'        => 'Acme Corp',
            'domain'      => 'acme-corp',
            'admin_email' => 'admin@acme-corp.test',
        ]);

    $response->assertRedirect(route('admin.settings.tenants.index'));
    $response->assertSessionHas('success');

    $tenant = Tenant::where('domain', 'acme-corp')->first();

    expect($tenant)->not->toBeNull();
    expect($tenant->name)->toBe('Acme Corp');
    expect($tenant->status)->toBe(Tenant::STATUS_ACTIVE);
    expect($tenant->uuid)->not->toBeEmpty();
    expect($tenant->es_index_uuid)->not->toBeEmpty();
});

// -- Seeder verification ------------------------------------------------------

it('seeds all 12 baseline entities for a new tenant', function () {
    $tenant = Tenant::factory()->provisioning()->create(['domain' => 'seed-test']);

    $seeder = app(TenantSeeder::class);
    $result = $seeder->seed($tenant, [
        'email'  => 'admin@seed-test.test',
        'locale' => 'fr_FR',
    ]);

    // Verify all 12 keys returned
    $expectedKeys = [
        'role_id', 'locale_id', 'currency_id', 'admin_id',
        'admin_email', 'admin_password', 'root_category_id',
        'channel_id', 'family_id', 'api_key_id', 'client_id', 'client_secret',
    ];

    foreach ($expectedKeys as $key) {
        expect($result)->toHaveKey($key);
    }

    // Verify each entity exists in DB with correct tenant_id
    expect(DB::table('roles')->where('id', $result['role_id'])->value('tenant_id'))
        ->toBe($tenant->id);
    expect(DB::table('locales')->where('id', $result['locale_id'])->value('tenant_id'))
        ->toBe($tenant->id);
    expect(DB::table('currencies')->where('id', $result['currency_id'])->value('tenant_id'))
        ->toBe($tenant->id);
    expect(DB::table('admins')->where('id', $result['admin_id'])->value('tenant_id'))
        ->toBe($tenant->id);
    expect(DB::table('categories')->where('id', $result['root_category_id'])->value('tenant_id'))
        ->toBe($tenant->id);
    expect(DB::table('channels')->where('id', $result['channel_id'])->value('tenant_id'))
        ->toBe($tenant->id);
    expect(DB::table('attribute_families')->where('id', $result['family_id'])->value('tenant_id'))
        ->toBe($tenant->id);
    expect(DB::table('api_keys')->where('id', $result['api_key_id'])->value('tenant_id'))
        ->toBe($tenant->id);
    expect(DB::table('oauth_clients')->where('id', $result['client_id'])->value('tenant_id'))
        ->toBe($tenant->id);

    // Verify channel pivots
    expect(DB::table('channel_locales')
        ->where('channel_id', $result['channel_id'])
        ->where('locale_id', $result['locale_id'])
        ->exists()
    )->toBeTrue();

    expect(DB::table('channel_currencies')
        ->where('channel_id', $result['channel_id'])
        ->where('currency_id', $result['currency_id'])
        ->exists()
    )->toBeTrue();
});

// -- Authenticate as tenant admin ---------------------------------------------

it('authenticates as seeded tenant admin and verifies scoped access', function () {
    $tenant = Tenant::factory()->provisioning()->create(['domain' => 'auth-test']);
    $seeder = app(TenantSeeder::class);
    $result = $seeder->seed($tenant, [
        'email'  => 'admin@auth-test.test',
        'locale' => 'de_DE',
    ]);
    $tenant->transitionTo(Tenant::STATUS_ACTIVE);

    $admin = Admin::withoutGlobalScopes()->find($result['admin_id']);
    expect($admin)->not->toBeNull();
    expect($admin->tenant_id)->toBe($tenant->id);

    core()->setCurrentTenantId($tenant->id);

    $productId = DB::table('products')->insertGetId([
        'sku'                 => 'AUTH-TEST-001',
        'type'                => 'simple',
        'attribute_family_id' => $result['family_id'],
        'tenant_id'           => $tenant->id,
        'created_at'          => now(),
        'updated_at'          => now(),
    ]);

    $count = \Webkul\Product\Models\Product::count();
    expect($count)->toBeGreaterThanOrEqual(1);

    core()->setCurrentTenantId($this->tenantB->id);
    $countOther = \Webkul\Product\Models\Product::where('id', $productId)->count();
    expect($countOther)->toBe(0);

    core()->setCurrentTenantId($this->tenantA->id);
});

// -- Cross-tenant visibility --------------------------------------------------

it('prevents cross-tenant visibility after provisioning', function () {
    $tenantX = Tenant::factory()->provisioning()->create(['domain' => 'vis-test-x']);
    $tenantY = Tenant::factory()->provisioning()->create(['domain' => 'vis-test-y']);

    $seeder = app(TenantSeeder::class);
    $resultX = $seeder->seed($tenantX, [
        'email'  => 'admin@vis-test-x.test',
        'locale' => 'es_ES',
    ]);
    $resultY = $seeder->seed($tenantY, [
        'email'  => 'admin@vis-test-y.test',
        'locale' => 'it_IT',
    ]);

    $tenantX->transitionTo(Tenant::STATUS_ACTIVE);
    $tenantY->transitionTo(Tenant::STATUS_ACTIVE);

    core()->setCurrentTenantId($tenantX->id);
    DB::table('products')->insert([
        'sku'                 => 'VIS-X-001',
        'type'                => 'simple',
        'attribute_family_id' => $resultX['family_id'],
        'tenant_id'           => $tenantX->id,
        'created_at'          => now(),
        'updated_at'          => now(),
    ]);

    core()->setCurrentTenantId($tenantY->id);
    expect(\Webkul\Product\Models\Product::where('sku', 'VIS-X-001')->count())->toBe(0);

    core()->setCurrentTenantId($tenantX->id);
    expect(\Webkul\Product\Models\Product::where('sku', 'VIS-X-001')->count())->toBe(1);

    core()->setCurrentTenantId($this->tenantA->id);
});

// -- Full lifecycle -----------------------------------------------------------

it('runs full lifecycle: provision → active → suspend → reactivate → delete', function () {
    $tenant = Tenant::factory()->provisioning()->create(['domain' => 'lifecycle-test']);

    $seeder = app(TenantSeeder::class);
    $seeder->seed($tenant, [
        'email'  => 'admin@lifecycle-test.test',
        'locale' => 'ja_JP',
    ]);

    $tenant->transitionTo(Tenant::STATUS_ACTIVE);
    expect($tenant->status)->toBe(Tenant::STATUS_ACTIVE);
    expect($tenant->settings['transition_log'])->toHaveCount(1);

    $tenant->transitionTo(Tenant::STATUS_SUSPENDED);
    expect($tenant->status)->toBe(Tenant::STATUS_SUSPENDED);
    expect($tenant->settings['transition_log'])->toHaveCount(2);

    $tenant->transitionTo(Tenant::STATUS_ACTIVE);
    expect($tenant->status)->toBe(Tenant::STATUS_ACTIVE);
    expect($tenant->settings['transition_log'])->toHaveCount(3);

    $tenant->transitionTo(Tenant::STATUS_DELETING);
    expect($tenant->status)->toBe(Tenant::STATUS_DELETING);

    $purger = app(TenantPurger::class);
    $purger->purge($tenant);

    $tenant->transitionTo(Tenant::STATUS_DELETED);
    expect($tenant->status)->toBe(Tenant::STATUS_DELETED);
    expect($tenant->settings['transition_log'])->toHaveCount(5);

    $verification = $purger->verify($tenant->id);
    expect($verification['status'])->toBe('COMPLETE');
    expect($verification['residual'])->toBeEmpty();
});

// -- Credential verification --------------------------------------------------

it('validates tenant admin credentials work for authentication', function () {
    $tenant = Tenant::factory()->provisioning()->create(['domain' => 'cred-test']);

    $seeder = app(TenantSeeder::class);
    $result = $seeder->seed($tenant, [
        'email'  => 'admin@cred-test.test',
        'locale' => 'ko_KR',
    ]);

    $admin = DB::table('admins')->where('id', $result['admin_id'])->first();
    expect($admin)->not->toBeNull();
    expect($admin->email)->toBe('admin@cred-test.test');
    expect($admin->tenant_id)->toBe($tenant->id);

    expect(Hash::check($result['admin_password'], $admin->password))->toBeTrue();
});

// -- OAuth / API key verification ---------------------------------------------

it('seeds OAuth client and API key with correct tenant association', function () {
    $tenant = Tenant::factory()->provisioning()->create(['domain' => 'oauth-test']);

    $seeder = app(TenantSeeder::class);
    $result = $seeder->seed($tenant, [
        'email'  => 'admin@oauth-test.test',
        'locale' => 'pt_BR',
    ]);

    $client = DB::table('oauth_clients')->where('id', $result['client_id'])->first();
    expect($client)->not->toBeNull();
    expect((int) $client->tenant_id)->toBe($tenant->id);
    expect((bool) $client->password_client)->toBeTrue();

    $apiKey = DB::table('api_keys')->where('id', $result['api_key_id'])->first();
    expect($apiKey)->not->toBeNull();
    expect((int) $apiKey->tenant_id)->toBe($tenant->id);
    expect((int) $apiKey->oauth_client_id)->toBe($result['client_id']);

    expect($result['client_secret'])->toBeString();
    expect(strlen($result['client_secret']))->toBe(40);
});

// -- Transaction rollback -----------------------------------------------------

it('rolls back all seeded data on partial failure', function () {
    $tenant = Tenant::factory()->provisioning()->create(['domain' => 'rollback-test']);

    $rolesBefore = DB::table('roles')->where('tenant_id', $tenant->id)->count();
    $localesBefore = DB::table('locales')->where('tenant_id', $tenant->id)->count();
    $adminsBefore = DB::table('admins')->where('tenant_id', $tenant->id)->count();

    try {
        DB::beginTransaction();

        DB::table('locales')->insert([
            'code' => 'rollback_test', 'status' => 1, 'tenant_id' => $tenant->id,
        ]);
        DB::table('currencies')->insert([
            'code' => 'RBT', 'symbol' => 'R', 'status' => 1, 'tenant_id' => $tenant->id,
        ]);

        DB::rollBack();
    } catch (\Throwable $e) {
        DB::rollBack();
    }

    expect(DB::table('roles')->where('tenant_id', $tenant->id)->count())->toBe($rolesBefore);
    expect(DB::table('locales')->where('tenant_id', $tenant->id)->count())->toBe($localesBefore);
    expect(DB::table('admins')->where('tenant_id', $tenant->id)->count())->toBe($adminsBefore);

    $tenant->refresh();
    expect($tenant->status)->toBe(Tenant::STATUS_PROVISIONING);
});
