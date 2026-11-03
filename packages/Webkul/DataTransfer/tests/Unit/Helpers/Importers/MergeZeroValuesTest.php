<?php

use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Helpers\Importers\Category\Importer as CategoryImporter;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer as ProductImporter;
use Webkul\DataTransfer\Helpers\Sources\Excel;

function invokeImporterMerge(string $class, string $method, array $properties, array $newValues, array $oldValues): array
{
    $reflection = new ReflectionClass($class);
    $importer = $reflection->newInstanceWithoutConstructor();

    foreach ($properties as $name => $value) {
        $reflection->getProperty($name)->setValue($importer, $value);
    }

    return $reflection->getMethod($method)->invoke($importer, $newValues, $oldValues);
}

it('keeps zero values when merging an updated product row into its stored values', function () {
    $merged = invokeImporterMerge(
        ProductImporter::class,
        'mergeAttributeValues',
        ['channelsAndLocales' => ['default' => ['en_US']]],
        [
            'common'                  => ['quantity' => '0', 'weight' => 0, 'featured' => false],
            'channel_specific'        => ['default' => ['stock' => '0']],
            'locale_specific'         => ['en_US' => ['rank' => '0']],
            'channel_locale_specific' => ['default' => ['en_US' => ['price_tier' => '0']]],
        ],
        [
            'common'                  => ['quantity' => '5', 'weight' => 3, 'featured' => true, 'sku' => 'zero-sku'],
            'channel_specific'        => ['default' => ['stock' => '9']],
            'locale_specific'         => ['en_US' => ['rank' => '4']],
            'channel_locale_specific' => ['default' => ['en_US' => ['price_tier' => '2']]],
        ],
    );

    expect($merged['common'])->toBe(['quantity' => '0', 'weight' => 0, 'featured' => false, 'sku' => 'zero-sku'])
        ->and($merged['channel_specific']['default'])->toBe(['stock' => '0'])
        ->and($merged['locale_specific']['en_US'])->toBe(['rank' => '0'])
        ->and($merged['channel_locale_specific']['default']['en_US'])->toBe(['price_tier' => '0']);
});

it('still drops blank values when merging an updated product row', function () {
    $merged = invokeImporterMerge(
        ProductImporter::class,
        'mergeAttributeValues',
        ['channelsAndLocales' => ['default' => ['en_US']]],
        ['common'             => ['name' => null, 'color' => '', 'tags' => []]],
        ['common'             => ['name' => 'Old', 'color' => 'red', 'tags' => ['a'], 'sku' => 'blank-sku']],
    );

    expect($merged['common'])->toBe(['sku' => 'blank-sku']);
});

it('keeps zero values when merging an updated category row into its stored values', function () {
    $merged = invokeImporterMerge(
        CategoryImporter::class,
        'mergeCategoryFieldValues',
        ['locales' => ['en_US']],
        [
            'common'          => ['position' => '0', 'blank' => ''],
            'locale_specific' => ['en_US' => ['rank' => 0]],
        ],
        [
            'common'          => ['position' => '7', 'blank' => 'old'],
            'locale_specific' => ['en_US' => ['rank' => 3]],
        ],
    );

    expect($merged['common'])->toBe(['position' => '0'])
        ->and($merged['locale_specific']['en_US'])->toBe(['rank' => 0]);
});

it('does not treat a spreadsheet row holding only zeros as blank', function () {
    Storage::fake('private')->put('imports/zero-row.csv', "sku,quantity\n0,0\n");

    $source = new Excel('imports/zero-row.csv');
    $source->rewind();

    expect($source->getColumnNames())->toBe(['sku', 'quantity'])
        ->and($source->current())->toBe(['sku' => '0', 'quantity' => '0']);
});
