<?php

use Illuminate\Support\Facades\DB;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;

describe('Product Importer pgsql bulk-mode compatibility (issue #798)', function () {
    it('does not issue MySQL-only SET SESSION statements when driver is not mysql', function () {
        config()->set('import.mysql_bulk_mode', true);

        $importer = app(Importer::class);

        $reflection = new ReflectionClass($importer);

        expect($reflection->hasMethod('toggleMysqlBulkMode'))
            ->toBeTrue('Importer must expose toggleMysqlBulkMode helper that gates MySQL session vars by driver');

        $method = $reflection->getMethod('toggleMysqlBulkMode');
        $method->setAccessible(true);

        $original = DB::getDefaultConnection();

        config()->set('database.connections.fake_pgsql', [
            'driver'   => 'pgsql',
            'host'     => '127.0.0.1',
            'port'     => 5432,
            'database' => 'unopim_fake',
            'username' => 'postgres',
            'password' => 'postgres',
        ]);

        DB::setDefaultConnection('fake_pgsql');

        try {
            $method->invoke($importer, false);
            $method->invoke($importer, true);
        } finally {
            DB::setDefaultConnection($original);
        }

        expect(true)->toBeTrue();
    });
});
