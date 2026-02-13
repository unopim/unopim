<?php

use Illuminate\Support\Facades\Mail;
use Webkul\Tenant\Models\Tenant;

beforeEach(function () {
    Mail::fake();
});

/*
|--------------------------------------------------------------------------
| Story 7.1: Category observer uses tenant-aware index routing
|--------------------------------------------------------------------------
*/

it('generates tenant-specific ES category index name when tenant context exists', function () {
    $tenant = Tenant::factory()->create([
        'status'        => Tenant::STATUS_ACTIVE,
        'es_index_uuid' => 'cat-uuid-1234',
    ]);

    core()->setCurrentTenantId($tenant->id);

    $observer = new \Webkul\ElasticSearch\Observers\Category;

    $reflection = new \ReflectionMethod($observer, 'getIndexName');
    $reflection->setAccessible(true);
    $indexName = $reflection->invoke($observer);

    $prefix = config('elasticsearch.prefix') ?? '';
    expect($indexName)->toBe(strtolower($prefix.'_tenant_cat-uuid-1234_categories'));

    core()->setCurrentTenantId(null);
});

it('uses global category index name when no tenant context', function () {
    core()->setCurrentTenantId(null);

    $observer = new \Webkul\ElasticSearch\Observers\Category;

    $reflection = new \ReflectionMethod($observer, 'getIndexName');
    $reflection->setAccessible(true);
    $indexName = $reflection->invoke($observer);

    $prefix = config('elasticsearch.prefix') ?? '';
    expect($indexName)->toBe(strtolower($prefix.'_categories'));
    expect($indexName)->not->toContain('_tenant_');
});

it('falls back to tenant_id when tenants table query fails for category', function () {
    $nonExistentId = 99999;
    core()->setCurrentTenantId($nonExistentId);

    $observer = new \Webkul\ElasticSearch\Observers\Category;

    $reflection = new \ReflectionMethod($observer, 'getIndexName');
    $reflection->setAccessible(true);
    $indexName = $reflection->invoke($observer);

    expect($indexName)->toContain("_tenant_{$nonExistentId}_categories");

    core()->setCurrentTenantId(null);
});

it('generates different category index names for different tenants', function () {
    $tenant1 = Tenant::factory()->create([
        'status'        => Tenant::STATUS_ACTIVE,
        'es_index_uuid' => 'cat-alpha',
    ]);
    $tenant2 = Tenant::factory()->create([
        'status'        => Tenant::STATUS_ACTIVE,
        'es_index_uuid' => 'cat-beta',
    ]);

    core()->setCurrentTenantId($tenant1->id);
    $observer1 = new \Webkul\ElasticSearch\Observers\Category;
    $ref = new \ReflectionMethod($observer1, 'getIndexName');
    $ref->setAccessible(true);
    $name1 = $ref->invoke($observer1);

    core()->setCurrentTenantId($tenant2->id);
    $observer2 = new \Webkul\ElasticSearch\Observers\Category;
    $name2 = $ref->invoke($observer2);

    expect($name1)->not->toBe($name2);
    expect($name1)->toContain('cat-alpha');
    expect($name2)->toContain('cat-beta');

    core()->setCurrentTenantId(null);
});

/*
|--------------------------------------------------------------------------
| Story 7.4: ResolveTenantIndex trait consistency
|--------------------------------------------------------------------------
*/

it('category observer uses same tenant suffix pattern as product observer', function () {
    $tenant = Tenant::factory()->create([
        'status'        => Tenant::STATUS_ACTIVE,
        'es_index_uuid' => 'shared-uuid',
    ]);

    core()->setCurrentTenantId($tenant->id);

    // Product observer
    $normalizer = app(\Webkul\ElasticSearch\Indexing\Normalizer\ProductNormalizer::class);
    $productObserver = new \Webkul\ElasticSearch\Observers\Product($normalizer);
    $productRef = new \ReflectionMethod($productObserver, 'getIndexName');
    $productRef->setAccessible(true);
    $productIndex = $productRef->invoke($productObserver);

    // Category observer
    $categoryObserver = new \Webkul\ElasticSearch\Observers\Category;
    $categoryRef = new \ReflectionMethod($categoryObserver, 'getIndexName');
    $categoryRef->setAccessible(true);
    $categoryIndex = $categoryRef->invoke($categoryObserver);

    // Both should contain the same tenant suffix
    expect($productIndex)->toContain('_tenant_shared-uuid');
    expect($categoryIndex)->toContain('_tenant_shared-uuid');

    // But different entity suffixes
    expect($productIndex)->toEndWith('_products');
    expect($categoryIndex)->toEndWith('_categories');

    core()->setCurrentTenantId(null);
});
