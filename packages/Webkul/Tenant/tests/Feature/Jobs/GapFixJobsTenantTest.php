<?php

use Webkul\Core\Jobs\UpdateCreateVisitableIndex;
use Webkul\Core\Jobs\UpdateCreateVisitIndex;
use Webkul\Tenant\Jobs\TenantAwareJob;
use Webkul\Tenant\Jobs\TenantSandbox;
use Webkul\Webhook\Jobs\SendBulkProductWebhook;

/*
|--------------------------------------------------------------------------
| Gap-Fix Job Tenant Context Tests
|--------------------------------------------------------------------------
|
| Verifies that the three jobs identified in the tenant isolation audit
| now properly use TenantAwareJob to capture and restore tenant context.
|
*/

// -- Structural: trait presence -------------------------------------------

it('SendBulkProductWebhook uses TenantAwareJob trait', function () {
    expect(class_uses_recursive(SendBulkProductWebhook::class))
        ->toContain(TenantAwareJob::class);
});

it('UpdateCreateVisitIndex uses TenantAwareJob trait', function () {
    expect(class_uses_recursive(UpdateCreateVisitIndex::class))
        ->toContain(TenantAwareJob::class);
});

it('UpdateCreateVisitableIndex uses TenantAwareJob trait', function () {
    expect(class_uses_recursive(UpdateCreateVisitableIndex::class))
        ->toContain(TenantAwareJob::class);
});

// -- Context capture at dispatch time -------------------------------------

it('SendBulkProductWebhook captures tenant context on construction', function () {
    $this->actingAsTenant($this->tenantA);

    $job = new SendBulkProductWebhook([1, 2, 3], $this->fixture($this->tenantA, 'admin_id'));

    expect($job->tenantId)->toBe($this->tenantA->id);
});

it('UpdateCreateVisitIndex captures tenant context on construction', function () {
    $this->actingAsTenant($this->tenantA);

    $job = new UpdateCreateVisitIndex(null, [
        'method'       => 'GET',
        'url'          => '/test',
        'ip'           => '127.0.0.1',
        'visitor_id'   => 1,
        'visitor_type' => 'admin',
    ]);

    expect($job->tenantId)->toBe($this->tenantA->id);
});

it('UpdateCreateVisitableIndex captures tenant context on construction', function () {
    $this->actingAsTenant($this->tenantA);

    $job = new UpdateCreateVisitableIndex([
        'path_info'    => '/test-product',
        'method'       => 'GET',
        'url'          => '/test-product',
        'ip'           => '127.0.0.1',
        'visitor_id'   => 1,
        'visitor_type' => 'admin',
    ]);

    expect($job->tenantId)->toBe($this->tenantA->id);
});

// -- Context capture with different tenants -------------------------------

it('captures different tenant context for each tenant', function () {
    $this->actingAsTenant($this->tenantA);
    $jobA = new SendBulkProductWebhook([1], $this->fixture($this->tenantA, 'admin_id'));

    $this->actingAsTenant($this->tenantB);
    $jobB = new SendBulkProductWebhook([2], $this->fixture($this->tenantB, 'admin_id'));

    expect($jobA->tenantId)->toBe($this->tenantA->id);
    expect($jobB->tenantId)->toBe($this->tenantB->id);
    expect($jobA->tenantId)->not->toBe($jobB->tenantId);
});

// -- Null context (platform mode) -----------------------------------------

it('captures null tenant context in platform mode', function () {
    $this->clearTenantContext();

    $job = new UpdateCreateVisitableIndex([
        'path_info'    => '/test',
        'method'       => 'GET',
        'url'          => '/test',
        'ip'           => '127.0.0.1',
        'visitor_id'   => 1,
        'visitor_type' => 'admin',
    ]);

    expect($job->tenantId)->toBeNull();
});

// -- Middleware presence ---------------------------------------------------

it('gap-fix jobs provide TenantSandbox middleware', function () {
    $job1 = new SendBulkProductWebhook([1], 1);
    $job2 = new UpdateCreateVisitIndex(null, ['method' => 'GET']);
    $job3 = new UpdateCreateVisitableIndex(['path_info' => '/x']);

    expect($job1->middleware())->toBeArray();
    expect($job1->middleware()[0])->toBeInstanceOf(TenantSandbox::class);

    expect($job2->middleware())->toBeArray();
    expect($job2->middleware()[0])->toBeInstanceOf(TenantSandbox::class);

    expect($job3->middleware())->toBeArray();
    expect($job3->middleware()[0])->toBeInstanceOf(TenantSandbox::class);
});

// -- Serialization preserves tenant context -------------------------------

it('serialization preserves tenant context for gap-fix jobs', function () {
    $this->actingAsTenant($this->tenantA);

    $job = new SendBulkProductWebhook([1, 2], $this->fixture($this->tenantA, 'admin_id'));

    $serialized = serialize($job);
    $unserialized = unserialize($serialized);

    expect($unserialized->tenantId)->toBe($this->tenantA->id);
});
