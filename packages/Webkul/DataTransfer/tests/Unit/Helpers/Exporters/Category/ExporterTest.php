<?php

use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\DataTransfer\Contracts\JobTrack;
use Webkul\DataTransfer\Helpers\Exporters\Category\Exporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

/**
 * Build a category Exporter whose job instance carries exactly the given filters.
 */
function makeCategoryExporter(array $filters): Exporter
{
    $jobInstance = new stdClass;
    $jobInstance->code = 'category-export';
    $jobInstance->entity_type = 'categories';
    $jobInstance->filters = $filters;

    $exportTrack = Mockery::mock(JobTrack::class);
    $exportTrack->id = 1;
    $exportTrack->jobInstance = $jobInstance;
    $exportTrack->shouldReceive('getAttribute')
        ->with('jobInstance')
        ->andReturn($jobInstance);

    $exporter = new Exporter(
        app(JobTrackBatchRepository::class),
        app(FileExportFileBuffer::class),
        app(CategoryFieldRepository::class),
    );

    $exporter->setExport($exportTrack);

    return $exporter;
}

function callSetFieldsAdditionalData(Exporter $exporter, array $additionalData): array
{
    $method = new ReflectionMethod($exporter, 'setFieldsAdditionalData');
    $method->setAccessible(true);

    return $method->invoke($exporter, $additionalData, 'exports/1/categories');
}

it('exports categories when the job instance filters omit with media', function () {
    $exporter = makeCategoryExporter(['file_format' => 'Csv']);

    $result = callSetFieldsAdditionalData($exporter, ['name' => 'Root']);

    expect($result)->toBeArray();
});

it('exports categories when the job instance carries no filters at all', function () {
    $exporter = makeCategoryExporter([]);

    $result = callSetFieldsAdditionalData($exporter, ['name' => 'Root']);

    expect($result)->toBeArray();
});
