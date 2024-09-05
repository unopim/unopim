<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Webkul\User\Models\Admin;

use function Pest\Laravel\get;

it('should returns the user index page', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.settings.users.index'));

    $response->assertStatus(200);
    $response->assertOk();
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

    $this->assertDatabaseHas('admins', [
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

    $this->assertDatabaseHas('admins', [
        'name' => 'testadmin',
        'email'=> 'update@example.com',
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
        'id'                    => $admin->id,
        'email'                 => 'update@example.com',
        'name'                  => 'testadmin',
        'status'                => 1,
        'role_id'               => 1,
        'timezone'              => 'Asia/Kolkata',
        'ui_locale_id'          => 1,
        'password'              => '',
        'image'                 => [
            UploadedFile::fake()->image('avatar.jpg'),
        ],
    ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('admins', [
        'name' => 'testadmin',
        'email'=> 'update@example.com',
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

    $this->assertDatabaseMissing('admins', [
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
        'email'                 => 'invalid-email', // Invalid email
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
