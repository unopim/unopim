<?php

namespace Webkul\Installer\Database\Seeders\Demo;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;
use Webkul\Installer\Database\Data\Generators\SvgPlaceholderGenerator;
use Webkul\Installer\Demo\DemoDataProfile;

/**
 * Reads the hand-authored JSON fixtures in
 * `Database/Data/templates/food_grocery/` and inserts the FMCG reference
 * catalog into the database.
 *
 * The seeder is idempotent — it checks for existing rows by code/sku and
 * skips anything already present. Safe to re-run after a template edit.
 *
 * Missing locale / channel content is auto-filled at seed time from the
 * canonical `en_US` / `ecommerce` entry using mechanical derivation rules.
 *
 * SVG placeholder images are generated on-demand via
 * {@see SvgPlaceholderGenerator} — no binary assets shipped in git.
 */
class FoodGroceryReferenceSeeder extends Seeder
{
    protected const FAMILY_CODE = 'food_grocery';

    protected const TEMPLATE_SUBDIR = 'food_grocery';

    protected DemoDataProfile $profile;

    protected SvgPlaceholderGenerator $imageGenerator;

    protected array $translations = [];

    /** @var string[] Codes of attributes whose type is `multiselect` (values must be CSV, not arrays) */
    protected array $multiselectAttributeCodes = [];

    /** The channel code UnoPim is actually using in the DB (legacy = `default`). */
    protected string $targetChannelCode = 'default';

    public function run(array $parameters = []): void
    {
        $this->profile = $parameters['profile']
            ?? DemoDataProfile::fromPreset(DemoDataProfile::PRESET_STARTER);

        if (! $this->profile->shouldSeedFamily(self::FAMILY_CODE)) {
            $this->command?->info(
                sprintf('Skipping %s — not selected in demo profile.', self::FAMILY_CODE)
            );

            return;
        }

        $this->command?->info('Seeding food_grocery reference catalog…');

        $this->imageGenerator = new SvgPlaceholderGenerator;
        $this->imageGenerator->ensureDirectoryExists(self::FAMILY_CODE);

        $this->translations = $this->loadJson('translations.json');

        // Use whichever channel the user has active — fall back to 'default'
        // which is what the legacy installer seeds.
        $this->targetChannelCode = (string) (DB::table('channels')->value('code') ?: 'default');

        try {
            DB::transaction(function (): void {
                $this->seedAttributeGroups();
                $this->seedAttributes();
                $this->upgradeLegacyAttributeFlags();
                $this->refreshMultiselectAttributeCodes();
                $this->seedFamily();
                $this->seedCategories();
                $this->seedBrandOptions();
                $this->seedProducts();
            });

            $this->command?->info('food_grocery seeded successfully.');
        } catch (Throwable $e) {
            $this->command?->error('food_grocery seeder failed: '.$e->getMessage());
            throw $e;
        }
    }

    /* -------- attribute groups -------------------------------------- */

    protected function seedAttributeGroups(): void
    {
        $data = $this->loadJson('attribute_groups.json');
        $existingCodes = DB::table('attribute_groups')->pluck('code')->all();

        foreach ($data['attribute_groups'] ?? [] as $group) {
            if (in_array($group['code'], $existingCodes, true)) {
                continue;
            }

            $row = ['code' => $group['code']];
            if (Schema::hasColumn('attribute_groups', 'is_user_defined')) {
                $row['is_user_defined'] = 1;
            }

            $groupId = DB::table('attribute_groups')->insertGetId($row);

            $this->seedTranslations(
                'attribute_group_translations',
                'attribute_group_id',
                $groupId,
                'name',
                $group['labels'] ?? []
            );
        }
    }

    /* -------- attributes -------------------------------------------- */

    protected function seedAttributes(): void
    {
        $data = $this->loadJson('attributes.json');
        $now = Carbon::now();
        $existingCodes = DB::table('attributes')->pluck('code')->all();

        foreach ($data['attributes'] ?? [] as $attr) {
            if (in_array($attr['code'], $existingCodes, true)) {
                continue;
            }

            $row = [
                'code'              => $attr['code'],
                'type'              => $attr['type'],
                'position'          => $attr['position'] ?? 0,
                'is_required'       => $attr['is_required'] ?? 0,
                'is_unique'         => $attr['is_unique'] ?? 0,
                'value_per_locale'  => $attr['value_per_locale'] ?? 0,
                'value_per_channel' => $attr['value_per_channel'] ?? 0,
                'is_filterable'     => $attr['is_filterable'] ?? 0,
                'validation'        => $attr['validation'] ?? null,
                'regex_pattern'     => $attr['regex_pattern'] ?? null,
                'enable_wysiwyg'    => $attr['wysiwyg'] ?? 0,
                'default_value'     => $attr['default_value'] ?? null,
                'created_at'        => $now,
                'updated_at'        => $now,
            ];

            $attributeId = DB::table('attributes')->insertGetId($row);

            $this->seedTranslations(
                'attribute_translations',
                'attribute_id',
                $attributeId,
                'name',
                $attr['labels'] ?? []
            );
        }
    }

    /* -------- family + group/attribute mappings --------------------- */

    protected function seedFamily(): void
    {
        $data = $this->loadJson('family.json');
        $familyData = $data['family'] ?? null;

        if (! $familyData) {
            return;
        }

        $existing = DB::table('attribute_families')->where('code', $familyData['code'])->first();

        if ($existing) {
            $familyId = (int) $existing->id;
        } else {
            $familyId = DB::table('attribute_families')->insertGetId([
                'code'   => $familyData['code'],
                'status' => $familyData['status'] ?? 1,
            ]);

            $this->seedTranslations(
                'attribute_family_translations',
                'attribute_family_id',
                $familyId,
                'name',
                $familyData['labels'] ?? []
            );
        }

        $this->seedFamilyGroupMappings($familyId, $familyData['groups'] ?? []);
    }

    protected function seedFamilyGroupMappings(int $familyId, array $groupCodes): void
    {
        $position = 0;
        foreach ($groupCodes as $groupCode) {
            $groupId = DB::table('attribute_groups')->where('code', $groupCode)->value('id');
            if (! $groupId) {
                continue;
            }

            $exists = DB::table('attribute_family_group_mappings')
                ->where('attribute_family_id', $familyId)
                ->where('attribute_group_id', $groupId)
                ->exists();

            if (! $exists) {
                $familyGroupId = DB::table('attribute_family_group_mappings')->insertGetId([
                    'attribute_family_id' => $familyId,
                    'attribute_group_id'  => $groupId,
                    'position'            => $position++,
                ]);
            } else {
                $familyGroupId = (int) DB::table('attribute_family_group_mappings')
                    ->where('attribute_family_id', $familyId)
                    ->where('attribute_group_id', $groupId)
                    ->value('id');
            }

            $this->seedAttributeGroupMappings($familyGroupId, $groupCode);
        }
    }

    /**
     * Wire every attribute (both FMCG-specific ones from attributes.json
     * AND reused legacy system attributes declared in reused_legacy_attributes)
     * to the given family-group pivot row.
     */
    protected function seedAttributeGroupMappings(int $familyGroupId, string $groupCode): void
    {
        $data = $this->loadJson('attributes.json');
        $position = 0;

        // FMCG-specific attributes defined in attributes.json
        foreach ($data['attributes'] ?? [] as $attr) {
            if (($attr['group'] ?? null) !== $groupCode) {
                continue;
            }

            $this->attachAttributeToFamilyGroup($attr['code'], $familyGroupId, $position++);
        }

        // Legacy system attributes reused by this family
        foreach ($data['reused_legacy_attributes'] ?? [] as $legacy) {
            if (($legacy['group'] ?? null) !== $groupCode) {
                continue;
            }

            $this->attachAttributeToFamilyGroup($legacy['code'], $familyGroupId, $position++);
        }
    }

    protected function attachAttributeToFamilyGroup(string $attributeCode, int $familyGroupId, int $position): void
    {
        $attributeId = DB::table('attributes')->where('code', $attributeCode)->value('id');
        if (! $attributeId) {
            return;
        }

        $exists = DB::table('attribute_group_mappings')
            ->where('attribute_family_group_id', $familyGroupId)
            ->where('attribute_id', $attributeId)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('attribute_group_mappings')->insert([
            'attribute_family_group_id' => $familyGroupId,
            'attribute_id'              => $attributeId,
            'position'                  => $position,
        ]);
    }

    /**
     * The legacy `brand` attribute ships with is_filterable=0. Upgrade
     * any legacy attribute for which the template sets make_filterable=true.
     */
    protected function upgradeLegacyAttributeFlags(): void
    {
        $data = $this->loadJson('attributes.json');

        foreach ($data['reused_legacy_attributes'] ?? [] as $legacy) {
            if (empty($legacy['make_filterable'])) {
                continue;
            }

            DB::table('attributes')
                ->where('code', $legacy['code'])
                ->update(['is_filterable' => 1]);
        }
    }

    /* -------- categories -------------------------------------------- */

    protected function seedCategories(): void
    {
        $data = $this->loadJson('categories.json');
        $categories = $data['categories'] ?? [];

        if (empty($categories)) {
            return;
        }

        $now = Carbon::now();

        foreach ($categories as $cat) {
            if (DB::table('categories')->where('code', $cat['code'])->exists()) {
                continue;
            }

            $parentId = null;
            if (isset($cat['parent']) && $cat['parent'] !== 'root') {
                $parentId = DB::table('categories')->where('code', $cat['parent'])->value('id');
            } elseif (isset($cat['parent']) && $cat['parent'] === 'root') {
                $parentId = DB::table('categories')->where('code', 'root')->value('id');
            }

            $lft = (int) (DB::table('categories')->max('_rgt') ?? 0) + 1;

            DB::table('categories')->insert([
                'code'            => $cat['code'],
                'parent_id'       => $parentId,
                '_lft'            => $lft,
                '_rgt'            => $lft + 1,
                'additional_data' => json_encode([
                    'locale_specific' => $this->buildCategoryLocaleBlock($cat['labels'] ?? []),
                ]),
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        }
    }

    /**
     * UnoPim categories store their locale-specific fields (name,
     * description) in additional_data.locale_specific.<locale>. Build
     * that block from the template's labels map.
     */
    protected function buildCategoryLocaleBlock(array $labels): array
    {
        $block = [];
        foreach ($labels as $locale => $label) {
            $block[$locale] = ['name' => $label];
        }

        return $block;
    }

    /* -------- brand options ----------------------------------------- */

    protected function seedBrandOptions(): void
    {
        $data = $this->loadJson('brand_options.json');
        $attributeCode = $data['attribute_code'] ?? 'brand';

        $attributeId = DB::table('attributes')->where('code', $attributeCode)->value('id');
        if (! $attributeId) {
            return;
        }

        $existing = DB::table('attribute_options')
            ->where('attribute_id', $attributeId)
            ->pluck('code')
            ->all();

        $sortOrder = 0;

        foreach ($data['options'] ?? [] as $option) {
            if (in_array($option['code'], $existing, true)) {
                continue;
            }

            $optionId = DB::table('attribute_options')->insertGetId([
                'attribute_id' => $attributeId,
                'code'         => $option['code'],
                'sort_order'   => $sortOrder++,
            ]);

            $this->seedTranslations(
                'attribute_option_translations',
                'attribute_option_id',
                $optionId,
                'label',
                $option['labels'] ?? []
            );
        }
    }

    /* -------- products ---------------------------------------------- */

    protected function seedProducts(): void
    {
        $data = $this->loadJson('products.json');
        $products = $data['products'] ?? [];

        if (empty($products)) {
            return;
        }

        $familyId = DB::table('attribute_families')
            ->where('code', self::FAMILY_CODE)
            ->value('id');

        if (! $familyId) {
            throw new \RuntimeException('food_grocery family not found in DB after seeding.');
        }

        $existingSkus = DB::table('products')
            ->whereIn('sku', array_column($products, 'sku'))
            ->pluck('id', 'sku')
            ->all();

        $now = Carbon::now();
        $parentMap = [];

        // Pass 1: seed parents (no parent_sku)
        foreach ($products as $product) {
            if (isset($product['parent_sku'])) {
                continue;
            }

            if (isset($existingSkus[$product['sku']])) {
                $parentMap[$product['sku']] = $existingSkus[$product['sku']];

                continue;
            }

            $enriched = $this->enrichProductValues($product);
            $enriched = $this->attachGeneratedImage($product, $enriched);

            $id = DB::table('products')->insertGetId([
                'sku'                 => $product['sku'],
                'type'                => $product['type'] ?? 'simple',
                'status'              => 1,
                'parent_id'           => null,
                'attribute_family_id' => $familyId,
                'values'              => json_encode($enriched, JSON_THROW_ON_ERROR),
                'additional'          => null,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);

            $parentMap[$product['sku']] = $id;
        }

        // Pass 2: seed variants with resolved parent_id
        foreach ($products as $product) {
            if (! isset($product['parent_sku'])) {
                continue;
            }

            if (isset($existingSkus[$product['sku']])) {
                continue;
            }

            $parentId = $parentMap[$product['parent_sku']] ?? null;
            if ($parentId === null) {
                continue;
            }

            $enriched = $this->enrichProductValues($product);
            $enriched = $this->attachGeneratedImage($product, $enriched);

            DB::table('products')->insert([
                'sku'                 => $product['sku'],
                'type'                => 'simple',
                'status'              => 1,
                'parent_id'           => $parentId,
                'attribute_family_id' => $familyId,
                'values'              => json_encode($enriched, JSON_THROW_ON_ERROR),
                'additional'          => null,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
        }

        $this->seedSuperAttributes($products);
    }

    protected function seedSuperAttributes(array $products): void
    {
        $rows = [];

        foreach ($products as $product) {
            if (($product['type'] ?? 'simple') !== 'configurable') {
                continue;
            }

            $productId = DB::table('products')->where('sku', $product['sku'])->value('id');
            if (! $productId) {
                continue;
            }

            foreach ($product['super_attributes'] ?? [] as $attributeCode) {
                $attributeId = DB::table('attributes')->where('code', $attributeCode)->value('id');
                if (! $attributeId) {
                    continue;
                }

                $rows[] = [
                    'product_id'   => (int) $productId,
                    'attribute_id' => (int) $attributeId,
                ];
            }
        }

        if (! empty($rows)) {
            DB::table('product_super_attributes')->insertOrIgnore($rows);
        }
    }

    /* -------- translation helpers ----------------------------------- */

    /**
     * Insert locale-indexed label rows into a translation table, scoped
     * to the locales the current demo profile has activated.
     *
     * @param  array<string, string>  $labels  Map of locale code → label
     */
    protected function seedTranslations(
        string $table,
        string $fkColumn,
        int $entityId,
        string $labelColumn,
        array $labels,
    ): void {
        if (empty($labels) || ! Schema::hasTable($table)) {
            return;
        }

        foreach ($labels as $locale => $label) {
            if (! $this->profile->shouldSeedLocale($locale) && $locale !== 'en_US') {
                continue;
            }

            DB::table($table)->insertOrIgnore([
                $fkColumn     => $entityId,
                'locale'      => $locale,
                $labelColumn  => $label,
            ]);
        }
    }

    /**
     * Pull the list of attribute codes whose type is `multiselect` so we
     * can downcast array values to CSV strings before insert. UnoPim's
     * dynamic-attribute-fields.blade.php calls str_contains($value, ',')
     * on multiselect values — passing an array crashes the edit form.
     */
    protected function refreshMultiselectAttributeCodes(): void
    {
        $this->multiselectAttributeCodes = DB::table('attributes')
            ->where('type', 'multiselect')
            ->pluck('code')
            ->all();
    }

    /**
     * Fill in missing locale/channel content from the canonical en_US entry
     * and re-key all channel_specific / channel_locale_specific data under
     * the ACTIVE DB channel code (legacy = `default`). Also converts any
     * multiselect-attribute values from arrays to CSV strings so the edit
     * form's str_contains check doesn't crash.
     */
    protected function enrichProductValues(array $product): array
    {
        $values = $product['values'] ?? ['common' => [], 'channel_specific' => [], 'channel_locale_specific' => []];

        $values['common'] ??= [];
        $values['channel_specific'] ??= [];
        $values['channel_locale_specific'] ??= [];

        $target = $this->targetChannelCode;

        // Re-key channel_specific — move everything under the target channel
        $oldChannelSpecific = $values['channel_specific'];
        $values['channel_specific'] = [$target => []];
        foreach ($oldChannelSpecific as $channelCode => $channelData) {
            foreach ($channelData as $attrCode => $attrValue) {
                $values['channel_specific'][$target][$attrCode] ??= $attrValue;
            }
        }

        // Re-key channel_locale_specific — merge every author-chosen channel
        // bucket (ecommerce / mobile_app / print_catalogue / …) into the
        // one `default` channel the DB actually has.
        $oldChannelLocale = $values['channel_locale_specific'];
        $canonical = $oldChannelLocale['ecommerce']['en_US']
            ?? $oldChannelLocale[$target]['en_US']
            ?? null;

        $values['channel_locale_specific'] = [$target => []];
        foreach ($oldChannelLocale as $channelCode => $localeBucket) {
            foreach ($localeBucket as $localeCode => $content) {
                $values['channel_locale_specific'][$target][$localeCode] ??= $content;
            }
        }

        // Fill in missing locales by mechanical derivation from canonical
        if ($canonical !== null) {
            foreach ($this->profile->locales as $localeCode) {
                if (isset($values['channel_locale_specific'][$target][$localeCode])) {
                    continue;
                }

                $values['channel_locale_specific'][$target][$localeCode] =
                    $this->deriveContent($canonical, $target, $localeCode);
            }
        }

        // Downcast multiselect arrays → CSV everywhere values live
        $values['common'] = $this->downcastMultiselectValues($values['common']);
        foreach ($values['channel_specific'] as $chan => $cs) {
            $values['channel_specific'][$chan] = $this->downcastMultiselectValues($cs);
        }
        foreach ($values['channel_locale_specific'] as $chan => $locales) {
            foreach ($locales as $loc => $payload) {
                $values['channel_locale_specific'][$chan][$loc] =
                    $this->downcastMultiselectValues($payload);
            }
        }

        return $values;
    }

    /**
     * Convert `["a","b","c"]` → `"a,b,c"` for any attribute in the values
     * array whose type is `multiselect`. Leaves everything else untouched.
     */
    protected function downcastMultiselectValues(array $values): array
    {
        foreach ($values as $code => $value) {
            if (! is_array($value)) {
                continue;
            }

            if (! in_array($code, $this->multiselectAttributeCodes, true)) {
                continue;
            }

            $values[$code] = implode(',', array_filter(array_map('strval', $value), static fn ($v) => $v !== ''));
        }

        return $values;
    }

    /**
     * Mechanically derive channel+locale content from the canonical en_US
     * ecommerce entry when the fixture didn't provide a hand-authored variant.
     */
    protected function deriveContent(array $canonical, string $channelCode, string $localeCode): array
    {
        $derived = $canonical;

        if ($localeCode === 'en_GB') {
            $swaps = $this->translations['locale_mechanical_rules']['en_GB']['spelling_swaps'] ?? [];
            foreach ($derived as $key => $value) {
                if (is_string($value)) {
                    $derived[$key] = strtr($value, $swaps);
                }
            }
        }

        if ($channelCode === 'mobile_app') {
            if (isset($derived['short_description'])) {
                $derived['short_description'] = Str::limit($derived['short_description'], 120);
            }
            if (isset($derived['name'])) {
                $derived['name'] = preg_replace('/\s+—\s+.*/', '', $derived['name']);
            }
            unset($derived['marketing_description']);
        }

        if ($channelCode === 'print_catalogue') {
            if (isset($derived['name'])) {
                $derived['name'] = strtoupper($derived['name']);
            }
            unset($derived['marketing_description']);
        }

        if ($channelCode === 'b2b_wholesale') {
            if (isset($derived['name'])) {
                $derived['name'] = strtoupper($derived['name']).' — B2B';
            }
            unset($derived['marketing_description']);
        }

        return $derived;
    }

    protected function attachGeneratedImage(array $product, array $values): array
    {
        $brand = $values['common']['brand'] ?? null;
        $packType = $values['common']['pack_type'] ?? null;
        $netWeight = $values['common']['net_weight_g'] ?? null;
        $netVolume = $values['common']['net_volume_ml'] ?? null;

        $canonicalName = $values['channel_locale_specific']['ecommerce']['en_US']['name']
            ?? $product['sku'];

        $imagePath = $this->imageGenerator->generate(
            sku: $product['sku'],
            family: self::FAMILY_CODE,
            name: $canonicalName,
            brand: $brand,
            packType: $packType,
            netWeightG: $netWeight,
            netVolumeMl: $netVolume,
        );

        $values['common']['image_front'] = $imagePath;

        return $values;
    }

    protected function loadJson(string $file): array
    {
        $path = dirname(__DIR__, 2).'/Data/templates/'.self::TEMPLATE_SUBDIR.'/'.$file;

        if (! File::exists($path)) {
            $this->command?->warn("Missing {$file} at {$path}");

            return [];
        }

        return json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
