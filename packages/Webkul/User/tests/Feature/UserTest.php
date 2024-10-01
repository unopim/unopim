<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Webkul\User\Models\Admin;

use function Pest\Laravel\get;

it('should returns the user index page', function () {
    $this->loginAsAdmin();

    get(route('admin.settings.users.index'))
        ->assertStatus(200)
        ->assertSeeText(trans('admin::app.settings.users.index.title'));
});

it('should return the user as json for edit', function () {
    $this->loginAsAdmin();

    $user = Admin::factory()->create();

    $response = get(route('admin.settings.users.edit', ['id' => $user->id]));

    $response->assertStatus(200)
        ->assertJsonFragment($user->toArray());
});

it('should return the users datagrid', function () {
    $this->loginAsAdmin();
    Admin::factory()->create();

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->json('GET', route('admin.settings.users.index'));

    $data = $response->json();

    $this->assertArrayHasKey('records', $data);
    $this->assertArrayHasKey('columns', $data);
    $this->assertNotEmpty($data['records']);

    $this->assertDatabaseHas($this->getFullTableName(Admin::class), [
        'id'    => $data['records'][0]['user_id'],
        'email' => $data['records'][0]['email'],
    ]);
});

it('should store the newly created admin', function () {
    $this->loginAsAdmin();

    $response = $this->post(route('admin.settings.users.store'), [
        'name'                  => 'test',
        'email'                 => 'test@example.com',
        'password'              => 'admin1234',
        'status'                => 1,
        'role_id'               => 1,
        'timezone'              => 'Asia/Kolkata',
        'ui_locale_id'          => 1,
        'password_confirmation' => 'admin1234',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas($this->getFullTableName(Admin::class), [
        'email' => 'test@example.com',
    ]);
});

it('should update the user', function () {
    $this->loginAsAdmin();

    $admin = Admin::factory()->create([
        'email'        => 'update@example.com',
        'password'     => Hash::make('password'),
        'ui_locale_id' => 1,
    ]);

    $response = $this->put(route('admin.settings.users.update'), [
        'id'           => $admin->id,
        'email'        => 'update@example.com',
        'name'         => 'testadmin',
        'status'       => 1,
        'role_id'      => 1,
        'timezone'     => 'Asia/Kolkata',
        'ui_locale_id' => 1,
        'password'     => '',
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas($this->getFullTableName(Admin::class), [
        'name'  => 'testadmin',
        'email' => 'update@example.com',
    ]);
});

it('should update the user with image', function () {
    $this->loginAsAdmin();

    $admin = Admin::factory()->create([
        'email'        => 'update@example.com',
        'password'     => Hash::make('password'),
        'ui_locale_id' => 1,
    ]);

    Storage::fake();

    $response = $this->put(route('admin.settings.users.update'), [
        'id'           => $admin->id,
        'email'        => 'update@example.com',
        'name'         => 'testadmin',
        'status'       => 1,
        'role_id'      => 1,
        'timezone'     => 'Asia/Kolkata',
        'ui_locale_id' => 1,
        'password'     => '',
        'image'        => [
            UploadedFile::fake()->image('avatar.jpg'),
        ],
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas($this->getFullTableName(Admin::class), [
        'name'  => 'testadmin',
        'email' => 'update@example.com',
    ]);
});

it('should delete the user', function () {
    $this->loginAsAdmin();

    $admin = Admin::factory()->create([
        'email'        => 'delete@example.com',
        'password'     => Hash::make('password'),
        'ui_locale_id' => 1,
    ]);

    $response = $this->delete(route('admin.settings.users.delete', ['id' => $admin->id]));
    $response->assertStatus(200);

    $this->assertDatabaseMissing($this->getFullTableName(Admin::class), [
        'id' => $admin->id,
    ]);
});

it('should not store the admin with invalid data', function () {
    $this->loginAsAdmin();

    $response = $this->post(route('admin.settings.users.store'), [
        'name'                  => 'as',
        'email'                 => 'amg',
        'password'              => '123',
        'status'                => 1,
        'role_id'               => 1,
        'timezone'              => 'Asia/Kolkata',
        'ui_locale_id'          => 1,
        'password_confirmation' => '1234',
    ]);

    $response->assertSessionHasErrors([
        'email',
        'password_confirmation',
    ]);

    $response->assertInvalid();
});

it('should not update the admin with invalid data', function () {
    $this->loginAsAdmin();

    $admin = Admin::factory()->create([
        'email'        => 'update@example.com',
        'password'     => Hash::make('password'),
        'ui_locale_id' => 1,
    ]);

    $response = $this->put(route('admin.settings.users.update'), [
        'id'                    => $admin->id,
        'email'                 => 'invalid-email',
        'name'                  => '',
        'status'                => 1,
        'role_id'               => 1,
        'timezone'              => 'Asia/Kolkata',
        'ui_locale_id'          => 1,
        'password_confirmation' => 'aa',
    ]);

    $response->assertSessionHasErrors([
        'email',
        'name',
        'password_confirmation',
    ]);

    $response->assertInvalid();
});

it('should not delete the logged in user', function () {
    $user = $this->loginAsAdmin();

    $response = $this->delete(route('admin.settings.users.delete', ['id' => $user->id]));

    $response->assertStatus(400);
});

it('should returns the users account page', function () {
    $this->loginAsAdmin();

    get(route('admin.account.edit'))
        ->assertStatus(200)
        ->assertSeeText(trans('admin::app.account.edit.title'));
});

it('should give validation errors when updating the user', function () {
    $this->loginAsAdmin();

    $response = $this->put(route('admin.account.update'), []);

    $response->assertStatus(302);
    $response->assertSessionHasErrors([
        'name',
        'current_password',
        'timezone',
        'ui_locale_id',
    ]);

    $response->assertInvalid();
});

it('should not update the user with invalid email', function () {
    $this->loginAsAdmin();

    $response = $this->put(route('admin.account.update'), [
        'name'             => 'John',
        'email'            => 'invalid-email',
        'current_password' => 'password',
        'password'         => '',
        'image'            => '',
        'timezone'         => 'Asia/Kolkata',
        'ui_locale_id'     => 1,
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors([
        'email',
    ]);

    $response->assertInvalid();
});

it('should not update the user without name', function () {
    $this->loginAsAdmin();

    $response = $this->put(route('admin.account.update'), [
        'name'             => '',
        'email'            => 'update@example.com',
        'current_password' => 'password',
        'password'         => '',
        'image'            => '',
        'timezone'         => 'Asia/Kolkata',
        'ui_locale_id'     => 1,
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors([
        'name',
    ]);

    $response->assertInvalid();
});

it('should not update the user without current password', function () {
    $this->loginAsAdmin();

    $response = $this->put(route('admin.account.update'), [
        'name'             => 'John',
        'email'            => 'update@example.com',
        'current_password' => '',
        'password'         => '',
        'image'            => '',
        'timezone'         => 'Asia/Kolkata',
        'ui_locale_id'     => 1,
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors([
        'current_password',
    ]);

    $response->assertInvalid();
});

it('should not update the user without ui-locale', function () {
    $this->loginAsAdmin();

    $response = $this->put(route('admin.account.update'), [
        'name'             => 'John',
        'email'            => 'update@example.com',
        'current_password' => 'password',
        'password'         => '',
        'image'            => '',
        'timezone'         => 'Asia/Kolkata',
        'ui_locale_id'     => '',
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors([
        'ui_locale_id',
    ]);

    $response->assertInvalid();
});

it('should not update the user without timezone', function () {
    $this->loginAsAdmin();

    $response = $this->put(route('admin.account.update'), [
        'name'             => 'John',
        'email'            => 'update@example.com',
        'current_password' => 'password',
        'password'         => '',
        'image'            => '',
        'timezone'         => '',
        'ui_locale_id'     => 1,
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors([
        'timezone',
    ]);

    $response->assertInvalid();
});

it('should update the user with all required data', function () {
    $this->loginAsAdmin();

    $response = $this->put(route('admin.account.update'), [
        'name'                  => 'John doe',
        'email'                 => 'new@example.com',
        'current_password'      => 'password',
        'password'              => 'password2',
        'image'                 => '',
        'timezone'              => 'Asia/Kolkata',
        'ui_locale_id'          => 2,
        'password_confirmation' => 'password2',
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas($this->getFullTableName(Admin::class), [
        'name'  => 'John doe',
        'email' => 'new@example.com',
    ]);
});
