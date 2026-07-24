<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The `configuration.system_settings.edit` ACL key was renamed to
 * `configuration.system_settings.appearance` when System Settings moved to
 * per-section permissions. Rewrite the key inside every custom role's stored
 * permission set so existing grants keep the same access under the new name.
 */
return new class extends Migration
{
    private const OLD_KEY = 'configuration.system_settings.edit';

    private const NEW_KEY = 'configuration.system_settings.appearance';

    public function up(): void
    {
        $this->rewrite(self::OLD_KEY, self::NEW_KEY);
    }

    public function down(): void
    {
        $this->rewrite(self::NEW_KEY, self::OLD_KEY);
    }

    private function rewrite(string $from, string $to): void
    {
        DB::table('roles')
            ->where('permission_type', 'custom')
            ->orderBy('id')
            ->each(function (object $role) use ($from, $to): void {
                $permissions = json_decode($role->permissions ?? '[]', true);

                if (! is_array($permissions) || ! in_array($from, $permissions, true)) {
                    return;
                }

                $permissions = array_values(array_unique(array_map(
                    fn (string $permission): string => $permission === $from ? $to : $permission,
                    $permissions
                )));

                DB::table('roles')
                    ->where('id', $role->id)
                    ->update(['permissions' => json_encode($permissions)]);
            });
    }
};
