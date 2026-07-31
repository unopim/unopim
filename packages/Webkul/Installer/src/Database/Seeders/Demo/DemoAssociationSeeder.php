<?php

namespace Webkul\Installer\Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Webkul\Installer\Database\Seeders\Demo\Concerns\LoadsDemoData;

/**
 * Seeds the association types, their custom fields and the demo product links.
 *
 * Links carry `additional_data` in the shape the association UI writes, so the
 * custom fields on the user-defined types are populated rather than empty.
 */
class DemoAssociationSeeder extends Seeder
{
    use LoadsDemoData;

    /** @var array<string, int> */
    protected array $typeIds = [];

    public function __construct(
        protected DemoProductSeeder $catalog,
    ) {}

    public function run(): void
    {
        $data = $this->demoData('associations');

        DB::transaction(function () use ($data): void {
            foreach ($data['types'] as $position => $type) {
                $this->seedType($type, $position + 1);
            }

            $this->seedLinks($this->withCatalogCoverage($data['links']));
        });
    }

    /**
     * Fill in products the curated link list does not mention.
     *
     * Hand-written links carry the interesting cases (spare parts, bundles,
     * cross-family pairings); this adds the obvious ones — catalogue
     * neighbours and same-brand cross-sells — so no product opens with an
     * empty associations panel.
     *
     * @param  array<string, array<string, mixed>>  $curated
     * @return array<string, array<string, mixed>>
     */
    protected function withCatalogCoverage(array $curated): array
    {
        $products = array_filter(
            $this->catalog->catalog(),
            static fn (array $product): bool => ($product['categories'] ?? []) !== [],
        );

        $byCategory = [];
        $byBrand = [];

        foreach ($products as $product) {
            $byCategory[$product['categories'][0]][] = $product['sku'];

            $brand = $product['common']['brand'] ?? null;

            if ($brand !== null) {
                $byBrand[$brand][] = $product['sku'];
            }
        }

        foreach ($products as $product) {
            $sku = $product['sku'];

            $curated[$sku]['related_products'] ??= $this->pick($byCategory[$product['categories'][0]] ?? [], $sku, 4);

            $brand = $product['common']['brand'] ?? null;

            if ($brand !== null) {
                $curated[$sku]['cross_sells'] ??= $this->pick(
                    array_diff($byBrand[$brand] ?? [], $byCategory[$product['categories'][0]] ?? []),
                    $sku,
                    3,
                );
            }

            if ($curated[$sku]['related_products'] === []) {
                unset($curated[$sku]['related_products']);
            }

            if (($curated[$sku]['cross_sells'] ?? null) === []) {
                unset($curated[$sku]['cross_sells']);
            }
        }

        return array_filter($curated);
    }

    /**
     * @param  array<int, string>  $candidates
     * @return array<int, string>
     */
    protected function pick(array $candidates, string $exclude, int $limit): array
    {
        return array_slice(
            array_values(array_filter($candidates, static fn (string $sku): bool => $sku !== $exclude)),
            0,
            $limit,
        );
    }

    /**
     * @param  array<string, mixed>  $type
     */
    protected function seedType(array $type, int $position): void
    {
        $now = Date::now();

        DB::table('association_types')->updateOrInsert(
            ['code' => $type['code']],
            [
                'status'          => true,
                'position'        => $position,
                'is_user_defined' => $type['is_user_defined'] ?? false,
                'updated_at'      => $now,
                'created_at'      => $now,
            ]
        );

        $typeId = (int) DB::table('association_types')->where('code', $type['code'])->value('id');

        $this->typeIds[$type['code']] = $typeId;

        foreach ($type['labels'] as $locale => $name) {
            DB::table('association_type_translations')->updateOrInsert(
                ['association_type_id' => $typeId, 'locale' => $locale],
                ['name' => $name]
            );
        }

        foreach (array_values($type['fields'] ?? []) as $index => $field) {
            $this->seedField($typeId, $field, $index + 1);
        }
    }

    /**
     * @param  array<string, mixed>  $field
     */
    protected function seedField(int $typeId, array $field, int $position): void
    {
        $now = Date::now();

        DB::table('association_type_fields')->updateOrInsert(
            ['association_type_id' => $typeId, 'code' => $field['code']],
            [
                'type'             => $field['type'],
                'validation'       => $field['validation'] ?? null,
                'position'         => $position,
                'is_required'      => $field['is_required'] ?? false,
                'is_unique'        => false,
                'status'           => true,
                'section'          => $field['section'] ?? 'left',
                'value_per_locale' => $field['value_per_locale'] ?? false,
                'enable_wysiwyg'   => $field['enable_wysiwyg'] ?? false,
                'updated_at'       => $now,
                'created_at'       => $now,
            ]
        );

        $fieldId = (int) DB::table('association_type_fields')
            ->where('association_type_id', $typeId)
            ->where('code', $field['code'])
            ->value('id');

        foreach ($field['labels'] as $locale => $name) {
            DB::table('association_type_field_translations')->updateOrInsert(
                ['association_type_field_id' => $fieldId, 'locale' => $locale],
                ['name' => $name]
            );
        }

        foreach (array_values($field['options'] ?? []) as $index => $option) {
            DB::table('association_type_field_options')->updateOrInsert(
                ['association_type_field_id' => $fieldId, 'code' => $option['code']],
                ['sort_order' => $index + 1]
            );
        }
    }

    /**
     * @param  array<string, array<string, mixed>>  $links
     */
    protected function seedLinks(array $links): void
    {
        $now = Date::now();

        $productIds = DB::table('products')->pluck('id', 'sku');

        $rows = [];

        foreach ($links as $sku => $byType) {
            $productId = $productIds[$sku] ?? null;

            if (! $productId) {
                continue;
            }

            foreach ($byType as $typeCode => $targets) {
                $typeId = $this->typeIds[$typeCode] ?? null;

                if (! $typeId) {
                    continue;
                }

                foreach (array_values($targets) as $position => $target) {
                    $targetSku = is_array($target) ? $target['sku'] : $target;
                    $relatedId = $productIds[$targetSku] ?? null;

                    if (! $relatedId || $relatedId === $productId) {
                        continue;
                    }

                    $rows[] = [
                        'product_id'          => (int) $productId,
                        'association_type_id' => $typeId,
                        'related_product_id'  => (int) $relatedId,
                        'position'            => $position + 1,
                        'additional_data'     => $this->additionalData($target),
                        'created_at'          => $now,
                        'updated_at'          => $now,
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('product_associations')->insert($chunk);
        }
    }

    /**
     * @param  array<string, mixed>|string  $target
     */
    protected function additionalData(array|string $target): ?string
    {
        if (! is_array($target)) {
            return null;
        }

        $data = [];

        if (isset($target['data'])) {
            $data['common'] = $target['data'];
        }

        if (isset($target['locale_data'])) {
            $data['locale_specific'] = $target['locale_data'];
        }

        return $data === [] ? null : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
