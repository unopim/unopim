<?php

use Illuminate\Http\UploadedFile;
use Webkul\Admin\Http\Controllers\Settings\DataTransfer\ImportController;

function uploadLimitsJpeg(): string
{
    return base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/wAALCAABAAEBAREA/8QAFAABAAAAAAAAAAAAAAAAAAAACf/EABQQAQAAAAAAAAAAAAAAAAAAAAD/2gAIAQEAAD8AKp//2Q==');
}

function uploadLimitsZip(int $count, ?string $payload = null): string
{
    $path = tempnam(sys_get_temp_dir(), 'imgzip').'.zip';

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE);

    for ($i = 0; $i < $count; $i++) {
        $zip->addFromString("sku-$i.jpg", ($payload ?? uploadLimitsJpeg()).random_bytes(512));
    }

    $zip->close();

    return $path;
}

function uploadLimitsPost($test, string $path, string $name = 'images.zip')
{
    return $test->post(route('admin.settings.data_transfer.imports.upload_images_zip'), [
        'images_zip' => new UploadedFile($path, $name, 'application/zip', null, true),
    ]);
}

beforeEach(fn () => $this->loginAsAdmin());

it('accepts a catalogue sized archive that the old thousand entry cap rejected', function () {
    $path = uploadLimitsZip(1200);

    uploadLimitsPost($this, $path)
        ->assertOk()
        ->assertJsonPath('files_count', 1200);

    @unlink($path);
});

it('names the entry cap instead of calling a valid archive invalid', function () {
    config(['image_import.max_entries' => 5]);

    $path = uploadLimitsZip(6);

    $response = uploadLimitsPost($this, $path)->assertUnprocessable();

    expect($response->json('message'))
        ->toContain('6')
        ->toContain('5')
        ->not->toContain('not a valid ZIP archive');

    @unlink($path);
});

it('names the extracted size limit separately', function () {
    config(['image_import.max_total_size' => 100]);

    $path = uploadLimitsZip(3);

    $response = uploadLimitsPost($this, $path)->assertUnprocessable();

    expect($response->json('message'))->toContain('add up to more than');

    @unlink($path);
});

it('still rejects an archive that expands far beyond its compressed size', function () {
    config(['image_import.max_compression_ratio' => 2]);

    $path = uploadLimitsZip(1, str_repeat('A', 200000));

    $response = uploadLimitsPost($this, $path)->assertUnprocessable();

    expect($response->json('message'))->toContain('expands far beyond');

    @unlink($path);
});

it('caps the upload rule at what the server actually accepts', function () {
    $controller = new ReflectionClass(ImportController::class);

    $method = $controller->getMethod('maxUploadKilobytes');
    $method->setAccessible(true);

    $instance = app(ImportController::class);

    $phpLimitKb = intdiv(min(
        (int) (ini_get('upload_max_filesize')[0] ?? 0) * 1024 * 1024,
        (int) (ini_get('post_max_size')[0] ?? 0) * 1024 * 1024
    ), 1024);

    expect($method->invoke($instance))->toBeLessThanOrEqual(102400)
        ->and($method->invoke($instance))->toBeGreaterThan(0)
        ->and($phpLimitKb)->toBeGreaterThan(0);
});
