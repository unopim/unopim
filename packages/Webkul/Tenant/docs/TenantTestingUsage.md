# TenantTesting Trait Usage Guide

The `TenantTesting` trait provides comprehensive utilities for testing multi-tenant applications in Laravel. This guide covers all available methods and provides examples for common testing scenarios.

## Installation

Add the trait to your test classes:

```php
<?php

use Tests\TestCase;
use Webkul\Tenant\Models\Tenant;
use Webkul\Tenant\Traits\TenantTesting;

class TenantTest extends TestCase
{
    use TenantTesting;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantContext();
    }

    protected function tearDown(): void
    {
        $this->tearDownTenantContext();
        parent::tearDown();
    }
}
```

## Basic Usage

### 1. Creating Test Tenants

#### Create a basic tenant

```php
/** @test */
public function basic_tenant_creation()
{
    $tenant = $this->createTestTenant();

    $this->assertTenantExists($tenant);
    $this->assertTenantActive($tenant);
}
```

#### Create tenant with custom attributes

```php
/** @test */
public function tenant_with_custom_attributes()
{
    $tenant = $this->createTestTenant([
        'name' => 'My Company',
        'domain' => 'my-company',
        'settings' => ['theme' => 'dark', 'timezone' => 'UTC'],
    ]);

    $this->assertEquals('My Company', $tenant->name);
    $this->assertTenantSetting($tenant, 'theme', 'dark');
}
```

#### Create tenant with specific states

```php
/** @test */
public function tenant_with_states()
{
    $activeTenant = $this->createTestTenant([], ['active']);
    $suspendedTenant = $this->createTestTenant([], ['suspended']);
    $provisioningTenant = $this->createTestTenant([], ['provisioning']);

    $this->assertTenantStatus($activeTenant, Tenant::STATUS_ACTIVE);
    $this->assertTenantStatus($suspendedTenant, Tenant::STATUS_SUSPENDED);
    $this->assertTenantStatus($provisioningTenant, Tenant::STATUS_PROVISIONING);
}
```

#### Create tenant with user

```php
/** @test */
public function tenant_with_user()
{
    $tenant = $this->createTestTenantWithUser(
        ['name' => 'Company with User'],
        ['email' => 'john@company.com', 'name' => 'John Doe']
    );

    $this->assertTenantExists($tenant);
    $this->assertEquals('john@company.com', $tenant->users->first()->email);
}
```

### 2. Tenant Context Management

#### Switch between tenants

```php
/** @test */
public function switch_tenant_context()
{
    $tenant1 = $this->createTestTenant(['name' => 'Tenant 1']);
    $tenant2 = $this->createTestTenant(['name' => 'Tenant 2']);

    // Switch to first tenant
    $this->actingAsTenant($tenant1);
    // ... perform operations as tenant 1

    // Switch to second tenant
    $this->actingAsTenant($tenant2);
    // ... perform operations as tenant 2
}
```

### 3. Tenant-Aware Job Testing

#### Create and test tenant-aware jobs

```php
/** @test */
public function test_tenant_aware_job()
{
    $tenant = $this->createTestTenant();

    $job = $this->createTenantAwareJob(
        TestTenantJob::class,
        ['data' => 'test payload'],
        $tenant
    );

    // Execute the job
    $result = $this->testTenantAwareJob(
        TestTenantJob::class,
        ['data' => 'test payload'],
        $tenant
    );

    // Assert job was executed in tenant context
    $this->assertEquals($tenant->id, $result['tenant_id']);
    $this->assertEquals('success', $result['result']['status']);
}
```

### 4. Tenant Database Operations

#### Test tenant database isolation

```php
/** @test */
public function tenant_database_isolation()
{
    $tenant1 = $this->createTestTenant(['name' => 'Tenant 1']);
    $tenant2 = $this->createTestTenant(['name' => 'Tenant 2']);

    // Set up tenant databases
    $this->setupTenantDatabase($tenant1);
    $this->setupTenantDatabase($tenant2);

    // Create data in tenant 1
    DB::table('test_table')->insert([
        'name' => 'Tenant 1 Data',
        'tenant_id' => $tenant1->id,
    ]);

    // Create data in tenant 2
    DB::table('test_table')->insert([
        'name' => 'Tenant 2 Data',
        'tenant_id' => $tenant2->id,
    ]);

    // Verify tenant 1 only sees its data
    $this->actingAsTenant($tenant1);
    $tenant1Data = DB::table('test_table')->where('tenant_id', $tenant1->id)->get();
    $this->assertCount(1, $tenant1Data);

    // Verify tenant 2 only sees its data
    $this->actingAsTenant($tenant2);
    $tenant2Data = DB::table('test_table')->where('tenant_id', $tenant2->id)->get();
    $this->assertCount(1, $tenant2Data);
}
```

#### Clean up tenant database

```php
/** @test */
public function clean_tenant_database()
{
    $tenant = $this->createTestTenant();
    $this->setupTenantDatabase($tenant);

    // Insert test data
    DB::table('migrations')->insert([
        'migration' => '0001_create_test_table',
        'batch' => 1,
    ]);

    // Clean up
    $this->cleanTenantDatabase($tenant);

    // Verify database is empty
    $this->assertTenantDatabaseEmpty($tenant);
}
```

### 5. Tenant Assertions

#### Various assertion methods

```php
/** @test */
public function tenant_assertions()
{
    $tenant = $this->createTestTenant([
        'name' => 'Test Company',
        'domain' => 'test-company',
        'status' => 'active',
        'settings' => ['theme' => 'dark'],
    ]);

    // Assert tenant exists
    $this->assertTenantExists($tenant);

    // Assert tenant status
    $this->assertTenantStatus($tenant, 'active');
    $this->assertTenantActive($tenant);

    // Assert tenant settings
    $this->assertTenantSetting($tenant, 'theme', 'dark');

    // Assert tenant has table
    $this->assertTenantHasTable($tenant, 'migrations');
}
```

### 6. Multiple Tenant Operations

#### Create multiple tenants

```php
/** @test */
public function create_multiple_tenants()
{
    $tenants = $this->createTestTenants(3, [
        'status' => 'active',
    ]);

    // Custom states for each tenant
    $tenants = $this->createTestTenants(3, [
        'name' => 'Company',
    ], [
        ['active'],     // Tenant 0: active
        ['suspended'],  // Tenant 1: suspended
        ['provisioning'], // Tenant 2: provisioning
    ]);

    $this->assertCount(3, $tenants);
    $this->assertTenantStatus($tenants[0], 'active');
    $this->assertTenantStatus($tenants[1], 'suspended');
    $this->assertTenantStatus($tenants[2], 'provisioning');
}
```

### 7. Tenant API Testing

#### Simulate tenant API requests

```php
/** @test */
public function simulate_tenant_api_requests()
{
    $tenant = $this->createTestTenant();

    // Test POST request
    $response = $this->simulateTenantRequest(
        'post',
        '/api/users',
        [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ],
        $tenant
    );

    $response->assertStatus(200);
    $response->assertJsonFragment(['email' => 'john@example.com']);

    // Test GET request
    $response = $this->simulateTenantRequest(
        'get',
        '/api/users',
        [],
        $tenant
    );

    $response->assertStatus(200);
}
```

### 8. Tenant Storage Testing

#### Test tenant-specific storage

```php
/** @test */
public function tenant_storage_isolation()
{
    $tenant1 = $this->createTestTenant(['name' => 'Tenant 1']);
    $tenant2 = $this->createTestTenant(['name' => 'Tenant 2']);

    // Set up storage for both tenants
    $this->setupTenantStorage($tenant1);
    $this->setupTenantStorage($tenant2);

    // Upload files to tenant 1 storage
    Storage::disk($this->getTenantStorageDisk($tenant1))
        ->put('test.txt', 'Tenant 1 file');

    // Upload files to tenant 2 storage
    Storage::disk($this->getTenantStorageDisk($tenant2))
        ->put('test.txt', 'Tenant 2 file');

    // Verify tenant isolation
    $this->assertEquals('Tenant 1 file',
        Storage::disk($this->getTenantStorageDisk($tenant1))->get('test.txt'));
    $this->assertEquals('Tenant 2 file',
        Storage::disk($this->getTenantStorageDisk($tenant2))->get('test.txt'));

    // Clean up storage
    $this->cleanTenantStorage($tenant1);
    $this->cleanTenantStorage($tenant2);
}
```

### 9. Integration with Laravel Features

#### Testing with Events

```php
/** @test */
public function test_tenant_events()
{
    Event::fake();

    $tenant = $this->createTestTenant();

    Event::assertDispatched(TenantCreated::class, function ($event) use ($tenant) {
        return $event->tenant->id === $tenant->id;
    });
}
```

#### Testing with Commands

```php
/** @test */
public function test_tenant_command()
{
    $tenant = $this->createTestTenant();

    // Execute tenant command
    Artisan::call('tenant:run', [
        'command' => 'migrate',
        '--tenant' => $tenant->id,
    ]);

    $this->assertTenantHasTable($tenant, 'migrations');
}
```

### 10. Advanced Usage

#### Custom test setup

```php
/** @test */
public function custom_tenant_setup()
{
    // Create tenant with demo data
    $tenant = $this->createTestTenantWithUser(
        ['name' => 'Demo Company'],
        ['email' => 'admin@demo.com'],
        true // Include demo data
    );

    // Perform operations
    $this->actingAsTenant($tenant);
    // ... test logic
}
```

#### Tenant lifecycle testing

```php
/** @test */
public function tenant_lifecycle_testing()
{
    $tenant = $this->createTestTenant(['status' => 'provisioning']);

    // Simulate tenant activation
    $tenant->transitionTo(Tenant::STATUS_ACTIVE);

    $this->assertTenantStatus($tenant, 'active');

    // Simulate tenant suspension
    $tenant->transitionTo(Tenant::STATUS_SUSPENDED);

    $this->assertTenantStatus($tenant, 'suspended');
}
```

## Best Practices

1. **Always use setUpTenantContext() and tearDownTenantContext()**: This ensures proper isolation between tests.

2. **Verify tenant isolation**: Always test that tenant data and resources are properly isolated.

3. **Clean up after tests**: Use tearDownTenantContext() to remove tenant data and reset the application state.

4. **Use appropriate assertions**: Use the provided assertion methods to verify tenant states and data.

5. **Test tenant-aware jobs**: Ensure jobs maintain proper tenant context and can handle different tenant states.

6. **Mock external services**: Use Laravel's mocking capabilities to isolate tenant-specific operations.

7. **Test edge cases**: Test tenant creation, deletion, state transitions, and error scenarios.

## Troubleshooting

### Common Issues

1. **Tenant context not switching**: Ensure you're calling `actingAsTenant()` before performing operations.

2. **Database connection errors**: Verify tenant databases are properly created and configured.

3. **Storage issues**: Check that tenant storage disks are properly configured and cleaned up.

4. **Job execution failures**: Ensure tenant-aware jobs have the correct middleware configured.

### Debug Tips

1. Enable debug logging to see tenant setup/cleanup details:
   ```php
   Config::set('app.debug', true);
   ```

2. Use the `getCurrentTenant()` method to verify tenant context:
   ```php
   $currentTenant = $this->getCurrentTenant();
   dd($currentTenant);
   ```

3. Check tenant database connections:
   ```php
   $connectionName = $this->getTenantConnectionName($tenant);
   dd(config("database.connections.{$connectionName}"));
   ```