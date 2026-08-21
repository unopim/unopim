<?php

use Illuminate\Support\Facades\DB;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;

/**
 * MariaDB honours `unique_checks` and `foreign_key_checks` exactly as MySQL
 * does, so gating the toggles on the literal `mysql` driver string silently
 * drops the bulk-import optimisation for every MariaDB install.
 */
describe('Product Importer MariaDB bulk-mode compatibility', function () {
    beforeEach(function () {
        $this->original = DB::getDefaultConnection();

        /**
         * Resolved against the real connection first: the importer touches the
         * database while booting, so it cannot be built once the default points
         * at a driver with no server behind it.
         */
        $importer = app(Importer::class);

        $this->supportsBulkMode = function (string $driver) use ($importer): bool {
            config()->set("database.connections.fake_{$driver}", [
                'driver'   => $driver,
                'host'     => '127.0.0.1',
                'database' => 'unopim_fake',
                'username' => 'unopim',
                'password' => 'unopim',
            ]);

            DB::setDefaultConnection("fake_{$driver}");

            return (new ReflectionMethod(Importer::class, 'supportsBulkModeToggles'))->invoke($importer);
        };
    });

    afterEach(function () {
        DB::setDefaultConnection($this->original);
    });

    it('applies bulk-mode session vars on mysql', function () {
        expect(($this->supportsBulkMode)('mysql'))->toBeTrue();
    });

    it('applies bulk-mode session vars on mariadb', function () {
        expect(($this->supportsBulkMode)('mariadb'))->toBeTrue();
    });

    it('skips bulk-mode session vars on pgsql', function () {
        expect(($this->supportsBulkMode)('pgsql'))->toBeFalse();
    });
});
