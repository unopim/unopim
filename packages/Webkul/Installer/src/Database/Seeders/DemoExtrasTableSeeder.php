<?php

namespace Webkul\Installer\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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

        try {
            if ($isMysql) {
                DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            }

            $appliedTables = [];

            foreach ($decoded['tables'] as $table => $rows) {
                if (! is_array($rows)) {
                    continue;
                }

                DB::table($table)->delete();

                if (empty($rows)) {
                    continue;
                }

                // DB::table()->insert() supports chunked inserts; chunk to
                // avoid MySQL max_allowed_packet limits on large payloads
                // (audits has 137 rows with big JSON columns).
                foreach (array_chunk($rows, 200) as $chunk) {
                    DB::table($table)->insert($chunk);
                }

                $appliedTables[] = $table;
            }

            if ($isMysql) {
                DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            }

            DatabaseSequenceHelper::fixSequences($appliedTables);

            $this->command?->info('Demo extras seeded successfully ('.count($appliedTables).' tables).');
        } catch (Throwable $e) {
            if ($isMysql) {
                DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            }
            $this->command?->error('Failed to seed demo extras: '.$e->getMessage());
        }
    }
}
