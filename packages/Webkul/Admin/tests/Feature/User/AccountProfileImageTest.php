<?php

use Illuminate\Http\UploadedFile;
use Webkul\User\Models\Admin;

function aiAccountPayload(array $overrides = []): array
{
    $user = auth()->guard('admin')->user();

    return [
        '_method'      => 'PUT',
        'name'         => $user->name,
        'email'        => $user->email,
        'timezone'     => 'UTC',
        'ui_locale_id' => $user->ui_locale_id,
        ...$overrides,
    ];
}

function aiPngUpload(string $name = 'temp.png'): UploadedFile
{
    return UploadedFile::fake()->image($name, 1024, 1024);
}

it('persists a profile image uploaded as an array field', function () {
    $this->loginAsAdmin();

    $user = auth()->guard('admin')->user();

    $this->post(route('admin.account.update'), aiAccountPayload(['image' => [aiPngUpload()]]))
        ->assertSuccessful();

    expect(Admin::find($user->id)->image)->not->toBeNull();
});

it('persists a profile image when gravatar is also enabled', function () {
    $this->loginAsAdmin();

    $user = auth()->guard('admin')->user();

    $this->post(route('admin.account.update'), aiAccountPayload([
        'use_gravatar' => 1,
        'image'        => [aiPngUpload()],
    ]))->assertSuccessful();

    expect(Admin::find($user->id)->image)->not->toBeNull();
});

it('keeps the stored image when the editor re-sends its path', function () {
    $this->loginAsAdmin();

    $user = auth()->guard('admin')->user();

    $this->post(route('admin.account.update'), aiAccountPayload(['image' => [aiPngUpload()]]))
        ->assertSuccessful();

    $stored = Admin::find($user->id)->image;

    expect($stored)->not->toBeNull();

    $this->post(route('admin.account.update'), aiAccountPayload(['image' => $stored]))
        ->assertSuccessful();

    expect(Admin::find($user->id)->image)->toBe($stored);
});

it('serves a stored profile image from a path under the public disk', function () {
    $this->loginAsAdmin();

    $user = auth()->guard('admin')->user();

    $this->post(route('admin.account.update'), aiAccountPayload(['image' => [aiPngUpload()]]))
        ->assertSuccessful();

    $admin = Admin::find($user->id);

    expect($admin->image)->toStartWith('admins/'.$admin->id.'/')
        ->and($admin->image_url)->toContain('/storage/'.$admin->image);
});
