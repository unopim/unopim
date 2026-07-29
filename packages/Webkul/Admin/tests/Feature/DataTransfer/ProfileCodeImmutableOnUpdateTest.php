<?php

use Webkul\DataTransfer\Models\JobInstances;

/*
 * The edit forms render the profile code as a disabled input mirrored by a
 * hidden one, and neither controller persists `code` on update — the code is
 * immutable once the profile exists. Validating its format on update therefore
 * only rejected codes the user cannot change, which locked every profile whose
 * code predates the format rule (the seeded "Product Export" among them) out
 * of editing entirely.
 */

it('updates an export profile whose existing code predates the code format rule', function () {
    $export = JobInstances::factory()->exportJob()->entityProduct()->create([
        'code' => 'Legacy Product Export',
    ]);

    $this->loginAsAdmin();

    $this->put(route('admin.settings.data_transfer.exports.update', $export->id), [
        'code'            => 'Legacy Product Export',
        'entity_type'     => 'products',
        'field_separator' => ',',
        'filters'         => ['file_format' => 'Csv', 'locale' => ['de_DE']],
    ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.settings.data_transfer.exports.export-view', $export->id));

    expect($export->fresh()->filters['locale'])->toBe(['de_DE']);
});

it('leaves the export profile code untouched on update', function () {
    $export = JobInstances::factory()->exportJob()->entityProduct()->create([
        'code' => 'Legacy Product Export',
    ]);

    $this->loginAsAdmin();

    $this->put(route('admin.settings.data_transfer.exports.update', $export->id), [
        'code'            => 'renamed_code',
        'entity_type'     => 'products',
        'field_separator' => ',',
        'filters'         => ['file_format' => 'Csv'],
    ])->assertSessionHasNoErrors();

    expect($export->fresh()->code)->toBe('Legacy Product Export');
});

it('updates an import profile whose existing code predates the code format rule', function () {
    $import = JobInstances::factory()->importJob()->entityProduct()->create([
        'code' => 'Legacy Product Import',
    ]);

    $this->loginAsAdmin();

    $this->put(route('admin.settings.data_transfer.imports.update', $import->id), [
        'code'                => 'Legacy Product Import',
        'entity_type'         => 'products',
        'action'              => 'append',
        'validation_strategy' => 'skip-errors',
        'allowed_errors'      => 0,
        'field_separator'     => ',',
    ])->assertSessionHasNoErrors();

    expect($import->fresh()->code)->toBe('Legacy Product Import');
});
