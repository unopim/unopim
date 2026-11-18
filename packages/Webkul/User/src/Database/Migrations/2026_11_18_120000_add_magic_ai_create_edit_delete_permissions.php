<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill: Magic AI prompts and system prompts gained their own create / edit / delete
 * permissions. Roles that could already perform those actions keep them.
 */
return new class extends Migration
{
    /** Newly granted key => the existing key that implied it. */
    private const GRANTS = [
        'ai-agent.prompt.create'        => 'ai-agent.prompt.edit',
        'ai-agent.system-prompt.create' => 'ai-agent.system-prompt.edit',
    ];

    public function up(): void
    {
        $this->eachCustomRole(function (object $role, array $permissions): void {
            $updated = $permissions;

            foreach (self::GRANTS as $granted => $required) {
                if (in_array($required, $permissions, true) && ! in_array($granted, $updated, true)) {
                    $updated[] = $granted;
                }
            }

            if ($updated !== $permissions) {
                $this->store($role, $updated);
            }
        });
    }

    public function down(): void
    {
        $this->eachCustomRole(function (object $role, array $permissions): void {
            $updated = array_values(array_diff($permissions, array_keys(self::GRANTS)));

            if ($updated !== $permissions) {
                $this->store($role, $updated);
            }
        });
    }

    private function eachCustomRole(callable $callback): void
    {
        DB::table('roles')
            ->where('permission_type', 'custom')
            ->orderBy('id')
            ->each(function (object $role) use ($callback): void {
                $permissions = json_decode($role->permissions ?? '[]', true);

                if (is_array($permissions)) {
                    $callback($role, $permissions);
                }
            });
    }

    private function store(object $role, array $permissions): void
    {
        DB::table('roles')
            ->where('id', $role->id)
            ->update(['permissions' => json_encode(array_values(array_unique($permissions)))]);
    }
};
