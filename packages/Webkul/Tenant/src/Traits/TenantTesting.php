<?php

namespace Webkul\Tenant\Traits;

use Webkul\Tenant\Models\Tenant;
use Webkul\Tenant\Models\TenantProxy;
use Webkul\Tenant\Jobs\TenantAwareJob;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Foundation\Testing\TestCase;
use PHPUnit\Framework\Assert;

/**
 * TenantTesting trait provides comprehensive testing utilities for multi-tenant applications.
 *
 * This trait enables easy setup and management of tenant contexts in tests, providing:
 * - Tenant context management with automatic setup and teardown
 * - Factory methods for creating test tenants
 * - Tenant authentication helpers
 * - Tenant-aware job testing
 * - Assertion helpers for tenant-scoped operations
 * - Database isolation between tenants
 *
 * @example
 * use Webkul\Tenant\Traits\TenantTesting;
 *
 * class TenantTest extends TestCase
 * {
 *     use TenantTesting;
 *
 *     public function testTenantCreation()
 *     {
 *         $tenant = $this->createTestTenant();
 *         $this->assertTenantExists($tenant);
 *     }
 * }
 */
trait TenantTesting
{
    /**
     * The current test tenant.
     *
     * @var Tenant|null
     */
    protected ?Tenant $testTenant = null;

    /**
     * Tenant database connections cache.
     *
     * @var array<string, string>
     */
    protected static array $tenantConnections = [];

    /**
     * Setup tenant context before each test.
     *
     * This method automatically sets up a test tenant context for each test method.
     * It creates a new tenant and sets it as the current tenant context.
     *
     * @return void
     */
    protected function setUpTenantContext(): void
    {
        // Reset any existing tenant context
        $this->tearDownTenantContext();

        // Create a new test tenant
        $this->testTenant = $this->createTestTenant();

        // Set the tenant context
        $this->actingAsTenant($this->testTenant);

        // Log tenant creation
        $this->logTenantSetup();
    }

    /**
     * Clean up tenant context after each test.
     *
     * This method cleans up the tenant context and removes the test tenant from the database.
     * It ensures test isolation by removing any tenant-specific data.
     *
     * @return void
     */
    protected function tearDownTenantContext(): void
    {
        if ($this->testTenant) {
            $this->logTenantCleanup();

            // Clean up tenant-specific database tables
            $this->cleanTenantDatabase($this->testTenant);

            // Remove tenant files and storage
            $this->cleanTenantStorage($this->testTenant);

            // Delete the tenant record
            $this->testTenant->delete();

            $this->testTenant = null;
        }

        // Reset any cached connections
        self::$tenantConnections = [];

        // Reset tenant context
        if (function_exists('core')) {
            core()->setCurrentTenantId(null);
        }
    }

    /**
     * Create a test tenant with optional custom attributes.
     *
     * @param array $attributes Custom tenant attributes
     * @param array $states Tenant states to apply (e.g., 'provisioning', 'suspended')
     * @return Tenant The created tenant instance
     */
    protected function createTestTenant(
        array $attributes = [],
        array $states = []
    ): Tenant {
        $factory = TenantProxy::factory()->create($attributes);

        // Apply any requested states
        foreach ($states as $state) {
            $factory->$state();
        }

        // Save the tenant
        $factory->save();

        return $factory;
    }

    /**
     * Create a test tenant with user and related entities.
     *
     * @param array $attributes Tenant attributes
     * @param array $userAttributes User attributes
     * @param bool $withDemoData Whether to include demo data
     * @return Tenant The created tenant with user
     */
    protected function createTestTenantWithUser(
        array $attributes = [],
        array $userAttributes = [],
        bool $withDemoData = false
    ): Tenant {
        // Create the tenant
        $tenant = $this->createTestTenant($attributes);

        // Set up tenant database connection
        $this->setupTenantDatabase($tenant);

        // Create user for the tenant
        if (class_exists('Webkul\Admin\Database\Factories\AdminFactory')) {
            $user = \Webkul\Admin\Database\Factories\AdminFactory::new()->create(
                array_merge($userAttributes, [
                    'tenant_id' => $tenant->id,
                ])
            );
        } else {
            // Fallback for user creation
            $user = \App\Models\User::factory()->create(
                array_merge($userAttributes, [
                    'tenant_id' => $tenant->id,
                ])
            );
        }

        // Add demo data if requested
        if ($withDemoData) {
            $this->addTenantDemoData($tenant);
        }

        return $tenant;
    }

    /**
     * Switch to a specific tenant context.
     *
     * @param Tenant $tenant The tenant to switch to
     * @return void
     */
    protected function actingAsTenant(Tenant $tenant): void
    {
        // Set the tenant ID in the application
        if (function_exists('core')) {
            core()->setCurrentTenantId($tenant->id);
        }

        // Set up tenant database connection
        $this->setupTenantDatabase($tenant);

        // Set up tenant storage
        $this->setupTenantStorage($tenant);
    }

    /**
     * Setup tenant-specific database connection.
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function setupTenantDatabase(Tenant $tenant): void
    {
        $connectionName = "tenant_{$tenant->id}";

        if (!isset(self::$tenantConnections[$tenant->id])) {
            // Configure database connection for this tenant
            Config::set("database.connections.{$connectionName}", [
                'driver' => env('DB_TENANT_DRIVER', 'mysql'),
                'host' => env('DB_TENANT_HOST', env('DB_HOST')),
                'port' => env('DB_TENANT_PORT', env('DB_PORT')),
                'database' => env('DB_DATABASE') . "_tenant_{$tenant->id}",
                'username' => env('DB_TENANT_USERNAME', env('DB_USERNAME')),
                'password' => env('DB_TENANT_PASSWORD', env('DB_PASSWORD')),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
            ]);

            // Create database if it doesn't exist
            $this->createTenantDatabase($connectionName);

            self::$tenantConnections[$tenant->id] = $connectionName;
        }

        // Use the tenant connection
        DB::setDefaultConnection($connectionName);
    }

    /**
     * Create tenant-specific database.
     *
     * @param string $connectionName
     * @return void
     */
    protected function createTenantDatabase(string $connectionName): void
    {
        try {
            // Connect to the main database without specifying a database
            $mainConnection = config('database.default');
            $databaseName = config("database.connections.{$connectionName}.database");

            DB::purge($connectionName);
            Config::set("database.connections.{$connectionName}.database", null);

            // Create database
            DB::connection($connectionName)->statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}`");

            // Restore the database name
            Config::set("database.connections.{$connectionName}.database", $databaseName);

        } catch (\Exception $e) {
            // Handle cases where we don't have permission to create databases
            \Log::warning("Could not create tenant database: " . $e->getMessage());
        }
    }

    /**
     * Setup tenant-specific storage.
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function setupTenantStorage(Tenant $tenant): void
    {
        // Configure tenant-specific filesystem
        $diskName = "tenant_{$tenant->id}";

        // Add tenant disk to filesystem configuration
        Config::set("filesystems.disks.{$diskName}", [
            'driver' => 'local',
            'root' => storage_path("app/tenant/{$tenant->id}"),
            'url' => env('APP_URL') . "/storage/tenant/{$tenant->id}",
            'visibility' => 'private',
            'throw' => false,
        ]);
    }

    /**
     * Clean up tenant-specific database.
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function cleanTenantDatabase(Tenant $tenant): void
    {
        if (!isset(self::$tenantConnections[$tenant->id])) {
            return;
        }

        try {
            $connectionName = self::$tenantConnections[$tenant->id];
            $databaseName = config("database.connections.{$connectionName}.database");

            // Drop tenant database
            DB::connection($connectionName)->statement("DROP DATABASE IF EXISTS `{$databaseName}`");

            // Remove connection config
            Config::set("database.connections.{$connectionName}", null);

            // Clean up cached connections
            unset(self::$tenantConnections[$tenant->id]);
        } catch (\Exception $e) {
            \Log::warning("Could not drop tenant database: " . $e->getMessage());
        }
    }

    /**
     * Clean up tenant-specific storage.
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function cleanTenantStorage(Tenant $tenant): void
    {
        $diskName = "tenant_{$tenant->id}";

        // Remove tenant storage directory
        $storagePath = storage_path("app/tenant/{$tenant->id}");
        if (file_exists($storagePath)) {
            array_map('unlink', glob("$storagePath/*.*"));
            rmdir($storagePath);
        }

        // Remove disk config
        Config::set("filesystems.disks.{$diskName}", null);
    }

    /**
     * Add demo data to tenant.
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function addTenantDemoData(Tenant $tenant): void
    {
        // Run tenant seeder if available
        if (class_exists('Webkul\Tenant\Services\TenantDemoSeeder')) {
            $seeder = new \Webkul\Tenant\Services\TenantDemoSeeder();
            $seeder->run($tenant->id);
        }
    }

    /**
     * Create a tenant-aware job for testing.
     *
     * @param string $jobClass The job class to create
     * @param array $parameters Job parameters
     * @param Tenant|null $tenant The tenant context (defaults to current test tenant)
     * @return TenantAwareJob The created job instance
     */
    protected function createTenantAwareJob(
        string $jobClass,
        array $parameters = [],
        ?Tenant $tenant = null
    ): TenantAwareJob {
        $tenant ??= $this->testTenant;
        $job = new $jobClass(...$parameters);

        if ($tenant) {
            $job->tenantId = $tenant->id;
            $job->captureTenantContext();
        }

        return $job;
    }

    /**
     * Test a tenant-aware job execution.
     *
     * @param string $jobClass The job class to test
     * @param array $parameters Job parameters
     * @param Tenant|null $tenant The tenant context
     * @return array Job execution results
     */
    protected function testTenantAwareJob(
        string $jobClass,
        array $parameters = [],
        ?Tenant $tenant = null
    ): array {
        $job = $this->createTenantAwareJob($jobClass, $parameters, $tenant);

        // Execute the job
        $result = $job->handle();

        return [
            'job' => $job,
            'result' => $result,
            'tenant_id' => $job->tenantId,
        ];
    }

    /**
     * Assert tenant exists.
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function assertTenantExists(Tenant $tenant): void
    {
        $exists = TenantProxy::where('id', $tenant->id)->exists();
        Assert::assertTrue($exists, "Tenant with ID {$tenant->id} should exist");
    }

    /**
     * Assert tenant does not exist.
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function assertTenantNotExists(Tenant $tenant): void
    {
        $exists = TenantProxy::where('id', $tenant->id)->exists();
        Assert::assertFalse($exists, "Tenant with ID {$tenant->id} should not exist");
    }

    /**
     * Assert tenant has status.
     *
     * @param Tenant $tenant
     * @param string $status
     * @return void
     */
    protected function assertTenantStatus(Tenant $tenant, string $status): void
    {
        Assert::assertEquals($status, $tenant->status);
    }

    /**
     * Assert tenant is active.
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function assertTenantActive(Tenant $tenant): void
    {
        $this->assertTenantStatus($tenant, Tenant::STATUS_ACTIVE);
    }

    /**
     * Assert tenant has setting.
     *
     * @param Tenant $tenant
     * @param string $key
     * @param mixed $value
     * @return void
     */
    protected function assertTenantSetting(Tenant $tenant, string $key, $value): void
    {
        Assert::assertEquals($value, $tenant->settings[$key] ?? null);
    }

    /**
     * Assert tenant database has table.
     *
     * @param Tenant $tenant
     * @param string $tableName
     * @return void
     */
    protected function assertTenantHasTable(Tenant $tenant, string $tableName): void
    {
        $connectionName = self::$tenantConnections[$tenant->id] ?? "tenant_{$tenant->id}";

        $hasTable = DB::connection($connectionName)->getSchemaBuilder()->hasTable($tableName);
        Assert::assertTrue($hasTable, "Tenant database should have table {$tableName}");
    }

    /**
     * Assert tenant database is empty.
     *
     * @param Tenant $tenant
     * @param array $excludeTables Tables to exclude from check
     * @return void
     */
    protected function assertTenantDatabaseEmpty(
        Tenant $tenant,
        array $excludeTables = ['migrations', 'tenant_settings']
    ): void
    {
        $connectionName = self::$tenantConnections[$tenant->id] ?? "tenant_{$tenant->id}";
        $tables = DB::connection($connectionName)->table('information_schema.tables')
            ->where('table_schema', config("database.connections.{$connectionName}.database"))
            ->pluck('table_name')
            ->values()
            ->all();

        $filteredTables = array_filter($tables, fn($table) => !in_array($table, $excludeTables));

        Assert::assertEmpty($filteredTables, "Tenant database should be empty but found: " . implode(', ', $filteredTables));
    }

    /**
     * Simulate tenant API request.
     *
     * @param string $method HTTP method
     * @param string $url URL to test
     * @param array $data Request data
     * @param Tenant|null $tenant Tenant context
     * @return \Illuminate\Testing\TestResponse The test response
     */
    protected function simulateTenantRequest(
        string $method,
        string $url,
        array $data = [],
        ?Tenant $tenant = null
    ): \Illuminate\Testing\TestResponse {
        $this->actingAsTenant($tenant ?? $this->testTenant);

        return $this->{$method}($url, $data);
    }

    /**
     * Log tenant setup.
     *
     * @return void
     */
    protected function logTenantSetup(): void
    {
        if (app()->environment('testing') && config('app.debug')) {
            \Log::info("Tenant test setup: " . ($this->testTenant->name ?? 'Unknown'), [
                'tenant_id' => $this->testTenant->id,
                'tenant_uuid' => $this->testTenant->uuid,
                'tenant_domain' => $this->testTenant->domain,
                'tenant_status' => $this->testTenant->status,
            ]);
        }
    }

    /**
     * Log tenant cleanup.
     *
     * @return void
     */
    protected function logTenantCleanup(): void
    {
        if (app()->environment('testing') && config('app.debug')) {
            \Log::info("Tenant test cleanup: " . ($this->testTenant->name ?? 'Unknown'), [
                'tenant_id' => $this->testTenant->id,
                'tenant_uuid' => $this->testTenant->uuid,
            ]);
        }
    }

    /**
     * Get tenant connection name.
     *
     * @param Tenant $tenant
     * @return string
     */
    protected function getTenantConnectionName(Tenant $tenant): string
    {
        return self::$tenantConnections[$tenant->id] ?? "tenant_{$tenant->id}";
    }

    /**
     * Get tenant database name.
     *
     * @param Tenant $tenant
     * @return string
     */
    protected function getTenantDatabaseName(Tenant $tenant): string
    {
        $connectionName = $this->getTenantConnectionName($tenant);
        return config("database.connections.{$connectionName}.database");
    }

    /**
     * Get tenant storage disk.
     *
     * @param Tenant $tenant
     * @return string
     */
    protected function getTenantStorageDisk(Tenant $tenant): string
    {
        return "tenant_{$tenant->id}";
    }

    /**
     * Helper method to get current tenant in tests.
     *
     * @return Tenant|null The current test tenant
     */
    protected function getCurrentTenant(): ?Tenant
    {
        return $this->testTenant;
    }

    /**
     * Set a custom tenant for testing.
     *
     * @param array $attributes Tenant attributes
     * @return Tenant The created tenant
     */
    protected function setTestTenant(array $attributes = []): Tenant
    {
        $this->testTenant = $this->createTestTenant($attributes);
        $this->actingAsTenant($this->testTenant);
        return $this->testTenant;
    }

    /**
     * Bulk create test tenants.
     *
     * @param int $count Number of tenants to create
     * @param array $attributes Base attributes for all tenants
     * @param array $customStates Custom states per tenant index
     * @return array<Tenant> The created tenants
     */
    protected function createTestTenants(
        int $count,
        array $attributes = [],
        array $customStates = []
    ): array {
        $tenants = [];

        for ($i = 0; $i < $count; $i++) {
            $tenantAttributes = $attributes;

            // Add unique identifier if not provided
            if (!isset($tenantAttributes['name']) && !isset($tenantAttributes['domain'])) {
                $tenantAttributes['name'] = "Test Tenant {$i}";
                $tenantAttributes['domain'] = "test-tenant-{$i}";
            }

            // Get custom states for this tenant
            $states = $customStates[$i] ?? [];

            $tenant = $this->createTestTenant($tenantAttributes, $states);
            $tenants[] = $tenant;
        }

        return $tenants;
    }
}