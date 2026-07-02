<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function makeImageZip(array $entries): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'imgzip').'.zip';

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach ($entries as $name => $content) {
        $zip->addFromString($name, $content);
    }

    $zip->close();

    return new UploadedFile($path, 'payload.zip', 'application/zip', null, true);
}

$onePixelPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

it('does not extract script/executable files from an imported image zip', function () use ($onePixelPng) {
    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.imports.edit']);

    $zip = makeImageZip([
        'evil.svg'   => '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)"><script>alert(1)</script></svg>',
        'evil.html'  => '<html><body><script>alert(document.cookie)</script></body></html>',
        'evil.php'   => '<?php echo "pwned"; ?>',
        'evil.phtml' => '<?php echo "pwned"; ?>',
        'good.png'   => $onePixelPng,
    ]);

    $path = $this->post(route('admin.settings.data_transfer.imports.upload_images_zip'), [
        'images_zip' => $zip,
    ])->json('path');

    expect($path)->not->toBeNull();

    foreach (['evil.svg', 'evil.html', 'evil.php', 'evil.phtml'] as $dangerous) {
        expect(Storage::disk('public')->exists($path.'/'.$dangerous))->toBeFalse();
    }

    Storage::disk('public')->deleteDirectory($path);
});

it('still extracts legitimate image files from an imported zip', function () use ($onePixelPng) {
    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.imports.edit']);

    $zip = makeImageZip([
        'product.png' => $onePixelPng,
        'evil.php'    => '<?php echo "pwned"; ?>',
    ]);

    $path = $this->post(route('admin.settings.data_transfer.imports.upload_images_zip'), [
        'images_zip' => $zip,
    ])->json('path');

    expect(Storage::disk('public')->exists($path.'/product.png'))->toBeTrue()
        ->and(Storage::disk('public')->exists($path.'/evil.php'))->toBeFalse();

    Storage::disk('public')->deleteDirectory($path);
});

it('rejects oversized zip entries to prevent memory-exhaustion DoS', function () use ($onePixelPng) {
    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.imports.edit']);

    config(['image_import.max_entry_size' => 1024 * 1024]);

    $zip = makeImageZip([
        'huge.png' => $onePixelPng.str_repeat("\0", 2 * 1024 * 1024),
        'good.png' => $onePixelPng,
    ]);

    $response = $this->post(route('admin.settings.data_transfer.imports.upload_images_zip'), [
        'images_zip' => $zip,
    ]);

    $path = $response->json('path');

    expect(Storage::disk('public')->exists($path.'/huge.png'))->toBeFalse()
        ->and(Storage::disk('public')->exists($path.'/good.png'))->toBeTrue()
        ->and($response->json('files_count'))->toBe(1);

    Storage::disk('public')->deleteDirectory($path);
});

it('preserves subfolders so same-named images do not collide', function () use ($onePixelPng) {
    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.imports.edit']);

    $zip = makeImageZip([
        'a/logo.png' => $onePixelPng,
        'b/logo.png' => $onePixelPng,
    ]);

    $response = $this->post(route('admin.settings.data_transfer.imports.upload_images_zip'), [
        'images_zip' => $zip,
    ]);

    $path = $response->json('path');

    expect(Storage::disk('public')->exists($path.'/a/logo.png'))->toBeTrue()
        ->and(Storage::disk('public')->exists($path.'/b/logo.png'))->toBeTrue()
        ->and($response->json('files_count'))->toBe(2);

    Storage::disk('public')->deleteDirectory($path);
});
