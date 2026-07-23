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
                try {
                    DB::statement("SET session_replication_role = 'replica'");
                } catch (Throwable $e) {
                    throw new \RuntimeException('PostgreSQL requires a superuser role to set session_replication_role; unable to bypass FK triggers for demo extras seeding.', $e->getCode(), previous: $e);
                }
            }

            $userConfig = $this->snapshotUserConfig();

            $appliedTables = [];

            foreach ($decoded['tables'] as $table => $rows) {
                if (! is_array($rows)) {
                    continue;
                }

                if ($table === 'core_config') {
                    $rows = array_values(array_filter(
                        $rows,
                        static fn (array $row): bool => ! str_starts_with($row['code'] ?? '', 'general.magic_ai.')
                    ));
                }

                if ($table === 'magic_ai_platforms') {
                    $rows = [];
                }

                if ($table === 'admins') {
                    continue;
                }

                DB::table($table)->delete();

                if ($rows === []) {
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
                $this->hasIntegerIdSequence(...)
            ));

            DatabaseSequenceHelper::fixSequences($tablesWithIdSequence);

            $this->restoreUserConfig($userConfig);

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

        if ($boolColumns === []) {
            return $rows;
        }

        foreach ($rows as &$row) {
            foreach ($boolColumns as $col) {
                if (array_key_exists($col, $row) && is_int($row[$col])) {
                    $row[$col] = (bool) $row[$col];
                }
            }
        }
        unset($row);

        return $rows;
    }

    /**
     * True if `$table` has an `id` column whose type is an integer-family
     * column — i.e. the only shape DatabaseSequenceHelper::fixSequence()
     * knows how to handle.
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

    /**
     * Snapshot the locale / currency / channel-wiring the user configured
     * during the base installer so it can be re-applied after the demo
     * dump replaces these tables wholesale. Channels are keyed by code
     * because the demo dump reassigns their ids.
     *
     * @return array{
     *     locale_codes: array<int, string>,
     *     currency_codes: array<int, string>,
     *     channel_wiring: array<string, array{locale_codes: array<int, string>, currency_codes: array<int, string>, translations: array<int, array{locale: string, name: string}>}>
     * }
     */
    protected function snapshotUserConfig(): array
    {
        $enabledLocaleCodes = DB::table('locales')->where('status', 1)->pluck('code')->all();
        $enabledCurrencyCodes = DB::table('currencies')->where('status', 1)->pluck('code')->all();

        $channelWiring = [];

        foreach (DB::table('channels')->get(['id', 'code']) as $channel) {
            $channelWiring[$channel->code] = [
                'locale_codes' => DB::table('channel_locales')
                    ->join('locales', 'locales.id', '=', 'channel_locales.locale_id')
                    ->where('channel_locales.channel_id', $channel->id)
                    ->pluck('locales.code')
                    ->all(),
                'currency_codes' => DB::table('channel_currencies')
                    ->join('currencies', 'currencies.id', '=', 'channel_currencies.currency_id')
                    ->where('channel_currencies.channel_id', $channel->id)
                    ->pluck('currencies.code')
                    ->all(),
                'translations' => DB::table('channel_translations')
                    ->where('channel_id', $channel->id)
                    ->get(['locale', 'name'])
                    ->map(static fn ($t): array => ['locale' => $t->locale, 'name' => $t->name])
                    ->all(),
            ];
        }

        return [
            'locale_codes'   => $enabledLocaleCodes,
            'currency_codes' => $enabledCurrencyCodes,
            'channel_wiring' => $channelWiring,
        ];
    }

    /**
     * Re-apply the user's locale / currency / channel selections on top of
     * the demo dump. The dump's own enabled locales (en_US, de_DE, fr_FR)
     * and currencies (USD, EUR) stay enabled too — they are required by
     * the demo product translations — but the user's picks are added
     * back so installing with sample products no longer wipes them.
     *
     * @param  array{
     *     locale_codes: array<int, string>,
     *     currency_codes: array<int, string>,
     *     channel_wiring: array<string, array{locale_codes: array<int, string>, currency_codes: array<int, string>, translations: array<int, array{locale: string, name: string}>}>
     * }  $state
     */
    protected function restoreUserConfig(array $state): void
    {
        if (! empty($state['locale_codes'])) {
            DB::table('locales')
                ->whereIn('code', $state['locale_codes'])
                ->update(['status' => 1]);
        }

        if (! empty($state['currency_codes'])) {
            DB::table('currencies')
                ->whereIn('code', $state['currency_codes'])
                ->update(['status' => 1]);
        }

        foreach ($state['channel_wiring'] as $code => $wiring) {
            $channelId = DB::table('channels')->where('code', $code)->value('id');

            if (! $channelId) {
                continue;
            }

            $localeIds = DB::table('locales')
                ->whereIn('code', $wiring['locale_codes'])
                ->pluck('id')
                ->all();

            foreach ($localeIds as $localeId) {
                DB::table('channel_locales')->updateOrInsert(
                    ['channel_id' => $channelId, 'locale_id' => $localeId],
                    []
                );
            }

            $currencyIds = DB::table('currencies')
                ->whereIn('code', $wiring['currency_codes'])
                ->pluck('id')
                ->all();

            foreach ($currencyIds as $currencyId) {
                DB::table('channel_currencies')->updateOrInsert(
                    ['channel_id' => $channelId, 'currency_id' => $currencyId],
                    []
                );
            }

            foreach ($wiring['translations'] as $translation) {
                DB::table('channel_translations')->updateOrInsert(
                    ['channel_id' => $channelId, 'locale' => $translation['locale']],
                    ['name' => $translation['name']]
                );
            }
        }
    }
}
