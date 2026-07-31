<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Webkul\AdminApi\Models\Apikey;
use Webkul\User\Models\Admin;

function measurementUnitPermissionsMigration(): Migration
{
    return require base_path(
        'packages/Webkul/Measurement/src/Database/Migrations/2026_07_29_120000_add_api_measurement_unit_permissions.php'
    );
}

function insertMeasurementApiKey(string $permissionType, ?string $permissions): int
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

function measurementApiKeyPermissions(int $id): mixed
{
    $raw = DB::table('api_keys')->where('id', $id)->value('permissions');

    return $raw === null ? null : json_decode($raw, true);
}

function seedMeasurementApiKeyScenarios(): array
{
    return [
        'familyView' => insertMeasurementApiKey('custom', json_encode([
            'api.catalog.measurements',
        ])),
        'familyWrite' => insertMeasurementApiKey('custom', json_encode([
            'api.catalog.measurements.create',
            'api.catalog.measurements.edit',
            'api.catalog.measurements.delete',
        ])),
        'fullAccess'      => insertMeasurementApiKey('all', null),
        'fullAccessArray' => insertMeasurementApiKey('all', json_encode([
            'api.catalog.measurements',
        ])),
        'malformed'      => insertMeasurementApiKey('custom', json_encode('api.catalog.measurements')),
        'nullCustom'     => insertMeasurementApiKey('custom', null),
        'alreadyGranted' => insertMeasurementApiKey('custom', json_encode([
            'api.catalog.measurements',
            'api.catalog.measurements.units',
            'api.catalog.measurements.create',
            'api.catalog.measurements.units.create',
        ])),
        'unrelated' => insertMeasurementApiKey('custom', json_encode([
            'api.catalog.products',
            'api.settings.locales',
        ])),
    ];
}

describe('measurement unit permission backfill migration', function () {
    it('grants each new unit node to the key holding its family counterpart', function () {
        $keys = seedMeasurementApiKeyScenarios();

        measurementUnitPermissionsMigration()->up();

        expect(measurementApiKeyPermissions($keys['familyView']))->toBe([
            'api.catalog.measurements',
            'api.catalog.measurements.units',
        ]);

        expect(measurementApiKeyPermissions($keys['familyWrite']))->toBe([
            'api.catalog.measurements.create',
            'api.catalog.measurements.edit',
            'api.catalog.measurements.delete',
            'api.catalog.measurements.units.create',
            'api.catalog.measurements.units.edit',
            'api.catalog.measurements.units.delete',
        ]);
    });

    it('leaves full access keys untouched', function () {
        $keys = seedMeasurementApiKeyScenarios();

        measurementUnitPermissionsMigration()->up();

        expect(measurementApiKeyPermissions($keys['fullAccess']))->toBeNull();
        expect(measurementApiKeyPermissions($keys['fullAccessArray']))->toBe([
            'api.catalog.measurements',
        ]);
    });

    it('skips rows whose permissions do not decode to an array', function () {
        $keys = seedMeasurementApiKeyScenarios();

        measurementUnitPermissionsMigration()->up();

        expect(measurementApiKeyPermissions($keys['malformed']))->toBe('api.catalog.measurements');
        expect(measurementApiKeyPermissions($keys['nullCustom']))->toBeNull();
    });

    it('leaves unrelated permission sets alone', function () {
        $keys = seedMeasurementApiKeyScenarios();

        measurementUnitPermissionsMigration()->up();

        expect(measurementApiKeyPermissions($keys['unrelated']))->toBe([
            'api.catalog.products',
            'api.settings.locales',
        ]);
    });

    it('does not duplicate nodes a key already holds', function () {
        $keys = seedMeasurementApiKeyScenarios();

        measurementUnitPermissionsMigration()->up();

        expect(measurementApiKeyPermissions($keys['alreadyGranted']))->toBe([
            'api.catalog.measurements',
            'api.catalog.measurements.units',
            'api.catalog.measurements.create',
            'api.catalog.measurements.units.create',
        ]);
    });

    it('is idempotent when run twice', function () {
        $keys = seedMeasurementApiKeyScenarios();

        measurementUnitPermissionsMigration()->up();

        $afterFirstRun = array_map(
            fn (int $id): mixed => measurementApiKeyPermissions($id),
            $keys
        );

        measurementUnitPermissionsMigration()->up();

        $afterSecondRun = array_map(
            fn (int $id): mixed => measurementApiKeyPermissions($id),
            $keys
        );

        expect($afterSecondRun)->toBe($afterFirstRun);
    });

    it('removes only the new unit nodes on rollback', function () {
        $keys = seedMeasurementApiKeyScenarios();

        $migration = measurementUnitPermissionsMigration();

        $migration->up();
        $migration->down();

        expect(measurementApiKeyPermissions($keys['familyView']))->toBe([
            'api.catalog.measurements',
        ]);

        expect(measurementApiKeyPermissions($keys['familyWrite']))->toBe([
            'api.catalog.measurements.create',
            'api.catalog.measurements.edit',
            'api.catalog.measurements.delete',
        ]);

        expect(measurementApiKeyPermissions($keys['alreadyGranted']))->toBe([
            'api.catalog.measurements',
            'api.catalog.measurements.create',
        ]);

        expect(measurementApiKeyPermissions($keys['fullAccess']))->toBeNull();
        expect(measurementApiKeyPermissions($keys['fullAccessArray']))->toBe([
            'api.catalog.measurements',
        ]);
        expect(measurementApiKeyPermissions($keys['malformed']))->toBe('api.catalog.measurements');
        expect(measurementApiKeyPermissions($keys['nullCustom']))->toBeNull();
        expect(measurementApiKeyPermissions($keys['unrelated']))->toBe([
            'api.catalog.products',
            'api.settings.locales',
        ]);
    });

    it('restores unit access through the model permission check after the backfill', function () {
        $keys = seedMeasurementApiKeyScenarios();

        $apiKey = Apikey::find($keys['familyView']);

        expect($apiKey->hasPermission('api.catalog.measurements.units'))->toBeFalse();

        measurementUnitPermissionsMigration()->up();

        expect($apiKey->fresh()->hasPermission('api.catalog.measurements.units'))->toBeTrue();
    });
});
