<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Backfill: measurement unit endpoints moved off the family API permissions onto
 * their own nodes, so grant each new node to any key already holding the family
 * counterpart it used to ride on. Without this, existing keys lose unit access.
 */
return new class extends Migration
{
    private const GRANTS = [
        'api.catalog.measurements'        => 'api.catalog.measurements.units',
        'api.catalog.measurements.create' => 'api.catalog.measurements.units.create',
        'api.catalog.measurements.edit'   => 'api.catalog.measurements.units.edit',
        'api.catalog.measurements.delete' => 'api.catalog.measurements.units.delete',
    ];

    public function up(): void
    {
        $this->eachCustomKey(function (object $apiKey, array $permissions): void {
            $granted = $permissions;

            foreach (self::GRANTS as $required => $grant) {
                if (
                    in_array($required, $permissions, true)
                    && ! in_array($grant, $granted, true)
                ) {
                    $granted[] = $grant;
                }
            }

            if ($granted !== $permissions) {
                $this->store($apiKey, $granted);
            }
        });
    }

    public function down(): void
    {
        $this->eachCustomKey(function (object $apiKey, array $permissions): void {
            $remaining = array_values(array_diff($permissions, array_values(self::GRANTS)));

            if ($remaining !== $permissions) {
                $this->store($apiKey, $remaining);
            }
        });
    }

    private function eachCustomKey(callable $callback): void
    {
        DB::table('api_keys')
            ->where('permission_type', 'custom')
            ->orderBy('id')
            ->each(function (object $apiKey) use ($callback): void {
                $permissions = json_decode($apiKey->permissions ?? '[]', true);

                if (! is_array($permissions)) {
                    return;
                }

                $callback($apiKey, $permissions);
            });
    }

    private function store(object $apiKey, array $permissions): void
    {
        DB::table('api_keys')
            ->where('id', $apiKey->id)
            ->update(['permissions' => json_encode(array_values(array_unique($permissions)))]);
    }
};
