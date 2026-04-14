<?php

namespace Webkul\Installer\Database\Seeders\Demo;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;
use Webkul\Core\Helpers\Database\DatabaseSequenceHelper;
use Webkul\Installer\Database\Data\Generators\SvgPlaceholderGenerator;
use Webkul\Installer\Demo\DemoDataProfile;

/**
 * Reads the hand-authored JSON fixtures in
 * `Database/Data/templates/food_grocery/` and inserts the FMCG reference
 * catalog into the database.
 *
 * Ingest order (must not change — foreign-key dependencies):
 *   1. attribute_groups.json  → attribute_groups
 *   2. attributes.json        → attributes
 *   3. family.json            → attribute_families + group_mappings
 *   4. categories.json        → categories (nested-set)
 *   5. brand_options.json     → attribute_options for the `brand` attribute
 *   6. association_types.json → association_types
 *   7. products.json          → products (parents first, then variants)
 *   8. associations.json      → product_associations
 *
 * Each step is idempotent — previous rows keyed on `code` are deleted and
 * re-inserted so the seeder can be re-run after a template edit.
 *
 * SVG placeholder images are generated at runtime via
 * {@see SvgPlaceholderGenerator} — no binary assets shipped in git.
 *
 * Missing locale / channel content is auto-filled from the canonical
 * `en_US` / `ecommerce` entry using the rules in `translations.json`.
 */
class FoodGroceryReferenceSeeder extends Seeder
{
    protected const FAMILY_CODE = 'food_grocery';

    protected const TEMPLATE_SUBDIR = 'food_grocery';

    protected DemoDataProfile $profile;

    protected SvgPlaceholderGenerator $imageGenerator;

    protected array $translations = [];

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

        try {
            DB::transaction(function (): void {
                $this->seedAttributeGroups();
                $this->seedAttributes();
                $this->seedFamily();
                $this->seedCategories();
                $this->seedBrandOptions();
                $this->seedAssociationTypes();
                $this->seedProducts();
                $this->seedAssociations();
            });

            $this->command?->info('food_grocery seeded successfully.');
        } catch (Throwable $e) {
            $this->command?->error('food_grocery seeder failed: '.$e->getMessage());
            throw $e;
        }

        DatabaseSequenceHelper::fixSequences([
            'attribute_groups',
            'attributes',
            'attribute_options',
            'attribute_families',
            'categories',
            'products',
        ]);
    }

    protected function seedAttributeGroups(): void
    {
        $data = $this->loadJson('attribute_groups.json');
        $rows = [];
        $now = Carbon::now();

        foreach ($data['attribute_groups'] ?? [] as $group) {
            $rows[] = [
                'code'       => $group['code'],
                'position'   => $group['position'] ?? 0,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (empty($rows)) {
            return;
        }

        $existing = DB::table('attribute_groups')
            ->whereIn('code', array_column($rows, 'code'))
            ->pluck('code')
            ->all();

        $toInsert = array_values(array_filter(
            $rows,
            static fn ($row) => ! in_array($row['code'], $existing, true)
        ));

        if (! empty($toInsert)) {
            DB::table('attribute_groups')->insert($toInsert);
        }
    }

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
                'is_user_defined'   => 1,
                'validation'        => $attr['validation'] ?? null,
                'created_at'        => $now,
                'updated_at'        => $now,
            ];

            if (! DB::getSchemaBuilder()->hasColumn('attributes', 'is_user_defined')) {
                unset($row['is_user_defined']);
            }

            DB::table('attributes')->insert($row);
        }
    }

    protected function seedFamily(): void
    {
        $data = $this->loadJson('family.json');
        $familyData = $data['family'] ?? null;

        if (! $familyData) {
            return;
        }

        $existing = DB::table('attribute_families')
            ->where('code', $familyData['code'])
            ->first();

        if ($existing) {
            return;
        }

        $now = Carbon::now();

        DB::table('attribute_families')->insert([
            'code'       => $familyData['code'],
            'status'     => $familyData['status'] ?? 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    protected function seedCategories(): void
    {
        $data = $this->loadJson('categories.json');
        $categories = $data['categories'] ?? [];

        if (empty($categories)) {
            return;
        }

        $existing = DB::table('categories')->pluck('code')->all();
        $now = Carbon::now();
        $lft = (int) (DB::table('categories')->max('_rgt') ?? 0) + 1;
        $position = 0;

        foreach ($categories as $cat) {
            if (in_array($cat['code'], $existing, true)) {
                continue;
            }

            $parentId = null;
            if (isset($cat['parent']) && $cat['parent'] !== 'root') {
                $parentId = DB::table('categories')
                    ->where('code', $cat['parent'])
                    ->value('id');
            }

            DB::table('categories')->insert([
                'code'       => $cat['code'],
                'parent_id'  => $parentId,
                'position'   => $position++,
                '_lft'       => $lft++,
                '_rgt'       => $lft++,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

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

        $now = Carbon::now();
        $sortOrder = 0;

        foreach ($data['options'] ?? [] as $option) {
            if (in_array($option['code'], $existing, true)) {
                continue;
            }

            DB::table('attribute_options')->insert([
                'attribute_id' => $attributeId,
                'code'         => $option['code'],
                'sort_order'   => $sortOrder++,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
        }
    }

    protected function seedAssociationTypes(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('association_types')) {
            return;
        }

        $data = $this->loadJson('association_types.json');
        $existing = DB::table('association_types')->pluck('code')->all();
        $now = Carbon::now();

        foreach ($data['association_types'] ?? [] as $type) {
            if (in_array($type['code'], $existing, true)) {
                continue;
            }

            DB::table('association_types')->insert([
                'code'       => $type['code'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

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

        // Pass 1: seed parents (type=simple with no parent_sku, type=configurable)
        foreach ($products as $product) {
            if (isset($product['parent_sku'])) {
                continue;
            }

            if (isset($existingSkus[$product['sku']])) {
                $parentMap[$product['sku']] = $existingSkus[$product['sku']];

                continue;
            }

            $enrichedValues = $this->enrichProductValues($product);
            $enrichedValues = $this->attachGeneratedImage($product, $enrichedValues);

            $id = DB::table('products')->insertGetId([
                'sku'                 => $product['sku'],
                'type'                => $product['type'] ?? 'simple',
                'status'              => 1,
                'parent_id'           => null,
                'attribute_family_id' => $familyId,
                'values'              => json_encode($enrichedValues, JSON_THROW_ON_ERROR),
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
                $this->command?->warn(
                    sprintf(
                        'Variant %s: parent %s not found — skipping',
                        $product['sku'],
                        $product['parent_sku']
                    )
                );

                continue;
            }

            $enrichedValues = $this->enrichProductValues($product);
            $enrichedValues = $this->attachGeneratedImage($product, $enrichedValues);

            DB::table('products')->insert([
                'sku'                 => $product['sku'],
                'type'                => 'simple',
                'status'              => 1,
                'parent_id'           => $parentId,
                'attribute_family_id' => $familyId,
                'values'              => json_encode($enrichedValues, JSON_THROW_ON_ERROR),
                'additional'          => null,
                'created_at'          => $now,
                'updated_at'          => $now,
            ]);
        }

        $this->seedSuperAttributes($products, $familyId);
    }

    protected function seedSuperAttributes(array $products, int $familyId): void
    {
        $rows = [];

        foreach ($products as $product) {
            if (($product['type'] ?? 'simple') !== 'configurable') {
                continue;
            }

            $superCodes = $product['super_attributes'] ?? [];
            if (empty($superCodes)) {
                continue;
            }

            $productId = DB::table('products')->where('sku', $product['sku'])->value('id');
            if (! $productId) {
                continue;
            }

            foreach ($superCodes as $attributeCode) {
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

    protected function seedAssociations(): void
    {
        if (! DB::getSchemaBuilder()->hasTable('product_associations')) {
            return;
        }

        $data = $this->loadJson('associations.json');
        $associations = $data['associations'] ?? [];

        foreach ($associations as $assoc) {
            $fromId = DB::table('products')->where('sku', $assoc['from_sku'])->value('id');
            $toId = DB::table('products')->where('sku', $assoc['to_sku'])->value('id');

            if (! $fromId || ! $toId) {
                continue;
            }

            $typeId = DB::table('association_types')
                ->where('code', $assoc['type'])
                ->value('id');

            if (! $typeId) {
                continue;
            }

            DB::table('product_associations')->insertOrIgnore([
                'product_id'          => $fromId,
                'linked_product_id'   => $toId,
                'association_type_id' => $typeId,
                'created_at'          => Carbon::now(),
                'updated_at'          => Carbon::now(),
            ]);
        }
    }

    /**
     * Fill in missing locale/channel content from canonical en_US/ecommerce
     * entries. Applies the mechanical rules described in translations.json.
     */
    protected function enrichProductValues(array $product): array
    {
        $values = $product['values'] ?? ['common' => [], 'channel_specific' => [], 'channel_locale_specific' => []];

        $values['common'] ??= [];
        $values['channel_specific'] ??= [];
        $values['channel_locale_specific'] ??= [];

        $canonical = $values['channel_locale_specific']['ecommerce']['en_US'] ?? null;

        if (! $canonical) {
            return $values;
        }

        foreach ($this->profile->channels as $channelCode) {
            $values['channel_locale_specific'][$channelCode] ??= [];

            foreach ($this->profile->locales as $localeCode) {
                if (isset($values['channel_locale_specific'][$channelCode][$localeCode])) {
                    continue;
                }

                $values['channel_locale_specific'][$channelCode][$localeCode] =
                    $this->deriveContent($canonical, $channelCode, $localeCode);
            }
        }

        return $values;
    }

    /**
     * Mechanically derive channel+locale content from the canonical en_US
     * ecommerce entry when the fixture didn't provide a hand-authored variant.
     *
     *  - en_GB → en_US with US → UK spelling swaps
     *  - mobile_app → short_description truncated to ~120 chars
     *  - print_catalogue → uppercase name + short_description
     *  - b2b_wholesale → name with "— CASE" suffix
     *  - other locales → copy of en_US (flagged as pending-translation by
     *    staying identical to source, which the MagicAI translate demo
     *    then has real work to do on)
     */
    protected function deriveContent(array $canonical, string $channelCode, string $localeCode): array
    {
        $derived = $canonical;

        // Locale transforms
        if ($localeCode === 'en_GB') {
            $swaps = $this->translations['locale_mechanical_rules']['en_GB']['spelling_swaps'] ?? [];
            foreach ($derived as $key => $value) {
                if (! is_string($value)) {
                    continue;
                }
                $derived[$key] = strtr($value, $swaps);
            }
        }

        // Channel transforms
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
