<?php

namespace Webkul\Tenant\Tests\Unit;

use Tests\TestCase;
use Webkul\Tenant\Models\Tenant;
use Webkul\Tenant\Traits\TenantTesting;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;

class TenantTestingTest extends TestCase
{
    use TenantTesting;

    /**
     * Setup before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Set up tenant context for each test
        $this->setUpTenantContext();
    }

    /**
     * Cleanup after each test.
     */
    protected function tearDown(): void
    {
        // Clean up tenant context
        $this->tearDownTenantContext();

        parent::tearDown();
    }

    /** @test */
    public function it_creates_a_test_tenant()
    {
        // Given
        $tenant = $this->createTestTenant();

        // Then
        $this->assertTenantExists($tenant);
        $this->assertEquals('active', $tenant->status);
    }

    /** @test */
    public function it_creates_tenant_with_custom_attributes()
    {
        // Given
        $attributes = [
            'name' => 'Test Company',
            'domain' => 'test-company',
            'settings' => ['theme' => 'dark'],
        ];

        $tenant = $this->createTestTenant($attributes);

        // Then
        $this->assertEquals('Test Company', $tenant->name);
        $this->assertEquals('test-company', $tenant->domain);
        $this->assertEquals('dark', $tenant->settings['theme']);
    }

    /** @test */
    public function it_creates_tenant_with_status_states()
    {
        // Given
        $tenant = $this->createTestTenant([], ['suspended']);

        // Then
        $this->assertTenantStatus($tenant, Tenant::STATUS_SUSPENDED);
    }

    /** @test */
    public function it_creates_tenant_with_user()
    {
        // Given
        $tenant = $this->createTestTenantWithUser(
            ['name' => 'Company with User'],
            ['email' => 'user@company.com']
        );

        // Then
        $this->assertTenantExists($tenant);
        $this->assertInstanceOf(\App\Models\User::class, $tenant->users->first());
        $this->assertEquals('user@company.com', $tenant->users->first()->email);
    }

    /** @test */
    public function it_switches_to_tenant_context()
    {
        // Given
        $tenant = $this->createTestTenant(['name' => 'Switched Tenant']);
        $originalTenantId = function_exists('core') ? core()->getCurrentTenantId() : null;

        // When
        $this->actingAsTenant($tenant);

        // Then
        if (function_exists('core')) {
            $this->assertEquals($tenant->id, core()->getCurrentTenantId());
        }
    }

    /** @test */
    public function it_sets_up_tenant_database_connection()
    {
        // Given
        $tenant = $this->createTestTenant();

        // When
        $connectionName = $this->getTenantConnectionName($tenant);

        // Then
        $this->assertEquals("tenant_{$tenant->id}", $connectionName);
        $this->assertTenantHasTable($tenant, 'migrations');
    }

    /** @test */
    public function it_cleans_up_tenant_database()
    {
        // Given
        $tenant = $this->createTestTenant();
        $this->setupTenantDatabase($tenant);

        // When
        $this->cleanTenantDatabase($tenant);

        // Then
        $this->assertTenantDatabaseEmpty($tenant);
    }

    /** @test */
    public function it_creates_tenant_aware_job()
    {
        // Given
        $tenant = $this->createTestTenant();

        // When
        $job = $this->createTenantAwareJob(
            \Tests\Jobs\TestTenantJob::class,
            ['data' => 'test'],
            $tenant
        );

        // Then
        $this->assertEquals($tenant->id, $job->tenantId);
    }

    /** @test */
    public function it_tests_tenant_aware_job_execution()
    {
        // Given
        $tenant = $this->createTestTenant();

        // When
        $result = $this->testTenantAwareJob(
            \Tests\Jobs\TestTenantJob::class,
            ['data' => 'test'],
            $tenant
        );

        // Then
        $this->assertEquals($tenant->id, $result['tenant_id']);
    }

    /** @test */
    public function it_asserts_tenant_exists()
    {
        // Given
        $tenant = $this->createTestTenant();

        // Then
        $this->assertTenantExists($tenant);
    }

    /** @test */
    public function it_asserts_tenant_status()
    {
        // Given
        $tenant = $this->createTestTenant([], ['suspended']);

        // Then
        $this->assertTenantStatus($tenant, Tenant::STATUS_SUSPENDED);
        $this->assertTenantActive($this->createTestTenant());
    }

    /** @test */
    public function it_asserts_tenant_setting()
    {
        // Given
        $settings = ['theme' => 'dark', 'language' => 'en'];
        $tenant = $this->createTestTenant(['settings' => $settings]);

        // Then
        $this->assertTenantSetting($tenant, 'theme', 'dark');
        $this->assertTenantSetting($tenant, 'language', 'en');
    }

    /** @test */
    public function it_simulates_tenant_api_request()
    {
        // Given
        $tenant = $this->createTestTenant();

        // When
        $response = $this->simulateTenantRequest(
            'post',
            '/api/test',
            ['data' => 'test'],
            $tenant
        );

        // Then
        $response->assertStatus(200);
    }

    /** @test */
    public function it_creates_multiple_tenants()
    {
        // Given
        $tenants = $this->createTestTenants(3, [
            'status' => 'active',
        ]);

        // Then
        $this->assertCount(3, $tenants);
        $tenants->each(fn($tenant) => $this->assertTenantExists($tenant));
    }

    /** @test */
    public function it_gets_current_tenant()
    {
        // Given
        $tenant = $this->createTestTenant();

        // Then
        $this->assertEquals($tenant->id, $this->getCurrentTenant()->id);
    }

    /** @test */
    public function it_sets_custom_test_tenant()
    {
        // Given
        $tenant = $this->setTestTenant(['name' => 'Custom Tenant']);

        // Then
        $this->assertEquals('Custom Tenant', $tenant->name);
        $this->assertEquals($tenant, $this->getCurrentTenant());
    }

    /** @test */
    public function it_maintains_tenant_isolation()
    {
        // Given
        $tenant1 = $this->createTestTenant(['name' => 'Tenant 1']);
        $tenant2 = $this->createTestTenant(['name' => 'Tenant 2']);

        // When
        $this->actingAsTenant($tenant1);
        $this->setupTenantDatabase($tenant1);

        $this->actingAsTenant($tenant2);
        $this->setupTenantDatabase($tenant2);

        // Then
        $this->assertTenantExists($tenant1);
        $this->assertTenantExists($tenant2);

        // Database should be isolated
        $connection1 = $this->getTenantConnectionName($tenant1);
        $connection2 = $this->getTenantConnectionName($tenant2);

        $this->assertNotEquals($connection1, $connection2);
    }
}