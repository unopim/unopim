<?php

use Illuminate\Support\Facades\Queue;
use Webkul\DataTransfer\Jobs\Export\ExportTrackBatch;
use Webkul\DataTransfer\Models\JobInstances;

use function Pest\Laravel\get;

it('renders the export now control as an ajax form', function () {
    $this->loginAsAdmin();

    $export = JobInstances::factory()->exportJob()->entityProduct()->create();

    get(route('admin.settings.data_transfer.exports.export-view', $export->id))
        ->assertOk()
        ->assertSee('data-ajax-form="true"', false)
        ->assertSee(route('admin.settings.data_transfer.exports.export_now', ['id' => $export->id]), false);
});

it('starts the export without a page redirect when submitted through the ajax form', function () {
    Queue::fake();

    $this->loginAsAdmin();

    $export = JobInstances::factory()->exportJob()->entityProduct()->create();

    $response = $this->withHeader('X-Ajax-Form', 'true')
        ->put(route('admin.settings.data_transfer.exports.export_now', ['id' => $export->id]));

    $response->assertOk()
        ->assertJsonPath('message', trans('admin::app.settings.data-transfer.exports.batch.title'))
        ->assertJsonStructure(['message', 'redirect_url']);

    expect($response->json('redirect_url'))->toContain('job-tracker');

    Queue::assertPushed(ExportTrackBatch::class);
});

it('keeps returning a redirect for a plain form submit', function () {
    Queue::fake();

    $this->loginAsAdmin();

    $export = JobInstances::factory()->exportJob()->entityProduct()->create();

    $this->put(route('admin.settings.data_transfer.exports.export_now', ['id' => $export->id]))
        ->assertRedirect();
});
