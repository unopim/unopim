<?php

use Webkul\DataTransfer\Contracts\Exporters\WritesExportFile;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\AbstractExporter;
use Webkul\DataTransfer\Models\JobTrack;

it('lets an exporter write its own export file instead of the flat file writer', function () {
    $jobTrack = JobTrack::factory()->export()->create();

    $rows = [['code' => 'red'], ['code' => 'blue']];

    $exporter = Mockery::mock(AbstractExporter::class, WritesExportFile::class);
    $exporter->shouldReceive('writeExportFile')->once()->with($rows)->andReturn('exports/'.$jobTrack->id.'/records.jsonl');
    $exporter->shouldNotReceive('getFilters', 'getFileName', 'getExportParameter');

    $helper = app(Export::class)->setExport($jobTrack);

    (new ReflectionProperty(Export::class, 'typeExporter'))->setValue($helper, $exporter);

    $helper->flush($rows);

    expect($jobTrack->fresh()->file_path)->toBe('exports/'.$jobTrack->id.'/records.jsonl');
});
