<?php

use Illuminate\Support\Facades\DB;
use Webkul\Notification\Models\Notification;
use Webkul\Webhook\Models\WebhookSetting;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\Tenant\Models\TenantOAuthClient;

/*
|--------------------------------------------------------------------------
| Wave 3 Tenant Isolation Tests
|--------------------------------------------------------------------------
|
| Proves that Tenant A cannot see Tenant B's data for Wave 3 operational
| models: Notification, WebhookSetting, JobInstances, AttributeOption,
| CategoryField, TenantOAuthClient (Passport), and more.
|
*/

// -- helpers ---------------------------------------------------------------

function seedAttribute(int $tenantId): int
{
    return DB::table('attributes')->insertGetId([
        'code'           => 'attr-t'.$tenantId.'-'.uniqid(),
        'type'           => 'text',
        'validation'     => null,
        'position'       => 1,
        'is_required'    => 0,
        'is_unique'      => 0,
        'value_per_locale' => 0,
        'value_per_channel' => 0,
        'tenant_id'      => $tenantId,
        'created_at'     => now(),
        'updated_at'     => now(),
    ]);
}

// -- tests ----------------------------------------------------------------

it('isolates Notification records between tenants', function () {
    $tA = $this->tenantA->id;
    $tB = $this->tenantB->id;

    DB::table('notifications')->insert([
        ['tenant_id' => $tA, 'type' => 'info', 'created_at' => now(), 'updated_at' => now()],
        ['tenant_id' => $tB, 'type' => 'warning', 'created_at' => now(), 'updated_at' => now()],
    ]);

    // Tenant A context
    core()->setCurrentTenantId($tA);
    $rows = Notification::all();
    expect($rows)->toHaveCount(1);
    expect($rows->first()->type)->toBe('info');

    // Tenant B context
    core()->setCurrentTenantId($tB);
    $rows = Notification::all();
    expect($rows)->toHaveCount(1);
    expect($rows->first()->type)->toBe('warning');
});

it('isolates WebhookSetting records between tenants', function () {
    $tA = $this->tenantA->id;
    $tB = $this->tenantB->id;

    DB::table('webhook_settings')->insert([
        ['tenant_id' => $tA, 'field' => 'url_a', 'created_at' => now(), 'updated_at' => now()],
        ['tenant_id' => $tB, 'field' => 'url_b', 'created_at' => now(), 'updated_at' => now()],
    ]);

    core()->setCurrentTenantId($tA);
    expect(WebhookSetting::all())->toHaveCount(1);
    expect(WebhookSetting::first()->field)->toBe('url_a');

    core()->setCurrentTenantId($tB);
    expect(WebhookSetting::all())->toHaveCount(1);
    expect(WebhookSetting::first()->field)->toBe('url_b');
});

it('isolates JobInstances records between tenants', function () {
    $tA = $this->tenantA->id;
    $tB = $this->tenantB->id;

    DB::table('job_instances')->insert([
        [
            'tenant_id' => $tA, 'code' => 'import-a', 'entity_type' => 'products',
            'type' => 'import', 'action' => 'append', 'validation_strategy' => 'skip-errors',
            'allowed_errors' => 10, 'created_at' => now(), 'updated_at' => now(),
        ],
        [
            'tenant_id' => $tB, 'code' => 'import-b', 'entity_type' => 'categories',
            'type' => 'import', 'action' => 'append', 'validation_strategy' => 'skip-errors',
            'allowed_errors' => 5, 'created_at' => now(), 'updated_at' => now(),
        ],
    ]);

    core()->setCurrentTenantId($tA);
    expect(JobInstances::all())->toHaveCount(1);
    expect(JobInstances::first()->code)->toBe('import-a');

    core()->setCurrentTenantId($tB);
    expect(JobInstances::all())->toHaveCount(1);
    expect(JobInstances::first()->code)->toBe('import-b');
});

it('isolates AttributeOption records between tenants', function () {
    $tA = $this->tenantA->id;
    $tB = $this->tenantB->id;

    $attrA = seedAttribute($tA);
    $attrB = seedAttribute($tB);

    DB::table('attribute_options')->insert([
        ['tenant_id' => $tA, 'attribute_id' => $attrA, 'code' => 'opt-red'],
        ['tenant_id' => $tB, 'attribute_id' => $attrB, 'code' => 'opt-blue'],
    ]);

    core()->setCurrentTenantId($tA);
    $opts = \Webkul\Attribute\Models\AttributeOption::all();
    expect($opts)->toHaveCount(1);
    expect($opts->first()->code)->toBe('opt-red');

    core()->setCurrentTenantId($tB);
    $opts = \Webkul\Attribute\Models\AttributeOption::all();
    expect($opts)->toHaveCount(1);
    expect($opts->first()->code)->toBe('opt-blue');
});

it('isolates CategoryField records between tenants', function () {
    $tA = $this->tenantA->id;
    $tB = $this->tenantB->id;

    DB::table('category_fields')->insert([
        [
            'tenant_id' => $tA, 'code' => 'field-a', 'type' => 'text',
            'is_required' => 0, 'is_unique' => 0, 'status' => 1,
            'section' => 'left', 'value_per_locale' => 0, 'enable_wysiwyg' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ],
        [
            'tenant_id' => $tB, 'code' => 'field-b', 'type' => 'textarea',
            'is_required' => 0, 'is_unique' => 0, 'status' => 1,
            'section' => 'left', 'value_per_locale' => 0, 'enable_wysiwyg' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ],
    ]);

    core()->setCurrentTenantId($tA);
    $fields = \Webkul\Category\Models\CategoryField::all();
    expect($fields)->toHaveCount(1);
    expect($fields->first()->code)->toBe('field-a');

    core()->setCurrentTenantId($tB);
    $fields = \Webkul\Category\Models\CategoryField::all();
    expect($fields)->toHaveCount(1);
    expect($fields->first()->code)->toBe('field-b');
});

it('isolates TenantOAuthClient (Passport) records between tenants', function () {
    $tA = $this->tenantA->id;
    $tB = $this->tenantB->id;

    DB::table('oauth_clients')->insert([
        'tenant_id' => $tA,
        'name' => 'Client A', 'secret' => 'secret-a', 'redirect' => 'http://localhost',
        'personal_access_client' => false, 'password_client' => true, 'revoked' => false,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('oauth_clients')->insert([
        'tenant_id' => $tB,
        'name' => 'Client B', 'secret' => 'secret-b', 'redirect' => 'http://localhost',
        'personal_access_client' => false, 'password_client' => true, 'revoked' => false,
        'created_at' => now(), 'updated_at' => now(),
    ]);

    core()->setCurrentTenantId($tA);
    $clients = TenantOAuthClient::all();
    expect($clients)->toHaveCount(1);
    expect($clients->first()->name)->toBe('Client A');

    core()->setCurrentTenantId($tB);
    $clients = TenantOAuthClient::all();
    expect($clients)->toHaveCount(1);
    expect($clients->first()->name)->toBe('Client B');
});

it('auto-sets tenant_id on Notification creation from current context', function () {
    core()->setCurrentTenantId($this->tenantA->id);

    $notification = Notification::create([
        'type' => 'success',
    ]);

    expect($notification->tenant_id)->toBe($this->tenantA->id);
});
