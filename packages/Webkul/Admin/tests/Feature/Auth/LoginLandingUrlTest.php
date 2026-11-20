<?php

use Illuminate\Support\Facades\Hash;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

function createLandingTestAdmin(array $permissions): Admin
{
    $role = Role::factory()->create(['permission_type' => 'custom', 'permissions' => $permissions]);

    return Admin::factory()->create([
        'email'    => 'landing-'.uniqid().'@example.com',
        'password' => Hash::make('password'),
        'status'   => 1,
        'role_id'  => $role->id,
    ]);
}

it('lands a role granted export execute with its parent keys on the exports page', function () {
    $admin = createLandingTestAdmin(['data_transfer', 'data_transfer.export', 'data_transfer.export.execute']);

    $this->post(route('admin.session.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.settings.data_transfer.exports.index'));

    $this->assertAuthenticatedAs($admin, 'admin');

    $this->get(route('admin.settings.data_transfer.exports.index'))->assertOk();
});

it('skips acl routes that need parameters and fails closed when nothing else is reachable', function () {
    $admin = createLandingTestAdmin(['data_transfer.export.execute']);

    $this->post(route('admin.session.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ])
        ->assertRedirect(route('admin.session.create'))
        ->assertSessionHas('error', trans('admin::app.errors.403.message'));

    $this->assertGuest('admin');
});

it('returns the login page as the ajax redirect url when only parameterised routes are permitted', function () {
    $admin = createLandingTestAdmin(['data_transfer.export.execute']);

    $this->postJson(route('admin.session.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJson(['redirect_url' => route('admin.session.create')]);

    $this->assertGuest('admin');
});

it('lands a role holding a parameterless acl route on that route', function () {
    $admin = createLandingTestAdmin(['data_transfer.export']);

    $this->post(route('admin.session.store'), [
        'email'    => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.settings.data_transfer.exports.index'));

    $this->assertAuthenticatedAs($admin, 'admin');
});
