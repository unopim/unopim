<?php

use Webkul\DataTransfer\Models\JobInstances as JobInstance;

function legacyExport(string $code): JobInstance
{
    return JobInstance::create([
        'code'        => $code,
        'entity_type' => 'products',
        'type'        => 'export',
        'action'      => 'fetch',
        'filters'     => ['file_format' => 'Csv', 'with_media' => '1'],
    ]);
}

it('persists export filters for a job whose code contains a space', function () {
    $this->loginAsAdmin();

    $export = legacyExport('Product Export '.uniqid());

    $this->put(route('admin.settings.data_transfer.exports.update', $export->id), [
        'code'            => $export->code,
        'entity_type'     => 'products',
        'field_separator' => ',',
        'filters'         => [
            'file_format' => 'Xlsx',
            'with_media'  => '0',
            'channels'    => 'default',
        ],
    ])->assertRedirect()->assertSessionHasNoErrors();

    $filters = $export->fresh()->filters;

    expect($filters['file_format'])->toBe('Xlsx')
        ->and($filters['channels'])->toBe('default')
        ->and($filters['with_media'])->toBe('0');
});

it('persists import settings for a job whose code contains a space', function () {
    $this->loginAsAdmin();

    $import = JobInstance::create([
        'code'        => 'Product Import '.uniqid(),
        'entity_type' => 'products',
        'type'        => 'import',
        'action'      => 'append',
        'filters'     => [],
    ]);

    $this->put(route('admin.settings.data_transfer.imports.update', $import->id), [
        'code'                => $import->code,
        'entity_type'         => 'products',
        'action'              => 'append',
        'validation_strategy' => 'stop-on-errors',
        'allowed_errors'      => 0,
        'field_separator'     => ',',
        'process_in_queue'    => 0,
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($import->fresh()->entity_type)->toBe('products');
});
