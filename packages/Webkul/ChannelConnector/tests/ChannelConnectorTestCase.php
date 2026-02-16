<?php

namespace Webkul\ChannelConnector\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Passport;
use Tests\TestCase;
use Webkul\AdminApi\Models\Apikey;
use Webkul\Tenant\Models\Tenant;
use Webkul\User\Contracts\Admin as AdminContract;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;
use Webkul\User\Tests\Concerns\UserAssertions;

abstract class ChannelConnectorTestCase extends TestCase
{
    use RefreshDatabase, UserAssertions;

    /**
     * All channel connector permissions for testing.
     */
    protected array $channelConnectorPermissions = [
        'channel_connector.connectors.view',
        'channel_connector.connectors.create',
        'channel_connector.connectors.edit',
        'channel_connector.connectors.delete',
        'channel_connector.mappings.view',
        'channel_connector.mappings.edit',
        'channel_connector.sync.view',
        'channel_connector.sync.create',
        'channel_connector.conflicts.view',
        'channel_connector.conflicts.edit',
        'channel_connector.webhooks.view',
        'channel_connector.webhooks.manage',
        'channel_connector.dashboard.view',
    ];

    protected ?Tenant $testTenant = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable foreign key constraints for SQLite
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        }

        if (Schema::hasTable('tenants')) {
            $this->testTenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
            core()->setCurrentTenantId($this->testTenant->id);
        }
    }

    protected function tearDown(): void
    {
        if (Schema::hasTable('tenants')) {
            core()->setCurrentTenantId(null);
        }

        $this->testTenant = null;

        parent::tearDown();
    }

    /**
     * Login as admin with all channel connector permissions.
     * This overrides the parent method to grant permissions automatically.
     */
    public function loginAsAdmin(?AdminContract $admin = null): AdminContract
    {
        if ($admin === null) {
            $role = Role::factory()->create([
                'permission_type' => 'custom',
                'permissions'     => $this->channelConnectorPermissions,
            ]);

            $admin = Admin::factory()->create([
                'password' => \Illuminate\Support\Facades\Hash::make('password'),
                'role_id'  => $role->id,
            ]);
        }

        $this->actingAs($admin, 'admin');

        return $admin;
    }

    /**
     * Login as admin without channel connector permissions (for ACL testing).
     */
    protected function loginAsAdminWithoutPermissions(): AdminContract
    {
        $role = Role::factory()->create([
            'permission_type' => 'custom',
            'permissions'     => ['dashboard'], // minimal permission, not channel_connector
        ]);

        $admin = Admin::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role_id'  => $role->id,
        ]);

        $this->actingAs($admin, 'admin');

        return $admin;
    }

    /**
     * Login with specific permissions (for ACL testing).
     */
    protected function loginWithPermissions(string $permissionType = 'custom', array $permissions = []): AdminContract
    {
        $role = Role::factory()->create([
            'permission_type' => $permissionType,
            'permissions'     => $permissions,
        ]);

        $admin = Admin::factory()->create([
            'password' => \Illuminate\Support\Facades\Hash::make('password'),
            'role_id'  => $role->id,
        ]);

        $this->actingAs($admin, 'admin');

        return $admin;
    }

    /**
     * Create an API token for admin authentication via OAuth2.
     */
    protected function createAdminApiToken(?Admin $admin = null, array $scopes = []): string
    {
        // Ensure tenant context is set before creating admin
        if (Schema::hasTable('tenants') && $this->testTenant) {
            core()->setCurrentTenantId($this->testTenant->id);
        }

        $admin = $admin ?? Admin::factory()->create([
            'role_id' => Role::factory()->create([
                'permission_type' => 'all',
            ])->id,
        ]);

        // Create api_keys record so ScopeMiddleware can check permission_type
        Apikey::create([
            'name'            => 'Test API Key',
            'admin_id'        => $admin->id,
            'permission_type' => 'all',
            'revoked'         => false,
        ]);

        // Use Passport's built-in actingAs for testing
        Passport::actingAs($admin, $scopes, 'api');

        // Return a mock token string - Passport handles auth internally
        return 'test-token';
    }
}
