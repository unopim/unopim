<?php

use Illuminate\Support\Facades\Mail;
use Webkul\Completeness\Jobs\BulkProductCompletenessJob;
use Webkul\Completeness\Jobs\ProductCompletenessJob;
use Webkul\Tenant\Jobs\TenantAwareJob;
use Webkul\Tenant\Jobs\TenantSandbox;

beforeEach(function () {
    Mail::fake();
});

/*
|--------------------------------------------------------------------------
| Story 8.3: TenantAwareJob on Completeness Jobs
|--------------------------------------------------------------------------
|
| Verifies that completeness jobs use TenantAwareJob trait,
| capture tenant context, and include tenant in uniqueId.
|
*/

it('ProductCompletenessJob has TenantAwareJob trait', function () {
    $traits = class_uses_recursive(ProductCompletenessJob::class);
    expect($traits)->toContain(TenantAwareJob::class);
});

it('BulkProductCompletenessJob has TenantAwareJob trait', function () {
    $traits = class_uses_recursive(BulkProductCompletenessJob::class);
    expect($traits)->toContain(TenantAwareJob::class);
});

it('ProductCompletenessJob captures tenant context at dispatch time', function () {
    core()->setCurrentTenantId($this->tenantA->id);

    $job = new ProductCompletenessJob([1, 2, 3]);
    expect($job->tenantId)->toBe($this->tenantA->id);
});

it('BulkProductCompletenessJob captures tenant context at dispatch time', function () {
    core()->setCurrentTenantId($this->tenantA->id);

    $job = new BulkProductCompletenessJob([], 42);
    expect($job->tenantId)->toBe($this->tenantA->id);
});

it('BulkProductCompletenessJob uniqueId includes tenant', function () {
    core()->setCurrentTenantId($this->tenantA->id);

    $job = new BulkProductCompletenessJob([], 42);
    expect($job->uniqueId())->toContain((string) $this->tenantA->id);
    expect($job->uniqueId())->toContain('completeness-job-42');
});

it('ProductCompletenessJob provides TenantSandbox middleware', function () {
    $job = new ProductCompletenessJob([1]);
    $middleware = $job->middleware();

    expect($middleware)->toHaveCount(1);
    expect($middleware[0])->toBeInstanceOf(TenantSandbox::class);
});
