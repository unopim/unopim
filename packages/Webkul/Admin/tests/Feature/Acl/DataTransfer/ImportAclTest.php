<?php

use Webkul\DataTransfer\Models\JobInstances;

it('should display the import index page if user has permission', function () {
    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.imports']);

    $this->get(route('admin.settings.data_transfer.imports.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.settings.data-transfer.imports.index.title'));
});

it('should not display the import index page if user does not have permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.settings.data_transfer.imports.index'))
        ->assertSeeText('Unauthorized')
        ->assertDontSeeText(trans('admin::app.settings.data-transfer.imports.index.title'));
});

it('should display the import create page if user has permission', function () {
    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.imports.create']);

    $this->get(route('admin.settings.data_transfer.imports.create'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.settings.data-transfer.imports.create.title'));
});

it('should not display the import create page if user does not have create permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.settings.data_transfer.imports.create'))
        ->assertSeeText('Unauthorized')
        ->assertDontSeeText(trans('admin::app.settings.data-transfer.imports.create.title'));
});

it('should display the import edit page if user has edit permission', function () {
    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.imports.edit']);

    $jobId = JobInstances::factory()->importJob()->entityProduct()->create()->id;

    $this->get(route('admin.settings.data_transfer.imports.edit', $jobId))
        ->assertOk()
        ->assertSeeText(trans('admin::app.settings.data-transfer.imports.edit.title'));
});

it('should not display the import edit page if user does not have edit permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.settings.data_transfer.imports.edit', 1))
        ->assertSeeText('Unauthorized')
        ->assertDontSeeText(trans('admin::app.settings.data-transfer.imports.edit.title'));
});

it('should display the import delete action if user has delete permission', function () {
    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.imports.delete']);

    $jobId = JobInstances::factory()->importJob()->entityProduct()->create()->id;

    $this->delete(route('admin.settings.data_transfer.imports.delete', $jobId))
        ->assertOk();

    $this->assertDatabaseMissing($this->getFullTableName(JobInstances::class), ['id' => $jobId]);
});

it('should not allow the import delete action if user does not have delete permission', function () {
    $this->loginWithPermissions();

    $jobId = JobInstances::factory()->importJob()->entityProduct()->create()->id;

    $this->delete(route('admin.settings.data_transfer.imports.delete', $jobId))
        ->assertSeeText('Unauthorized');

    $this->assertDatabaseHas($this->getFullTableName(JobInstances::class), ['id' => $jobId]);
});

it('should display the import now action if user has the execute permission', function () {
    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.imports.execute']);

    $jobId = JobInstances::factory()->importJob()->entityProduct()->create()->id;

    $this->put(route('admin.settings.data_transfer.imports.import_now', $jobId))
        ->assertRedirect()
        ->assertDontSeeText('Unauthorized');
});

it('should not allow the import now action if user does not have the execute permission', function () {
    $this->loginWithPermissions();

    $this->put(route('admin.settings.data_transfer.imports.import_now', 1))
        ->assertSeeText('Unauthorized');
});
