<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('rejects a scalar executable profile image upload', function () {
    Storage::fake(config('filesystems.default'));

    $admin = $this->loginAsAdmin();

    $this->put(route('admin.account.update'), [
        'name'             => $admin->name,
        'email'            => $admin->email,
        'current_password' => 'password',
        'timezone'         => 'UTC',
        'ui_locale_id'     => $admin->ui_locale_id,
        'image'            => UploadedFile::fake()->create('shell.php', 10, 'application/x-php'),
    ])->assertSessionHasErrors('image');

    expect($admin->fresh()->image)->toBeNull();
});

it('rejects profile image content whose filename has an active-content extension', function () {
    Storage::fake(config('filesystems.default'));

    $admin = $this->loginAsAdmin();

    $this->put(route('admin.account.update'), [
        'name'             => $admin->name,
        'email'            => $admin->email,
        'current_password' => 'password',
        'timezone'         => 'UTC',
        'ui_locale_id'     => $admin->ui_locale_id,
        'image'            => [UploadedFile::fake()->image('payload.html')],
    ])->assertSessionHasErrors('image.0');

    expect($admin->fresh()->image)->toBeNull();
});

it('stores a valid profile image under a generated filename', function () {
    Storage::fake(config('filesystems.default'));

    $admin = $this->loginAsAdmin();

    $this->put(route('admin.account.update'), [
        'name'             => $admin->name,
        'email'            => $admin->email,
        'current_password' => 'password',
        'timezone'         => 'UTC',
        'ui_locale_id'     => $admin->ui_locale_id,
        'image'            => [UploadedFile::fake()->image('profile.png')],
    ])->assertSessionHasNoErrors();

    $path = $admin->fresh()->image;

    expect(basename($path))->toMatch('/^[A-Za-z0-9]{40}\.png$/')
        ->and($path)->not->toContain('profile');

    Storage::assertExists($path);
});
