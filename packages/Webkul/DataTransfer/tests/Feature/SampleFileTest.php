<?php

use Illuminate\Support\Facades\Storage;

function sampleRows(string $path): array
{
    $handle = fopen(Storage::disk('public')->path($path), 'r');

    $rows = [];

    while (($row = fgetcsv($handle, escape: '\\')) !== false) {
        $rows[] = $row;
    }

    fclose($handle);

    return $rows;
}

dataset('sample paths', function () {
    foreach (['importers', 'exporters'] as $configFile) {
        $config = require __DIR__."/../../src/Config/$configFile.php";

        foreach ($config as $type => $settings) {
            if (isset($settings['sample_path'])) {
                yield "$configFile.$type" => [$settings['sample_path']];
            }
        }
    }
});

it('ships every configured sample file on the public disk', function (string $path) {
    expect(Storage::disk('public')->exists($path))->toBeTrue();
})->with('sample paths');

it('keeps every sample comma separated with a consistent column count', function (string $path) {
    $rows = sampleRows($path);

    expect($rows)->not->toBeEmpty();

    $columnCount = count($rows[0]);

    expect($columnCount)->toBeGreaterThan(1);

    foreach ($rows as $index => $row) {
        expect($row)->toHaveCount($columnCount, sprintf('Row %d of %s has a different column count', $index + 1, $path));
    }
})->with('sample paths');

it('keeps every sample small enough to read as an example', function (string $path) {
    expect(count(sampleRows($path)) - 1)->toBeLessThanOrEqual(10);
})->with('sample paths');

it('downloads the sample for a known importer type', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.settings.data_transfer.imports.download_sample', 'products'))
        ->assertOk()
        ->assertDownload('products.csv');
});

it('downloads the sample for a known exporter type', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.settings.data_transfer.exports.download_sample', 'products'))
        ->assertOk()
        ->assertDownload('products.csv');
});

it('returns not found for an unknown sample type', function (string $route) {
    $this->loginAsAdmin();

    $this->get(route($route, 'no-such-type'))->assertNotFound();
})->with([
    'admin.settings.data_transfer.imports.download_sample',
    'admin.settings.data_transfer.exports.download_sample',
]);

it('returns not found when no sample type is given', function (string $route) {
    $this->loginAsAdmin();

    $this->get(route($route))->assertNotFound();
})->with([
    'admin.settings.data_transfer.imports.download_sample',
    'admin.settings.data_transfer.exports.download_sample',
]);
