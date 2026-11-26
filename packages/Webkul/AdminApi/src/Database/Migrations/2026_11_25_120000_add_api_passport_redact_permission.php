<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const string REQUIRED = 'api.catalog.passports.withdraw';

    private const string GRANT = 'api.catalog.passports.redact';

    public function up(): void
    {
        $this->eachCustomKey(function (object $apiKey, array $permissions): void {
            if (in_array(self::REQUIRED, $permissions, true) && ! in_array(self::GRANT, $permissions, true)) {
                $this->store($apiKey, [...$permissions, self::GRANT]);
            }
        });
    }

    public function down(): void
    {
        $this->eachCustomKey(function (object $apiKey, array $permissions): void {
            if (in_array(self::GRANT, $permissions, true)) {
                $this->store($apiKey, array_diff($permissions, [self::GRANT]));
            }
        });
    }

    private function eachCustomKey(callable $callback): void
    {
        DB::table('api_keys')
            ->where('permission_type', 'custom')
            ->lazyById()
            ->each(function (object $apiKey) use ($callback): void {
                $permissions = json_decode($apiKey->permissions ?? '[]', true);

                if (is_array($permissions)) {
                    $callback($apiKey, $permissions);
                }
            });
    }

    private function store(object $apiKey, array $permissions): void
    {
        DB::table('api_keys')
            ->where('id', $apiKey->id)
            ->update(['permissions' => json_encode(array_values(array_unique($permissions)))]);
    }
};
