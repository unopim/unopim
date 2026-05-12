<?php

use Illuminate\Support\Facades\DB;
use Webkul\Installer\Console\Commands\Installer;

it('lists every table in the configured schema regardless of prefix, so orphan tables from prior installs (e.g. pim_*) are picked up for cleanup', function () {
    $markers = [
        '__test_pim_orphan_'.uniqid(),
        '__test_add_orphan_'.uniqid(),
        '__test_orphan_'.uniqid(),
    ];

    foreach ($markers as $name) {
        DB::statement("CREATE TABLE IF NOT EXISTS `$name` (id INT PRIMARY KEY)");
    }

    try {
        $installer = app(Installer::class);

        $listed = (new ReflectionClass($installer))
            ->getMethod('listTablesInConfiguredSchema')
            ->invoke($installer);

        foreach ($markers as $name) {
            expect($listed)->toContain($name);
        }
    } finally {

        foreach ($markers as $name) {
            DB::statement("DROP TABLE IF EXISTS `$name`");
        }
    }
});
