<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Services\SampleFiles;
use Webkul\Product\Models\VariantStructure;
use Webkul\Product\Services\VariantStructureWriter;

/**
 * The shipped samples are only meant to import against a stock installation:
 * one active locale, one channel currency. The workspace database usually has
 * more of both, which would demand extra per-locale rows and per-currency
 * price columns the samples deliberately do not carry.
 */
function reduceToStockInstallation(): void
{
    $currencyIds = DB::table('currencies')->where('code', '!=', 'USD')->pluck('id');

    DB::table('channel_currencies')->whereIn('currency_id', $currencyIds)->delete();
    DB::table('currencies')->whereIn('id', $currencyIds)->update(['status' => 0]);
    DB::table('locales')->where('code', '!=', 'en_US')->update(['status' => 0]);
}

function forgetSampleProducts(): void
{
    $skus = DB::table('products')
        ->where('sku', 'like', 'configurable-product-%')
        ->orWhere('sku', 'like', 'simple-product-%')
        ->orWhere('sku', 'like', 'variant-product-%')
        ->pluck('id');

    if ($skus->isEmpty()) {
        return;
    }

    DB::table('products')->whereIn('parent_id', $skus)->delete();
    DB::table('products')->whereIn('id', $skus)->delete();
}

function seedSampleVariantStructures(): void
{
    $family = AttributeFamily::query()->where('code', 'default')->firstOrFail();

    $structures = [
        'color'            => ['levels' => 1, 'axes' => ['level_1' => ['color']]],
        'size'             => ['levels' => 1, 'axes' => ['level_1' => ['size']]],
        'color_size'       => ['levels' => 1, 'axes' => ['level_1' => ['color', 'size']]],
        'color_group_size' => ['levels' => 2, 'axes' => ['level_1' => ['color'], 'level_2' => ['size']]],
    ];

    $writer = app(VariantStructureWriter::class);

    foreach ($structures as $code => $desired) {
        if (VariantStructure::query()->where('attribute_family_id', $family->id)->where('code', $code)->exists()) {
            continue;
        }

        $writer->create($family, ['code' => $code, 'name' => $code] + $desired);
    }
}

function importSample(string $entityType, string $samplePath, string $action = Import::ACTION_APPEND, string $imagesDirectory = ''): array
{
    $filePath = 'import/'.basename($samplePath);

    Storage::disk('private')->put($filePath, (string) file_get_contents($samplePath));

    $jobInstance = JobInstances::factory()->create([
        'code'                  => 'sample-'.md5($samplePath.$action),
        'entity_type'           => $entityType,
        'type'                  => 'import',
        'action'                => $action,
        'field_separator'       => ',',
        'file_path'             => $filePath,
        'images_directory_path' => $imagesDirectory,
    ]);

    $jobTrack = JobTrack::factory()->create([
        'job_instances_id'      => $jobInstance->id,
        'type'                  => 'import',
        'action'                => $action,
        'validation_strategy'   => 'skip-erros',
        'allowed_errors'        => 1000,
        'field_separator'       => ',',
        'file_path'             => $filePath,
        'images_directory_path' => $imagesDirectory,
        'state'                 => Import::STATE_PENDING,
    ]);

    Storage::forgetDisk(['public', 'local', 'private']);

    $helper = app(Import::class)
        ->setImport($jobTrack)
        ->setLogger(app('log')->channel('single'));

    $helper->validate();

    $errors = $helper->getFormattedErrors();

    if ($errors === []) {
        $helper->started();

        foreach ($jobTrack->refresh()->batches as $batch) {
            $helper->start($batch);
        }
    }

    return $errors;
}

function samplePath(string $type, string $key = SampleFiles::DEFAULT_KEY): string
{
    return app(SampleFiles::class)->path('importers', $type, $key);
}

$chain = [
    'locales',
    'currencies',
    'channels',
    'attribute-groups',
    'attributes',
    'attribute-options',
    'attribute-families',
    'category-fields',
    'categories',
    'products',
    'product-associations',
    'roles',
    'users',
];

it('imports every shipped sample against a stock installation', function () use ($chain) {
    reduceToStockInstallation();
    forgetSampleProducts();

    foreach ($chain as $type) {
        if ($type === 'products') {
            seedSampleVariantStructures();
        }

        expect(importSample($type, samplePath($type)))->toBe([], "$type sample failed to import");
    }

    expect(importSample('products', samplePath('products', 'variants')))->toBe([], 'variant sample failed to import');
});

it('deletes with every shipped delete sample', function () use ($chain) {
    reduceToStockInstallation();
    forgetSampleProducts();

    foreach ($chain as $type) {
        if ($type === 'products') {
            seedSampleVariantStructures();
        }

        importSample($type, samplePath($type));
    }

    foreach (array_reverse($chain) as $type) {
        expect(importSample($type, samplePath($type, 'delete'), Import::ACTION_DELETE))
            ->toBe([], "$type delete sample failed to import");
    }
});

it('imports the multi-locale product sample once the extra locales are active', function () {
    reduceToStockInstallation();

    DB::table('locales')->whereIn('code', ['fr_FR', 'de_DE'])->update(['status' => 1]);

    $channelId = DB::table('channels')->where('code', 'default')->value('id');

    foreach (DB::table('locales')->whereIn('code', ['fr_FR', 'de_DE'])->pluck('id') as $localeId) {
        DB::table('channel_locales')->insertOrIgnore(['channel_id' => $channelId, 'locale_id' => $localeId]);
    }

    foreach (['attribute-groups', 'attributes', 'attribute-families', 'categories'] as $type) {
        importSample($type, samplePath($type));
    }

    expect(importSample('products', samplePath('products', 'multi-locale')))->toBe([]);
});

it('imports the products archived with their images', function () {
    reduceToStockInstallation();

    foreach (['attribute-groups', 'attributes', 'attribute-families', 'categories'] as $type) {
        importSample($type, samplePath($type));
    }

    $directory = 'sample-images-'.uniqid();

    $zip = new ZipArchive;
    $zip->open(app(SampleFiles::class)->path('importers', 'products', images: true));
    $zip->extractTo(storage_path('app/public/'.$directory));
    $zip->close();

    $errors = importSample('products', storage_path('app/public/'.$directory.'/products.csv'), imagesDirectory: $directory);

    File::deleteDirectory(storage_path('app/public/'.$directory));

    expect($errors)->toBe([]);
});
