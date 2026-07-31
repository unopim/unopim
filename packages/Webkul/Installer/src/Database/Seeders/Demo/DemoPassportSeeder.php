<?php

namespace Webkul\Installer\Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Throwable;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\Installer\Database\Seeders\Demo\Concerns\LoadsDemoData;
use Webkul\Product\Models\Product;
use Webkul\ProductPassport\Database\Seeders\PassportPresetSeeder;
use Webkul\Publication\Services\Publisher;

/**
 * Installs the shipped passport presets, binds the ESPR template to the demo
 * catalog and publishes digital product passports.
 *
 * The presets ship with every field unmapped because binding them is an
 * operator decision; the demo makes that decision so the template is a working
 * example rather than an empty shell. Publication runs through {@see Publisher}
 * so payloads, checksums and version history are produced by the real service.
 */
class DemoPassportSeeder extends Seeder
{
    use LoadsDemoData;

    protected const TEMPLATE_CODE = 'espr_general';

    /**
     * Families the ESPR template is bound to. A family maps to exactly one
     * template, so the battery preset stays installed but unbound — it is
     * there to be explored and assigned to a battery family of your own.
     */
    protected const TEMPLATE_FAMILIES = [
        'audio_electronics', 'apparel', 'home_kitchen', 'outdoor',
        'furniture_lighting', 'sports_fitness', 'beauty_personal_care',
    ];

    protected const PUBLISH_CHANNEL = 'ecommerce';

    /**
     * Products left unpublished on purpose, so the passport grid shows work in
     * progress next to live passports rather than a uniformly green list.
     */
    protected const WITHHELD = [
        'aurex-echo-soundbar',
        'kinetiq-pulse-rowing-machine',
    ];

    public function __construct(
        protected PassportPresetSeeder $presets,
        protected Publisher $publisher,
        protected DemoProductSeeder $catalog,
    ) {}

    public function run(): void
    {
        $this->installPresets();

        $templateId = (int) DB::table('passport_templates')->where('code', self::TEMPLATE_CODE)->value('id');

        if ($templateId === 0) {
            return;
        }

        $this->mapFields($templateId);

        $this->mapFamilies($templateId);

        $this->enablePassports();

        $this->publish();
    }

    protected function installPresets(): void
    {
        foreach ($this->presets->available() as $code) {
            try {
                $this->presets->run((string) $code);
            } catch (Throwable) {
                continue;
            }
        }
    }

    /**
     * Bind the preset's fields to catalog attributes, and fill the ones that
     * state programme-level policy with a fixed value per locale.
     */
    protected function mapFields(int $templateId): void
    {
        $mapping = $this->demoData('passport')[self::TEMPLATE_CODE] ?? [];

        if ($mapping === []) {
            return;
        }

        $attributeIds = DB::table('attributes')->pluck('id', 'code');

        $fields = DB::table('passport_template_fields')
            ->where('passport_template_id', $templateId)
            ->pluck('id', 'code');

        foreach ($mapping as $code => $source) {
            $fieldId = $fields[$code] ?? null;

            if (! $fieldId) {
                continue;
            }

            if (isset($source['attribute'])) {
                $attributeId = $attributeIds[$source['attribute']] ?? null;

                if (! $attributeId) {
                    continue;
                }

                DB::table('passport_template_fields')->where('id', $fieldId)->update([
                    'source_type'  => 'attribute',
                    'attribute_id' => (int) $attributeId,
                ]);

                continue;
            }

            DB::table('passport_template_fields')->where('id', $fieldId)->update([
                'source_type'  => 'fixed',
                'attribute_id' => null,
            ]);

            foreach ($source['fixed'] as $locale => $value) {
                DB::table('passport_template_field_translations')->updateOrInsert(
                    ['passport_template_field_id' => $fieldId, 'locale' => $locale],
                    ['fixed_value' => $value],
                );
            }
        }
    }

    protected function mapFamilies(int $templateId): void
    {
        $familyIds = DB::table('attribute_families')->pluck('id', 'code');

        foreach (self::TEMPLATE_FAMILIES as $familyCode) {
            if (! isset($familyIds[$familyCode])) {
                continue;
            }

            DB::table('passport_template_families')->updateOrInsert(
                ['attribute_family_id' => (int) $familyIds[$familyCode]],
                ['passport_template_id' => $templateId],
            );
        }
    }

    /**
     * The publish gate fails closed, so the passport tier has to be switched on
     * before the demo publishes. Written at the global scope because that is the
     * only scope the settings screen edits — a channel-scoped row would leave the
     * feature on with no way to turn it off from the admin.
     */
    protected function enablePassports(): void
    {
        DB::table('core_config')->updateOrInsert(
            [
                'code'         => 'catalog.product_passport.settings.enabled',
                'channel_code' => null,
                'locale_code'  => null,
            ],
            ['value' => '1'],
        );
    }

    /**
     * Publish every featured product in each locale the channel carries, then
     * stamp plausible view counts so the passport analytics are not empty.
     */
    protected function publish(): void
    {
        $channel = Channel::query()->where('code', self::PUBLISH_CHANNEL)->first();

        if (! $channel instanceof Channel) {
            return;
        }

        $adminId = (int) DB::table('admins')->orderBy('id')->value('id');

        $locales = Locale::query()
            ->whereIn('id', DB::table('channel_locales')->where('channel_id', $channel->id)->pluck('locale_id'))
            ->where('status', 1)
            ->get();

        foreach ($this->publishableSkus() as $sku) {
            $product = Product::query()->where('sku', $sku)->first();

            if (! $product instanceof Product) {
                continue;
            }

            foreach ($locales as $locale) {
                try {
                    $this->publisher->publish($product, $channel, $locale, 'dpp', $adminId ?: null);
                } catch (Throwable) {
                    continue;
                }
            }
        }

        $this->seedViewStats();
    }

    /**
     * @return array<int, string>
     */
    protected function publishableSkus(): array
    {
        $skus = [];

        foreach ($this->catalog->catalog() as $product) {
            if (($product['common']['is_featured'] ?? false) !== true) {
                continue;
            }

            if (in_array($product['sku'], self::WITHHELD, true)) {
                continue;
            }

            $skus[] = $product['sku'];
        }

        return $skus;
    }

    /**
     * Daily view counts over the last fortnight, derived from the publication
     * id so a re-seed reproduces the same series.
     */
    protected function seedViewStats(): void
    {
        $publications = DB::table('publications')->pluck('id');

        if ($publications->isEmpty()) {
            return;
        }

        DB::table('publication_view_stats')->whereIn('publication_id', $publications)->delete();

        $locales = DB::table('publication_versions')
            ->whereIn('publication_id', $publications)
            ->get(['publication_id', 'locale_id'])
            ->unique(fn ($row): string => $row->publication_id.':'.$row->locale_id);

        $today = Date::now()->startOfDay();
        $rows = [];

        foreach ($locales as $row) {
            for ($day = 1; $day <= 14; $day++) {
                $rows[] = [
                    'publication_id' => $row->publication_id,
                    'locale_id'      => $row->locale_id,
                    'viewed_on'      => $today->copy()->subDays($day)->toDateString(),
                    'views'          => 3 + (($row->publication_id * 7 + $day * 11) % 23),
                ];
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('publication_view_stats')->insert($chunk);
        }
    }
}
