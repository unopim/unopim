<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Webkul\Core\Rules\FileMimeExtensionMatch;
use Webkul\Core\Rules\FileOrImageValidValue;

it('accepts matching JPEG aliases for uploads and existing media', function (string $extension) {
    Storage::fake(config('filesystems.default'));

    $image = UploadedFile::fake()->image('photo.jpg');
    $file = new UploadedFile($image->getRealPath(), 'photo.'.$extension, test: true);
    $path = 'product/25/photo/photo.'.$extension;
    Storage::put($path, file_get_contents($image->getRealPath()));

    $rule = new FileOrImageValidValue(
        isImage: true,
        allowedMimes: [strtolower($extension)],
        allowedExtensions: [strtolower($extension)],
        allowedPathPrefixes: ['product/25/photo'],
    );

    expect(Validator::make(['image' => $file], ['image' => [$rule]])->passes())->toBeTrue()
        ->and(Validator::make(['image' => $path], ['image' => [$rule]])->passes())->toBeTrue()
        ->and(Validator::make(['image' => $file], ['image' => [new FileMimeExtensionMatch]])->passes())->toBeTrue();
})->with(['jpg', 'jpeg', 'jfif', 'jif', 'JPG', 'JFIF']);

it('accepts both TIFF extensions with matching content', function (string $extension) {
    $file = UploadedFile::fake()->create('photo.'.$extension, 1, 'image/tiff');

    $validator = Validator::make(['image' => $file], [
        'image' => [new FileOrImageValidValue(isImage: true, allowedMimes: [$extension], allowedExtensions: [$extension])],
    ]);

    expect($validator->passes())->toBeTrue();
})->with(['tif', 'tiff']);

it('does not broaden a configured extension allowlist to JPEG aliases', function () {
    $image = UploadedFile::fake()->image('photo.jpg');
    $file = new UploadedFile($image->getRealPath(), 'photo.jfif', test: true);

    $validator = Validator::make(['image' => $file], [
        'image' => [new FileOrImageValidValue(isImage: true, allowedExtensions: ['jpg'])],
    ]);

    expect($validator->fails())->toBeTrue();
});

it('rejects mismatched content renamed to an allowed image alias', function (string $extension, string $contentType) {
    Storage::fake(config('filesystems.default'));

    $content = $contentType === 'png'
        ? UploadedFile::fake()->image('source.png')
        : UploadedFile::fake()->createWithContent('source.html', '<html>not an image</html>');
    $file = new UploadedFile($content->getRealPath(), 'photo.'.$extension, test: true);
    $path = 'product/25/photo/photo.'.$extension;
    Storage::put($path, file_get_contents($content->getRealPath()));
    $rule = new FileOrImageValidValue(isImage: true, allowedPathPrefixes: ['product/25/photo']);

    expect(Validator::make(['image' => $file], ['image' => [$rule]])->fails())->toBeTrue()
        ->and(Validator::make(['image' => $path], ['image' => [$rule]])->fails())->toBeTrue();
})->with(['jfif', 'jif', 'tiff'])->with(['html', 'png']);

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

function fakeDocx(string $name, bool $withMacroProject): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'docx');

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>');
    $zip->addFromString('word/document.xml', '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body/></w:document>');

    if ($withMacroProject) {
        $zip->addFromString('word/vbaProject.bin', 'x');
    }

    $zip->close();

    return UploadedFile::fake()->createWithContent($name, file_get_contents($path));
}

it('rejects a pdf upload carrying an embedded javascript action with the reason', function () {
    $file = UploadedFile::fake()->createWithContent('payload.pdf', "%PDF-1.7\n1 0 obj<</Type/Catalog/OpenAction<</S/JavaScript/JS(app.alert\\(1\\))>>>>endobj\n%%EOF");

    $validator = Validator::make(['file' => $file], ['file' => [new FileOrImageValidValue]]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('file'))->toContain(trans('core::validation.active-content-reasons.embedded_javascript_or_action'));
});

it('rejects a docx upload shipping a vba macro project', function () {
    $validator = Validator::make(['file' => fakeDocx('payload.docx', true)], ['file' => [new FileOrImageValidValue]]);

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->first('file'))->toContain(trans('core::validation.active-content-reasons.embedded_vba_macro'));
});

it('accepts a docx upload without macros', function () {
    $validator = Validator::make(['file' => fakeDocx('spec.docx', false)], ['file' => [new FileOrImageValidValue]]);

    expect($validator->passes())->toBeTrue();
});

it('rejects an rtf upload carrying an auto-executing object', function () {
    $file = UploadedFile::fake()->createWithContent('payload.rtf', '{\rtf1\ansi{\object\objautlink\objupdate}}');

    $validator = Validator::make(['file' => $file], ['file' => [new FileOrImageValidValue]]);

    expect($validator->fails())->toBeTrue();
});
