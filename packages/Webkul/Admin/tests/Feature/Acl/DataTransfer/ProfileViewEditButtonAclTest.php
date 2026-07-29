<?php

use Webkul\DataTransfer\Models\JobInstances;

/*
 * The export/import profile pages always rendered their "Edit" button, even for
 * an admin whose role withholds the edit permission. The button led straight to
 * a 403, so the action had to be hidden the same way the execute button is.
 */

it('hides the edit button on the export profile page when the role withholds edit', function () {
    $export = JobInstances::factory()->exportJob()->entityProduct()->create();

    $this->loginWithPermissions(permissions: [
        'data_transfer',
        'data_transfer.export',
        'data_transfer.export.execute',
    ]);

    $this->get(route('admin.settings.data_transfer.exports.export-view', $export->id))
        ->assertOk()
        ->assertDontSee(route('admin.settings.data_transfer.exports.edit', $export->id));
});

it('shows the edit button on the export profile page when the role grants edit', function () {
    $export = JobInstances::factory()->exportJob()->entityProduct()->create();

    $this->loginWithPermissions(permissions: [
        'data_transfer',
        'data_transfer.export',
        'data_transfer.export.edit',
    ]);

    $this->get(route('admin.settings.data_transfer.exports.export-view', $export->id))
        ->assertOk()
        ->assertSee(route('admin.settings.data_transfer.exports.edit', $export->id));
});

it('hides the edit button on the import profile page when the role withholds edit', function () {
    $import = JobInstances::factory()->importJob()->entityProduct()->create();

    $this->loginWithPermissions(permissions: [
        'data_transfer',
        'data_transfer.imports',
        'data_transfer.imports.execute',
    ]);

    $this->get(route('admin.settings.data_transfer.imports.import-view', $import->id))
        ->assertOk()
        ->assertDontSee(route('admin.settings.data_transfer.imports.edit', $import->id));
});

it('shows the edit button on the import profile page when the role grants edit', function () {
    $import = JobInstances::factory()->importJob()->entityProduct()->create();

    $this->loginWithPermissions(permissions: [
        'data_transfer',
        'data_transfer.imports',
        'data_transfer.imports.edit',
    ]);

    $this->get(route('admin.settings.data_transfer.imports.import-view', $import->id))
        ->assertOk()
        ->assertSee(route('admin.settings.data_transfer.imports.edit', $import->id));
});
