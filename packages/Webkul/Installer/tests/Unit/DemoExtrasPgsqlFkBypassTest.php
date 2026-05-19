<?php

use Illuminate\Support\Facades\DB;
use Webkul\Installer\Database\Seeders\DemoExtrasTableSeeder;

/**
 * The CLI run reproduced the failure verbatim:
 */
beforeEach(function () {
    DB::spy();
});

it('emits SET session_replication_role replica/origin around the demo dump on pgsql', function () {
    DB::shouldReceive('getDriverName')->andReturn('pgsql');
    DB::shouldReceive('statement')->withArgs(fn ($sql) => $sql === "SET session_replication_role = 'replica'")->once();
    DB::shouldReceive('statement')->withArgs(fn ($sql) => $sql === "SET session_replication_role = 'origin'")->once();

    DB::shouldReceive('table')->andThrow(new RuntimeException('stop here'));
    DB::shouldReceive('statement')->byDefault();

    try {
        app(DemoExtrasTableSeeder::class)->run();
    } catch (Throwable) {

    }
});

it('does NOT emit session_replication_role on mysql (uses FOREIGN_KEY_CHECKS = 0 instead)', function () {
    DB::shouldReceive('getDriverName')->andReturn('mysql');
    DB::shouldReceive('statement')->withArgs(fn ($sql) => $sql === 'SET FOREIGN_KEY_CHECKS = 0')->once();
    DB::shouldReceive('statement')->withArgs(fn ($sql) => str_contains($sql, 'session_replication_role'))->never();
    DB::shouldReceive('table')->andThrow(new RuntimeException('stop here'));
    DB::shouldReceive('statement')->byDefault();

    try {
        app(DemoExtrasTableSeeder::class)->run();
    } catch (Throwable) {

    }
});
