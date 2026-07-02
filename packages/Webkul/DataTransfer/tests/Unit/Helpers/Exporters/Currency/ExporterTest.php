<?php

use Webkul\Core\Models\Currency;
use Webkul\Core\Repositories\CurrencyRepository;
use Webkul\DataTransfer\Helpers\Exporters\Currency\Exporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

it('exports all currencies when status filter is set to all', function () {
    Currency::factory()->create(['code' => 'USD', 'status' => 1]);
    Currency::factory()->create(['code' => 'EUR', 'status' => 0]);

    $jobInstance = JobInstances::create([
        'code'                => 'currency_export',
        'entity_type'         => 'currencies',
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'filters'             => ['status' => 'all', 'file_format' => 'Csv'],
    ]);

    $jobTrack = JobTrack::create([
        'state'               => 'pending',
        'type'                => $jobInstance->type,
        'action'              => $jobInstance->action,
        'validation_strategy' => $jobInstance->validation_strategy,
        'job_instances_id'    => $jobInstance->id,
        'meta'                => $jobInstance->toArray(),
    ]);

    $exporter = new Exporter(
        app(JobTrackBatchRepository::class),
        app(FileExportFileBuffer::class)
    );

    $exporter->setSource(app(CurrencyRepository::class));
    $exporter->setExport($jobTrack);

    $reflection = new ReflectionClass($exporter);
    $method = $reflection->getMethod('getResults');
    $method->setAccessible(true);
    $results = iterator_to_array($method->invoke($exporter));

    expect(count($results))->toBeGreaterThanOrEqual(2);

    $codes = array_map(fn ($c) => $c->code, $results);
    expect($codes)->toContain('USD');
    expect($codes)->toContain('EUR');
});

it('exports only enabled currencies when status filter is set to enable', function () {
    Currency::query()->delete();
    Currency::factory()->create(['code' => 'USD', 'status' => 1]);
    Currency::factory()->create(['code' => 'EUR', 'status' => 0]);

    $jobInstance = JobInstances::create([
        'code'                => 'currency_export_enabled',
        'entity_type'         => 'currencies',
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'filters'             => ['status' => 'enable', 'file_format' => 'Csv'],
    ]);

    $jobTrack = JobTrack::create([
        'state'               => 'pending',
        'type'                => $jobInstance->type,
        'action'              => $jobInstance->action,
        'validation_strategy' => $jobInstance->validation_strategy,
        'job_instances_id'    => $jobInstance->id,
        'meta'                => $jobInstance->toArray(),
    ]);

    $exporter = new Exporter(
        app(JobTrackBatchRepository::class),
        app(FileExportFileBuffer::class)
    );

    $exporter->setSource(app(CurrencyRepository::class));
    $exporter->setExport($jobTrack);

    $reflection = new ReflectionClass($exporter);
    $method = $reflection->getMethod('getResults');
    $method->setAccessible(true);
    $results = iterator_to_array($method->invoke($exporter));

    $codes = array_map(fn ($c) => $c->code, $results);
    expect($codes)->toContain('USD');
    expect($codes)->not->toContain('EUR');
});
