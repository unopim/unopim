<?php

namespace Webkul\Installer\Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Webkul\Installer\Database\Seeders\Demo\Concerns\LoadsDemoData;

/**
 * Enables the demo locales/currencies and wires the demo channels.
 *
 * Operator-enabled locales and currencies are never disabled — the demo
 * set is added on top of whatever the installer was asked for.
 */
class DemoCoreSeeder extends Seeder
{
    use LoadsDemoData;

    public function run(): void
    {
        $data = $this->demoData('channels');

        DB::transaction(function () use ($data): void {
            DB::table('locales')->whereIn('code', $data['locales'])->update(['status' => 1]);
            DB::table('currencies')->whereIn('code', $data['currencies'])->update(['status' => 1]);

            $rootId = (int) DB::table('categories')->where('code', 'root')->value('id');

            foreach ($data['channels'] as $channel) {
                $this->seedChannel($channel, $rootId);
            }
        });
    }

    /**
     * @param  array<string, mixed>  $channel
     */
    protected function seedChannel(array $channel, int $rootId): void
    {
        $now = Date::now();

        $rootCategoryId = (int) (
            DB::table('categories')->where('code', $channel['root'])->value('id') ?: $rootId
        );

        DB::table('channels')->updateOrInsert(
            ['code' => $channel['code']],
            [
                'root_category_id' => $rootCategoryId,
                'updated_at'       => $now,
                'created_at'       => $now,
            ]
        );

        $channelId = (int) DB::table('channels')->where('code', $channel['code'])->value('id');

        foreach ($channel['names'] as $locale => $name) {
            DB::table('channel_translations')->updateOrInsert(
                ['channel_id' => $channelId, 'locale' => $locale],
                ['name' => $name, 'updated_at' => $now, 'created_at' => $now]
            );
        }

        $localeIds = DB::table('locales')->whereIn('code', $channel['locales'])->pluck('id');

        DB::table('channel_locales')->where('channel_id', $channelId)->delete();

        foreach ($localeIds as $localeId) {
            DB::table('channel_locales')->insert([
                'channel_id' => $channelId,
                'locale_id'  => $localeId,
            ]);
        }

        $currencyIds = DB::table('currencies')->whereIn('code', $channel['currencies'])->pluck('id');

        DB::table('channel_currencies')->where('channel_id', $channelId)->delete();

        foreach ($currencyIds as $currencyId) {
            DB::table('channel_currencies')->insert([
                'channel_id'  => $channelId,
                'currency_id' => $currencyId,
            ]);
        }
    }
}
