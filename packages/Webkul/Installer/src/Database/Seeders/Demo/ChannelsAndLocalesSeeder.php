<?php

namespace Webkul\Installer\Database\Seeders\Demo;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Installer\Demo\DemoDataProfile;

/**
 * Activates the 4 demo channels and 8 demo locales referenced by the
 * reference-catalog seeders. Runs before every family seeder so the
 * downstream seeders can populate per-channel / per-locale values
 * against rows that actually exist in the DB.
 *
 * Idempotent: checks for existing rows by code and skips anything
 * already present. Safe to re-run.
 *
 * Locales activated:
 *   en_US · en_GB · de_DE · fr_FR · es_ES · it_IT · nl_NL · pl_PL
 *
 * Currencies activated:
 *   USD · EUR · GBP · CHF · CNY
 *
 * Channels created:
 *   default (preserved if it already exists)
 *   ecommerce        — Web ecommerce, all 8 locales + USD/EUR/GBP/CHF
 *   mobile_app       — Mobile app, 5 locales + USD/EUR/GBP
 *   print_catalogue  — Print catalogue, 3 locales + EUR
 *   b2b_wholesale    — B2B wholesale, 3 locales + USD/EUR/CNY
 */
class ChannelsAndLocalesSeeder extends Seeder
{
    /** @var string[] The locales Phase-0+ demo data expects to be active. */
    protected const DEMO_LOCALES = [
        'en_US', 'en_GB', 'de_DE', 'fr_FR', 'es_ES', 'it_IT', 'nl_NL', 'pl_PL',
    ];

    /** @var string[] The currencies Phase-0+ demo data expects to be active. */
    protected const DEMO_CURRENCIES = ['USD', 'EUR', 'GBP', 'CHF', 'CNY'];

    /**
     * Channel definitions mapping channel code → per-channel metadata.
     *
     * @var array<string, array{labels: array<string, string>, locales: string[], currencies: string[]}>
     */
    protected const DEMO_CHANNELS = [
        'ecommerce' => [
            'labels' => [
                'en_US' => 'E-commerce (Web)',
                'en_GB' => 'E-commerce (Web)',
                'de_DE' => 'E-Commerce (Web)',
                'fr_FR' => 'E-commerce (Web)',
                'es_ES' => 'Comercio electrónico (Web)',
                'it_IT' => 'E-commerce (Web)',
                'nl_NL' => 'E-commerce (Web)',
                'pl_PL' => 'E-commerce (Web)',
            ],
            'locales'    => ['en_US', 'en_GB', 'de_DE', 'fr_FR', 'es_ES', 'it_IT', 'nl_NL', 'pl_PL'],
            'currencies' => ['USD', 'EUR', 'GBP', 'CHF'],
        ],
        'mobile_app' => [
            'labels' => [
                'en_US' => 'Mobile App',
                'en_GB' => 'Mobile App',
                'de_DE' => 'Mobile App',
                'fr_FR' => 'Application Mobile',
                'es_ES' => 'Aplicación móvil',
            ],
            'locales'    => ['en_US', 'en_GB', 'de_DE', 'fr_FR', 'es_ES'],
            'currencies' => ['USD', 'EUR', 'GBP'],
        ],
        'print_catalogue' => [
            'labels' => [
                'en_US' => 'Print Catalogue',
                'de_DE' => 'Print-Katalog',
                'fr_FR' => 'Catalogue imprimé',
            ],
            'locales'    => ['en_US', 'de_DE', 'fr_FR'],
            'currencies' => ['EUR'],
        ],
        'b2b_wholesale' => [
            'labels' => [
                'en_US' => 'B2B Wholesale',
                'de_DE' => 'B2B-Großhandel',
                'fr_FR' => 'B2B (Gros)',
            ],
            'locales'    => ['en_US', 'de_DE', 'fr_FR'],
            'currencies' => ['USD', 'EUR', 'CNY'],
        ],
    ];

    public function run(array $parameters = []): void
    {
        $profile = $parameters['profile'] ?? DemoDataProfile::fromPreset(DemoDataProfile::PRESET_STARTER);

        if ($profile->isMinimal()) {
            $this->command?->info('ChannelsAndLocalesSeeder: minimal preset — nothing to activate.');

            return;
        }

        $this->command?->info('Activating demo locales, currencies and channels…');

        DB::transaction(function (): void {
            $this->activateLocales();
            $this->activateCurrencies();
            $this->createChannels();
        });

        $this->command?->info('ChannelsAndLocalesSeeder complete.');
    }

    protected function activateLocales(): void
    {
        DB::table('locales')
            ->whereIn('code', self::DEMO_LOCALES)
            ->update(['status' => 1]);
    }

    protected function activateCurrencies(): void
    {
        DB::table('currencies')
            ->whereIn('code', self::DEMO_CURRENCIES)
            ->update(['status' => 1]);
    }

    protected function createChannels(): void
    {
        $rootCategoryId = DB::table('categories')->where('code', 'root')->value('id') ?? 1;
        $now = Carbon::now();

        // Backfill the pre-existing `default` channel to all 8 locales + 5
        // currencies so admins who land on the default channel (which is
        // usually the starting point) immediately see multi-locale /
        // multi-currency content. Without this, default stays wired to
        // just the original install-time locale.
        $defaultChannelId = DB::table('channels')->where('code', 'default')->value('id');
        if ($defaultChannelId) {
            $this->wireChannelLocales($defaultChannelId, self::DEMO_LOCALES);
            $this->wireChannelCurrencies($defaultChannelId, self::DEMO_CURRENCIES);
        }

        foreach (self::DEMO_CHANNELS as $code => $spec) {
            $channelId = DB::table('channels')->where('code', $code)->value('id');

            if (! $channelId) {
                $channelId = DB::table('channels')->insertGetId([
                    'code'             => $code,
                    'root_category_id' => $rootCategoryId,
                    'created_at'       => $now,
                    'updated_at'       => $now,
                ]);
            }

            $this->upsertChannelTranslations($channelId, $spec['labels']);
            $this->wireChannelLocales($channelId, $spec['locales']);
            $this->wireChannelCurrencies($channelId, $spec['currencies']);
        }
    }

    protected function upsertChannelTranslations(int $channelId, array $labels): void
    {
        $now = Carbon::now();

        foreach ($labels as $localeCode => $label) {
            $exists = DB::table('channel_translations')
                ->where('channel_id', $channelId)
                ->where('locale', $localeCode)
                ->exists();

            if ($exists) {
                DB::table('channel_translations')
                    ->where('channel_id', $channelId)
                    ->where('locale', $localeCode)
                    ->update(['name' => $label, 'updated_at' => $now]);

                continue;
            }

            DB::table('channel_translations')->insert([
                'channel_id' => $channelId,
                'locale'     => $localeCode,
                'name'       => $label,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    protected function wireChannelLocales(int $channelId, array $localeCodes): void
    {
        $localeIds = DB::table('locales')
            ->whereIn('code', $localeCodes)
            ->pluck('id');

        foreach ($localeIds as $localeId) {
            DB::table('channel_locales')->updateOrInsert(
                ['channel_id' => $channelId, 'locale_id' => $localeId],
                []
            );
        }
    }

    protected function wireChannelCurrencies(int $channelId, array $currencyCodes): void
    {
        $currencyIds = DB::table('currencies')
            ->whereIn('code', $currencyCodes)
            ->pluck('id');

        foreach ($currencyIds as $currencyId) {
            DB::table('channel_currencies')->updateOrInsert(
                ['channel_id' => $channelId, 'currency_id' => $currencyId],
                []
            );
        }
    }
}
