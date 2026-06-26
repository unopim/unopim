<?php

use Illuminate\Support\Carbon;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;

function productExportRun(): JobTrack
{
    $jobInstance = JobInstances::create([
        'code'                => 'product_export_'.uniqid(),
        'entity_type'         => 'products',
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'filters'             => ['file_format' => 'Csv'],
    ]);

    return JobTrack::create([
        'state'               => Export::STATE_PROCESSING,
        'type'                => $jobInstance->type,
        'action'              => $jobInstance->action,
        'validation_strategy' => $jobInstance->validation_strategy,
        'job_instances_id'    => $jobInstance->id,
        'meta'                => $jobInstance->toArray(),
    ]);
}

function resolveUpdatedAfter(JobTrack $run, array $filters): ?string
{
    return resolveTimeBound($run, 'resolveUpdatedAfter', $filters);
}

function resolveUpdatedBefore(JobTrack $run, array $filters): ?string
{
    return resolveTimeBound($run, 'resolveUpdatedBefore', $filters);
}

function resolveTimeBound(JobTrack $run, string $method, array $filters): ?string
{
    $exporter = app(Exporter::class);
    $exporter->setExport($run);

    $reflection = new ReflectionMethod($exporter, $method);
    $reflection->setAccessible(true);

    return $reflection->invoke($exporter, $filters);
}

it('returns null when there is no time condition', function () {
    expect(resolveUpdatedAfter(productExportRun(), ['time_condition' => 'none']))->toBeNull();
    expect(resolveUpdatedAfter(productExportRun(), []))->toBeNull();
});

it('resolves the last N days into a past date', function () {
    $resolved = resolveUpdatedAfter(productExportRun(), [
        'time_condition' => 'last_n_days',
        'time_value'     => 7,
    ]);

    expect(Carbon::parse($resolved)->toDateString())->toBe(now()->subDays(7)->toDateString());
});

it('resolves the between dates range to the start and end of day bounds', function () {
    $run = productExportRun();

    $filters = [
        'time_condition' => 'between_dates',
        'time_date'      => '2026-01-15',
        'time_date_end'  => '2026-02-20',
    ];

    expect(resolveUpdatedAfter($run, $filters))->toBe('2026-01-15 00:00:00');
    expect(resolveUpdatedBefore($run, $filters))->toBe('2026-02-20 23:59:59');
});

it('returns no upper bound for conditions other than between dates', function () {
    expect(resolveUpdatedBefore(productExportRun(), ['time_condition' => 'last_n_days', 'time_value' => 7]))->toBeNull();
    expect(resolveUpdatedBefore(productExportRun(), ['time_condition' => 'none']))->toBeNull();
    expect(resolveUpdatedBefore(productExportRun(), []))->toBeNull();
});

it('resolves since last export to the previous completed run timestamp', function () {
    $run = productExportRun();
    $completedAt = now()->subDays(3);

    JobTrack::create([
        'state'               => Export::STATE_COMPLETED,
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'job_instances_id'    => $run->job_instances_id,
        'completed_at'        => $completedAt,
        'meta'                => [],
    ]);

    $resolved = resolveUpdatedAfter($run, ['time_condition' => 'since_last_export']);

    expect(Carbon::parse($resolved)->toDateTimeString())->toBe($completedAt->toDateTimeString());
});

it('returns null for since last export when no previous run exists', function () {
    expect(resolveUpdatedAfter(productExportRun(), ['time_condition' => 'since_last_export']))->toBeNull();
});
