<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Webkul\Core\Rules\FileOrImageValidValue;

it('enforces gallery file-count limits', function () {
    $files = [
        UploadedFile::fake()->image('one.png'),
        UploadedFile::fake()->image('two.png'),
        UploadedFile::fake()->image('three.png'),
    ];

    $validator = Validator::make(['gallery' => $files], [
        'gallery' => [new FileOrImageValidValue(isImage: true, isMultiple: true, maxFiles: 2)],
    ]);

    expect($validator->fails())->toBeTrue();
});

it('allows the multipart array shape for one image but rejects multiple images', function () {
    $rule = new FileOrImageValidValue(isImage: true);

    $singleImage = Validator::make([
        'image' => [UploadedFile::fake()->image('one.png')],
    ], [
        'image' => [$rule],
    ]);

    $multipleImages = Validator::make([
        'image' => [
            UploadedFile::fake()->image('one.png'),
            UploadedFile::fake()->image('two.png'),
        ],
    ], [
        'image' => [$rule],
    ]);

    expect($singleImage->passes())->toBeTrue()
        ->and($multipleImages->fails())->toBeTrue();
});

it('enforces per-file media size limits', function () {
    $file = UploadedFile::fake()->create('large.png', 2049, 'image/png');

    $validator = Validator::make(['image' => $file], [
        'image' => [new FileOrImageValidValue(isImage: true, maxKilobytes: 2048)],
    ]);

    expect($validator->fails())->toBeTrue();
});

it('enforces aggregate gallery size limits', function () {
    $files = [
        UploadedFile::fake()->image('one.png')->size(700),
        UploadedFile::fake()->image('two.png')->size(700),
    ];

    $validator = Validator::make(['gallery' => $files], [
        'gallery' => [new FileOrImageValidValue(
            isImage: true,
            isMultiple: true,
            maxTotalKilobytes: 1024,
        )],
    ]);

    expect($validator->fails())->toBeTrue();
});

it('accepts only existing media paths under the expected record prefix', function () {
    Storage::fake(config('filesystems.default'));

    $image = UploadedFile::fake()->image('photo.png');
    Storage::put('product/25/photo/photo.png', file_get_contents($image->getRealPath()));

    $rule = new FileOrImageValidValue(
        isImage: true,
        allowedPathPrefixes: ['product/25/photo'],
    );

    expect(Validator::make(['image' => 'product/25/photo/photo.png'], ['image' => [$rule]])->passes())->toBeTrue()
        ->and(Validator::make(['image' => 'product/26/photo/photo.png'], ['image' => [$rule]])->fails())->toBeTrue();
});

it('rejects existing media paths with a mismatched stored type', function () {
    Storage::fake(config('filesystems.default'));
    Storage::put('product/25/photo/fake.png', '<html>not an image</html>');

    $validator = Validator::make(['image' => 'product/25/photo/fake.png'], [
        'image' => [new FileOrImageValidValue(
            isImage: true,
            allowedPathPrefixes: ['product/25/photo'],
        )],
    ]);

    expect($validator->fails())->toBeTrue();
});

it('rejects a PDF upload carrying an embedded JavaScript action', function () {
    $payload = "%PDF-1.7\n1 0 obj<</Type/Catalog/OpenAction<</S/JavaScript/JS(app.alert\\(1\\))>>>>endobj\n%%EOF";
    $file = UploadedFile::fake()->createWithContent('payload.pdf', $payload);

    $validator = Validator::make(['file' => $file], [
        'file' => [new FileOrImageValidValue],
    ]);

    expect($validator->fails())->toBeTrue();
});

it('accepts a clean PDF upload with no active content', function () {
    $payload = "%PDF-1.7\n1 0 obj<</Type/Catalog>>endobj\n%%EOF";
    $file = UploadedFile::fake()->createWithContent('spec-sheet.pdf', $payload);

    $validator = Validator::make(['file' => $file], [
        'file' => [new FileOrImageValidValue],
    ]);

    expect($validator->passes())->toBeTrue();
});

it('rejects an SVG upload carrying an embedded script tag', function () {
    $payload = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(document.domain)</script></svg>';
    $file = UploadedFile::fake()->createWithContent('payload.svg', $payload);

    $validator = Validator::make(['image' => $file], [
        'image' => [new FileOrImageValidValue],
    ]);

    expect($validator->fails())->toBeTrue();
});

it('rejects an SVG upload carrying an inline event handler', function () {
    $payload = '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(document.domain)"></svg>';
    $file = UploadedFile::fake()->createWithContent('payload.svg', $payload);

    $validator = Validator::make(['image' => $file], [
        'image' => [new FileOrImageValidValue],
    ]);

    expect($validator->fails())->toBeTrue();
});

it('accepts a clean SVG upload with no script content', function () {
    $payload = '<svg xmlns="http://www.w3.org/2000/svg"><circle cx="5" cy="5" r="4"/></svg>';
    $file = UploadedFile::fake()->createWithContent('logo.svg', $payload);

    $validator = Validator::make(['image' => $file], [
        'image' => [new FileOrImageValidValue],
    ]);

    expect($validator->passes())->toBeTrue();
});
