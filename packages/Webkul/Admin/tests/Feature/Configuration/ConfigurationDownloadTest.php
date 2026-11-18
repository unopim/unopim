<?php

use Illuminate\Support\Facades\Storage;
use Webkul\Core\Models\CoreConfig;

beforeEach(function (): void {
    $this->loginAsAdmin();

    Storage::fake();
});

it('returns 404 instead of 500 for a history suffix on system settings pages', function (string $section): void {
    $this->get(route('admin.configuration.download', ['slug' => 'system', 'slug2' => $section, 'path' => 'history']))
        ->assertNotFound();
})->with(['system.appearance', 'system.email', 'system.measurement']);

it('returns 404 when the stored configuration file is missing on disk', function (): void {
    CoreConfig::query()->create(['code' => 'general.design.admin_logo', 'value' => 'configuration/missing-logo.png']);

    $this->get(route('admin.configuration.download', ['slug' => 'general', 'slug2' => 'design', 'path' => 'missing-logo.png']))
        ->assertNotFound();
});

it('downloads a stored configuration file', function (): void {
    Storage::put('configuration/logo.png', 'fake-image-bytes');

    CoreConfig::query()->create(['code' => 'general.design.admin_logo', 'value' => 'configuration/logo.png']);

    $response = $this->get(route('admin.configuration.download', ['slug' => 'general', 'slug2' => 'design', 'path' => 'logo.png']))
        ->assertOk();

    expect($response->headers->get('Content-Disposition'))->toContain('attachment')
        ->and($response->streamedContent())->toBe('fake-image-bytes');
});
