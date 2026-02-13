<?php

namespace Webkul\Tenant\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\CreatesApplication;
use Webkul\Tenant\Models\Tenant;

abstract class TenantTestCase extends TestCase
{
    use CreatesApplication, RefreshDatabase;

    protected Tenant $tenantA;

    protected Tenant $tenantB;

    /**
     * Per-tenant fixture data: ['tenantId' => ['role_id' => ..., 'admin_id' => ..., ...]].
     */
    protected array $fixtures = [];

    protected function refreshTestDatabase(): void
    {
        if (! RefreshDatabaseState::$migrated) {
            $this->artisan('migrate:fresh', $this->migrateFreshUsing());

            // Create test-only stub table (not part of app migrations)
            Schema::create('tenant_test_stubs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->string('name');
                $table->timestamps();
            });

            $this->app[\Illuminate\Contracts\Console\Kernel::class]->setArtisan(null);

            RefreshDatabaseState::$migrated = true;
        }

        $this->beginDatabaseTransaction();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenantA = Tenant::factory()->create(['domain' => 'tenant-a']);
        $this->tenantB = Tenant::factory()->create(['domain' => 'tenant-b']);

        $this->seedTenantFixtures($this->tenantA);
        $this->seedTenantFixtures($this->tenantB);

        core()->setCurrentTenantId($this->tenantA->id);
    }

    protected function tearDown(): void
    {
        core()->setCurrentTenantId(null);

        parent::tearDown();
    }

    /**
     * Switch current tenant context.
     */
    protected function actingAsTenant(Tenant $tenant): static
    {
        core()->setCurrentTenantId($tenant->id);

        return $this;
    }

    /**
     * Alias for actingAsTenant().
     */
    protected function switchTenant(Tenant $tenant): void
    {
        $this->actingAsTenant($tenant);
    }

    /**
     * Clear tenant context (platform/operator mode).
     */
    protected function clearTenantContext(): void
    {
        core()->setCurrentTenantId(null);
    }

    /**
     * Assert that a model class is properly tenant-isolated.
     *
     * Creates a record in Tenant A, switches to Tenant B, verifies count is 0.
     */
    protected function assertTenantIsolated(string $modelClass, array $attributes = []): void
    {
        // Create in Tenant A
        $this->actingAsTenant($this->tenantA);
        $model = new $modelClass;
        $table = $model->getTable();
        $record = DB::table($table)->insertGetId(array_merge(
            ['tenant_id' => $this->tenantA->id],
            $attributes,
        ));

        // Verify Tenant A sees it
        $countA = $modelClass::count();
        $this->assertGreaterThanOrEqual(1, $countA, "Tenant A should see the record in {$table}");

        // Switch to Tenant B and verify isolation
        $this->actingAsTenant($this->tenantB);
        $countB = $modelClass::where('id', $record)->count();
        $this->assertEquals(0, $countB, "Tenant B should NOT see Tenant A's record in {$table}");

        // Restore Tenant A context
        $this->actingAsTenant($this->tenantA);
    }

    /**
     * Get fixture data for a tenant.
     */
    protected function fixture(Tenant $tenant, string $key): mixed
    {
        return $this->fixtures[$tenant->id][$key] ?? null;
    }

    /**
     * Seed baseline fixtures for a tenant: role, admin, attribute family.
     */
    private function seedTenantFixtures(Tenant $tenant): void
    {
        $tid = $tenant->id;
        $now = now();

        $roleId = DB::table('roles')->insertGetId([
            'name'            => "Admin Role ({$tenant->domain})",
            'description'     => 'Default admin role',
            'permission_type' => 'all',
            'permissions'     => json_encode([]),
            'tenant_id'       => $tid,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        $adminId = DB::table('admins')->insertGetId([
            'name'       => "Admin ({$tenant->domain})",
            'email'      => "admin@{$tenant->domain}.test",
            'password'   => bcrypt('password'),
            'role_id'    => $roleId,
            'status'     => 1,
            'tenant_id'  => $tid,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $familyId = DB::table('attribute_families')->insertGetId([
            'code'      => "default-{$tenant->domain}",
            'tenant_id' => $tid,
        ]);

        $this->fixtures[$tid] = [
            'role_id'   => $roleId,
            'admin_id'  => $adminId,
            'family_id' => $familyId,
        ];
    }
}
