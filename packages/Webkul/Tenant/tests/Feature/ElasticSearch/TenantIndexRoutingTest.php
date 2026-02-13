<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Webkul\Tenant\Models\Tenant;

beforeEach(function () {
    Mail::fake();
});

it('generates tenant-specific ES index name when tenant context exists', function () {
    $tenant = Tenant::factory()->create([
        'status'        => Tenant::STATUS_ACTIVE,
        'es_index_uuid' => 'test-uuid-1234',
    ]);

    core()->setCurrentTenantId($tenant->id);

    // Use reflection to test the observer's getIndexName
    $normalizer = app(\Webkul\ElasticSearch\Indexing\Normalizer\ProductNormalizer::class);
    $observer = new \Webkul\ElasticSearch\Observers\Product($normalizer);

    $reflection = new \ReflectionMethod($observer, 'getIndexName');
    $reflection->setAccessible(true);
    $indexName = $reflection->invoke($observer);

    $prefix = config('elasticsearch.prefix') ?? '';
    expect($indexName)->toContain('_tenant_test-uuid-1234_products');
    expect($indexName)->toBe(strtolower($prefix.'_tenant_test-uuid-1234_products'));

    core()->setCurrentTenantId(null);
});

it('uses global index name when no tenant context', function () {
    core()->setCurrentTenantId(null);

    $normalizer = app(\Webkul\ElasticSearch\Indexing\Normalizer\ProductNormalizer::class);
    $observer = new \Webkul\ElasticSearch\Observers\Product($normalizer);

    $reflection = new \ReflectionMethod($observer, 'getIndexName');
    $reflection->setAccessible(true);
    $indexName = $reflection->invoke($observer);

    $prefix = config('elasticsearch.prefix') ?? '';
    expect($indexName)->toBe(strtolower($prefix.'_products'));
    expect($indexName)->not->toContain('_tenant_');
});

it('falls back to tenant_id when tenants table query fails', function () {
    // Use a non-existent tenant ID to trigger the catch fallback in resolveTenantIndexSuffix
    $nonExistentId = 99999;
    core()->setCurrentTenantId($nonExistentId);

    $normalizer = app(\Webkul\ElasticSearch\Indexing\Normalizer\ProductNormalizer::class);
    $observer = new \Webkul\ElasticSearch\Observers\Product($normalizer);

    $reflection = new \ReflectionMethod($observer, 'getIndexName');
    $reflection->setAccessible(true);
    $indexName = $reflection->invoke($observer);

    // Should fall back to using the tenant ID directly
    expect($indexName)->toContain("_tenant_{$nonExistentId}_products");

    core()->setCurrentTenantId(null);
});

it('generates different index names for different tenants', function () {
    $tenant1 = Tenant::factory()->create([
        'status'        => Tenant::STATUS_ACTIVE,
        'es_index_uuid' => 'uuid-alpha',
    ]);
    $tenant2 = Tenant::factory()->create([
        'status'        => Tenant::STATUS_ACTIVE,
        'es_index_uuid' => 'uuid-beta',
    ]);

    $normalizer = app(\Webkul\ElasticSearch\Indexing\Normalizer\ProductNormalizer::class);

    core()->setCurrentTenantId($tenant1->id);
    $observer1 = new \Webkul\ElasticSearch\Observers\Product($normalizer);
    $ref = new \ReflectionMethod($observer1, 'getIndexName');
    $ref->setAccessible(true);
    $name1 = $ref->invoke($observer1);

    core()->setCurrentTenantId($tenant2->id);
    $observer2 = new \Webkul\ElasticSearch\Observers\Product($normalizer);
    $name2 = $ref->invoke($observer2);

    expect($name1)->not->toBe($name2);
    expect($name1)->toContain('uuid-alpha');
    expect($name2)->toContain('uuid-beta');

    core()->setCurrentTenantId(null);
});
