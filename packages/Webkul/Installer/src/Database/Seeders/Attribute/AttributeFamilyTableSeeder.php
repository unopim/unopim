<?php

namespace Webkul\Installer\Database\Seeders\Attribute;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Helpers\Database\DatabaseSequenceHelper;
use Webkul\Installer\Database\Seeders\Concerns\SeedsMissingRows;

class AttributeFamilyTableSeeder extends Seeder
{
    use SeedsMissingRows;

    /**
     * Seed the application's database.
     */
    public function run(array $parameters = []): void
    {
        $defaultLocale = $parameters['default_locale'] ?? config('app.locale');

        $insertedIds = $this->insertMissingRows('attribute_families', [
            [
                'id'     => 1,
                'code'   => 'default',
                'status' => 1,
            ],
        ]);

        $locales = $parameters['allowed_locales'] ?? [$defaultLocale];

        foreach ($locales as $locale) {
            DB::table('attribute_family_translations')->insert($this->rowsOfInserted([
                [
                    'locale'              => $locale,
                    'name'                => trans('installer::app.seeders.attribute.attribute-families.default', [], $locale),
                    'attribute_family_id' => 1,
                ],
            ], 'attribute_family_id', $insertedIds));
        }

        $familyId = DB::table('attribute_families')->where('code', 'default')->value('id');

        $groupIds = DB::table('attribute_groups')
            ->whereIn('code', ['general', 'description', 'meta_description', 'price', 'media'])
            ->pluck('id', 'code')
            ->all();

        $this->insertMissingRows('attribute_family_group_mappings', [
            [
                'id'                  => 1,
                'attribute_family_id' => $familyId,
                'attribute_group_id'  => $groupIds['general'],
                'position'            => 1,
            ], [
                'id'                  => 2,
                'attribute_family_id' => $familyId,
                'attribute_group_id'  => $groupIds['description'],
                'position'            => 2,
            ], [
                'id'                  => 3,
                'attribute_family_id' => $familyId,
                'attribute_group_id'  => $groupIds['meta_description'],
                'position'            => 3,
            ], [
                'id'                  => 4,
                'attribute_family_id' => $familyId,
                'attribute_group_id'  => $groupIds['price'],
                'position'            => 4,
            ], [
                'id'                  => 5,
                'attribute_family_id' => $familyId,
                'attribute_group_id'  => $groupIds['media'],
                'position'            => 5,
            ],
        ], ['attribute_family_id', 'attribute_group_id']);

        $attributeMappings = [
            [
                'attribute' => 'sku',
                'group'     => 'general',
                'position'  => 1,
            ], [
                'attribute' => 'product_number',
                'group'     => 'general',
                'position'  => 2,
            ], [
                'attribute' => 'name',
                'group'     => 'general',
                'position'  => 3,
            ], [
                'attribute' => 'url_key',
                'group'     => 'general',
                'position'  => 4,
            ], [
                'attribute' => 'tax_category_id',
                'group'     => 'general',
                'position'  => 5,
            ], [
                'attribute' => 'color',
                'group'     => 'general',
                'position'  => 6,
            ], [
                'attribute' => 'size',
                'group'     => 'general',
                'position'  => 7,
            ], [
                'attribute' => 'brand',
                'group'     => 'general',
                'position'  => 8,
            ], [
                'attribute' => 'short_description',
                'group'     => 'description',
                'position'  => 1,
            ], [
                'attribute' => 'description',
                'group'     => 'description',
                'position'  => 2,
            ], [
                'attribute' => 'price',
                'group'     => 'price',
                'position'  => 1,
            ], [
                'attribute' => 'cost',
                'group'     => 'price',
                'position'  => 2,
            ], [
                'attribute' => 'meta_title',
                'group'     => 'meta_description',
                'position'  => 1,
            ], [
                'attribute' => 'meta_keywords',
                'group'     => 'meta_description',
                'position'  => 2,
            ], [
                'attribute' => 'meta_description',
                'group'     => 'meta_description',
                'position'  => 3,
            ], [
                'attribute' => 'image',
                'group'     => 'media',
                'position'  => 5,
            ],
        ];

        $familyGroupIds = DB::table('attribute_family_group_mappings')
            ->where('attribute_family_id', $familyId)
            ->pluck('id', 'attribute_group_id')
            ->all();

        $attributeIds = DB::table('attributes')
            ->whereIn('code', array_column($attributeMappings, 'attribute'))
            ->pluck('id', 'code')
            ->all();

        $mappedAttributeIds = DB::table('attribute_group_mappings')
            ->whereIn('attribute_family_group_id', $familyGroupIds)
            ->pluck('attribute_id')
            ->all();

        DB::table('attribute_group_mappings')->insert(
            collect($attributeMappings)
                ->map(fn (array $mapping): array => [
                    'attribute_id'              => $attributeIds[$mapping['attribute']],
                    'attribute_family_group_id' => $familyGroupIds[$groupIds[$mapping['group']]],
                    'position'                  => $mapping['position'],
                ])
                ->reject(fn (array $mapping): bool => in_array($mapping['attribute_id'], $mappedAttributeIds))
                ->values()
                ->all()
        );

        DatabaseSequenceHelper::fixSequences([
            'attribute_families',
            'attribute_family_translations',
            'attribute_family_group_mappings',
        ]);
    }
}
