<?php

use Illuminate\Support\Facades\DB;
use Webkul\Installer\Database\Seeders\User\RolesTableSeeder;

it('does not delete rows from the admins table', function () {
    if (! DB::table('roles')->where('id', 1)->exists()) {
        DB::table('roles')->insert([
            'id'              => 1,
            'name'            => 'tmp-role',
            'description'     => 'tmp',
            'permission_type' => 'all',
        ]);
    }

    DB::table('admins')->insert([
        'id'           => 9999,
        'name'         => 'sentinel',
        'email'        => 'sentinel@example.test',
        'password'     => bcrypt('whatever'),
        'api_token'    => str_repeat('a', 80),
        'role_id'      => 1,
        'status'       => 1,
        'timezone'     => 'UTC',
        'ui_locale_id' => 58,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    app(RolesTableSeeder::class)->run();

    expect(DB::table('admins')->where('id', 9999)->exists())->toBeTrue();
});

it('inserts role id=1 when none exists', function () {
    DB::table('roles')->where('id', 1)->delete();

    app(RolesTableSeeder::class)->run();

    expect(DB::table('roles')->where('id', 1)->exists())->toBeTrue();
});

it('is idempotent — does not re-insert role id=1 when it already exists', function () {
    app(RolesTableSeeder::class)->run();
    DB::table('roles')->where('id', 1)->update(['name' => 'mutated-by-operator']);

    app(RolesTableSeeder::class)->run();

    expect(DB::table('roles')->where('id', 1)->value('name'))->toBe('mutated-by-operator');
});

it('does not wipe operator-added roles', function () {
    if (! DB::table('roles')->where('id', 1)->exists()) {
        app(RolesTableSeeder::class)->run();
    }

    DB::table('roles')->insert([
        'id'              => 5000,
        'name'            => 'operator-role',
        'description'     => 'added by ops',
        'permission_type' => 'all',
    ]);

    app(RolesTableSeeder::class)->run();

    expect(DB::table('roles')->where('id', 5000)->exists())->toBeTrue();
});
