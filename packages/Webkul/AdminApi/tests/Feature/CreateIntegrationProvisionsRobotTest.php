<?php

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Webkul\AdminApi\Models\Apikey;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;
use Webkul\User\Tests\Concerns\UserAssertions;

uses(UserAssertions::class);

it('creates an integration bound to a fresh robot without an admin_id input', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->loginAsAdmin();

    $integrationName = 'ZZ-Test-'.uniqid();

    $response = $this->post(route('admin.configuration.integrations.store'), [
        'name'            => $integrationName,
        'permission_type' => 'all',
    ]);

    $key = Apikey::where('name', $integrationName)->firstOrFail();
    $robot = Admin::findOrFail($key->admin_id);

    expect($robot->isApiUser())->toBeTrue()
        ->and($robot->email)->toEndWith('@api.local');

    $response->assertRedirect(route('admin.configuration.integrations.edit', $key->id));
});

it('does not add a role to the roles listing when an integration is created', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->loginAsAdmin();

    $rolesBefore = Role::pluck('id')->all();

    $this->post(route('admin.configuration.integrations.store'), [
        'name'            => 'ZZ-Test-'.uniqid(),
        'permission_type' => 'all',
    ]);

    expect(Role::pluck('id')->all())->toBe($rolesBefore)
        ->and(Role::where('name', 'API')->exists())->toBeFalse();
});

it('renders the integration edit page without an assign user field', function () {
    $this->withoutMiddleware(PreventRequestForgery::class);

    $this->loginAsAdmin();

    $integrationName = 'ZZ-Test-'.uniqid();

    $this->post(route('admin.configuration.integrations.store'), [
        'name'            => $integrationName,
        'permission_type' => 'all',
    ]);

    $key = Apikey::where('name', $integrationName)->firstOrFail();

    $response = $this->get(route('admin.configuration.integrations.edit', $key->id));

    $response->assertOk()
        ->assertDontSee('Assign User')
        ->assertDontSee('name="admin_id"', false);
});
