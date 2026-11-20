<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Models\JobInstances;

use function Pest\Laravel\post;
use function Pest\Laravel\put;

beforeEach(function (): void {
    Storage::fake('private');
});

/**
 * Import profile form payload for the given entity type.
 *
 * @return array<string, mixed>
 */
function importProfilePayload(string $entityType, string $code): array
{
    return [
        'code'                => $code,
        'entity_type'         => $entityType,
        'field_separator'     => ',',
        'allowed_errors'      => 0,
        'file'                => UploadedFile::fake()->create('rows.csv'),
        'action'              => 'append',
        'validation_strategy' => 'skip-erros',
    ];
}

$importManager = ['dashboard', 'data_transfer.imports', 'data_transfer.imports.create', 'data_transfer.imports.edit'];

it('refuses a role or user import profile saved by an admin without full access', function (string $entityType) use ($importManager): void {
    $this->loginWithPermissions('custom', $importManager);

    $code = 'access_'.uniqid();

    post(route('admin.settings.data_transfer.imports.store'), importProfilePayload($entityType, $code))
        ->assertSessionHas('error', trans('admin::app.common.unauthorized'));

    $this->assertDatabaseMissing($this->getFullTableName(JobInstances::class), ['code' => $code]);
})->with(['roles', 'users']);

it('refuses switching an import profile to or from role and user imports without full access', function (string $from, string $to) use ($importManager): void {
    $import = JobInstances::factory()->importJob()->create(['entity_type' => $from, 'file_path' => 'imports/original.csv']);

    $this->loginWithPermissions('custom', $importManager);

    put(route('admin.settings.data_transfer.imports.update', $import->id), importProfilePayload($to, $import->code))
        ->assertSessionHas('error', trans('admin::app.common.unauthorized'));

    expect($import->fresh()->entity_type)->toBe($from)
        ->and($import->fresh()->file_path)->toBe('imports/original.csv');
})->with([
    'into users'     => ['products', 'users'],
    'within roles'   => ['roles', 'roles'],
    'out of users'   => ['users', 'products'],
]);

it('lets a full-access admin save role and user import profiles', function (): void {
    $this->loginAsAdmin();

    $code = 'access_'.uniqid();

    post(route('admin.settings.data_transfer.imports.store'), importProfilePayload('users', $code))
        ->assertSessionHas('success');

    $this->assertDatabaseHas($this->getFullTableName(JobInstances::class), ['code' => $code, 'entity_type' => 'users']);
});

it('still lets an import manager save other import profiles', function () use ($importManager): void {
    $this->loginWithPermissions('custom', $importManager);

    $code = 'access_'.uniqid();

    post(route('admin.settings.data_transfer.imports.store'), importProfilePayload('categories', $code))
        ->assertSessionHas('success');

    $this->assertDatabaseHas($this->getFullTableName(JobInstances::class), ['code' => $code]);
});
