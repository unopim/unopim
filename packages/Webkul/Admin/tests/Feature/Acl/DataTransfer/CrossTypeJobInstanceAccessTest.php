<?php

use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;

use function Pest\Laravel\delete;
use function Pest\Laravel\get;

/**
 * Create a product job instance of the given type pointing at the given file.
 */
function crossTypeJobInstance(string $type, string $filePath): JobInstances
{
    $factory = JobInstances::factory()->entityProduct();

    $jobInstance = ($type === 'import' ? $factory->importJob() : $factory->exportJob())->create();

    $jobInstance->update(['file_path' => $filePath]);

    return $jobInstance;
}

beforeEach(function (): void {
    Storage::fake('private');

    Storage::disk('private')->put('imports/source/products.csv', 'sku,name');
    Storage::disk('private')->put('exports/products/products.csv', 'sku,name');
});

it('does not let export execute download the source file of an import', function (): void {
    $import = crossTypeJobInstance('import', 'imports/source/products.csv');

    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.export', 'data_transfer.export.execute']);

    get(route('admin.settings.data_transfer.exports.download', $import->id))->assertNotFound();
});

it('does not let import execute download the file of an export', function (): void {
    $export = crossTypeJobInstance('export', 'exports/products/products.csv');

    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.imports', 'data_transfer.imports.execute']);

    get(route('admin.settings.data_transfer.imports.download', $export->id))->assertNotFound();
});

it('still lets each execute permission download a file of its own job type', function (string $type, string $filePath, string $route, string $viewPermission, string $executePermission): void {
    $jobInstance = crossTypeJobInstance($type, $filePath);

    $this->loginWithPermissions(permissions: ['data_transfer', $viewPermission, $executePermission]);

    get(route($route, $jobInstance->id))->assertOk();
})->with([
    'import' => ['import', 'imports/source/products.csv', 'admin.settings.data_transfer.imports.download', 'data_transfer.imports', 'data_transfer.imports.execute'],
    'export' => ['export', 'exports/products/products.csv', 'admin.settings.data_transfer.exports.download', 'data_transfer.export', 'data_transfer.export.execute'],
]);

it('does not let an export role open, update or delete an import job', function (): void {
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

it('does not let an import role open, update or delete an export job', function (): void {
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

it('does not let a role open or run a job of the other type', function (string $type, string $filePath, array $permissions, string $method, string $route): void {
    $jobInstance = crossTypeJobInstance($type, $filePath);

    $this->loginWithPermissions(permissions: ['data_transfer', ...$permissions]);

    $this->{$method}(route($route, $jobInstance->id))->assertNotFound();

    expect(JobTrack::where('job_instances_id', $jobInstance->id)->exists())->toBeFalse();
})->with(function (): array {
    $exportRole = ['data_transfer.export', 'data_transfer.export.edit', 'data_transfer.export.execute'];
    $importRole = ['data_transfer.imports', 'data_transfer.imports.edit', 'data_transfer.imports.execute'];

    return [
        'export view'     => ['import', 'imports/source/products.csv', $exportRole, 'get', 'admin.settings.data_transfer.exports.export-view'],
        'export now'      => ['import', 'imports/source/products.csv', $exportRole, 'put', 'admin.settings.data_transfer.exports.export_now'],
        'export validate' => ['import', 'imports/source/products.csv', $exportRole, 'post', 'admin.settings.data_transfer.exports.validate'],
        'export start'    => ['import', 'imports/source/products.csv', $exportRole, 'post', 'admin.settings.data_transfer.exports.start'],
        'export link'     => ['import', 'imports/source/products.csv', $exportRole, 'post', 'admin.settings.data_transfer.exports.link'],
        'export index'    => ['import', 'imports/source/products.csv', $exportRole, 'post', 'admin.settings.data_transfer.exports.index_data'],
        'import view'     => ['export', 'exports/products/products.csv', $importRole, 'get', 'admin.settings.data_transfer.imports.import-view'],
        'import now'      => ['export', 'exports/products/products.csv', $importRole, 'put', 'admin.settings.data_transfer.imports.import_now'],
    ];
});

it('does not let an export role read or fail the stats of an import run', function (): void {
    $importTrack = JobTrack::factory()->create(['state' => 'processing', 'heartbeat_at' => now()->subHour()]);

    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.export']);

    get(route('admin.settings.data_transfer.exports.stats', $importTrack->id))->assertNotFound();

    expect($importTrack->fresh()->state)->toBe('processing');
});

it('does not let an import role validate, run or read the error report of an export run', function (string $method, string $route): void {
    Storage::disk('private')->put('exports/errors/products.csv', 'sku,error');

    $exportTrack = JobTrack::factory()->export()->create(['error_file_path' => 'exports/errors/products.csv']);

    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.imports', 'data_transfer.imports.edit', 'data_transfer.imports.execute']);

    $this->{$method}(route($route, $exportTrack->id))->assertNotFound();

    expect($exportTrack->fresh()->state)->toBe('pending');
})->with([
    'validate'     => ['post', 'admin.settings.data_transfer.imports.validate'],
    'start'        => ['post', 'admin.settings.data_transfer.imports.start'],
    'link'         => ['post', 'admin.settings.data_transfer.imports.link'],
    'index'        => ['post', 'admin.settings.data_transfer.imports.index_data'],
    'error report' => ['get', 'admin.settings.data_transfer.imports.download_error_report'],
]);

it('does not let an import route run a track whose job instance is an export', function (): void {
    $exportInstance = JobInstances::factory()->entityProduct()->exportJob()->create();

    $track = JobTrack::factory()->create(['job_instances_id' => $exportInstance->id]);

    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.imports', 'data_transfer.imports.edit', 'data_transfer.imports.execute']);

    $this->post(route('admin.settings.data_transfer.imports.validate', $track->id))->assertNotFound();
});

it('still lets an export role read the stats of its own run', function (): void {
    $exportTrack = JobTrack::factory()->export()->create();

    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.export']);

    get(route('admin.settings.data_transfer.exports.stats', $exportTrack->id))->assertOk();
});
