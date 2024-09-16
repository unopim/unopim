<?php

use Webkul\DataTransfer\Models\JobInstances;

it('should display the export index page if user has permission', function () {
    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.export']);

    $this->get(route('admin.settings.data_transfer.exports.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.settings.data-transfer.exports.index.title'));
});

it('should not display the export index page if user does not have permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.settings.data_transfer.exports.index'))
        ->assertSeeText('Unauthorized')
        ->assertDontSeeText(trans('admin::app.settings.data-transfer.exports.index.title'));
});

it('should display the export create page if user has permission', function () {
    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.export.create']);

    $this->get(route('admin.settings.data_transfer.exports.create'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.settings.data-transfer.exports.create.title'));
});

it('should not display the export create page if user does not have create permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.settings.data_transfer.exports.create'))
        ->assertSeeText('Unauthorized')
        ->assertDontSeeText(trans('admin::app.settings.data-transfer.exports.create.title'));
});

it('should display the export edit page if user has edit permission', function () {
    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.export.edit']);

    $jobId = JobInstances::factory()->exportJob()->entityProduct()->create()->id;

    $this->get(route('admin.settings.data_transfer.exports.edit', $jobId))
        ->assertOk()
        ->assertSeeText(trans('admin::app.settings.data-transfer.exports.edit.title'));
});

it('should not display the export edit page if user does not have edit permission', function () {
    $this->loginWithPermissions();

    $this->get(route('admin.settings.data_transfer.exports.edit', 1))
        ->assertSeeText('Unauthorized')
        ->assertDontSeeText(trans('admin::app.settings.data-transfer.exports.edit.title'));
});

it('should display the export delete action if user has delete permission', function () {
    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.export.delete']);

    $jobId = JobInstances::factory()->exportJob()->entityProduct()->create()->id;

    $this->delete(route('admin.settings.data_transfer.exports.delete', $jobId))
        ->assertOk();
});

it('should not allow the export delete action if user does not have delete permission', function () {
    $this->loginWithPermissions();

    $this->delete(route('admin.settings.data_transfer.exports.delete', 1))
        ->assertSeeText('Unauthorized');
});

it('should display the export now action if user has execute permission', function () {
    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.export.execute']);

    $jobId = JobInstances::factory()->exportJob()->entityProduct()->create()->id;

    $this->put(route('admin.settings.data_transfer.exports.export_now', $jobId))
        ->assertRedirect()
        ->assertDontSeeText('Unauthorized');
});

it('should not allow the export now action if user does not have execute permission', function () {
    $this->loginWithPermissions();

    $this->put(route('admin.settings.data_transfer.exports.export_now', 1))
        ->assertSeeText('Unauthorized');
});

it('should display the job tracker grid if has correct permisssion', function () {
    $this->loginWithPermissions(permissions: ['data_transfer', 'data_transfer.job_tracker']);

    $this->get(route('admin.settings.data_transfer.tracker.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.settings.data-transfer.tracker.index.title'));
});

it('should not display the job tracker grid if has correct permisssion', function () {
    $this->loginWithPermissions();

    $this->get(route(trans('admin.settings.data_transfer.tracker.index')))
        ->assertSeeText('Unauthorized');
});
