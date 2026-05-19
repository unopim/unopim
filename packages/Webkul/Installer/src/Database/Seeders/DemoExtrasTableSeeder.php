<?php

namespace Webkul\Installer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use JsonException;
use Throwable;
use Webkul\Core\Helpers\Database\DatabaseSequenceHelper;

/**
 * Loads every non-product / non-category table that ships with the
 * demo catalog from `Installer/src/Database/Data/demo_extras.json`.
 *
 * The JSON is a single document of the form:
 *
 *   {
 *     "tables": {
 *       "locales": [{...row...}, ...],
 *       "channels": [...],
 *       ...
 *     }
 *   }
 *
 * Table keys are applied in-order so FK dependencies land safely
 * (parents before children). The seeder truncates each target table
 * before inserting to guarantee idempotency.
 *
 * Products, product_super_attributes and categories are intentionally
 * handled by ProductTableSeeder / CategoryDemoTableSeeder so the
 * variant-synthesis and image-copy logic stays in those classes.
 */
class DemoExtrasTableSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = __DIR__.'/../Data/demo_extras.json';

        if (! File::exists($jsonPath)) {
            $this->command?->error('demo_extras.json file not found.');

            return;
        }

        try {
            $decoded = json_decode(
                File::get($jsonPath),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            $this->command?->error('Failed to parse demo_extras.json: '.$e->getMessage());

            return;
        }

        if (! isset($decoded['tables']) || ! is_array($decoded['tables'])) {
            $this->command?->error('Invalid JSON: missing "tables" key.');

            return;
        }

        $driver = DB::getDriverName();
        $isMysql = in_array($driver, ['mysql', 'mariadb'], true);
        $isPgsql = $driver === 'pgsql';

        try {
            if ($isMysql) {
                DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            } elseif ($isPgsql) {

                DB::statement("SET session_replication_role = 'replica'");
            }

            $appliedTables = [];

            foreach ($decoded['tables'] as $table => $rows) {
                if (! is_array($rows)) {
                    continue;
                }

                // The Magic AI config entries in the demo dump point at hardcoded
                // platform/model/channel/locale values that don't reflect the
                // target install (encrypted api_key with a different APP_KEY,
                // model names the user hasn't configured, translation channels
                // the user hasn't set up). Strip these so Magic AI starts in the
                // same empty-placeholder state a fresh install has — the user
                // can opt into their own values via Configuration → Magic AI.
                if ($table === 'core_config') {
                    $rows = array_values(array_filter(
                        $rows,
                        static fn (array $row): bool => ! str_starts_with($row['code'] ?? '', 'general.magic_ai.')
                    ));
                }

                // The seeded platform row has an api_key encrypted with a
                // different APP_KEY and is thus useless on any install. Skip it.
                if ($table === 'magic_ai_platforms') {
                    $rows = [];
                }

                // The demo dump captures the admin@example.com / admin123 row
                // that was created on the source server. Replaying it here would
                // wipe out the admin record the user just configured via the
                // installer (see Installer::createAdminCredentials and
                // InstallerController::adminConfigSetup) and replace it with the
                // hardcoded demo credentials, locking the user out with their
                // chosen password. AdminsTableSeeder + the admin-config step
                // already own admin provisioning, so leave the table untouched.
                if ($table === 'admins') {
                    continue;
                }

                DB::table($table)->delete();

                if (empty($rows)) {
                    continue;
                }

                $rows = $this->castBooleanColumns($table, $rows);

                foreach (array_chunk($rows, 200) as $chunk) {
                    DB::table($table)->insert($chunk);
                }

                $appliedTables[] = $table;
            }

            if ($isMysql) {
                DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            } elseif ($isPgsql) {
                DB::statement("SET session_replication_role = 'origin'");
            }

            $tablesWithIdSequence = array_values(array_filter(
                $appliedTables,
                fn (string $table): bool => $this->hasIntegerIdSequence($table)
            ));

            DatabaseSequenceHelper::fixSequences($tablesWithIdSequence);

            $this->command?->info('Demo extras seeded successfully ('.count($appliedTables).' tables).');
        } catch (Throwable $e) {
            if ($isMysql) {
                DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            } elseif ($isPgsql) {

                try {
                    DB::statement("SET session_replication_role = 'origin'");
                } catch (Throwable) {
                }
            }
            $this->command?->error('Failed to seed demo extras: '.$e->getMessage());

            throw $e;
        }
    }

    /**
     * Convert integer 0/1 values to PHP bools for columns the schema
     * declares as `boolean`. PostgreSQL rejects integers in boolean columns
     * (SQLSTATE 42804); MySQL silently coerces them. Casting at the value
     * level keeps the JSON dump portable across both drivers without
     * having to ship two parallel demo datasets.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return array<int, array<string, mixed>>
     */
    protected function castBooleanColumns(string $table, array $rows): array
    {
        try {
            $columns = Schema::getColumns($table);
        } catch (Throwable) {
            return $rows;
        }

        $boolColumns = [];

        foreach ($columns as $column) {
            $name = $column['name'] ?? null;
            $typeName = strtolower((string) ($column['type_name'] ?? ''));
            $type = strtolower((string) ($column['type'] ?? ''));

            // Postgres returns 'bool'/'boolean'; MySQL stores boolean as
            // tinyint(1) and reports type_name='tinyint' / type='tinyint(1)'.
            $isBool = in_array($typeName, ['bool', 'boolean'], true)
                || $type === 'tinyint(1)';

            if ($name && $isBool) {
                $boolColumns[] = $name;
            }
        }

        if (empty($boolColumns)) {
            return $rows;
        }

        foreach ($rows as &$row) {
            foreach ($boolColumns as $col) {
                if (array_key_exists($col, $row) && is_int($row[$col])) {
                    $row[$col] = (bool) $row[$col];
                }
            }
        }

        return $rows;
    }

    /**
     * True if `$table` has an `id` column whose type is an integer-family
     * column backed by a sequence — i.e. the only shape
     * DatabaseSequenceHelper::fixSequence() knows how to handle.
     */
    protected function hasIntegerIdSequence(string $table): bool
    {
        try {
            $columns = $this->getTableColumns($table);
        } catch (Throwable) {
            return false;
        }

        foreach ($columns as $column) {
            if (($column['name'] ?? null) !== 'id') {
                continue;
            }

            $typeName = strtolower((string) ($column['type_name'] ?? ''));
            $type = strtolower((string) ($column['type'] ?? ''));

            return in_array($typeName, ['int', 'int2', 'int4', 'int8', 'integer', 'bigint', 'smallint', 'tinyint', 'mediumint'], true)
                || str_starts_with($type, 'int')
                || str_starts_with($type, 'bigint')
                || str_starts_with($type, 'smallint')
                || str_starts_with($type, 'tinyint')
                || str_starts_with($type, 'mediumint');
        }

        return false;
    }

    /**
     * Indirection so tests can stub the schema source without mocking the
     * Schema facade (which needs a real DB connection). Production callers
     * get the live introspection.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function getTableColumns(string $table): array
    {
        return Schema::getColumns($table);
    }
}
