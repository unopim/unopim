<?php

use Illuminate\Support\Facades\DB;
use Webkul\Tenant\Models\Tenant;
use Webkul\User\Models\Admin;

/*
|--------------------------------------------------------------------------
| TenantController Tests
|--------------------------------------------------------------------------
|
| Verifies admin web CRUD for tenant management.
|
*/

beforeEach(function () {
    // Seed the minimum data the admin layout needs to render:
    // a locale, a currency, and a default channel linked to both.
    $localeId = DB::table('locales')->insertGetId([
        'code'   => 'en_US',
        'status' => 1,
    ]);
    $currencyId = DB::table('currencies')->insertGetId([
        'code'   => 'USD',
        'symbol' => '$',
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

    // Reset Core's cached channel so it picks up the freshly-seeded one.
    // Use withoutGlobalScopes because Channel has BelongsToTenant trait.
    $channel = \Webkul\Core\Models\Channel::withoutGlobalScopes()->find($channelId);
    core()->setDefaultChannel($channel);

    // Create a platform operator admin with a role that has 'all' permissions
    $this->platformAdmin = Admin::factory()->create([
        'tenant_id' => null,
        'role_id'   => $this->fixture($this->tenantA, 'role_id'),
    ]);
});

// -- Index ------------------------------------------------------------------

it('renders tenant index page for platform operator', function () {
    $response = $this->actingAs($this->platformAdmin, 'admin')
        ->get(route('admin.settings.tenants.index'));

    $response->assertStatus(200);
    $response->assertViewIs('tenant::settings.tenants.index');
});

it('returns DataGrid JSON for AJAX request', function () {
    $response = $this->actingAs($this->platformAdmin, 'admin')
        ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->get(route('admin.settings.tenants.index'));

    $response->assertStatus(200);
    $response->assertJsonStructure(['records', 'columns']);
});

// -- Create form -----------------------------------------------------------

it('renders tenant create form', function () {
    $response = $this->actingAs($this->platformAdmin, 'admin')
        ->get(route('admin.settings.tenants.create'));

    $response->assertStatus(200);
    $response->assertViewIs('tenant::settings.tenants.create');
});

// -- Show -------------------------------------------------------------------

it('renders tenant show page', function () {
    $response = $this->actingAs($this->platformAdmin, 'admin')
        ->get(route('admin.settings.tenants.show', $this->tenantA->id));

    $response->assertStatus(200);
    $response->assertViewIs('tenant::settings.tenants.show');
});

// -- Edit form --------------------------------------------------------------

it('renders tenant edit form', function () {
    $response = $this->actingAs($this->platformAdmin, 'admin')
        ->get(route('admin.settings.tenants.edit', $this->tenantA->id));

    $response->assertStatus(200);
    $response->assertViewIs('tenant::settings.tenants.edit');
});

// -- Update -----------------------------------------------------------------

it('updates tenant name', function () {
    $response = $this->actingAs($this->platformAdmin, 'admin')
        ->put(route('admin.settings.tenants.update', $this->tenantA->id), [
            'name' => 'Updated Tenant Name',
        ]);

    $response->assertRedirect(route('admin.settings.tenants.edit', $this->tenantA->id));

    $this->tenantA->refresh();
    expect($this->tenantA->name)->toBe('Updated Tenant Name');
});

// -- Suspend ----------------------------------------------------------------

it('suspends an active tenant', function () {
    $response = $this->actingAs($this->platformAdmin, 'admin')
        ->postJson(route('admin.settings.tenants.suspend', $this->tenantA->id));

    $response->assertStatus(200);
    $response->assertJsonFragment(['message' => trans('tenant::app.tenants.suspend-success')]);

    $this->tenantA->refresh();
    expect($this->tenantA->status)->toBe(Tenant::STATUS_SUSPENDED);
});

// -- Activate ---------------------------------------------------------------

it('activates a suspended tenant', function () {
    $this->tenantA->update(['status' => Tenant::STATUS_SUSPENDED]);

    $response = $this->actingAs($this->platformAdmin, 'admin')
        ->postJson(route('admin.settings.tenants.activate', $this->tenantA->id));

    $response->assertStatus(200);
    $response->assertJsonFragment(['message' => trans('tenant::app.tenants.activate-success')]);

    $this->tenantA->refresh();
    expect($this->tenantA->status)->toBe(Tenant::STATUS_ACTIVE);
});

// -- Destroy ----------------------------------------------------------------

it('rejects deletion of provisioning tenant', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_PROVISIONING]);

    $response = $this->actingAs($this->platformAdmin, 'admin')
        ->deleteJson(route('admin.settings.tenants.destroy', $tenant->id));

    $response->assertStatus(400);
    $response->assertJsonFragment(['message' => trans('tenant::app.tenants.cannot-delete-provisioning')]);
});

// -- Access control ---------------------------------------------------------

it('blocks tenant users from admin tenant routes', function () {
    $tenantAdmin = Admin::factory()->create([
        'tenant_id' => $this->tenantA->id,
        'role_id'   => $this->fixture($this->tenantA, 'role_id'),
    ]);

    $response = $this->actingAs($tenantAdmin, 'admin')
        ->get(route('admin.settings.tenants.index'));

    $response->assertStatus(403);
});
