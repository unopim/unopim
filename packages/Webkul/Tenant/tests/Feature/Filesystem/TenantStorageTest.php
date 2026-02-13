<?php

use Webkul\Tenant\Filesystem\TenantStorage;

/*
|--------------------------------------------------------------------------
| TenantStorage Tests
|--------------------------------------------------------------------------
|
| Tests that TenantStorage::path() correctly prefixes paths based on
| the current tenant context, with special handling for imports/exports.
|
*/

it('returns path unchanged in platform mode (no tenant)', function () {
    $this->clearTenantContext();

    expect(TenantStorage::path('products/images/foo.jpg'))->toBe('products/images/foo.jpg');
    expect(TenantStorage::path('imports/products.csv'))->toBe('imports/products.csv');
    expect(TenantStorage::path('exports/report.xlsx'))->toBe('exports/report.xlsx');
});

it('prefixes general paths with tenant/{id}/', function () {
    $this->actingAsTenant($this->tenantA);

    $result = TenantStorage::path('products/images/foo.jpg');

    expect($result)->toBe("tenant/{$this->tenantA->id}/products/images/foo.jpg");
});

it('prefixes import paths with imports/tenant-{id}/', function () {
    $this->actingAsTenant($this->tenantA);

    $result = TenantStorage::path('imports/products.csv');

    expect($result)->toBe("imports/tenant-{$this->tenantA->id}/products.csv");
});

it('prefixes export paths with exports/tenant-{id}/', function () {
    $this->actingAsTenant($this->tenantA);

    $result = TenantStorage::path('exports/report.xlsx');

    expect($result)->toBe("exports/tenant-{$this->tenantA->id}/report.xlsx");
});

it('uses correct tenant prefix when switching tenants', function () {
    $this->actingAsTenant($this->tenantA);
    $pathA = TenantStorage::path('data/file.json');

    $this->actingAsTenant($this->tenantB);
    $pathB = TenantStorage::path('data/file.json');

    expect($pathA)->toBe("tenant/{$this->tenantA->id}/data/file.json");
    expect($pathB)->toBe("tenant/{$this->tenantB->id}/data/file.json");
    expect($pathA)->not->toBe($pathB);
});

it('handles nested import paths correctly', function () {
    $this->actingAsTenant($this->tenantA);

    $result = TenantStorage::path('imports/batch/2024/products.csv');

    expect($result)->toBe("imports/tenant-{$this->tenantA->id}/batch/2024/products.csv");
});

it('handles nested export paths correctly', function () {
    $this->actingAsTenant($this->tenantA);

    $result = TenantStorage::path('exports/42/tmp/data.csv');

    expect($result)->toBe("exports/tenant-{$this->tenantA->id}/42/tmp/data.csv");
});

it('does not double-prefix an already-prefixed path', function () {
    $this->actingAsTenant($this->tenantA);

    $result = TenantStorage::path('tinymce/image.png');

    expect($result)->toBe("tenant/{$this->tenantA->id}/tinymce/image.png");
});
