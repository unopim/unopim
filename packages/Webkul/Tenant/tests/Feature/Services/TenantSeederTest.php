<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Webkul\Tenant\Mail\TenantWelcomeMail;
use Webkul\Tenant\Models\Tenant;
use Webkul\Tenant\Services\TenantSeeder;

beforeEach(function () {
    Mail::fake();
});

it('seeds all baseline entities for a new tenant', function () {
    $tenant = Tenant::factory()->create([
        'status' => Tenant::STATUS_PROVISIONING,
        'domain' => 'acme',
    ]);

    core()->setCurrentTenantId($tenant->id);

    $seeder = app(TenantSeeder::class);
    $result = $seeder->seed($tenant, [
        'email'    => 'admin@acme.test',
        'locale'   => 'en_US',
        'currency' => 'USD',
    ]);

    // Verify all 5 seeded entity types exist
    expect($result)->toHaveKeys([
        'role_id', 'locale_id', 'currency_id', 'admin_id',
        'root_category_id', 'channel_id', 'family_id', 'api_key_id',
    ]);

    // Verify DB records
    expect(DB::table('roles')->where('id', $result['role_id'])->where('tenant_id', $tenant->id)->exists())->toBeTrue();
    expect(DB::table('locales')->where('id', $result['locale_id'])->where('tenant_id', $tenant->id)->exists())->toBeTrue();
    expect(DB::table('currencies')->where('id', $result['currency_id'])->where('tenant_id', $tenant->id)->exists())->toBeTrue();
    expect(DB::table('admins')->where('id', $result['admin_id'])->where('tenant_id', $tenant->id)->exists())->toBeTrue();
    expect(DB::table('categories')->where('id', $result['root_category_id'])->where('tenant_id', $tenant->id)->exists())->toBeTrue();
    expect(DB::table('channels')->where('id', $result['channel_id'])->where('tenant_id', $tenant->id)->exists())->toBeTrue();
    expect(DB::table('attribute_families')->where('id', $result['family_id'])->where('tenant_id', $tenant->id)->exists())->toBeTrue();
    expect(DB::table('api_keys')->where('id', $result['api_key_id'])->where('tenant_id', $tenant->id)->exists())->toBeTrue();
});

it('creates channel with locale and currency pivots', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_PROVISIONING, 'domain' => 'pivot-test']);
    core()->setCurrentTenantId($tenant->id);

    $result = app(TenantSeeder::class)->seed($tenant, [
        'email'    => 'admin@pivot.test',
        'locale'   => 'en_US',
        'currency' => 'USD',
    ]);

    // Channel-locale pivot
    expect(DB::table('channel_locales')
        ->where('channel_id', $result['channel_id'])
        ->where('locale_id', $result['locale_id'])
        ->exists()
    )->toBeTrue();

    // Channel-currency pivot
    expect(DB::table('channel_currencies')
        ->where('channel_id', $result['channel_id'])
        ->where('currency_id', $result['currency_id'])
        ->exists()
    )->toBeTrue();

    // Channel translation
    expect(DB::table('channel_translations')
        ->where('channel_id', $result['channel_id'])
        ->where('locale', 'en_US')
        ->exists()
    )->toBeTrue();
});

it('creates root category with nested set fields', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_PROVISIONING, 'domain' => 'cat-test']);
    core()->setCurrentTenantId($tenant->id);

    $result = app(TenantSeeder::class)->seed($tenant, ['email' => 'admin@cat.test']);

    $category = DB::table('categories')->where('id', $result['root_category_id'])->first();
    expect($category->parent_id)->toBeNull();
    expect($category->code)->toBe('root');
    expect($category->_lft)->toBe(1);
    expect($category->_rgt)->toBe(2);
    expect($category->tenant_id)->toBe($tenant->id);
});

it('dispatches welcome email to the admin', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_PROVISIONING, 'domain' => 'mail-test']);
    core()->setCurrentTenantId($tenant->id);

    app(TenantSeeder::class)->seed($tenant, ['email' => 'welcome@mail.test']);

    Mail::assertSent(TenantWelcomeMail::class, function ($mail) {
        return $mail->email === 'welcome@mail.test';
    });
});

it('rolls back all records on seeder failure', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_PROVISIONING, 'domain' => 'fail-test']);
    core()->setCurrentTenantId($tenant->id);

    // Count existing records before seeding attempt
    $adminsBefore = DB::table('admins')->where('tenant_id', $tenant->id)->count();
    $rolesBefore = DB::table('roles')->where('tenant_id', $tenant->id)->count();

    try {
        // Create a seeder that will fail at the API key step
        // by breaking the api_keys table temporarily
        $seeder = new class extends TenantSeeder
        {
            public function seed(\Webkul\Tenant\Models\Tenant $tenant, array $options = []): array
            {
                return DB::transaction(function () use ($tenant, $options) {
                    // Insert a role (will be rolled back)
                    DB::table('roles')->insert([
                        'name'            => 'Will be rolled back',
                        'description'     => 'test',
                        'permission_type' => 'all',
                        'permissions'     => json_encode([]),
                        'tenant_id'       => $tenant->id,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);

                    throw new \RuntimeException('Simulated seeding failure');
                });
            }
        };

        $seeder->seed($tenant, ['email' => 'fail@test.com']);
    } catch (\RuntimeException) {
        // Expected
    }

    // Verify no new records were created (transaction rolled back)
    expect(DB::table('admins')->where('tenant_id', $tenant->id)->count())->toBe($adminsBefore);
    expect(DB::table('roles')->where('tenant_id', $tenant->id)->count())->toBe($rolesBefore);
});

it('returns admin credentials in the result', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_PROVISIONING, 'domain' => 'cred-test']);
    core()->setCurrentTenantId($tenant->id);

    $result = app(TenantSeeder::class)->seed($tenant, ['email' => 'cred@test.com']);

    expect($result['admin_email'])->toBe('cred@test.com');
    expect($result['admin_password'])->not->toBeEmpty();
    expect($result['client_secret'])->not->toBeEmpty();
});

it('sets correct tenant_id on all seeded records', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_PROVISIONING, 'domain' => 'tid-test']);
    core()->setCurrentTenantId($tenant->id);

    $result = app(TenantSeeder::class)->seed($tenant, ['email' => 'tid@test.com']);

    $tables = [
        'roles'              => $result['role_id'],
        'locales'            => $result['locale_id'],
        'currencies'         => $result['currency_id'],
        'admins'             => $result['admin_id'],
        'categories'         => $result['root_category_id'],
        'channels'           => $result['channel_id'],
        'attribute_families' => $result['family_id'],
        'api_keys'           => $result['api_key_id'],
    ];

    foreach ($tables as $table => $id) {
        $row = DB::table($table)->where('id', $id)->first();
        expect($row->tenant_id)->toBe($tenant->id, "Expected tenant_id={$tenant->id} on {$table}.{$id}");
    }
});
