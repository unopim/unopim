<?php

use Illuminate\Support\Facades\Mail;
use Webkul\Tenant\Jobs\TenantAwareJob;
use Webkul\Tenant\Jobs\TenantSandbox;
use Webkul\Tenant\Models\Tenant;

beforeEach(function () {
    Mail::fake();
});

// --- Story 4.1: TenantAwareJob trait & TenantSandbox ---

it('captures tenant context at dispatch time', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
    core()->setCurrentTenantId($tenant->id);

    $job = new TestTenantJob('test-data');

    expect($job->tenantId)->toBe($tenant->id);

    core()->setCurrentTenantId(null);
});

it('serializes tenant_id into job payload (FR28)', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
    core()->setCurrentTenantId($tenant->id);

    $job = new TestTenantJob('test-data');

    // Simulate serialization (like queue would do)
    $serialized = serialize($job);
    $unserialized = unserialize($serialized);

    expect($unserialized->tenantId)->toBe($tenant->id);

    core()->setCurrentTenantId(null);
});

it('TenantSandbox restores tenant context before execution (FR29)', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);

    // Start with no tenant context (simulates a queue worker)
    core()->setCurrentTenantId(null);

    $job = new TestTenantJob('test-data');
    $job->tenantId = $tenant->id;

    $sandbox = new TenantSandbox;
    $capturedTenantId = null;

    $sandbox->handle($job, function ($job) use (&$capturedTenantId) {
        $capturedTenantId = core()->getCurrentTenantId();
    });

    expect($capturedTenantId)->toBe($tenant->id);
});

it('TenantSandbox clears context after execution (FR31)', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);

    $job = new TestTenantJob('test-data');
    $job->tenantId = $tenant->id;

    $sandbox = new TenantSandbox;
    $sandbox->handle($job, function ($job) {
        // Job runs here with context set
    });

    // After sandbox, context should be cleared
    expect(core()->getCurrentTenantId())->toBeNull();
});

it('TenantSandbox clears context even on exception', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);

    $job = new TestTenantJob('test-data');
    $job->tenantId = $tenant->id;

    $sandbox = new TenantSandbox;

    try {
        $sandbox->handle($job, function ($job) {
            throw new \RuntimeException('Job failed');
        });
    } catch (\RuntimeException) {
        // Expected
    }

    expect(core()->getCurrentTenantId())->toBeNull();
});

it('prevents context contamination between sequential jobs', function () {
    $tenant1 = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
    $tenant2 = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);

    $sandbox = new TenantSandbox;
    $capturedIds = [];

    // Job 1 for tenant 1
    $job1 = new TestTenantJob('data-1');
    $job1->tenantId = $tenant1->id;
    $sandbox->handle($job1, function () use (&$capturedIds) {
        $capturedIds[] = core()->getCurrentTenantId();
    });

    // Job 2 for tenant 2
    $job2 = new TestTenantJob('data-2');
    $job2->tenantId = $tenant2->id;
    $sandbox->handle($job2, function () use (&$capturedIds) {
        $capturedIds[] = core()->getCurrentTenantId();
    });

    expect($capturedIds[0])->toBe($tenant1->id);
    expect($capturedIds[1])->toBe($tenant2->id);
    expect(core()->getCurrentTenantId())->toBeNull();
});

it('provides middleware method returning TenantSandbox', function () {
    $job = new TestTenantJob('test');
    $middleware = $job->middleware();

    expect($middleware)->toBeArray();
    expect($middleware[0])->toBeInstanceOf(TenantSandbox::class);
});

// --- Story 4.4: Failed job context retention ---

it('retains tenant_id in serialized payload for failed jobs (FR30)', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
    core()->setCurrentTenantId($tenant->id);

    $job = new TestTenantJob('will-fail');

    // Simulate what happens when a job fails: payload is serialized
    $payload = json_encode([
        'job'      => serialize($job),
        'tenantId' => $job->tenantId,
    ]);

    // Simulate retry: deserialize and check tenant_id is preserved
    $decoded = json_decode($payload, true);
    $retryJob = unserialize($decoded['job']);

    expect($retryJob->tenantId)->toBe($tenant->id);

    core()->setCurrentTenantId(null);
});

// --- Story 4.5: Queue fairness ---

it('resolves tenant-specific queue names for fairness (FR33)', function () {
    $job = new TestTenantJob('test');
    $job->tenantId = 42;

    $queue = $job->resolveTenantQueue('imports');
    expect($queue)->toBe('tenant-42-imports');

    $defaultQueue = $job->resolveTenantQueue();
    expect($defaultQueue)->toBe('tenant-42-default');
});

it('uses default queue when no tenant context', function () {
    $job = new TestTenantJob('test');
    $job->tenantId = null;

    $queue = $job->resolveTenantQueue('imports');
    expect($queue)->toBe('imports');
});

// --- Test helper class ---
class TestTenantJob
{
    use TenantAwareJob;

    public function __construct(public string $data)
    {
        $this->captureTenantContext();
    }

    public function handle(): void
    {
        // no-op for testing
    }
}
