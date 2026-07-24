<?php

use Illuminate\Support\Facades\DB;
use Webkul\Installer\Database\Seeders\User\RolesTableSeeder;

/**
 * Regression: the Administrator role must always own role id 1 — the id the
 * installer and every admin seeder hardcode as the admin's role_id.
 *
 * The AdminApi "API" role migration runs during `migrate:fresh`, before the
 * base seeder. On a fresh install it claimed id 1 as an empty-permission
 * `custom` role, and the old seeder's "skip when id 1 exists" guard then left
 * the Administrator unseeded — so the freshly created admin inherited the empty
 * API role and hit the Bouncer 403 ("You do not have permission to access this
 * page") on login.
 */
describe('RolesTableSeeder keeps the Administrator at role id 1', function () {
    it('reasserts the Administrator when another role has claimed id 1', function () {
        // Simulate the API-role migration squatting id 1 before the seeder runs.
        DB::table('roles')->where('id', 1)->update([
            'name'            => 'API',
            'permission_type' => 'custom',
            'permissions'     => json_encode([]),
        ]);

        app(RolesTableSeeder::class)->run(['default_locale' => 'en_US']);

        $role = DB::table('roles')->where('id', 1)->first();

        expect($role)->not->toBeNull()
            ->and($role->permission_type)->toBe('all')
            ->and($role->name)->toBe('Administrator');
    });

    it('leaves an existing full-access Administrator untouched (respects operator renames)', function () {
        DB::table('roles')->where('id', 1)->update([
            'name'            => 'Chief Operator',
            'permission_type' => 'all',
        ]);

        app(RolesTableSeeder::class)->run(['default_locale' => 'en_US']);

        $role = DB::table('roles')->where('id', 1)->first();

        expect($role->permission_type)->toBe('all')
            ->and($role->name)->toBe('Chief Operator');
    });
});
