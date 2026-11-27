<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Webkul\User\Models\Admin;

/** The redact permission backfill migration. */
function passportRedactPermissionMigration(): Migration
{
    return require base_path(
        'packages/Webkul/AdminApi/src/Database/Migrations/2026_11_25_120000_add_api_passport_redact_permission.php'
    );
}

/** Inserts an api key row with raw permissions. */
function insertPassportApiKey(string $permissionType, ?string $permissions): int
{
    return DB::table('api_keys')->insertGetId([
        'name'            => 'key_'.uniqid(),
        'admin_id'        => Admin::factory()->create()->id,
        'permission_type' => $permissionType,
        'revoked'         => false,
        'permissions'     => $permissions,
        'created_at'      => now(),
        'updated_at'      => now(),
    ]);
}

/** The decoded permissions of an api key row. */
function passportApiKeyPermissions(int $id): mixed
{
    $raw = DB::table('api_keys')->where('id', $id)->value('permissions');

    return $raw === null ? null : json_decode($raw, true);
}

/**
 * One api key per backfill scenario, keyed by scenario.
 *
 * @return array<string, int>
 */
function seedPassportApiKeys(): array
{
    return [
        'withdraw'       => insertPassportApiKey('custom', json_encode(['api.catalog.passports', 'api.catalog.passports.withdraw'])),
        'alreadyGranted' => insertPassportApiKey('custom', json_encode(['api.catalog.passports.withdraw', 'api.catalog.passports.redact'])),
        'unrelated'      => insertPassportApiKey('custom', json_encode(['api.catalog.passports', 'api.catalog.passports.publish'])),
        'fullAccess'     => insertPassportApiKey('all', json_encode(['api.catalog.passports.withdraw'])),
        'malformed'      => insertPassportApiKey('custom', json_encode('api.catalog.passports.withdraw')),
        'nullCustom'     => insertPassportApiKey('custom', null),
    ];
}

it('grants redact to every custom key holding withdraw and leaves the rest alone', function (): void {
    $keys = seedPassportApiKeys();

    passportRedactPermissionMigration()->up();

    expect(passportApiKeyPermissions($keys['withdraw']))->toBe(['api.catalog.passports', 'api.catalog.passports.withdraw', 'api.catalog.passports.redact'])
        ->and(passportApiKeyPermissions($keys['alreadyGranted']))->toBe(['api.catalog.passports.withdraw', 'api.catalog.passports.redact'])
        ->and(passportApiKeyPermissions($keys['unrelated']))->toBe(['api.catalog.passports', 'api.catalog.passports.publish'])
        ->and(passportApiKeyPermissions($keys['fullAccess']))->toBe(['api.catalog.passports.withdraw'])
        ->and(passportApiKeyPermissions($keys['malformed']))->toBe('api.catalog.passports.withdraw')
        ->and(passportApiKeyPermissions($keys['nullCustom']))->toBeNull();
});

it('is idempotent when run twice', function (): void {
    $keys = seedPassportApiKeys();
    $migration = passportRedactPermissionMigration();

    $migration->up();
    $afterFirstRun = array_map(fn (int $id): mixed => passportApiKeyPermissions($id), $keys);

    $migration->up();

    expect(array_map(fn (int $id): mixed => passportApiKeyPermissions($id), $keys))->toBe($afterFirstRun);
});

it('removes only the redact permission on rollback', function (): void {
    $keys = seedPassportApiKeys();
    $migration = passportRedactPermissionMigration();

    $migration->up();
    $migration->down();

    expect(passportApiKeyPermissions($keys['withdraw']))->toBe(['api.catalog.passports', 'api.catalog.passports.withdraw'])
        ->and(passportApiKeyPermissions($keys['unrelated']))->toBe(['api.catalog.passports', 'api.catalog.passports.publish']);
});
