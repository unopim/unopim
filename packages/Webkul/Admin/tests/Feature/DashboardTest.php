<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\Product\Models\Product;

use function Pest\Laravel\get;
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

it('should return product stats with correct status breakdown', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::first();

    // Create active products
    Product::factory()->simple()->create([
        'attribute_family_id' => $family->id,
        'status'              => 1,
    ]);

    Product::factory()->simple()->create([
        'attribute_family_id' => $family->id,
        'status'              => 1,
    ]);

    // Create an inactive product
    Product::factory()->simple()->create([
        'attribute_family_id' => $family->id,
        'status'              => 0,
    ]);

    // Clear cache so fresh stats reflect newly created products
    Cache::forget('dashboard.product_stats');

    $response = getJson(route('admin.dashboard.stats', ['type' => 'product-stats']));

    $response->assertOk();

    $stats = $response->json('statistics');

    expect($stats)->toHaveKey('statusBreakdown');
    expect($stats['statusBreakdown'])->toHaveKey('active');
    expect($stats['statusBreakdown'])->toHaveKey('inactive');
    expect($stats['statusBreakdown']['active'])->toBeGreaterThanOrEqual(2);
    expect($stats['statusBreakdown']['inactive'])->toBeGreaterThanOrEqual(1);
    expect($stats['totalProducts'])->toBeGreaterThanOrEqual(3);
});

it('should filter products by status when filters are passed', function () {
    $this->loginAsAdmin();

    // Disable Elasticsearch so the DataGrid queries MySQL directly,
    // avoiding indexing delays with newly created factory products.
    $esEnabled = config('elasticsearch.enabled');
    config(['elasticsearch.enabled' => false]);

    $family = AttributeFamily::first();

    // Create active and inactive products
    $activeProduct = Product::factory()->simple()->create([
        'attribute_family_id' => $family->id,
        'status'              => 1,
    ]);

    $inactiveProduct = Product::factory()->simple()->create([
        'attribute_family_id' => $family->id,
        'status'              => 0,
    ]);

    // Request the product datagrid with status filter for inactive (0)
    $response = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
        ->json('GET', route('admin.catalog.products.index'), [
            'pagination' => [
                'per_page' => 100,
            ],
            'filters' => [
                'status' => ['0'],
            ],
        ]);

    $response->assertOk();

    $records = $response->json('records');

    // All returned records should be inactive (status = 0)
    foreach ($records as $record) {
        expect($record['status'])->toContain(trans('admin::app.common.disable'));
    }

    // The inactive product should be in the results
    $skus = collect($records)->pluck('sku')->toArray();
    expect($skus)->toContain($inactiveProduct->sku);

    // Restore original config
    config(['elasticsearch.enabled' => $esEnabled]);
});

it('should invalidate dashboard cache when product is created', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::first();

    // Prime the cache
    Cache::forget('dashboard.product_stats');
    getJson(route('admin.dashboard.stats', ['type' => 'product-stats']))->assertOk();
    expect(Cache::has('dashboard.product_stats'))->toBeTrue();

    // Creating a product should bust the cache via the observer
    Product::factory()->simple()->create([
        'attribute_family_id' => $family->id,
        'status'              => 1,
    ]);

    expect(Cache::has('dashboard.product_stats'))->toBeFalse();
    expect(Cache::has('dashboard.total_catalogs'))->toBeFalse();
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

it('should return 403 not 401 when authenticated user lacks dashboard permission', function () {
    // Create a user with custom role that has NO dashboard permission
    $this->loginWithPermissions('custom', ['catalog.products']);

    get(route('admin.dashboard.index'))
        ->assertStatus(403);
});
