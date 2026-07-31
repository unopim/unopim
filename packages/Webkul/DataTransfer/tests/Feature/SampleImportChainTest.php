<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Helpers\Import;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;

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

function importSample(string $entityType, string $samplePath): array
{
    $filePath = 'import/'.basename($samplePath);

    Storage::disk('private')->put($filePath, (string) file_get_contents(base_path('storage/app/public/'.$samplePath)));

    $jobInstance = JobInstances::factory()->create([
        'code'            => 'sample-'.md5($samplePath),
        'entity_type'     => $entityType,
        'type'            => 'import',
        'action'          => 'append',
        'field_separator' => ',',
        'file_path'       => $filePath,
    ]);

    $jobTrack = JobTrack::factory()->create([
        'job_instances_id'    => $jobInstance->id,
        'type'                => 'import',
        'action'              => 'append',
        'validation_strategy' => 'skip-erros',
        'allowed_errors'      => 1000,
        'field_separator'     => ',',
        'file_path'           => $filePath,
        'state'               => Import::STATE_PENDING,
    ]);

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

it('imports every shipped sample against a stock installation', function () {
    reduceToStockInstallation();

    $chain = [
        ['locales', 'data-transfer/samples/locales.csv'],
        ['currencies', 'data-transfer/samples/currencies.csv'],
        ['channels', 'data-transfer/samples/channels.csv'],
        ['attribute-groups', 'data-transfer/samples/attribute-groups.csv'],
        ['attributes', 'data-transfer/samples/attributes.csv'],
        ['attribute-options', 'data-transfer/samples/attribute-options.csv'],
        ['attribute-families', 'data-transfer/samples/attribute-families.csv'],
        ['category-fields', 'data-transfer/samples/category-fields.csv'],
        ['categories', 'data-transfer/samples/categories.csv'],
        ['products', 'data-transfer/samples/products.csv'],
        ['products', 'data-transfer/samples/sample-variant-products.csv'],
        ['product-associations', 'data-transfer/samples/product-associations.csv'],
        ['roles', 'data-transfer/samples/roles.csv'],
        ['users', 'data-transfer/samples/users.csv'],
    ];

    foreach ($chain as [$entityType, $samplePath]) {
        expect(importSample($entityType, $samplePath))->toBe([], "$samplePath failed to import");
    }
});
