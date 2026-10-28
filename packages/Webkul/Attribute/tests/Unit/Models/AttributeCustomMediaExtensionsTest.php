<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Webkul\Attribute\Models\Attribute;

function customExtensionZipUpload(string $name = 'bundle.zip'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'zip');

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('readme.txt', 'packaged asset');
    $zip->close();

    return new UploadedFile($path, $name, 'application/zip', null, true);
}

function customExtensionPdfUpload(string $name = 'spec.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        $name,
        "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n",
    );
}

function passesMediaRules(Attribute $attribute, string $method, UploadedFile $file): bool
{
    $value = $attribute->type === 'gallery' ? [$file] : $file;

    return Validator::make(['media' => $value], ['media' => $attribute->{$method}()])->passes();
}

it('accepts a file whose extension is only in the attribute allowed extensions', function (string $method) {
    $attribute = Attribute::factory()->make(['type' => 'file', 'allowed_extensions' => ['zip', 'rar', '7z', 'psd', 'pdf']]);

    expect(passesMediaRules($attribute, $method, customExtensionZipUpload()))->toBeTrue()
        ->and(passesMediaRules($attribute, $method, customExtensionPdfUpload()))->toBeTrue();
})->with(['getValidationRules', 'getValidationsOnlyMedia']);

it('rejects a default extension that the attribute allowed extensions leave out', function (string $method) {
    $attribute = Attribute::factory()->make(['type' => 'file', 'allowed_extensions' => ['zip']]);

    expect(passesMediaRules($attribute, $method, customExtensionPdfUpload()))->toBeFalse();
})->with(['getValidationRules', 'getValidationsOnlyMedia']);

it('rejects spoofed content renamed to a custom allowed extension', function (string $method) {
    $attribute = Attribute::factory()->make(['type' => 'file', 'allowed_extensions' => ['zip', 'pdf']]);

    $path = tempnam(sys_get_temp_dir(), 'spoof');
    file_put_contents($path, '<html><script>alert(1)</script></html>');

    $spoofed = new UploadedFile($path, 'bundle.zip', 'application/zip', null, true);

    expect(passesMediaRules($attribute, $method, $spoofed))->toBeFalse();
})->with(['getValidationRules', 'getValidationsOnlyMedia']);

it('keeps the default file allowlist when no allowed extensions are configured', function (string $method, mixed $allowedExtensions) {
    $attribute = Attribute::factory()->make(['type' => 'file', 'allowed_extensions' => $allowedExtensions]);

    expect(passesMediaRules($attribute, $method, customExtensionPdfUpload()))->toBeTrue()
        ->and(passesMediaRules($attribute, $method, customExtensionZipUpload()))->toBeFalse();
})->with(['getValidationRules', 'getValidationsOnlyMedia'])->with([[null], [[]]]);

it('accepts a custom non-image extension on a gallery attribute', function (string $method) {
    $attribute = Attribute::factory()->make(['type' => 'gallery', 'allowed_extensions' => ['jpg', 'pdf']]);

    expect(passesMediaRules($attribute, $method, customExtensionPdfUpload()))->toBeTrue()
        ->and(passesMediaRules($attribute, $method, UploadedFile::fake()->image('photo.jpg')))->toBeTrue()
        ->and(passesMediaRules($attribute, $method, customExtensionZipUpload()))->toBeFalse();
})->with(['getValidationRules', 'getValidationsOnlyMedia']);

it('never allows an executable or markup extension even when configured', function (string $method) {
    $attribute = Attribute::factory()->make(['type' => 'file', 'allowed_extensions' => ['zip', 'html', 'php', 'phtml']]);

    expect($attribute->resolvedAllowedExtensions())->toBe(['zip'])
        ->and(passesMediaRules($attribute, $method, UploadedFile::fake()->createWithContent('page.html', '<html><body>x</body></html>')))->toBeFalse()
        ->and(passesMediaRules($attribute, $method, UploadedFile::fake()->createWithContent('shell.php', '<?php echo 1;')))->toBeFalse()
        ->and(passesMediaRules($attribute, $method, customExtensionZipUpload()))->toBeTrue();
})->with(['getValidationRules', 'getValidationsOnlyMedia']);

it('normalises configured extensions to lowercase without a leading dot', function () {
    $attribute = Attribute::factory()->make(['type' => 'file', 'allowed_extensions' => ['.ZIP', ' Pdf ', '']]);

    expect($attribute->resolvedAllowedExtensions())->toBe(['zip', 'pdf']);
});

it('uses the per-attribute max file size in the client-side size rule', function (string $type) {
    $attribute = Attribute::factory()->make(['type' => $type, 'max_file_size' => 25]);

    expect($attribute->getValidationsField())->toContain('size:25600');
})->with(['file', 'image']);

it('falls back to the global upload size in the client-side size rule', function (string $type) {
    $attribute = Attribute::factory()->make(['type' => $type, 'max_file_size' => null]);

    expect($attribute->getValidationsField())->toContain('size:2048');
})->with(['file', 'image']);
