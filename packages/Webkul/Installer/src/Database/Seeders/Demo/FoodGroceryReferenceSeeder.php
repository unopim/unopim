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

    /** @var string[] All channel codes in the DB (we populate values for every one). */
    protected array $allChannelCodes = [];

    /** Primary channel used as the canonical source when deriving other channels' content. */
    protected string $primaryChannelCode = 'default';

    /**
     * UnoPim only ships three association buckets. Every fixture type
     * maps onto one of them — multiple fixture types can share a bucket.
     *
     * @var array<string, string>
     */
    protected const ASSOCIATION_TYPE_MAP = [
        'upsell'            => 'up_sells',
        'crosssell'         => 'cross_sells',
        'substitution'      => 'related_products',
        'pack_size_variant' => 'related_products',
        'case_contains'     => 'related_products',
        'seasonal_variant'  => 'related_products',
    ];

    /**
     * Prebuilt association map keyed by from_sku. Each entry is
     * `['related_products' => [...], 'up_sells' => [...], 'cross_sells' => [...]]`.
     *
     * @var array<string, array<string, string[]>>
     */
    protected array $associationsBySku = [];

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

        // Populate values across every channel currently active in the DB.
        // ChannelsAndLocalesSeeder should have created ecommerce / mobile_app /
        // print_catalogue / b2b_wholesale by the time we get here, but we fall
        // back gracefully to whatever is present.
        $this->allChannelCodes = DB::table('channels')->pluck('code')->all();
        if (empty($this->allChannelCodes)) {
            $this->allChannelCodes = ['default'];
        }

        // Prefer `ecommerce` as canonical, fall back to `default`
        $this->primaryChannelCode = in_array('ecommerce', $this->allChannelCodes, true)
            ? 'ecommerce'
            : $this->allChannelCodes[0];

        try {
            DB::transaction(function (): void {
                $this->seedAttributeGroups();
                $this->seedAttributes();
                $this->upgradeLegacyAttributeFlags();
                $this->refreshMultiselectAttributeCodes();
                $this->seedFamily();
                $this->seedCategories();
                $this->seedBrandOptions();
                $this->buildAssociationMap();
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
            $enriched = $this->attachCategoriesToValues($enriched, $product['categories'] ?? []);
            $enriched = $this->attachAssociationsToValues($enriched, $product['sku']);
            $enriched = $this->fillMissingFamilyAttributes($enriched, (int) $familyId, $product);

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
            $enriched = $this->attachCategoriesToValues($enriched, $product['categories'] ?? []);
            $enriched = $this->attachAssociationsToValues($enriched, $product['sku']);
            $enriched = $this->fillMissingFamilyAttributes($enriched, (int) $familyId, $product);

            DB::table('products')->insertGetId([
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

    /* -------- associations ------------------------------------------ */

    /**
     * Pre-build a per-SKU association map from associations.json. UnoPim
     * stores associations in the product's own `values.associations`
     * JSON bucket (not a pivot table), so we need the full list ready
     * before we start inserting products.
     *
     * The fixture declares 6 association types for teaching purposes
     * (upsell, crosssell, substitution, pack_size_variant, case_contains,
     * seasonal_variant). UnoPim only has 3 built-in buckets, so each
     * fixture type maps onto one of them via ASSOCIATION_TYPE_MAP.
     *
     * Rows referencing SKUs that aren't authored yet (e.g. the CASE-24
     * outer-pack) are silently dropped — we'd rather show fewer, correct
     * associations than dangling pointers.
     */
    protected function buildAssociationMap(): void
    {
        $data = $this->loadJson('associations.json');
        $allAuthoredSkus = array_column(
            $this->loadJson('products.json')['products'] ?? [],
            'sku'
        );

        $map = [];

        foreach ($data['associations'] ?? [] as $assoc) {
            $from = $assoc['from_sku'] ?? null;
            $to = $assoc['to_sku'] ?? null;
            $fixtureType = $assoc['type'] ?? null;

            if (! $from || ! $to || ! $fixtureType) {
                continue;
            }

            // Drop rows referencing un-authored SKUs so the UI doesn't
            // render dead links.
            if (! in_array($to, $allAuthoredSkus, true)) {
                continue;
            }

            $bucket = self::ASSOCIATION_TYPE_MAP[$fixtureType] ?? 'related_products';

            $map[$from] ??= ['related_products' => [], 'up_sells' => [], 'cross_sells' => []];
            $map[$from][$bucket][] = $to;
        }

        // Deduplicate each bucket.
        foreach ($map as $sku => $buckets) {
            foreach ($buckets as $bucket => $skus) {
                $map[$sku][$bucket] = array_values(array_unique($skus));
            }
        }

        $this->associationsBySku = $map;
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
     * Fill in missing locale/channel content from the canonical en_US entry.
     *
     *  - Treats the author-chosen `default` / `ecommerce` bucket as canonical.
     *  - Clones that canonical bucket into every active DB channel
     *    (ecommerce, mobile_app, print_catalogue, b2b_wholesale, default)
     *    via deriveContent() rules.
     *  - Clones into every locale the demo profile has activated.
     *  - Downcasts multiselect array values to CSV strings so the edit
     *    form's str_contains() check doesn't crash.
     */
    protected function enrichProductValues(array $product): array
    {
        $values = $product['values'] ?? ['common' => [], 'channel_specific' => [], 'channel_locale_specific' => []];

        $values['common'] ??= [];
        $values['channel_specific'] ??= [];
        $values['channel_locale_specific'] ??= [];

        // Find the canonical content bucket (first channel with an en_US entry).
        $canonical = null;
        foreach (['ecommerce', 'default'] as $candidate) {
            if (isset($values['channel_locale_specific'][$candidate]['en_US'])) {
                $canonical = $values['channel_locale_specific'][$candidate]['en_US'];

                break;
            }
        }
        if ($canonical === null) {
            foreach ($values['channel_locale_specific'] as $bucket) {
                if (isset($bucket['en_US'])) {
                    $canonical = $bucket['en_US'];

                    break;
                }
            }
        }

        // Also collect any hand-authored channel buckets (keyed by channel code).
        $authored = $values['channel_locale_specific'];
        $values['channel_locale_specific'] = [];

        foreach ($this->allChannelCodes as $channelCode) {
            $values['channel_locale_specific'][$channelCode] ??= [];

            foreach ($this->profile->locales as $localeCode) {
                // Hand-authored content wins
                if (isset($authored[$channelCode][$localeCode])) {
                    $values['channel_locale_specific'][$channelCode][$localeCode] =
                        $authored[$channelCode][$localeCode];

                    continue;
                }
                // Fallback: canonical content from another channel, same locale
                foreach ($authored as $otherChannel => $otherBucket) {
                    if (isset($otherBucket[$localeCode])) {
                        $values['channel_locale_specific'][$channelCode][$localeCode] =
                            $this->deriveContent($otherBucket[$localeCode], $channelCode, $localeCode);

                        continue 2;
                    }
                }
                // Last resort: derive from en_US canonical
                if ($canonical !== null) {
                    $values['channel_locale_specific'][$channelCode][$localeCode] =
                        $this->deriveContent($canonical, $channelCode, $localeCode);
                }
            }
        }

        // Re-key channel_specific under every channel (copy from author's input
        // or the first bucket).
        $authoredCS = $values['channel_specific'];
        $firstCS = $authoredCS[array_key_first($authoredCS) ?? ''] ?? [];
        $values['channel_specific'] = [];
        foreach ($this->allChannelCodes as $channelCode) {
            $values['channel_specific'][$channelCode] = $authoredCS[$channelCode] ?? $firstCS;
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

    /**
     * Generate a brand-coloured SVG placeholder that shows the product's
     * pack silhouette, name, size, and SKU, then store it and write the
     * path into `values.common.image`. We deliberately don't ship real
     * photography — the seeder is supposed to produce deterministic,
     * non-copyright visuals that reflect each product accurately. Random
     * stock photos don't.
     */
    protected function attachGeneratedImage(array $product, array $values): array
    {
        $brand = $values['common']['brand'] ?? null;
        $packType = $values['common']['pack_type'] ?? null;
        $netWeight = $values['common']['net_weight_g'] ?? null;
        $netVolume = $values['common']['net_volume_ml'] ?? null;
        $canonicalName = $values['channel_locale_specific']['ecommerce']['en_US']['name']
            ?? $values['channel_locale_specific']['default']['en_US']['name']
            ?? $product['sku'];

        $values['common']['image'] = $this->imageGenerator->generate(
            sku: $product['sku'],
            family: self::FAMILY_CODE,
            name: $canonicalName,
            brand: $brand,
            packType: $packType,
            netWeightG: $netWeight,
            netVolumeMl: $netVolume,
        );

        return $values;
    }

    /**
     * For every attribute in the food_grocery family that isn't already
     * set on a given product, synthesise a sensible placeholder value so
     * the edit form shows populated fields end-to-end.
     *
     * The rules:
     *  - Each attribute is written into the bucket its scoping flags
     *    dictate (common / channel_specific / locale_specific /
     *    channel_locale_specific).
     *  - Select and multiselect attributes are skipped unless we already
     *    know a safe option code — we don't invent option values.
     *  - Already-set values (including booleans and zeros) are preserved.
     */
    protected function fillMissingFamilyAttributes(array $values, int $familyId, array $product): array
    {
        $attributes = $this->familyAttributesCache($familyId);

        $productName = $values['channel_locale_specific']['ecommerce']['en_US']['name']
            ?? $values['channel_locale_specific']['default']['en_US']['name']
            ?? $product['sku'];

        foreach ($attributes as $attribute) {
            $code = $attribute->code;
            $type = $attribute->type;
            $perChannel = (bool) $attribute->value_per_channel;
            $perLocale = (bool) $attribute->value_per_locale;

            // Determine which bucket(s) this attribute belongs in and
            // ensure each has a value.
            if (! $perChannel && ! $perLocale) {
                if (! array_key_exists($code, $values['common'])) {
                    $default = $this->defaultForType($type, $code, $productName);
                    if ($default !== null) {
                        $values['common'][$code] = $default;
                    }
                }

                continue;
            }

            if ($perChannel && ! $perLocale) {
                foreach ($this->allChannelCodes as $channelCode) {
                    $values['channel_specific'][$channelCode] ??= [];
                    if (! array_key_exists($code, $values['channel_specific'][$channelCode])) {
                        $default = $this->defaultForType($type, $code, $productName);
                        if ($default !== null) {
                            $values['channel_specific'][$channelCode][$code] = $default;
                        }
                    }
                }

                continue;
            }

            if (! $perChannel && $perLocale) {
                // Per-locale-only attributes are stored under
                // locale_specific (rarely used in practice); fall back
                // to per-locale entries within channel_locale_specific.
                foreach ($this->profile->locales as $localeCode) {
                    $values['channel_locale_specific'][$this->primaryChannelCode] ??= [];
                    $values['channel_locale_specific'][$this->primaryChannelCode][$localeCode] ??= [];
                    if (! array_key_exists($code, $values['channel_locale_specific'][$this->primaryChannelCode][$localeCode])) {
                        $default = $this->defaultForType($type, $code, $productName);
                        if ($default !== null) {
                            $values['channel_locale_specific'][$this->primaryChannelCode][$localeCode][$code] = $default;
                        }
                    }
                }

                continue;
            }

            // perChannel && perLocale
            foreach ($this->allChannelCodes as $channelCode) {
                foreach ($this->profile->locales as $localeCode) {
                    $values['channel_locale_specific'][$channelCode] ??= [];
                    $values['channel_locale_specific'][$channelCode][$localeCode] ??= [];
                    if (! array_key_exists($code, $values['channel_locale_specific'][$channelCode][$localeCode])) {
                        $default = $this->defaultForType($type, $code, $productName);
                        if ($default !== null) {
                            $values['channel_locale_specific'][$channelCode][$localeCode][$code] = $default;
                        }
                    }
                }
            }
        }

        return $values;
    }

    /**
     * Returns the default value to use when a given attribute has no
     * value set for a product. Returns null for types we don't want to
     * synthesise (select/image/file) so the caller leaves the key
     * absent rather than setting a bogus value that would break the
     * form (e.g. an invented select option code).
     */
    protected function defaultForType(string $type, string $code, string $productName): mixed
    {
        // Select requires a real option code — skip rather than invent.
        // Image/file have their own upload flow and are handled elsewhere.
        if (in_array($type, ['select', 'image', 'file', 'gallery'], true)) {
            return null;
        }

        // Multiselect values are stored as CSV strings, not arrays —
        // the edit form's str_contains() check crashes on arrays.
        // Empty string is the canonical "no selection" form.
        if ($type === 'multiselect') {
            return '';
        }

        // Price: empty map — the edit form renders currency rows with
        // blank inputs. Better than an invented cost.
        if ($type === 'price') {
            return [];
        }

        if ($type === 'boolean') {
            return false;
        }

        if ($type === 'date') {
            return Carbon::now()->toDateString();
        }

        if ($type === 'datetime') {
            return Carbon::now()->toDateTimeString();
        }

        if ($type === 'textarea') {
            if (str_starts_with($code, 'meta_')) {
                return $productName;
            }
            if ($code === 'ingredients_list' || $code === 'storage_instructions') {
                return '—';
            }

            return '<p>'.htmlspecialchars($productName, ENT_QUOTES).'</p>';
        }

        // text and everything else — nutrition numeric defaults to 0,
        // meta/url/tagline defaults to the product name.
        if (str_ends_with($code, '_per_100g') || $code === 'shelf_life_days' || $code === 'pack_count' || $code === 'case_quantity' || $code === 'net_weight_g' || $code === 'net_volume_ml') {
            return '0';
        }

        if ($code === 'url_key') {
            return Str::slug($productName);
        }

        return $productName;
    }

    /**
     * Cache family attribute rows so we only hit the DB once per run.
     *
     * @return array<int, object{code: string, type: string, value_per_channel: int, value_per_locale: int}>
     */
    protected function familyAttributesCache(int $familyId): array
    {
        static $cache = [];
        if (isset($cache[$familyId])) {
            return $cache[$familyId];
        }

        $cache[$familyId] = DB::table('attribute_family_group_mappings as fgm')
            ->join('attribute_group_mappings as agm', 'agm.attribute_family_group_id', '=', 'fgm.id')
            ->join('attributes as a', 'a.id', '=', 'agm.attribute_id')
            ->where('fgm.attribute_family_id', $familyId)
            ->select('a.code', 'a.type', 'a.value_per_channel', 'a.value_per_locale')
            ->get()
            ->all();

        return $cache[$familyId];
    }

    /**
     * UnoPim reads a product's categories from `values.categories` —
     * a flat array of category codes at the root of the values JSON,
     * not inside common/channel_specific. See AbstractType::update()
     * (CATEGORY_VALUES_KEY constant).
     */
    protected function attachCategoriesToValues(array $values, array $categoryCodes): array
    {
        if (empty($categoryCodes)) {
            return $values;
        }

        // Skip codes that don't exist (seeder runs categories pass first,
        // but a typo in products.json shouldn't poison values).
        $existing = DB::table('categories')
            ->whereIn('code', $categoryCodes)
            ->pluck('code')
            ->all();

        $values['categories'] = array_values(array_intersect($categoryCodes, $existing));

        return $values;
    }

    /**
     * UnoPim stores product → product associations in
     * `values.associations.{related_products|up_sells|cross_sells}` — each
     * a flat array of linked SKUs. See AbstractType::update() and the
     * RELATED/UP_SELLS/CROSS_SELLS constants.
     */
    protected function attachAssociationsToValues(array $values, string $fromSku): array
    {
        if (empty($this->associationsBySku[$fromSku])) {
            return $values;
        }

        $values['associations'] = [];
        foreach ($this->associationsBySku[$fromSku] as $bucket => $skus) {
            if (empty($skus)) {
                continue;
            }
            $values['associations'][$bucket] = $skus;
        }

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
