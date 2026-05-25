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

        $userConfig = $this->snapshotUserConfig();

        try {
            if ($isMysql) {
                DB::statement('SET FOREIGN_KEY_CHECKS = 0');
            }

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

                if (empty($rows)) {
                    continue;
                }

                foreach (array_chunk($rows, 200) as $chunk) {
                    DB::table($table)->insert($chunk);
                }

                $appliedTables[] = $table;
            }

            if ($isMysql) {
                DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            }

            DatabaseSequenceHelper::fixSequences($appliedTables);

            $this->restoreUserConfig($userConfig);

            $this->command?->info('Demo extras seeded successfully ('.count($appliedTables).' tables).');
        } catch (Throwable $e) {
            if ($isMysql) {
                DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            }
            $this->command?->error('Failed to seed demo extras: '.$e->getMessage());
        }
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
                    ->map(static fn ($t) => ['locale' => $t->locale, 'name' => $t->name])
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
