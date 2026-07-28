<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Quick export used to be reachable by anyone who could view the product grid,
 * because it is a query parameter on the grid's own index route rather than a
 * route of its own. Now that it has a dedicated `catalog.products.quick_export`
 * key, grant it to every custom role that already holds `catalog.products` so
 * existing installs keep the capability they had before the upgrade.
 */
return new class extends Migration
{
    private const REQUIRED_KEY = 'catalog.products';

    private const GRANTED_KEY = 'catalog.products.quick_export';

    public function up(): void
    {
        $this->eachCustomRole(function (object $role, array $permissions): void {
            if (
                ! in_array(self::REQUIRED_KEY, $permissions, true)
                || in_array(self::GRANTED_KEY, $permissions, true)
            ) {
                return;
            }

            $permissions[] = self::GRANTED_KEY;

            $this->store($role, $permissions);
        });
    }

    public function down(): void
    {
        $this->eachCustomRole(function (object $role, array $permissions): void {
            if (! in_array(self::GRANTED_KEY, $permissions, true)) {
                return;
            }

            $this->store($role, array_values(array_diff($permissions, [self::GRANTED_KEY])));
        });
    }

    private function eachCustomRole(callable $callback): void
    {
        DB::table('roles')
            ->where('permission_type', 'custom')
            ->orderBy('id')
            ->each(function (object $role) use ($callback): void {
                $permissions = json_decode($role->permissions ?? '[]', true);

                if (! is_array($permissions)) {
                    return;
                }

                $callback($role, $permissions);
            });
    }

    private function store(object $role, array $permissions): void
    {
        DB::table('roles')
            ->where('id', $role->id)
            ->update(['permissions' => json_encode(array_values(array_unique($permissions)))]);
    }
};
