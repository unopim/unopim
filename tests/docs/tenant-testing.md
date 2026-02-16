# Tenant Testing Documentation for UnoPim

## Overview

This document provides comprehensive guidance on testing multi-tenant functionality in UnoPim. The UnoPim application implements a sophisticated multi-tenant architecture where each tenant's data is completely isolated from others. This document covers testing patterns, best practices, and common scenarios for ensuring tenant isolation and functionality.

## Table of Contents

1. [Multi-Tenant Architecture Overview](#multi-tenant-architecture-overview)
2. [Base Test Case Classes](#base-test-case-classes)
3. [Testing Patterns](#testing-patterns)
4. [Tenant Testing Traits](#tenant-testing-traits)
5. [Best Practices](#best-practices)
6. [Common Pitfalls and Solutions](#common-pitfalls-and-solutions)
7. [Testing Scenarios](#testing-scenarios)
8. [Advanced Testing Techniques](#advanced-testing-techniques)

## Multi-Tenant Architecture Overview

UnoPim implements a database-per-tenant model with the following key components:

- **Tenant Model**: Central tenant entity with status tracking
- **Tenant Context**: Core service for managing current tenant ID
- **Scoped Models**: All business models include `tenant_id` foreign key
- **Database Isolation**: Each tenant operates in isolation from others

```php
// Example tenant model relationship
class Product extends Model
{
    protected $fillable = [
        'tenant_id',
        'attribute_family_id',
        'sku',
        'values',
        // ... other fields
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
```

## Base Test Case Classes

### 1. ChannelConnectorTestCase

For testing channel connector functionality, extend `ChannelConnectorTestCase`:

```php
<?php

namespace Tests\Feature\ChannelConnector;

use Webkul\ChannelConnector\Tests\ChannelConnectorTestCase;
use Webkul\ChannelConnector\Models\ChannelConnector;

class ConnectorTest extends ChannelConnectorTestCase
{
    /** @test */
    public function it_creates_connector_with_tenant_context()
    {
        $this->loginAsAdmin();

        $connector = ChannelConnector::create([
            'code' => 'test-shopify',
            'name' => 'Test Shopify Store',
            'channel_type' => 'shopify',
            'credentials' => ['shop_url' => 'test.myshopify.com'],
            'status' => 'connected',
        ]);

        // Verify tenant is set
        $this->assertEquals($this->testTenant->id, $connector->tenant_id);
    }

    /** @test */
    public function it_enforces_tenant_scoped_uniqueness()
    {
        $this->loginAsAdmin();

        ChannelConnector::create([
            'code' => 'unique-code',
            'name' => 'First Store',
            'channel_type' => 'shopify',
            'status' => 'connected',
        ]);

        // This should fail due to tenant-scoped unique constraint
        $this->expectException(\Illuminate\Database\QueryException::class);
        ChannelConnector::create([
            'code' => 'unique-code',
            'name' => 'Second Store',
            'channel_type' => 'shopify',
            'status' => 'connected',
        ]);
    }
}
```

Key features of `ChannelConnectorTestCase`:

- Automatic tenant creation and context setup
- Permission management for channel connector features
- API token generation for OAuth2 testing
- SQLite foreign key support
- Admin user creation with appropriate permissions

### 2. TenantTestCase

For general multi-tenant testing, use `TenantTestCase`:

```php
<?php

namespace Tests\Feature;

use Webkul\Tenant\Tests\TenantTestCase;
use Webkul\Tenant\Models\Tenant;
use Webkul\Product\Models\Product;
use Webkul\User\Models\Admin;

class ProductTest extends TenantTestCase
{
    /** @test */
    public function test_tenant_isolation()
    {
        // Create a record in Tenant A
        $this->actingAsTenant($this->tenantA);
        $productA = Product::factory()->create([
            'sku' => 'SKU-A',
            'values' => ['common' => ['name' => 'Product A']]
        ]);

        // Verify Tenant A sees the product
        $this->assertGreaterThan(0, Product::count());

        // Switch to Tenant B and verify isolation
        $this->actingAsTenant($this->tenantB);
        $this->assertEquals(0, Product::where('id', $productA->id)->count());

        // Restore Tenant A context
        $this->actingAsTenant($this->tenantA);
    }

    /** @test */
    public function test_cross_tenant_operations()
    {
        // Test operations that should work across tenants
        $this->actingAsTenant($this->tenantA);
        $productA = Product::factory()->create(['sku' => 'cross-tenant-test']);

        $this->actingAsTenant($this->tenantB);
        $productB = Product::factory()->create(['sku' => 'cross-tenant-test']);

        // Both tenants can have products with same SKU
        $this->assertGreaterThan(0, Product::where('sku', 'cross-tenant-test')->count());
    }
}
```

## Testing Patterns

### 1. Tenant Context Switching Pattern

```php
it('maintains tenant context across operations', function () {
    // Start with Tenant A
    $this->actingAsTenant($this->tenantA);
    $product = Product::factory()->create(['name' => 'Product A']);

    // Verify Tenant A context
    $this->assertEquals($this->tenantA->id, $product->tenant_id);
    $this->assertEquals(1, Product::count());

    // Switch to Tenant B
    $this->actingAsTenant($this->tenantB);

    // Verify Tenant B isolation
    $this->assertEquals(0, Product::count());
    $this->assertNotEquals($this->tenantA->id, core()->getCurrentTenantId());

    // Switch back to Tenant A
    $this->actingAsTenant($this->tenantA);
    $this->assertEquals(1, Product::count());
    $this->assertEquals($product->id, Product::first()->id);
});
```

### 2. Permission Testing Pattern

```php
class PermissionTest extends ChannelConnectorTestCase
{
    /** @test */
    public function it_requires_channel_connector_permissions()
    {
        // Login without permissions
        $this->loginAsAdminWithoutPermissions();

        // Should be denied access
        $response = $this->get(route('admin.channel_connector.connectors.index'));
        $response->assertForbidden();
    }

    /** @test */
    public function it_allows_with_proper_permissions()
    {
        // Login with channel connector permissions
        $this->loginAsAdmin();

        // Should be allowed access
        $response = $this->get(route('admin.channel_connector.connectors.index'));
        $response->assertOk();
    }

    /** @test */
    public function it_supports_custom_permission_sets()
    {
        // Login with specific permissions
        $this->loginWithPermissions('custom', [
            'channel_connector.connectors.view',
            'channel_connector.connectors.create'
        ]);

        // Can view and create but not edit/delete
        $response = $this->get(route('admin.channel_connector.connectors.index'));
        $response->assertOk();

        // But cannot access edit routes
        $response = $this->get(route('admin.channel_connector.connectors.edit', 1));
        $response->assertForbidden();
    }
}
```

### 3. API Testing with Tenant Context

```php
class ApiTest extends ChannelConnectorTestCase
{
    /** @test */
    public function it_authenticates_api_requests_with_tenant_context()
    {
        $admin = $this->createAdminApiToken();

        // API requests include tenant context automatically
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $admin,
            'Accept' => 'application/json',
        ])->getJson('/api/v1/channel-connector/connectors');

        $response->assertJsonFragment([
            'message' => 'Connectors retrieved successfully'
        ]);
    }

    /** @test */
    public function it_scopes_api_results_to_tenant()
    {
        $this->loginAsAdmin();

        // Create connector in current tenant
        ChannelConnector::create([
            'code' => 'api-test-connector',
            'name' => 'API Test',
            'channel_type' => 'shopify',
            'status' => 'connected',
        ]);

        $this->actingAsTenant($this->tenantB);
        $response = $this->getJson('/api/v1/channel-connector/connectors');

        // Should return empty results for different tenant
        $response->assertJsonCount(0);
    }
}
```

## Tenant Testing Traits

### 1. TenantIsolationTrait

Create a reusable trait for tenant isolation testing:

```php
<?php

namespace Tests\Traits;

use Webkul\Tenant\Models\Tenant;

trait TenantIsolationTrait
{
    protected function assertTenantScope(string $modelClass, array $attributes = []): void
    {
        // Create record in current tenant
        $record = $modelClass::create(array_merge($attributes, [
            'tenant_id' => core()->getCurrentTenantId(),
        ]));

        // Verify scope works
        $this->assertEquals(1, $modelClass::count());
        $this->assertEquals($record->id, $modelClass::first()->id);

        // Test isolation by switching tenant
        $originalTenant = core()->getCurrentTenantId();
        $otherTenant = Tenant::where('id', '!=', $originalTenant)->first();

        if ($otherTenant) {
            core()->setCurrentTenantId($otherTenant->id);
            $this->assertEquals(0, $modelClass::where('id', $record->id)->count());
            core()->setCurrentTenantId($originalTenant);
        }
    }
}
```

### 2. MultiTenantTestHelper

Advanced helper trait for complex multi-tenant scenarios:

```php
<?php

namespace Tests\Traits;

trait MultiTenantTestHelper
{
    protected function createTenants(int $count = 2): array
    {
        $tenants = [];
        for ($i = 1; $i <= $count; $i++) {
            $tenants[] = Tenant::factory()->create([
                'domain' => "tenant{$i}.test.com",
                'status' => Tenant::STATUS_ACTIVE,
            ]);
        }
        return $tenants;
    }

    protected function actAsMultiUser(int $tenantId, string $role = 'admin'): void
    {
        $tenant = Tenant::find($tenantId);
        $this->actingAsTenant($tenant);

        // You might need to set up user context here
        // This depends on your authentication system
    }

    protected function assertNoDataLeak(array $tenants, string $modelClass): void
    {
        foreach ($tenants as $tenant) {
            $this->actingAsTenant($tenant);

            $count = $modelClass::count();
            foreach ($tenants as $otherTenant) {
                if ($otherTenant->id !== $tenant->id) {
                    $otherCount = $modelClass::where('tenant_id', $otherTenant->id)->count();
                    $this->assertEquals(0, $otherCount,
                        "Data leak detected from tenant {$tenant->id} to {$otherTenant->id}");
                }
            }
        }
    }
}
```

## Best Practices

### 1. Always Reset Tenant Context

```php
class ExampleTest extends TenantTestCase
{
    protected function tearDown(): void
    {
        // Always clear tenant context
        core()->setCurrentTenantId(null);

        parent::tearDown();
    }
}
```

### 2. Use Database Transactions Wisely

```php
class SyncTest extends ChannelConnectorTestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_sync_transaction_safety()
    {
        $this->loginAsAdmin();

        // Test that sync operations are atomic
        $connector = ChannelConnector::factory()->create();

        // Begin sync process
        DB::beginTransaction();

        try {
            // Perform multiple related operations
            $syncJob = ChannelSyncJob::create([
                'channel_connector_id' => $connector->id,
                'status' => 'running',
            ]);

            // Simulate failure
            throw new \Exception('Test failure');

        } catch (\Exception $e) {
            DB::rollBack();
        }

        // Verify no partial data
        $this->assertEquals(0, ChannelSyncJob::count());
    }
}
```

### 3. Test Edge Cases

```php
class EdgeCaseTest extends TenantTestCase
{
    /** @test */
    public function test_inactive_tenant_handling()
    {
        $inactiveTenant = Tenant::factory()->create([
            'status' => Tenant::STATUS_INACTIVE
        ]);

        $this->actingAsTenant($inactiveTenant);

        // Should not be able to perform operations
        $this->expectException(\Exception::class);
        Product::factory()->create();
    }

    /** @test */
    public function test_tenant_deletion_cascade()
    {
        $this->actingAsTenant($this->tenantA);

        // Create related records
        $product = Product::factory()->create();
        $mapping = ProductChannelMapping::factory()->create([
            'product_id' => $product->id,
        ]);

        // Delete tenant (should cascade delete)
        $this->tenantA->delete();

        // Verify all data is gone
        $this->assertEquals(0, Product::count());
        $this->assertEquals(0, ProductChannelMapping::count());
    }
}
```

### 4. Performance Considerations

```php
class PerformanceTest extends ChannelConnectorTestCase
{
    /** @test */
    public function test_tenant_query_performance()
    {
        $this->loginAsAdmin();

        // Create test data
        ChannelConnector::factory()->count(100)->create();

        // Test query with tenant scope
        $start = microtime(true);
        $connectors = ChannelConnector::all();
        $end = microtime(true);

        // Should return only current tenant's data
        $this->assertEquals(100, $connectors->count());
        $this->assertLessThan(0.01, $end - $start); // Should be fast
    }
}
```

## Common Pitfalls and Solutions

### 1. Tenant Context Leaks

**Problem**: Tests affecting each other due to tenant context not being properly reset.

**Solution**:
```php
class SafeTenantTest extends ChannelConnectorTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Always clear context before each test
        core()->setCurrentTenantId(null);
        $this->testTenant = Tenant::factory()->create();
        core()->setCurrentTenantId($this->testTenant->id);
    }

    protected function tearDown(): void
    {
        core()->setCurrentTenantId(null);
        parent::tearDown();
    }
}
```

### 2. Foreign Key Constraint Issues with SQLite

**Problem**: SQLite doesn't enforce foreign keys by default.

**Solution**:
```php
class SqliteSafeTest extends ChannelConnectorTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }
    }
}
```

### 3. Permission Inheritance Issues

**Problem**: Tests failing due to unexpected permission combinations.

**Solution**:
```php
class CleanPermissionTest extends ChannelConnectorTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure clean state
        $this->clearPermissionCache();
        $this->loginAsAdmin();
    }

    private function clearPermissionCache(): void
    {
        // Clear any cached permissions
        cache()->forget('permissions:' . $this->testTenant->id);
    }
}
```

### 4. Timezone Consistency

**Problem**: Tenant-specific settings affecting test timing.

**Solution**:
```php
class TimezoneAwareTest extends TenantTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure consistent timezone
        config(['app.timezone' => 'UTC']);
        date_default_timezone_set('UTC');
    }
}
```

## Testing Scenarios

### 1. Channel Connector Sync Testing

```php
class ChannelSyncTest extends ChannelConnectorTestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_full_sync_workflow()
    {
        $this->loginAsAdmin();

        // Create connector and sync job
        $connector = ChannelConnector::factory()->create();
        $syncJob = ChannelSyncJob::factory()->create([
            'channel_connector_id' => $connector->id,
            'status' => 'pending',
        ]);

        // Test sync process
        $this->artisan('channel-connector:sync', [
            'connector' => $connector->id,
        ]);

        // Verify job completion
        $syncJob->refresh();
        $this->assertEquals('completed', $syncJob->status);
    }

    /** @test */
    public function test_conflict_resolution()
    {
        $this->loginAsAdmin();

        $connector = ChannelConnector::factory()->create([
            'settings' => ['conflict_strategy' => 'auto_update'],
        ]);

        // Create conflicting data
        $product = Product::factory()->create();
        $mapping = ProductChannelMapping::factory()->create([
            'product_id' => $product->id,
            'external_id' => 'ext-123',
        ]);

        // Trigger sync with conflict
        $this->postJson(route('admin.channel_connector.sync.run', $connector->id));

        // Verify resolution
        $this->assertDatabaseHas('channel_sync_conflicts', [
            'channel_connector_id' => $connector->id,
            'status' => 'resolved',
        ]);
    }
}
```

### 2. Multi-Tenant Data Migration Testing

```php
class MigrationTest extends TenantTestCase
{
    /** @test */
    public function test_migration_applies_to_all_tenants()
    {
        // Run migrations
        $this->artisan('migrate', [
            '--path' => 'database/migrations',
        ]);

        // Verify tables exist in all tenants
        foreach ([$this->tenantA, $this->tenantB] as $tenant) {
            $this->actingAsTenant($tenant);

            Schema::hasTable('products');
            Schema::hasTable('channels');
        }
    }

    /** @test */
    public function test_rollback_migrations()
    {
        // Test rollback
        $this->artisan('migrate:rollback');

        foreach ([$this->tenantA, $this->tenantB] as $tenant) {
            $this->actingAsTenant($tenant);

            $this->assertFalse(Schema::hasTable('test_table'));
        }
    }
}
```

### 3. API Integration Testing

```php
class ApiIntegrationTest extends ChannelConnectorTestCase
{
    /** @test */
    public function test_oauth2_flow()
    {
        // Test token creation
        $token = $this->createAdminApiToken();

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
        ])->postJson('/api/v1/channel-connector/auth/shopify', [
            'shop_url' => 'test-shop.myshopify.com',
            'code' => 'auth_code_123',
        ]);

        $response->assertJsonStructure([
            'access_token',
            'expires_in',
            'shop',
        ]);
    }

    /** @test */
    public function test_rate_limiting()
    {
        $token = $this->createAdminApiToken();

        // Make multiple requests
        for ($i = 0; $i < 5; $i++) {
            $response = $this->withHeaders([
                'Authorization' => 'Bearer ' . $token,
            ])->get('/api/v1/channel-connector/connectors');

            $response->assertStatus(200);
        }

        // Should be rate limited
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->get('/api/v1/channel-connector/connectors');

        $response->assertStatus(429);
    }
}
```

## Advanced Testing Techniques

### 1. Mocking Tenant-Services

```php
class MockedServiceTest extends ChannelConnectorTestCase
{
    /** @test */
    public function test_with_mocked_channel_service()
    {
        $mockService = Mock::make(ChannelAdapterContract::class);
        $mockService->shouldReceive('getProducts')
            ->andReturn(collect([
                ['id' => '123', 'title' => 'Test Product'],
            ]));

        app()->instance(ChannelAdapterContract::class, $mockService);

        $this->loginAsAdmin();

        // Test with mocked service
        $response = $this->getJson('/api/v1/channel-connector/sync/test');
        $response->assertJsonFragment(['title' => 'Test Product']);
    }
}
```

### 2. Performance Profiling

```php
class PerformanceProfileTest extends ChannelConnectorTestCase
{
    /** @test */
    public function test_query_performance()
    {
        // Create test data
        Product::factory()->count(1000)->create();
        ChannelConnector::factory()->count(10)->create();

        $start = microtime(true);

        // Test complex query
        $results = Product::with(['tenant', 'channels'])
            ->whereHas('channels', function ($query) {
                $query->where('status', 'active');
            })
            ->limit(100)
            ->get();

        $duration = microtime(true) - $start;

        $this->assertLessThan(0.1, $duration); // Should execute under 100ms
        $this->assertEquals(100, $results->count());
    }
}
```

### 3. Stress Testing

```php
class StressTest extends TenantTestCase
{
    /** @test */
    public function test_tenant_isolation_under_load()
    {
        // Create many tenants and records
        $tenants = $this->createTenants(10);

        foreach ($tenants as $tenant) {
            $this->actingAsTenant($tenant);

            Product::factory()->count(100)->create();
            ChannelConnector::factory()->count(5)->create();
        }

        // Test isolation under concurrent operations
        foreach ($tenants as $tenant) {
            $this->actingAsTenant($tenant);
            $this->assertEquals(100, Product::count());
        }
    }
}
```

### 4. Security Testing

```php
class SecurityTest extends ChannelConnectorTestCase
{
    /** @test */
    public function test_tenant_data_injection()
    {
        $tenantA = $this->tenantA;
        $tenantB = $this->tenantB;

        // Create data in both tenants
        $this->actingAsTenant($tenantA);
        $productA = Product::factory()->create(['name' => 'Tenant A Product']);

        $this->actingAsTenant($tenantB);
        $productB = Product::factory()->create(['name' => 'Tenant B Product']);

        // Try to access from other tenant (should fail)
        $this->actingAsTenant($tenantA);
        $this->assertEquals(0, Product::where('id', $productB->id)->count());
    }

    /** @test */
    public function test_sql_injection_protection()
    {
        $this->loginAsAdmin();

        $maliciousInput = "1'; DROP TABLE products; --";

        $response = $this->postJson('/api/v1/channel-connector/connectors', [
            'name' => $maliciousInput,
            'channel_type' => 'shopify',
        ]);

        // Should not execute the injection
        $this->assertDatabaseCount('products', 0);
        $response->assertStatus(422); // Validation error
    }
}
```

## Conclusion

Proper tenant testing is crucial for maintaining data isolation and application reliability in UnoPim. By following the patterns and practices outlined in this document, you can ensure that your multi-tenant application behaves correctly under all conditions.

Remember to:
- Always test with the appropriate tenant context
- Verify tenant isolation after every operation
- Test edge cases and failure scenarios
- Maintain clean test state between runs
- Document any tenant-specific testing considerations

For additional information, refer to the source code of the base test classes and existing tests in the UnoPim codebase.