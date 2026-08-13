<?php

namespace Webkul\Installer\Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Core\Helpers\Database\DatabaseSequenceHelper;
use Webkul\Core\Rules\FileOrImageValidValue;
use Webkul\Installer\Database\Seeders\Demo\Concerns\LoadsDemoData;
use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Product\Services\VariantStructurePlanner;

/**
 * Seeds the demo catalog: configurable parents, their variant structures and
 * the product tree beneath them.
 *
 * A product declaring two axes gets a two-level structure — configurable →
 * variant_group (level 1) → simple (level 2) — which is the shape
 * {@see VariantStructurePlanner} expects. One axis
 * produces a single-level structure with simple variants directly under the
 * configurable.
 */
class DemoProductSeeder extends Seeder
{
    use LoadsDemoData;

    /**
     * Attributes whose value is scoped per channel and locale rather than
     * carried on the product's common values.
     */
    protected const CHANNEL_LOCALE_ATTRIBUTES = [
        'name', 'short_description', 'description', 'meta_title', 'meta_keywords', 'meta_description',
    ];

    /**
     * Attributes translated but not channel-scoped.
     */
    protected const LOCALE_ATTRIBUTES = [
        'highlights', 'care_instructions', 'ingredients', 'storage_instructions',
    ];

    protected const FAMILY_FILES = [
        'audio', 'apparel', 'home', 'outdoor', 'beauty', 'food', 'sports', 'furniture',
    ];

    /** @var array<string, int> */
    protected array $familyIds = [];

    /** @var array<string, int> */
    protected array $attributeIds = [];

    /** @var array<string, Attribute> */
    protected array $measurementAttributes = [];

    /** @var array<int, array<int, string>> Unique attribute codes per family id. */
    protected array $familyUniqueCodes = [];

    public function run(): void
    {
        $products = $this->catalog();

        $this->familyIds = DB::table('attribute_families')->pluck('id', 'code')->all();
        $this->attributeIds = DB::table('attributes')->pluck('id', 'code')->all();
        $this->measurementAttributes = Attribute::query()
            ->where('type', 'measurement')
            ->get()
            ->keyBy('code')
            ->all();
        $this->familyUniqueCodes = $this->loadFamilyUniqueCodes();

        $channels = DB::table('channels')->pluck('code')->all();

        $channelLocales = $this->channelLocales();

        DB::transaction(function () use ($products, $channels, $channelLocales): void {
            $this->clearCatalog();

            foreach ($products as $product) {
                $this->seedProduct($product, $channels, $channelLocales);
            }

            $this->relocateMedia();
        });

        DatabaseSequenceHelper::fixSequences(['products', 'variant_structures', 'variant_structure_axes', 'variant_structure_attributes']);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function catalog(): array
    {
        $products = [];

        foreach (self::FAMILY_FILES as $file) {
            $products = array_merge($products, $this->demoData('products/'.$file));
        }

        return $products;
    }

    /**
     * Publications hold a restricting foreign key to products, so a re-seed has
     * to drop the passport tier it created before the catalog underneath it.
     */
    protected function clearCatalog(): void
    {
        $publicationIds = DB::table('publications')->pluck('id');

        if ($publicationIds->isNotEmpty()) {
            $versionIds = DB::table('publication_versions')->whereIn('publication_id', $publicationIds)->pluck('id');

            DB::table('publication_version_payloads')->whereIn('publication_version_id', $versionIds)->delete();
            DB::table('publication_version_documents')->whereIn('publication_version_id', $versionIds)->delete();
            DB::table('publication_view_stats')->whereIn('publication_id', $publicationIds)->delete();
            DB::table('publication_versions')->whereIn('publication_id', $publicationIds)->delete();
            DB::table('publications')->whereIn('id', $publicationIds)->delete();
        }

        DB::table('publication_publish_attempts')->delete();
        DB::table('product_associations')->delete();
        DB::table('product_super_attributes')->delete();
        DB::table('product_completeness')->delete();
        DB::table('products')->whereNotNull('parent_id')->delete();
        DB::table('products')->delete();
        DB::table('variant_structure_axes')->delete();
        DB::table('variant_structure_attributes')->delete();
        DB::table('variant_structures')->delete();
    }

    /**
     * Locale codes wired to each channel, keyed by channel code.
     *
     * @return array<string, array<int, string>>
     */
    protected function channelLocales(): array
    {
        $rows = DB::table('channel_locales')
            ->join('channels', 'channels.id', '=', 'channel_locales.channel_id')
            ->join('locales', 'locales.id', '=', 'channel_locales.locale_id')
            ->where('locales.status', 1)
            ->get(['channels.code as channel', 'locales.code as locale']);

        $map = [];

        foreach ($rows as $row) {
            $map[$row->channel][] = $row->locale;
        }

        return $map;
    }

    /**
     * @param  array<string, mixed>  $product
     * @param  array<int, string>  $channels
     * @param  array<string, array<int, string>>  $channelLocales
     */
    protected function seedProduct(array $product, array $channels, array $channelLocales): void
    {
        $now = Date::now();

        $familyId = $this->familyIds[$product['family']] ?? null;

        if (! $familyId) {
            return;
        }

        $placements = $product['type'] === 'configurable'
            ? $this->placementMap($product, $this->familyUniqueCodes[$familyId] ?? [])
            : [];

        $structureId = $product['type'] === 'configurable'
            ? $this->seedStructure($product, $familyId, $placements)
            : null;

        $parentId = DB::table('products')->insertGetId([
            'sku'                   => $product['sku'],
            'type'                  => $product['type'],
            'status'                => 1,
            'attribute_family_id'   => $familyId,
            'variant_structure_id'  => $structureId,
            'values'                => $this->encodeValues($this->buildValues($product, $channels, $channelLocales, $placements)),
            'created_at'            => $now,
            'updated_at'            => $now,
        ]);

        if ($product['type'] !== 'configurable') {
            return;
        }

        $this->seedSuperAttributes($parentId, $product['axes'] ?? []);

        $this->seedVariantTree($product, $parentId, $familyId, $channels, $channelLocales, $placements);
    }

    /**
     * Structure code for a configurable's sku. SKUs are hyphenated, which the
     * Code rule the family form validates against rejects, so every non-word
     * character collapses to an underscore.
     */
    public function structureCode(string $sku): string
    {
        return preg_replace('/\W+/', '_', $sku).'_structure';
    }

    /**
     * Attribute code to structure level for a configurable, empty for anything
     * without axes.
     *
     * Unique attributes go to the variant, the level the family form forces
     * them to. Price joins them so each sellable row is priced on its own,
     * while the main image stays common for every row below to inherit and the
     * gallery sits wherever the axis it illustrates is decided.
     *
     * @param  array<string, mixed>  $product
     * @param  array<int, string>  $uniqueCodes
     * @return array<string, string>
     */
    public function placementMap(array $product, array $uniqueCodes): array
    {
        $axes = array_values($product['axes'] ?? []);

        if ($axes === []) {
            return [];
        }

        $levels = min(count($axes), 2);
        $map = [];

        foreach ($axes as $index => $code) {
            $map[$code] = $levels === 2 && $index === 0 ? 'sub_parent' : 'variant';
        }

        foreach ($uniqueCodes as $code) {
            $map[$code] ??= 'variant';
        }

        $map['price'] = 'variant';
        $map['image'] = 'common';
        $map['gallery'] = $levels === 2 ? 'sub_parent' : 'variant';

        return $map;
    }

    /**
     * The values a row at the given level may carry. Anything placed elsewhere
     * belongs to another row and is inherited from it, sku excepted: it
     * identifies every row and is written to all of them.
     *
     * @param  array<string, mixed>  $values
     * @param  array<string, string>  $placements
     * @return array<string, mixed>
     */
    public function ownedValues(array $values, array $placements, string $level): array
    {
        if ($placements === []) {
            return $values;
        }

        return array_filter(
            $values,
            static fn (string $code): bool => $code === 'sku' || ($placements[$code] ?? 'common') === $level,
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * Unique attribute codes each family carries, over the joins
     * {@see AttributeFamily::customAttributes()} uses.
     *
     * @return array<int, array<int, string>>
     */
    protected function loadFamilyUniqueCodes(): array
    {
        $rows = DB::table('attributes')
            ->join('attribute_group_mappings', 'attributes.id', '=', 'attribute_group_mappings.attribute_id')
            ->join('attribute_family_group_mappings', 'attribute_group_mappings.attribute_family_group_id', '=', 'attribute_family_group_mappings.id')
            ->where('attributes.is_unique', 1)
            ->get(['attribute_family_group_mappings.attribute_family_id as family_id', 'attributes.code']);

        $map = [];

        foreach ($rows as $row) {
            $map[(int) $row->family_id][$row->code] = $row->code;
        }

        return array_map(array_values(...), $map);
    }

    /**
     * Create the variant structure and its axes for a configurable product.
     *
     * @param  array<string, mixed>  $product
     * @param  array<string, string>  $placements
     */
    protected function seedStructure(array $product, int $familyId, array $placements): ?int
    {
        $axes = $product['axes'] ?? [];

        if ($axes === []) {
            return null;
        }

        $now = Date::now();
        $levels = min(count($axes), 2);

        $structureId = DB::table('variant_structures')->insertGetId([
            'attribute_family_id' => $familyId,
            'code'                => $this->structureCode($product['sku']),
            'name'                => $product['locales']['en_US']['name'] ?? $product['sku'],
            'levels'              => $levels,
            'created_at'          => $now,
            'updated_at'          => $now,
        ]);

        foreach (array_values($axes) as $index => $code) {
            if (! isset($this->attributeIds[$code])) {
                continue;
            }

            DB::table('variant_structure_axes')->insert([
                'variant_structure_id' => $structureId,
                'attribute_id'         => (int) $this->attributeIds[$code],
                'level'                => $index === 0 ? 'level_1' : 'level_2',
                'position'             => $index + 1,
            ]);
        }

        $this->seedStructurePlacements($structureId, $placements, array_values($axes));

        return $structureId;
    }

    /**
     * Record where each attribute is maintained. Axes are left out: they carry
     * their level on their own row, and the family form keeps them out of the
     * placement lists for the same reason.
     *
     * @param  array<string, string>  $placements
     * @param  array<int, string>  $axes
     */
    protected function seedStructurePlacements(int $structureId, array $placements, array $axes): void
    {
        foreach ($placements as $code => $level) {
            if (in_array($code, $axes, true) || ! isset($this->attributeIds[$code])) {
                continue;
            }

            DB::table('variant_structure_attributes')->insert([
                'variant_structure_id' => $structureId,
                'attribute_id'         => (int) $this->attributeIds[$code],
                'level'                => $level,
            ]);
        }
    }

    /**
     * @param  array<int, string>  $axes
     */
    protected function seedSuperAttributes(int $productId, array $axes): void
    {
        $rows = [];

        foreach ($axes as $code) {
            if (! isset($this->attributeIds[$code])) {
                continue;
            }

            $rows[] = [
                'product_id'   => $productId,
                'attribute_id' => (int) $this->attributeIds[$code],
            ];
        }

        if ($rows !== []) {
            DB::table('product_super_attributes')->insertOrIgnore($rows);
        }
    }

    /**
     * Build the product rows under a configurable.
     *
     * @param  array<string, mixed>  $product
     * @param  array<int, string>  $channels
     * @param  array<string, array<int, string>>  $channelLocales
     * @param  array<string, string>  $placements
     */
    protected function seedVariantTree(array $product, int $parentId, int $familyId, array $channels, array $channelLocales, array $placements): void
    {
        $axes = $product['axes'] ?? [];
        $variants = $product['variants'] ?? [];

        if ($axes === [] || $variants === []) {
            return;
        }

        if (count($axes) === 1) {
            foreach ($variants as $index => $variant) {
                $this->insertVariantRow($product, $variant, $parentId, $familyId, 'simple', $index, $channels, $channelLocales, $placements);
            }

            return;
        }

        [$levelOneAxis, $levelTwoAxis] = array_values($axes);

        $groups = [];

        foreach ($variants as $variant) {
            $groups[$variant['axis'][$levelOneAxis]][] = $variant;
        }

        $groupIndex = 0;

        foreach ($groups as $levelOneValue => $children) {
            $groupSku = $product['sku'].'-'.Str::slug((string) $levelOneValue);

            $groupId = $this->insertVariantRow(
                $product,
                [
                    'suffix' => Str::slug((string) $levelOneValue),
                    'axis'   => [$levelOneAxis => $levelOneValue],
                    'sku'    => $groupSku,
                ],
                $parentId,
                $familyId,
                'variant_group',
                $groupIndex++,
                $channels,
                $channelLocales,
                $placements,
            );

            foreach ($children as $childIndex => $child) {
                $this->insertVariantRow(
                    $product,
                    [
                        'suffix' => $child['suffix'],
                        'axis'   => [$levelTwoAxis => $child['axis'][$levelTwoAxis]],
                        'sku'    => $product['sku'].'-'.$child['suffix'],
                    ],
                    $groupId,
                    $familyId,
                    'simple',
                    $childIndex,
                    $channels,
                    $channelLocales,
                    $placements,
                );
            }
        }
    }

    /**
     * Insert one row below the configurable, carrying only what the variant
     * structure places at that row's level. Everything else — the name, the
     * main image, the descriptive copy — resolves down from the configurable.
     *
     * @param  array<string, mixed>  $product
     * @param  array<string, mixed>  $variant
     * @param  array<int, string>  $channels
     * @param  array<string, array<int, string>>  $channelLocales
     * @param  array<string, string>  $placements
     */
    protected function insertVariantRow(
        array $product,
        array $variant,
        int $parentId,
        int $familyId,
        string $type,
        int $index,
        array $channels,
        array $channelLocales,
        array $placements,
    ): int {
        $now = Date::now();

        $sku = $variant['sku'] ?? $product['sku'].'-'.$variant['suffix'];

        $level = $type === 'variant_group' ? 'sub_parent' : 'variant';

        $values = [
            'common' => $this->ownedValues(
                array_merge(
                    ['sku' => $sku, 'url_key' => $sku],
                    $variant['axis'],
                    $type === 'simple' ? [
                        'ean'            => $this->variantEan($product, $index),
                        'product_number' => $this->variantProductNumber($product, $sku),
                    ] : [],
                    $this->mediaValues($product),
                ),
                $placements,
                $level,
            ),
        ];

        $values['channel_locale_specific'] = $this->variantCopy($product, $channels, $channelLocales, $placements, $level);

        return (int) DB::table('products')->insertGetId([
            'sku'                  => $sku,
            'type'                 => $type,
            'status'               => 1,
            'parent_id'            => $parentId,
            'attribute_family_id'  => $familyId,
            'variant_structure_id' => null,
            'values'               => $this->encodeValues($values),
            'created_at'           => $now,
            'updated_at'           => $now,
        ]);
    }

    /**
     * Derive a product number per variant from the parent's. It is unique, so
     * the configurable cannot hold it and each row below needs its own.
     *
     * @param  array<string, mixed>  $product
     */
    protected function variantProductNumber(array $product, string $sku): string
    {
        $base = (string) ($product['common']['product_number'] ?? mb_strtoupper($product['sku']));

        return $base.'-'.mb_strtoupper((string) preg_replace('/^'.preg_quote($product['sku'], '/').'-/', '', $sku));
    }

    /**
     * Derive a unique, checksum-shaped EAN per variant from the parent's.
     *
     * @param  array<string, mixed>  $product
     */
    protected function variantEan(array $product, int $index): string
    {
        $base = (string) ($product['common']['ean'] ?? '4000000000000');

        return substr($base, 0, 9).str_pad((string) (($index + 1) % 10000), 4, '0', STR_PAD_LEFT);
    }

    /**
     * Channel and locale scoped values for a row below the configurable. The
     * name is maintained on the configurable and resolves down, so only what
     * the structure places at this row's level is written.
     *
     * @param  array<string, mixed>  $product
     * @param  array<int, string>  $channels
     * @param  array<string, array<int, string>>  $channelLocales
     * @param  array<string, string>  $placements
     * @return array<string, array<string, array<string, mixed>>>
     */
    protected function variantCopy(array $product, array $channels, array $channelLocales, array $placements, string $level): array
    {
        $values = [];

        foreach ($channels as $channel) {
            foreach ($channelLocales[$channel] ?? [] as $locale) {
                if (! isset($product['locales'][$locale])) {
                    continue;
                }

                $owned = $this->ownedValues(
                    ['price' => $this->prices($product['prices'] ?? [])],
                    $placements,
                    $level,
                );

                if ($owned !== []) {
                    $values[$channel][$locale] = $owned;
                }
            }
        }

        return $values;
    }

    /**
     * Assemble the full values payload for a parent product.
     *
     * @param  array<string, mixed>  $product
     * @param  array<int, string>  $channels
     * @param  array<string, array<int, string>>  $channelLocales
     * @param  array<string, string>  $placements
     * @return array<string, mixed>
     */
    protected function buildValues(array $product, array $channels, array $channelLocales, array $placements): array
    {
        $common = $this->normaliseMeasurements(array_merge(
            [
                'sku'     => $product['sku'],
                'url_key' => $product['sku'],
            ],
            $product['common'] ?? [],
        ));

        $media = $this->mediaValues($product);

        $values = [
            'common'     => $this->ownedValues(array_merge($common, $media), $placements, 'common'),
            'categories' => $product['categories'] ?? [],
        ];

        foreach ($product['locales'] as $locale => $copy) {
            foreach (self::LOCALE_ATTRIBUTES as $code) {
                if (isset($copy[$code])) {
                    $values['locale_specific'][$locale][$code] = $copy[$code];
                }
            }
        }

        foreach ($channels as $channel) {
            if (isset($product['cost'])) {
                $values['channel_specific'][$channel]['cost'] = $this->prices($product['cost']);
            }

            foreach ($channelLocales[$channel] ?? [] as $locale) {
                $copy = $product['locales'][$locale] ?? null;

                if ($copy === null) {
                    continue;
                }

                $values['channel_locale_specific'][$channel][$locale] = $this->ownedValues(
                    $this->channelLocaleValues($copy, $product),
                    $placements,
                    'common',
                );
            }
        }

        return $values;
    }

    /**
     * @param  array<string, string>  $copy
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    protected function channelLocaleValues(array $copy, array $product): array
    {
        $values = [];

        foreach (self::CHANNEL_LOCALE_ATTRIBUTES as $code) {
            if (isset($copy[$code])) {
                $values[$code] = $copy[$code];
            }
        }

        $values['meta_title'] ??= $copy['name'] ?? null;
        $values['meta_description'] ??= $copy['short_description'] ?? null;
        $values['meta_keywords'] ??= $this->keywords($copy['name'] ?? '');

        $values['price'] = $this->prices($product['prices'] ?? []);

        return array_filter($values, static fn ($value): bool => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, int|string>  $prices
     * @return array<string, string>
     */
    protected function prices(array $prices): array
    {
        return array_map(static fn ($amount): string => (string) $amount, $prices);
    }

    protected function keywords(string $name): string
    {
        return implode('|', array_slice(explode(' ', mb_strtolower($name)), 0, 6));
    }

    /**
     * Media paths for a product, skipping references the media seeder did not
     * produce so a missing file never becomes a broken image in the grid.
     *
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    protected function mediaValues(array $product): array
    {
        $key = $product['media'] ?? null;

        if ($key === null) {
            return [];
        }

        $disk = Storage::disk('public');
        $values = [];

        $primary = DemoMediaSeeder::CATALOG_PATH.'/'.$key.'.webp';

        if ($disk->exists($primary)) {
            $values['image'] = $primary;
        }

        $gallery = [];

        foreach (['-detail', '-context'] as $suffix) {
            $path = DemoMediaSeeder::CATALOG_PATH.'/'.$key.$suffix.'.webp';

            if ($disk->exists($path)) {
                $gallery[] = $path;
            }
        }

        if ($gallery !== []) {
            $values['gallery'] = $gallery;
        }

        $sheet = DemoMediaSeeder::SHEET_PATH.'/'.$product['family'].'.pdf';

        if ($disk->exists($sheet)) {
            $values['spec_sheet'] = $sheet;
        }

        return $values;
    }

    /**
     * Move every product's media under `product/<id>/<attribute code>/`.
     *
     * A stored media path is only accepted by
     * {@see FileOrImageValidValue} when it sits under the
     * owning product's own prefix, which cannot be known until the row has an
     * id — so the seeder writes the shared demo path first and rewrites it here.
     */
    protected function relocateMedia(): void
    {
        $disk = Storage::disk('public');

        DB::table('products')
            ->select('id', 'values')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($disk): void {
                foreach ($rows as $row) {
                    $values = json_decode((string) $row->values, true);

                    if (! is_array($values) || ! isset($values['common'])) {
                        continue;
                    }

                    $changed = false;

                    foreach (['image', 'spec_sheet', 'assembly_manual'] as $code) {
                        $path = $values['common'][$code] ?? null;

                        if (! is_string($path) || $path === '') {
                            continue;
                        }

                        $target = $this->relocate($disk, $path, (int) $row->id, $code);

                        if ($target === null) {
                            unset($values['common'][$code]);
                        } else {
                            $values['common'][$code] = $target;
                        }

                        $changed = true;
                    }

                    $gallery = $values['common']['gallery'] ?? null;

                    if (is_array($gallery)) {
                        $relocated = [];

                        foreach ($gallery as $path) {
                            $target = $this->relocate($disk, (string) $path, (int) $row->id, 'gallery');

                            if ($target !== null) {
                                $relocated[] = $target;
                            }
                        }

                        $values['common']['gallery'] = $relocated;
                        $changed = true;
                    }

                    if ($changed) {
                        DB::table('products')->where('id', $row->id)->update([
                            'values' => $this->encodeValues($values),
                        ]);
                    }
                }
            });
    }

    /**
     * Copy one media file under the product's own prefix, returning the new
     * path, or null when the source is missing.
     */
    protected function relocate($disk, string $path, int $productId, string $code): ?string
    {
        $target = 'product/'.$productId.'/'.$code.'/'.basename($path);

        if ($path === $target) {
            return $target;
        }

        if (! $disk->exists($path)) {
            return $disk->exists($target) ? $target : null;
        }

        $disk->put($target, $disk->get($path));

        return $target;
    }

    /**
     * Rewrite the datasets' `{value, unit}` pairs into the structure the
     * measurement package stores — amount, unit, family, base value and
     * symbol. The product form reads `amount`, so an unconverted value
     * renders as an empty field.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function normaliseMeasurements(array $values): array
    {
        foreach ($values as $code => $value) {
            $attribute = $this->measurementAttributes[$code] ?? null;

            if ($attribute === null || ! is_array($value) || ! isset($value['value'])) {
                continue;
            }

            $values[$code] = resolve(MeasurementHelper::class)->getMeasurementValueStructure(
                $value['value'],
                $value['unit'] ?? null,
                $attribute,
            );
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    protected function encodeValues(array $values): string
    {
        return json_encode($values, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
