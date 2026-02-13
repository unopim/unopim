<?php

use Psr\Log\LoggerInterface;
use Webkul\DataTransfer\Services\JobLogger;

/*
|--------------------------------------------------------------------------
| JobLogger Tenant Prefix Tests
|--------------------------------------------------------------------------
|
| Verifies that JobLogger::make() and JobLogger::getJobLogPath() include
| tenant-specific prefixes in log paths when tenant context is active.
|
*/

// -- getJobLogPath with tenant context ------------------------------------

it('getJobLogPath includes tenant prefix when tenant is active', function () {
    $this->actingAsTenant($this->tenantA);

    $path = JobLogger::getJobLogPath(42);

    expect($path)->toBe("logs/tenant-{$this->tenantA->id}/job-tracker/42/job.log");
});

// -- getJobLogPath without tenant context ---------------------------------

it('getJobLogPath returns standard path in platform mode', function () {
    $this->clearTenantContext();

    $path = JobLogger::getJobLogPath(42);

    expect($path)->toBe('logs/job-tracker/42/job.log');
});

// -- Path changes when switching tenants ----------------------------------

it('getJobLogPath changes prefix when switching tenants', function () {
    $this->actingAsTenant($this->tenantA);
    $pathA = JobLogger::getJobLogPath(1);

    $this->actingAsTenant($this->tenantB);
    $pathB = JobLogger::getJobLogPath(1);

    expect($pathA)->not->toBe($pathB);
    expect($pathA)->toContain("tenant-{$this->tenantA->id}");
    expect($pathB)->toContain("tenant-{$this->tenantB->id}");
});

// -- make() creates a valid logger ----------------------------------------

it('make() creates a LoggerInterface instance with tenant context', function () {
    $this->actingAsTenant($this->tenantA);

    $logger = JobLogger::make(99);

    expect($logger)->toBeInstanceOf(LoggerInterface::class);
});

it('make() creates a LoggerInterface instance without tenant context', function () {
    $this->clearTenantContext();

    $logger = JobLogger::make(99);

    expect($logger)->toBeInstanceOf(LoggerInterface::class);
});

// -- Path structure verification ------------------------------------------

it('tenant prefix follows tenant-{id}/ pattern', function () {
    $this->actingAsTenant($this->tenantA);

    $path = JobLogger::getJobLogPath(55);

    expect($path)->toMatch('/^logs\/tenant-\d+\/job-tracker\/55\/job\.log$/');
});

it('platform path has no tenant prefix', function () {
    $this->clearTenantContext();

    $path = JobLogger::getJobLogPath(55);

    expect($path)->not->toContain('tenant-');
    expect($path)->toBe('logs/job-tracker/55/job.log');
});
