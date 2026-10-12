<?php

use Illuminate\Support\Facades\DB;
use Webkul\Installer\Database\Seeders\User\RolesTableSeeder;

/**
 * Regression: Administrator must own role id 1 (hardcoded as admin's role_id). The API-role migration once squatted
 * id 1 before this seeder, and the old "skip when id 1 exists" guard left the admin on an empty role → Bouncer 403.
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
