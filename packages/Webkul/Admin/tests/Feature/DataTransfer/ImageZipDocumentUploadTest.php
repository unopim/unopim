<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\DataTransfer\Helpers\Error;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Helpers\Importers\FieldProcessor;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\Product\Models\Product;

function makeMediaZip(array $entries): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'mediazip');

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

    foreach ($entries as $name => $content) {
        $zip->addFromString($name, $content);
    }

    $zip->close();

    return new UploadedFile($path, 'media.zip', 'application/zip', null, true);
}

function makeOfficeOpenXml(string $mainPart, string $contentType): string
{
    $path = tempnam(sys_get_temp_dir(), 'ooxml');

    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Override PartName="/'.$mainPart.'" ContentType="'.$contentType.'"/></Types>');
    $zip->addFromString('_rels/.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"/>');
    $zip->addFromString($mainPart, '<?xml version="1.0"?><document/>');

    foreach (['[Content_Types].xml', '_rels/.rels', $mainPart] as $entry) {
        $zip->setCompressionName($entry, ZipArchive::CM_STORE);
    }

    $zip->close();

    $contents = file_get_contents($path);

    unlink($path);

    return $contents;
}

function uploadMediaZip($test, UploadedFile $zip)
{
    $test->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.imports.edit']);

    $response = $test->post(route('admin.settings.data_transfer.imports.upload_images_zip'), [
        'images_zip' => $zip,
    ]);

    @unlink($zip->getPathname());

    return $response;
}

$minimalPdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 200 200]>>endobj\ntrailer<</Root 1 0 R>>\n%%EOF\n";

$onePixelPng = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

it('extracts pdf documents next to images so file attributes can be imported from the zip', function () use ($minimalPdf, $onePixelPng) {
    $response = uploadMediaZip($this, makeMediaZip([
        'product.png'           => $onePixelPng,
        'datasheet.pdf'         => $minimalPdf,
        'manuals/ib-100-en.pdf' => $minimalPdf,
    ]))->assertOk();

    $path = $response->json('path');

    expect($response->json('files_count'))->toBe(3)
        ->and($response->json('skipped_files'))->toBe([])
        ->and($response->json('message'))->toBe(trans('admin::app.settings.data-transfer.imports.zip-upload-success'))
        ->and(Storage::disk('public')->exists($path.'/datasheet.pdf'))->toBeTrue()
        ->and(Storage::disk('public')->exists($path.'/manuals/ib-100-en.pdf'))->toBeTrue()
        ->and(Storage::disk('public')->exists($path.'/product.png'))->toBeTrue();

    Storage::disk('public')->deleteDirectory($path);
});

it('lists every skipped entry by name instead of dropping it silently', function () use ($minimalPdf, $onePixelPng) {
    $response = uploadMediaZip($this, makeMediaZip([
        'product.png'   => $onePixelPng,
        'datasheet.pdf' => $minimalPdf,
        'evil.php'      => '<?php echo "pwned"; ?>',
        'vector.svg'    => '<svg xmlns="http://www.w3.org/2000/svg"></svg>',
    ]))->assertOk();

    $path = $response->json('path');

    expect($response->json('files_count'))->toBe(2)
        ->and($response->json('skipped_files'))->toEqualCanonicalizing(['evil.php', 'vector.svg'])
        ->and($response->json('message'))->toContain('2 files extracted, 2 skipped')
        ->and($response->json('message'))->toContain('PDF')
        ->and(Storage::disk('public')->exists($path.'/evil.php'))->toBeFalse()
        ->and(Storage::disk('public')->exists($path.'/vector.svg'))->toBeFalse();

    Storage::disk('public')->deleteDirectory($path);
});

it('does not extract script content disguised with a pdf extension', function () {
    $response = uploadMediaZip($this, makeMediaZip([
        'manual.pdf' => '<?php echo "pwned"; ?>',
    ]))->assertOk();

    $path = $response->json('path');

    expect($response->json('files_count'))->toBe(0)
        ->and($response->json('skipped_files'))->toBe(['manual.pdf'])
        ->and(Storage::disk('public')->exists($path.'/manual.pdf'))->toBeFalse();

    Storage::disk('public')->deleteDirectory($path);
});

it('only extracts the extensions listed in the image import configuration', function () use ($minimalPdf, $onePixelPng) {
    config(['image_import.allowed_extensions' => ['png']]);

    $response = uploadMediaZip($this, makeMediaZip([
        'product.png'   => $onePixelPng,
        'datasheet.pdf' => $minimalPdf,
    ]))->assertOk();

    $path = $response->json('path');

    expect($response->json('files_count'))->toBe(1)
        ->and($response->json('skipped_files'))->toBe(['datasheet.pdf'])
        ->and(Storage::disk('public')->exists($path.'/datasheet.pdf'))->toBeFalse();

    Storage::disk('public')->deleteDirectory($path);
});

it('extracts every attribute-supported document, image and audio format', function () use ($minimalPdf, $onePixelPng) {
    $entries = [
        'bosch-wau28t64gb/images/front-view.png'          => $onePixelPng,
        'bosch-wau28t64gb/images/detail.tiff'             => "II*\x00\x08\x00\x00\x00\x00\x00\x00\x00",
        'bosch-wau28t64gb/docs/user-manual.pdf'           => $minimalPdf,
        'bosch-wau28t64gb/docs/warranty-card.docx'        => makeOfficeOpenXml('word/document.xml', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml'),
        'bosch-wau28t64gb/docs/product-brochure.pptx'     => makeOfficeOpenXml('ppt/presentation.xml', 'application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml'),
        'bosch-wau28t64gb/docs/installation-guide.rtf'    => '{\\rtf1\\ansi Bosch Serie 6 installation guide\\par}',
        'bosch-wau28t64gb/docs/care-instructions.txt'     => "Clean the detergent drawer and door seal once a month.\n",
        'bosch-wau28t64gb/docs/spec-sheet.csv'            => "sku,name,energy_class\nWAU28T64GB,Bosch Serie 6 Washing Machine,A\n",
        'bosch-wau28t64gb/docs/spec-sheet-semicolon.csv'  => "sku;name;price\nWAU28T64GB;Bosch Serie 6;649.99\n",
        'bosch-wau28t64gb/audio/product-demo.mp3'         => "ID3\x03\x00\x00\x00\x00\x00\x00".str_repeat("\xFF\xFB\x90\x00".str_repeat("\x00", 413), 3),
        'bosch-wau28t64gb/audio/product-demo.wav'         => 'RIFF'.pack('V', 36).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, 8000, 8000, 1, 8).'data'.pack('V', 0),
    ];

    $response = uploadMediaZip($this, makeMediaZip($entries))->assertOk();

    $path = $response->json('path');

    expect($response->json('files_count'))->toBe(count($entries))
        ->and($response->json('skipped_count'))->toBe(0)
        ->and($response->json('skipped_files'))->toBe([]);

    foreach (array_keys($entries) as $entry) {
        expect(Storage::disk('public')->exists($path.'/'.$entry))->toBeTrue();
    }

    Storage::disk('public')->deleteDirectory($path);
});

it('ignores operating system metadata entries instead of reporting them as skipped', function () use ($minimalPdf) {
    $response = uploadMediaZip($this, makeMediaZip([
        'docs/user-manual.pdf'                => $minimalPdf,
        '__MACOSX/docs/._user-manual.pdf'     => "\x00\x05\x16\x07\x00\x02\x00\x00Mac OS X",
        'docs/._user-manual.pdf'              => "\x00\x05\x16\x07\x00\x02\x00\x00Mac OS X",
        'docs/.DS_Store'                      => "\x00\x00\x00\x01Bud1",
        'docs/Thumbs.db'                      => "\xD0\xCF\x11\xE0",
    ]))->assertOk();

    $path = $response->json('path');

    expect($response->json('files_count'))->toBe(1)
        ->and($response->json('skipped_count'))->toBe(0)
        ->and($response->json('message'))->toBe(trans('admin::app.settings.data-transfer.imports.zip-upload-success'))
        ->and(Storage::disk('public')->exists($path.'/__MACOSX'))->toBeFalse();

    Storage::disk('public')->deleteDirectory($path);
});

it('caps the reported skipped entries while counting all of them', function () use ($onePixelPng) {
    config(['image_import.max_reported_skipped' => 2]);

    $response = uploadMediaZip($this, makeMediaZip([
        'product.png' => $onePixelPng,
        'a.php'       => '<?php echo 1;',
        'b.php'       => '<?php echo 2;',
        'c.exe'       => 'MZ',
        'd.html'      => '<html></html>',
    ]))->assertOk();

    $path = $response->json('path');

    expect($response->json('files_count'))->toBe(1)
        ->and($response->json('skipped_count'))->toBe(4)
        ->and($response->json('skipped_files'))->toHaveCount(2)
        ->and($response->json('message'))->toContain('1 files extracted, 4 skipped');

    Storage::disk('public')->deleteDirectory($path);
});

it('imports file, image and gallery attribute values from an uploaded zip into the product', function () use ($minimalPdf, $onePixelPng) {
    $response = uploadMediaZip($this, makeMediaZip([
        'bosch-wau28t64gb/images/front-view.png'       => $onePixelPng,
        'bosch-wau28t64gb/images/side-view.png'        => $onePixelPng,
        'bosch-wau28t64gb/docs/user-manual.pdf'        => $minimalPdf,
        'bosch-wau28t64gb/docs/warranty-card.docx'     => makeOfficeOpenXml('word/document.xml', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml'),
        'bosch-wau28t64gb/docs/care-instructions.txt'  => "Clean the detergent drawer and door seal once a month.\n",
    ]))->assertOk();

    $directory = $response->json('path');

    $codeSuffix = '_'.Str::lower(Str::random(6));

    $mediaColumns = [
        'user_manual'.$codeSuffix       => ['file', 'bosch-wau28t64gb/docs/user-manual.pdf'],
        'warranty_card'.$codeSuffix     => ['file', 'bosch-wau28t64gb/docs/warranty-card.docx'],
        'care_instructions'.$codeSuffix => ['file', 'bosch-wau28t64gb/docs/care-instructions.txt'],
        'front_image'.$codeSuffix       => ['image', 'bosch-wau28t64gb/images/front-view.png'],
        'product_gallery'.$codeSuffix   => ['gallery', 'bosch-wau28t64gb/images/front-view.png,bosch-wau28t64gb/images/side-view.png'],
    ];

    $familyProduct = seedRequiredProductValues(Product::factory()->simple()->create());

    $attributeGroup = $familyProduct->attribute_family->attributeFamilyGroupMappings->first();

    foreach ($mediaColumns as $code => [$type]) {
        $attributeGroup->customAttributes()->attach(Attribute::factory()->create(['code' => $code, 'type' => $type]));
    }

    $row = [
        'sku'               => 'BOSCH-WAU28T64GB',
        'type'              => 'simple',
        'attribute_family'  => $familyProduct->attribute_family->code,
        'channel'           => core()->getDefaultChannelCode(),
        'locale'            => 'en_US',
        'status'            => 'true',
        'name'              => 'Bosch Serie 6 WAU28T64GB 9kg 1400rpm Washing Machine',
        'url_key'           => 'bosch-serie-6-wau28t64gb-washing-machine',
        'short_description' => 'Freestanding 9kg front-loading washing machine with i-DOS auto dosing.',
        'description'       => 'Energy class A, 1400 rpm spin, SpeedPerfect and Home Connect app control.',
    ];

    foreach (core()->getDefaultChannel()->currencies->pluck('code') as $currencyCode) {
        $row['price ('.$currencyCode.')'] = '649.99';
    }

    foreach ($mediaColumns as $code => [, $mediaPath]) {
        $row[$code] = $mediaPath;
    }

    (new ReflectionProperty(FieldProcessor::class, 'pathExistsCache'))->setValue(null, []);

    foreach (['staticInitCache', 'staticInitCacheJobId'] as $property) {
        (new ReflectionProperty(Importer::class, $property))->setValue(null, null);
    }

    $jobTrack = JobTrack::factory()->create([
        'action'                => Import::ACTION_APPEND,
        'images_directory_path' => $directory,
    ]);

    Storage::forgetDisk(config('filesystems.default'));

    $importer = resolve(Importer::class);
    $importer->setImport($jobTrack);
    $importer->setErrorHelper($errorHelper = app(Error::class));

    expect($importer->validateRow($row, 1))->toBeTrue(json_encode($errorHelper->getAllErrors()));

    $importer->importBatch(JobTrackBatch::factory()->create(['data' => [$row], 'job_track_id' => $jobTrack->id]));

    $product = Product::where('sku', 'BOSCH-WAU28T64GB')->firstOrFail();

    $values = $product->values['common'];

    expect($values['user_manual'.$codeSuffix] ?? null)->toEndWith('user-manual.pdf')
        ->and($values['warranty_card'.$codeSuffix] ?? null)->toEndWith('warranty-card.docx')
        ->and($values['care_instructions'.$codeSuffix] ?? null)->toEndWith('care-instructions.txt')
        ->and($values['front_image'.$codeSuffix] ?? null)->toEndWith('front-view.png')
        ->and($values['product_gallery'.$codeSuffix] ?? [])->toHaveCount(2);

    Storage::disk('public')->deleteDirectory($directory);
    Storage::disk('public')->deleteDirectory('product/'.$product->id);
});
