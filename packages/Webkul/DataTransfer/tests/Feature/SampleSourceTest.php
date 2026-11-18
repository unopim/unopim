<?php

use Webkul\DataTransfer\Services\SampleFiles;

beforeEach(function () {
    $this->sampleSource = tempnam(sys_get_temp_dir(), 'sample-source-');

    file_put_contents($this->sampleSource, "code,name\nred,Red\n");

    config(['importers.packaged_records' => [
        'sample_path'   => SampleFiles::PUBLIC_PREFIX.'packaged-records.csv',
        'sample_source' => $this->sampleSource,
        'samples'       => [
            'extended' => [
                'path'   => SampleFiles::PUBLIC_PREFIX.'packaged-records-extended.csv',
                'label'  => 'data_transfer::app.samples.default',
                'source' => $this->sampleSource,
            ],
        ],
    ]]);
});

afterEach(function () {
    @unlink($this->sampleSource);
});

it('resolves a sample another package ships through sample_source', function () {
    $samples = app(SampleFiles::class);

    expect($samples->all('importers', 'packaged_records')['default']['source'])->toBe($this->sampleSource)
        ->and($samples->path('importers', 'packaged_records'))->toBe($this->sampleSource)
        ->and($samples->path('importers', 'packaged_records', 'extended'))->toBe($this->sampleSource);
});

it('ignores sample_source for the images archive', function () {
    config(['importers.packaged_records.sample_images_path' => SampleFiles::PUBLIC_PREFIX.'missing-images.zip']);

    expect(app(SampleFiles::class)->path('importers', 'packaged_records', images: true))->toBeNull();
});

it('downloads a sample shipped through sample_source', function () {
    $this->loginAsAdmin();

    $response = $this->get(route('admin.settings.data_transfer.imports.download_sample', 'packaged_records'))
        ->assertOk();

    expect($response->baseResponse->getFile()->getPathname())->toBe($this->sampleSource);
});
