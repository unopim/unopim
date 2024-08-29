<?php

use Webkul\AdminApi\Models\Apikey;

it('should return the intergration datagrid page', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.configuration.integrations.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.configuration.integrations.index.title'));
});

it('should return the integration create page', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.configuration.integrations.create'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.configuration.integrations.create.title'))
        ->assertSeeText(trans('admin::app.configuration.integrations.create.access-control'))
        ->assertSeeText(trans('admin::app.configuration.integrations.create.general'));
});

it('should return required validation errors for name, user and permission type when creating integration', function () {
    $this->loginAsAdmin();

    $response = $this->post(route('admin.configuration.integrations.store'), []);

    $response->assertInvalid([
        'name',
        'admin_id',
        'permission_type',
    ]);
});

it('should create the integration with permission all sucessfully', function () {
    $user = $this->loginAsAdmin();

    $response = $this->post(route('admin.configuration.integrations.store'), [
        'name'            => 'Test Integration',
        'admin_id'        => $user->id,
        'permission_type' => 'all',
    ]);

    $apiKey = $this->assertDatabaseHas($this->getFullTableName(Apikey::class), [
        'name'            => 'Test Integration',
        'admin_id'        => $user->id,
        'permission_type' => 'all',
    ]);

    $response->assertSessionHas('success', trans('admin::app.configuration.integrations.create-success'));
});

it('should create the integration with permission custom sucessfully', function () {
    $userId = $this->loginAsAdmin()->id;

    $permissions = ['api.catalog', 'api.catalog.products', 'api.catalog.products.create', 'api.catalog.products.edit'];

    $response = $this->post(route('admin.configuration.integrations.store'), [
        'name'            => 'Test Custom Integration',
        'admin_id'        => $userId,
        'permission_type' => 'custom',
        'permissions'     => $permissions,
    ]);

    $response->assertSessionHas('success', trans('admin::app.configuration.integrations.create-success'));

    $apiKey = ApiKey::where('name', 'Test Custom Integration')->where('admin_id', $userId)->where('permission_type', 'custom')->first();

    $this->assertTrue($apiKey instanceof ApiKey, 'Api Key not Found');

    $this->assertEquals($permissions, $apiKey->permissions);
});

it('should return validation error when integration already exists for a user', function () {
    $userId = $this->loginAsAdmin()->id;

    $apiKey = ApiKey::factory()->create(['permission_type' => 'all', 'admin_id' => $userId]);

    $this->post(route('admin.configuration.integrations.store'), [
        'name'            => 'Test Integration',
        'admin_id'        => $userId,
        'permission_type' => 'all',
    ])->assertInvalid(['admin_id']);
});

it('should return the integration edit page', function () {
    $userId = $this->loginAsAdmin()->id;

    $apiKey = ApiKey::factory()->create(['permission_type' => 'all', 'admin_id' => $userId]);

    $this->get(route('admin.configuration.integrations.edit', $apiKey->id))
        ->assertOk()
        ->assertSeeText(trans('admin::app.configuration.integrations.edit.title'));

});

it('should return validation messages for name and premission_type on update integration', function () {
    $userId = $this->loginAsAdmin()->id;

    $apiKey = ApiKey::factory()->create(['permission_type' => 'all', 'admin_id' => $userId]);

    $this->put(route('admin.configuration.integrations.update', $apiKey->id), [
        'name'            => '',
        'permission_type' => '',
    ])
        ->assertInvalid(['name', 'permission_type']);
});

it('should update the integration with permission type all sucessfully', function () {
    $userId = $this->loginAsAdmin()->id;

    $apiKey = ApiKey::factory()->create(['permission_type' => 'all', 'admin_id' => $userId]);

    $apiKeyId = $apiKey->id;

    $response = $this->put(route('admin.configuration.integrations.update', $apiKeyId), [
        'name'            => 'Test Integration',
        'admin_id'        => $userId,
        'permission_type' => 'custom',
    ]);

    $response->assertSessionHas('success', trans('admin::app.configuration.integrations.update-success'))
        ->assertRedirect(route('admin.configuration.integrations.edit', $apiKeyId));

    $this->assertDatabaseHas($this->getFullTableName(ApiKey::class), [
        'name'            => 'Test Integration',
        'admin_id'        => $userId,
        'permission_type' => 'custom',
    ]);
});

it('should update the integration with permission type custom sucessfully', function () {
    $userId = $this->loginAsAdmin()->id;

    $permissions = ['api.catalog', 'api.catalog.products', 'api.catalog.products.create'];

    $apiKey = ApiKey::factory()->create(['permission_type' => 'custom', 'permissions' => $permissions, 'admin_id' => $userId]);

    $permissions[] = 'api.catalog.products.edit';

    $response = $this->put(route('admin.configuration.integrations.update', $apiKey->id), [
        'name'            => 'Test Custom Integration',
        'admin_id'        => $userId,
        'permission_type' => 'custom',
        'permissions'     => $permissions,
    ]);

    $response->assertSessionHas('success', trans('admin::app.configuration.integrations.update-success'));

    $apiKey = ApiKey::where('name', 'Test Custom Integration')->where('admin_id', $userId)->where('permission_type', 'custom')->first();

    $this->assertTrue($apiKey instanceof ApiKey, 'Api Key not Found');

    $this->assertEquals($permissions, $apiKey->permissions);
});

it('should generate secret key and client id for a integration', function () {
    $userId = $this->loginAsAdmin()->id;

    $apiKey = ApiKey::factory()->create(['name' => 'Test', 'permission_type' => 'all', 'admin_id' => $userId]);

    $response = $this->post(route('admin.configuration.integrations.generate_key'), [
        'name'     => $apiKey->name,
        'admin_id' => $apiKey->admin_id,
        'apiId'    => $apiKey->id,
    ])->assertJsonStructure([
        'client_id',
        'secret_key',
        'oauth_client_id',
    ]);

    $data = $response->json();

    $this->assertDatabaseHas('oauth_clients', [
        'id'     => $data['oauth_client_id'],
        'secret' => $data['secret_key'],
    ]);
});

it('should regenerate secret key for a integration', function () {
    $userId = $this->loginAsAdmin()->id;

    $apiKey = ApiKey::factory()->create(['name' => 'Test', 'permission_type' => 'all', 'admin_id' => $userId]);

    $response = $this->post(route('admin.configuration.integrations.generate_key'), [
        'name'     => $apiKey->name,
        'admin_id' => $apiKey->admin_id,
        'apiId'    => $apiKey->id,
    ]);

    $oauthClientId = $response->json()['oauth_client_id'];

    $response = $this->post(route('admin.configuration.integrations.re_generate_secret_key'), [
        'oauth_client_id' => $oauthClientId,
    ]);

    $data = $response->json();

    $this->assertDatabaseHas('oauth_clients', [
        'id'     => $oauthClientId,
        'secret' => $data['secret_key'],
    ]);
});

it('should revoke the integration succesfully on delete', function () {
    $userId = $this->loginAsAdmin()->id;

    $apiKey = ApiKey::factory()->create(['permission_type' => 'all', 'admin_id' => $userId]);

    $this->delete(route('admin.configuration.integrations.delete', $apiKey->id))
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.configuration.integrations.delete-success')]);

    $this->assertDatabaseHas($this->getFullTableName(ApiKey::class), [
        'id'      => $apiKey->id,
        'revoked' => 1,
    ]);
});
