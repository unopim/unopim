<?php

use Webkul\DataTransfer\Services\SampleFiles;

function sampleRows(string $absolutePath): array
{
    $handle = fopen($absolutePath, 'r');

    $rows = [];

    while (($row = fgetcsv($handle, escape: '\\')) !== false) {
        $rows[] = $row;
    }

    fclose($handle);

    return $rows;
}

dataset('samples', function () {
    foreach (['importers', 'exporters'] as $configFile) {
        $config = require __DIR__."/../../src/Config/$configFile.php";

        foreach ($config as $type => $settings) {
            foreach (['default' => $settings['sample_path'] ?? null] + array_map(fn ($s) => $s['path'], $settings['samples'] ?? []) as $key => $path) {
                if ($path) {
                    yield "$configFile.$type.$key" => [$configFile, $type, $key];
                }
            }
        }
    }
});

it('resolves every configured sample to a shipped file', function (string $configFile, string $type, string $key) {
    expect(app(SampleFiles::class)->path($configFile, $type, $key))->toBeString();
})->with('samples');

it('keeps every sample comma separated with a consistent column count', function (string $configFile, string $type, string $key) {
    $rows = sampleRows(app(SampleFiles::class)->path($configFile, $type, $key));

    expect($rows)->not->toBeEmpty();

    $columnCount = count($rows[0]);

    expect($columnCount)->toBeGreaterThan(1);

    foreach ($rows as $index => $row) {
        expect($row)->toHaveCount($columnCount, sprintf('Row %d has a different column count', $index + 1));
    }
})->with('samples');

it('keeps every sample small enough to read as an example', function (string $configFile, string $type, string $key) {
    expect(count(sampleRows(app(SampleFiles::class)->path($configFile, $type, $key))) - 1)->toBeLessThanOrEqual(10);
})->with('samples');

it('ships a readable images archive for the product importer', function () {
    $path = app(SampleFiles::class)->path('importers', 'products', images: true);

    expect($path)->toBeString();

    $zip = new ZipArchive;

    expect($zip->open($path))->toBeTrue();
    expect($zip->locateName('products.csv'))->not->toBeFalse();

    $names = array_map(fn ($i) => $zip->getNameIndex($i), range(0, $zip->numFiles - 1));

    $images = array_values(array_filter($names, fn ($name) => str_ends_with($name, '.jpg')));

    expect($images)->not->toBeEmpty();

    $rows = array_map('str_getcsv', array_filter(explode("\n", (string) $zip->getFromName('products.csv'))));

    $imageColumn = array_search('image', $rows[0], true);

    expect($imageColumn)->not->toBeFalse();

    foreach (array_slice($rows, 1) as $row) {
        if ($row[$imageColumn] !== '') {
            expect($images)->toContain($row[$imageColumn]);
        }
    }

    $zip->close();
});

it('downloads a sample for a known type', function (string $route) {
    $this->loginAsAdmin();

    $this->get(route($route, 'products'))->assertOk()->assertDownload('products.csv');
})->with([
    'admin.settings.data_transfer.imports.download_sample',
]);

it('downloads a keyed sample', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.settings.data_transfer.imports.download_sample', ['products', 'variants']))
        ->assertOk()
        ->assertDownload('product-variants.csv');
});

it('downloads the product images archive', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.settings.data_transfer.imports.download_sample_zip', 'products'))
        ->assertOk()
        ->assertDownload('products-with-images.zip');
});

it('returns not found for unknown sample requests', function (array $parameters) {
    $this->loginAsAdmin();

    $this->get(route('admin.settings.data_transfer.imports.download_sample', $parameters))->assertNotFound();
})->with([
    'unknown type'     => [['no-such-type']],
    'unknown key'      => [['products', 'no-such-key']],
    'no type'          => [[]],
    'type without zip' => [['roles', 'variants']],
]);

it('renders every sample link on the job forms', function (string $route, string $sampleUrl) {
    $this->loginAsAdmin();

    $this->get(route($route))->assertOk()->assertSee($sampleUrl, false);
})->with([
    ['admin.settings.data_transfer.imports.create', 'download-sample/products/variants'],
    ['admin.settings.data_transfer.imports.create', 'download-sample-images-zip/products'],
]);

it('offers no sample file on the export form', function (string $route) {
    $this->loginAsAdmin();

    $this->get(route($route))->assertOk()->assertDontSee('download-sample', false);
})->with([
    'admin.settings.data_transfer.exports.create',
]);

it('returns not found when a type ships no images archive', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.settings.data_transfer.imports.download_sample_zip', 'roles'))->assertNotFound();
});
