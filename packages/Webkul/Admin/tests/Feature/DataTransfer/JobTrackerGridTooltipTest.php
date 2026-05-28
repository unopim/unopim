<?php

use Webkul\DataTransfer\Models\JobTrack;

it('should render a translated tooltip for the view action on job tracker grid, not the raw translation key', function () {
    $this->loginAsAdmin();

    JobTrack::factory()->create();

    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->getJson(route('admin.settings.data_transfer.tracker.index'));

    $response->assertOk();

    $titles = collect($response->json('actions'))->pluck('title')->toArray();

    foreach ($titles as $title) {
        expect($title)->not->toStartWith('admin::');
    }
});
