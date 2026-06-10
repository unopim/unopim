<?php

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Webkul\Installer\Database\Seeders\User\AdminsTableSeeder;

uses(DatabaseTransactions::class);

beforeEach(function () {
    // The seeder only inserts into an empty admins table; clear it so the
    // insert path runs regardless of leftover rows (rolled back afterwards).
    DB::table('admins')->delete();
});

describe('AdminsTableSeeder honours APP_TIMEZONE (issue #846)', function () {
    it('sets the seeded admin timezone to config(app.timezone) instead of always defaulting to UTC', function () {
        config(['app.timezone' => 'Asia/Kolkata']);

        app(AdminsTableSeeder::class)->run();

        $admin = DB::table('admins')->where('id', 1)->first();

        expect($admin)->not->toBeNull()
            ->and($admin->timezone)->toBe('Asia/Kolkata');
    });

    it('falls back to UTC when no app.timezone is configured', function () {
        config(['app.timezone' => 'UTC']);

        app(AdminsTableSeeder::class)->run();

        $admin = DB::table('admins')->where('id', 1)->first();

        expect($admin->timezone)->toBe('UTC');
    });
});
