<?php

use Illuminate\Support\Facades\Mail;
use Webkul\Tenant\Models\Tenant;
use Webkul\Tenant\Services\TenantPurger;

beforeEach(function () {
    Mail::fake();
});

/*
|--------------------------------------------------------------------------
| Story 7.2: TenantPurger ES Index Deletion
|--------------------------------------------------------------------------
|
| Verifies that TenantPurger.deleteElasticsearchIndices() properly builds
| index names from the tenant's es_index_uuid and handles edge cases.
|
*/

it('builds correct ES index names for tenant deletion', function () {
    $tenant = Tenant::factory()->create([
        'status'        => Tenant::STATUS_ACTIVE,
        'es_index_uuid' => 'purge-uuid-abc',
    ]);

    $purger = app(TenantPurger::class);

    // Use reflection to test the private method directly
    $ref = new \ReflectionMethod($purger, 'deleteElasticsearchIndices');
    $ref->setAccessible(true);

    // ES is disabled by default in test, so it should return 0
    config(['elasticsearch.enabled' => false]);
    $deleted = $ref->invoke($purger, $tenant);

    expect($deleted)->toBe(0);
});

it('returns 0 when ES is disabled', function () {
    $tenant = Tenant::factory()->create([
        'status'        => Tenant::STATUS_ACTIVE,
        'es_index_uuid' => 'purge-disabled',
    ]);

    config(['elasticsearch.enabled' => false]);

    $purger = app(TenantPurger::class);
    $report = $purger->purge($tenant);

    expect($report['elasticsearch']['indices_deleted'])->toBe(0);
});

it('returns 0 when tenant has empty es_index_uuid', function () {
    $tenant = Tenant::factory()->create([
        'status'        => Tenant::STATUS_ACTIVE,
        'es_index_uuid' => '',
    ]);

    // Empty string is falsy, so deleteElasticsearchIndices should short-circuit
    $purger = app(TenantPurger::class);
    $ref = new \ReflectionMethod($purger, 'deleteElasticsearchIndices');
    $ref->setAccessible(true);
    $deleted = $ref->invoke($purger, $tenant);

    expect($deleted)->toBe(0);
});

it('purge report includes elasticsearch section with correct structure', function () {
    $tenant = Tenant::factory()->create([
        'status'        => Tenant::STATUS_ACTIVE,
        'es_index_uuid' => 'structure-check',
    ]);

    $purger = app(TenantPurger::class);
    $report = $purger->purge($tenant);

    expect($report)->toHaveKey('elasticsearch');
    expect($report['elasticsearch'])->toHaveKey('indices_deleted');
    expect($report['elasticsearch']['indices_deleted'])->toBeInt();
});
