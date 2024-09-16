<?php

namespace Webkul\User\Tests\Concerns;

use Illuminate\Support\Facades\Hash;
use Webkul\User\Contracts\Admin as AdminContract;
use Webkul\User\Models\Admin;
use Webkul\User\Models\Role;

trait UserAssertions
{
    /**
     * Assert model wise.
     */
    public function assertModelWise(array $modelWiseAssertions): void
    {
        foreach ($modelWiseAssertions as $modelClassName => $modelAssertions) {
            foreach ($modelAssertions as $assertion) {
                $this->assertDatabaseHas(app($modelClassName)->getTable(), $assertion);
            }
        }
    }

    /**
     * Table name to use with assertDatabaseHas
     */
    public function getFullTableName($className): string
    {
        return app($className)->getTable();
    }

    public function loginAsAdmin(?AdminContract $admin = null): AdminContract
    {
        $admin = $admin ?? Admin::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin, 'admin');

        return $admin;
    }

    /**
     * @param  string  $permissionTye
     * @param  array  $permissions
     * @return AdminContract
     *
     * Get user with specified permissions
     */
    public function loginWithPermissions(string $permissionType = 'custom', mixed $permissions = ['dashboard']): AdminContract
    {
        $role = Role::factory()->create(['permission_type' => $permissionType, 'permissions' => $permissions]);

        $admin = Admin::factory()->create([
            'password' => Hash::make('password'),
            'role_id'  => $role->id,
        ]);

        $this->actingAs($admin, 'admin');

        return $admin;
    }
}
