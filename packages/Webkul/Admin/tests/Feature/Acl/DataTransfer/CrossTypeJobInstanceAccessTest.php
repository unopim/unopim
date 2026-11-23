<?php

use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Models\JobInstances;

use function Pest\Laravel\delete;
use function Pest\Laravel\get;

function crossTypeJobInstance(string $type, string $filePath): JobInstances
{
    $factory = JobInstances::factory()->entityProduct();

    $jobInstance = ($type === 'import' ? $factory->importJob() : $factory->exportJob())->create();

    $jobInstance->update(['file_path' => $filePath]);

    return $jobInstance;
}

beforeEach(function () {
    Storage::fake('private');

    Storage::disk('private')->put('imports/source/products.csv', 'sku,name');
    Storage::disk('private')->put('exports/products/products.csv', 'sku,name');
});

it('does not let export execute download the source file of an import', function () {
    $import = crossTypeJobInstance('import', 'imports/source/products.csv');

    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.export', 'data_transfer.export.execute']);

    get(route('admin.settings.data_transfer.exports.download', $import->id))->assertNotFound();
});

it('does not let import execute download the file of an export', function () {
    $export = crossTypeJobInstance('export', 'exports/products/products.csv');

    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.imports', 'data_transfer.imports.execute']);

    get(route('admin.settings.data_transfer.imports.download', $export->id))->assertNotFound();
});

it('still lets each execute permission download a file of its own job type', function (string $type, string $filePath, string $route, string $viewPermission, string $executePermission) {
    $jobInstance = crossTypeJobInstance($type, $filePath);

    $this->loginWithPermissions(permissions: ['data_transfer', $viewPermission, $executePermission]);

    get(route($route, $jobInstance->id))->assertOk();
})->with([
    'import' => ['import', 'imports/source/products.csv', 'admin.settings.data_transfer.imports.download', 'data_transfer.imports', 'data_transfer.imports.execute'],
    'export' => ['export', 'exports/products/products.csv', 'admin.settings.data_transfer.exports.download', 'data_transfer.export', 'data_transfer.export.execute'],
]);

it('does not let an export role open, update or delete an import job', function () {
    $import = crossTypeJobInstance('import', 'imports/source/products.csv');

    $this->loginWithPermissions(permissions: [
        'data_transfer',
        'data_transfer.export',
        'data_transfer.export.edit',
        'data_transfer.export.delete',
    ]);

    get(route('admin.settings.data_transfer.exports.edit', $import->id))->assertNotFound();

    $this->put(route('admin.settings.data_transfer.exports.update', $import->id), [
        'code'        => $import->code,
        'entity_type' => 'products',
    ])->assertNotFound();

    delete(route('admin.settings.data_transfer.exports.delete', $import->id))->assertNotFound();

    $this->assertDatabaseHas('job_instances', ['id' => $import->id, 'type' => 'import']);
});

it('does not let an import role open, update or delete an export job', function () {
    $export = crossTypeJobInstance('export', 'exports/products/products.csv');

    $this->loginWithPermissions(permissions: [
        'data_transfer',
        'data_transfer.imports',
        'data_transfer.imports.edit',
        'data_transfer.imports.delete',
    ]);

    get(route('admin.settings.data_transfer.imports.edit', $export->id))->assertNotFound();

    $this->put(route('admin.settings.data_transfer.imports.update', $export->id), [
        'code'        => $export->code,
        'entity_type' => 'products',
    ])->assertNotFound();

    delete(route('admin.settings.data_transfer.imports.delete', $export->id))->assertNotFound();

    $this->assertDatabaseHas('job_instances', ['id' => $export->id, 'type' => 'export']);
});
