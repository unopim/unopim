<?php

namespace Webkul\Installer\Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Webkul\Attribute\Models\Attribute;
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

    /** @var array<string, array<string, array<string, string>>> */
    protected array $optionLabels = [];

    /** @var array<string, Attribute> */
    protected array $measurementAttributes = [];

    public function run(): void
    {
        $products = $this->catalog();

        $this->familyIds = DB::table('attribute_families')->pluck('id', 'code')->all();
        $this->attributeIds = DB::table('attributes')->pluck('id', 'code')->all();
        $this->optionLabels = $this->loadOptionLabels();
        $this->measurementAttributes = Attribute::query()
            ->where('type', 'measurement')
            ->get()
            ->keyBy('code')
            ->all();

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

        $structureId = $product['type'] === 'configurable'
            ? $this->seedStructure($product, $familyId)
            : null;

        $parentId = DB::table('products')->insertGetId([
            'sku'                   => $product['sku'],
            'type'                  => $product['type'],
            'status'                => 1,
            'attribute_family_id'   => $familyId,
            'variant_structure_id'  => $structureId,
            'values'                => $this->encodeValues($this->buildValues($product, $channels, $channelLocales)),
            'created_at'            => $now,
            'updated_at'            => $now,
        ]);

        if ($product['type'] !== 'configurable') {
            return;
        }

        $this->seedSuperAttributes($parentId, $product['axes'] ?? []);

        $this->seedVariantTree($product, $parentId, $familyId, $channels, $channelLocales);
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
     * Create the variant structure and its axes for a configurable product.
     *
     * @param  array<string, mixed>  $product
     */
    protected function seedStructure(array $product, int $familyId): ?int
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

        $this->seedStructurePlacements($structureId, $levels);

        return $structureId;
    }

    /**
     * Pin the attributes that are maintained below the configurable: identity
     * data always sits on the variant, imagery on the level-1 group when there
     * is one, because that is where colour or finish is decided.
     */
    protected function seedStructurePlacements(int $structureId, int $levels): void
    {
        $placements = [
            'ean'            => 'variant',
            'product_number' => 'variant',
            'image'          => $levels === 2 ? 'sub_parent' : 'variant',
            'gallery'        => $levels === 2 ? 'sub_parent' : 'variant',
        ];

        foreach ($placements as $code => $level) {
            if (! isset($this->attributeIds[$code])) {
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
     */
    protected function seedVariantTree(array $product, int $parentId, int $familyId, array $channels, array $channelLocales): void
    {
        $axes = $product['axes'] ?? [];
        $variants = $product['variants'] ?? [];

        if ($axes === [] || $variants === []) {
            return;
        }

        if (count($axes) === 1) {
            foreach ($variants as $index => $variant) {
                $this->insertVariantRow($product, $variant, $parentId, $familyId, 'simple', $index, $channels, $channelLocales);
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
                );
            }
        }
    }

    /**
     * Insert one row below the configurable.
     *
     * The gallery stays at the level the variant structure assigns it to,
     * while the main image goes on every row: the product grid renders rows
     * independently and shows a placeholder for anything without one.
     *
     * @param  array<string, mixed>  $product
     * @param  array<string, mixed>  $variant
     * @param  array<int, string>  $channels
     * @param  array<string, array<int, string>>  $channelLocales
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
    ): int {
        $now = Date::now();

        $sku = $variant['sku'] ?? $product['sku'].'-'.$variant['suffix'];

        $media = $this->mediaValues($product);

        $rowMedia = $type === 'variant_group' || count($product['axes'] ?? []) === 1
            ? $media
            : array_intersect_key($media, ['image' => true]);

        $values = [
            'common' => array_merge(
                ['sku' => $sku, 'url_key' => $sku],
                $variant['axis'],
                $type === 'simple' ? ['ean' => $this->variantEan($product, $index)] : [],
                $rowMedia,
            ),
        ];

        $values['channel_locale_specific'] = $this->variantCopy($product, $variant, $type, $channels, $channelLocales);

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
     * Name and price for a row below the configurable. Without its own name a
     * variant shows up as a blank row in the product grid, so each one is
     * labelled with the axis values that distinguish it.
     *
     * @param  array<string, mixed>  $product
     * @param  array<string, mixed>  $variant
     * @param  array<int, string>  $channels
     * @param  array<string, array<int, string>>  $channelLocales
     * @return array<string, array<string, array<string, mixed>>>
     */
    protected function variantCopy(array $product, array $variant, string $type, array $channels, array $channelLocales): array
    {
        $values = [];

        foreach ($channels as $channel) {
            foreach ($channelLocales[$channel] ?? [] as $locale) {
                $copy = $product['locales'][$locale] ?? null;

                if ($copy === null) {
                    continue;
                }

                $values[$channel][$locale]['name'] = trim($copy['name'].' — '.$this->axisLabel($variant['axis'], $locale));

                if ($type === 'simple') {
                    $values[$channel][$locale]['price'] = $this->prices($product['prices'] ?? []);
                }
            }
        }

        return $values;
    }

    /**
     * Human-readable label for a variant's axis values in the given locale,
     * falling back to the option code when a translation is missing.
     *
     * @param  array<string, string>  $axis
     */
    protected function axisLabel(array $axis, string $locale): string
    {
        $labels = [];

        foreach ($axis as $attributeCode => $optionCode) {
            $labels[] = $this->optionLabels[$attributeCode][$optionCode][$locale]
                ?? $this->optionLabels[$attributeCode][$optionCode]['en_US']
                ?? $optionCode;
        }

        return implode(' / ', $labels);
    }

    /**
     * Option labels indexed by attribute code, option code and locale.
     *
     * @return array<string, array<string, array<string, string>>>
     */
    protected function loadOptionLabels(): array
    {
        $rows = DB::table('attribute_options')
            ->join('attributes', 'attributes.id', '=', 'attribute_options.attribute_id')
            ->leftJoin('attribute_option_translations', 'attribute_option_translations.attribute_option_id', '=', 'attribute_options.id')
            ->get([
                'attributes.code as attribute_code',
                'attribute_options.code as option_code',
                'attribute_option_translations.locale',
                'attribute_option_translations.label',
            ]);

        $labels = [];

        foreach ($rows as $row) {
            if ($row->locale === null) {
                continue;
            }

            $labels[$row->attribute_code][$row->option_code][$row->locale] = $row->label;
        }

        return $labels;
    }

    /**
     * Assemble the full values payload for a parent product.
     *
     * @param  array<string, mixed>  $product
     * @param  array<int, string>  $channels
     * @param  array<string, array<int, string>>  $channelLocales
     * @return array<string, mixed>
     */
    protected function buildValues(array $product, array $channels, array $channelLocales): array
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
            'common'     => array_merge($common, $media),
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

                $values['channel_locale_specific'][$channel][$locale] = $this->channelLocaleValues($copy, $product);
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
