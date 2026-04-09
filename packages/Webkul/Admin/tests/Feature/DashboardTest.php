<?php

use Illuminate\Support\Facades\DB;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;

use function Pest\Laravel\getJson;

it('should return latest 10 data transfer jobs with correct processed rows from summary', function () {
    $this->loginAsAdmin();

    // Clean up pre-existing records to ensure only test data is queried
    DB::table('job_track')->delete();

    $importInstance = JobInstances::factory()->importJob()->entityProduct()->create();
    $exportInstance = JobInstances::factory()->exportJob()->entityCategory()->create();

    // Create 12 jobs so we can verify limit is 10
    foreach (range(1, 6) as $i) {
        JobTrack::factory()->create([
            'state'                => 'completed',
            'type'                 => 'import',
            'job_instances_id'     => $importInstance->id,
            'processed_rows_count' => 0,
            'summary'              => ['processed' => $i * 10, 'created' => $i * 10, 'skipped' => 0],
            'started_at'           => now()->subMinutes(20 - $i),
            'completed_at'         => now()->subMinutes(10 - $i),
            'created_at'           => now()->subMinutes(20 - $i),
        ]);

        JobTrack::factory()->export()->create([
            'state'                => 'completed',
            'type'                 => 'export',
            'job_instances_id'     => $exportInstance->id,
            'processed_rows_count' => 0,
            'summary'              => ['processed' => $i * 5, 'created' => $i * 5, 'skipped' => 0],
            'started_at'           => now()->subMinutes(20 - $i),
            'completed_at'         => now()->subMinutes(10 - $i),
            'created_at'           => now()->subMinutes(19 - $i),
        ]);
    }

    $response = getJson(route('admin.dashboard.stats', ['type' => 'data-transfer-status']));

    $response->assertOk();

    $data = $response->json('statistics');

    // Should return exactly 10 recent jobs (not 5)
    expect($data['recentJobs'])->toHaveCount(10);

    // Verify processed_rows_count comes from summary, not the raw column (which is always 0)
    foreach ($data['recentJobs'] as $job) {
        expect($job['processed_rows_count'])->toBeGreaterThan(0);
    }

    // Verify entity_type is present
    $entityTypes = collect($data['recentJobs'])->pluck('entity_type')->unique()->values()->toArray();
    expect($entityTypes)->toContain('products');
    expect($entityTypes)->toContain('categories');

    // Verify jobSummary exists
    expect($data['jobSummary'])->toHaveKey('completed');
});

it('should return correct job type from job_instances table', function () {
    $this->loginAsAdmin();

    $exportInstance = JobInstances::factory()->exportJob()->entityCategory()->create();

    JobTrack::factory()->export()->create([
        'job_instances_id'     => $exportInstance->id,
        'state'                => 'completed',
        'type'                 => 'export',
        'processed_rows_count' => 0,
        'summary'              => ['processed' => 42, 'created' => 42, 'skipped' => 0],
        'started_at'           => now()->subMinutes(5),
        'completed_at'         => now(),
    ]);

    $response = getJson(route('admin.dashboard.stats', ['type' => 'data-transfer-status']));

    $response->assertOk();

    $recentJobs = $response->json('statistics.recentJobs');
    $job = collect($recentJobs)->firstWhere('entity_type', 'categories');

    expect($job)->not->toBeNull();
    expect($job['job_code'])->toBe($exportInstance->code);
    expect($job['type'])->toBe('export');
    expect($job['entity_type'])->toBe('categories');
    expect($job['processed_rows_count'])->toBe(42);
});
