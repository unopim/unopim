<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\get;
use function Pest\Laravel\put;

it('shows appearance settings page', function () {
    $this->loginAsAdmin();

    get(route('admin.settings.appearance.index'))
        ->assertStatus(200)
        ->assertSeeText(trans('admin::app.settings.appearance.title'));
});

it('updates logo and favicon from appearance settings', function () {
    $this->loginAsAdmin();

    Storage::fake();

    $response = put(route('admin.settings.appearance.update'), [
        'logo_image' => UploadedFile::fake()->image('logo.png', 192, 50),
        'favicon'    => UploadedFile::fake()->image('favicon.png', 16, 16),
    ]);

    $response->assertRedirect(route('admin.settings.system.index'));
    $response->assertSessionHas('success');
});
