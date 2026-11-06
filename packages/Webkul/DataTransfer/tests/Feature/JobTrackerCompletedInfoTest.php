<?php

use Webkul\DataTransfer\Models\JobTrack;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('shows the completed note an exporter registers in completed_info', function () {
    config(['exporters.products.completed_info' => 'The records were sent to the hub.']);

    $jobTrack = JobTrack::factory()->export()->completed()->create();

    $this->get(route('admin.settings.data_transfer.tracker.view', $jobTrack->id))
        ->assertOk()
        ->assertSee('The records were sent to the hub.')
        ->assertDontSee(trans('admin::app.settings.data-transfer.tracker.completed-info-sub'));
});

it('falls back to the default completed note without completed_info', function () {
    config(['importers.products.completed_info' => null]);

    $jobTrack = JobTrack::factory()->completed()->create();

    $this->get(route('admin.settings.data_transfer.tracker.view', $jobTrack->id))
        ->assertOk()
        ->assertSee(trans('admin::app.settings.data-transfer.tracker.completed-info-sub'));
});
