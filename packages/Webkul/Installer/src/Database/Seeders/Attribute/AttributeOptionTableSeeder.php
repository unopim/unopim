<?php

namespace Webkul\Installer\Database\Seeders\Attribute;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Helpers\Database\DatabaseSequenceHelper;
use Webkul\Installer\Database\Seeders\Concerns\SeedsMissingRows;

class AttributeOptionTableSeeder extends Seeder
{
    use SeedsMissingRows;

    /**
     * Seed the application's database.
     */
    public function run(array $parameters = []): void
    {
        $defaultLocale = $parameters['default_locale'] ?? config('app.locale');

        $attributeIds = DB::table('attributes')->where('type', 'select')->whereIn('code', ['color', 'size'])->pluck('id', 'code')->all();

        $options = [
            [
                'id'           => 1,
                'code'         => trans('installer::app.seeders.attribute.attribute-options.red', [], $defaultLocale),
                'sort_order'   => 1,
                'attribute_id' => $attributeIds['color'] ?? null,
            ], [
                'id'           => 2,
                'code'         => trans('installer::app.seeders.attribute.attribute-options.green', [], $defaultLocale),
                'sort_order'   => 2,
                'attribute_id' => $attributeIds['color'] ?? null,
            ], [
                'id'           => 3,
                'code'         => trans('installer::app.seeders.attribute.attribute-options.yellow', [], $defaultLocale),
                'sort_order'   => 3,
                'attribute_id' => $attributeIds['color'] ?? null,
            ], [
                'id'           => 4,
                'code'         => trans('installer::app.seeders.attribute.attribute-options.black', [], $defaultLocale),
                'sort_order'   => 4,
                'attribute_id' => $attributeIds['color'] ?? null,
            ], [
                'id'           => 5,
                'code'         => trans('installer::app.seeders.attribute.attribute-options.white', [], $defaultLocale),
                'sort_order'   => 5,
                'attribute_id' => $attributeIds['color'] ?? null,
            ], [
                'id'           => 6,
                'code'         => trans('installer::app.seeders.attribute.attribute-options.s', [], $defaultLocale),
                'sort_order'   => 1,
                'attribute_id' => $attributeIds['size'] ?? null,
            ], [
                'id'           => 7,
                'code'         => trans('installer::app.seeders.attribute.attribute-options.m', [], $defaultLocale),
                'sort_order'   => 2,
                'attribute_id' => $attributeIds['size'] ?? null,
            ], [
                'id'           => 8,
                'code'         => trans('installer::app.seeders.attribute.attribute-options.l', [], $defaultLocale),
                'sort_order'   => 3,
                'attribute_id' => $attributeIds['size'] ?? null,
            ], [
                'id'           => 9,
                'code'         => trans('installer::app.seeders.attribute.attribute-options.xl', [], $defaultLocale),
                'sort_order'   => 4,
                'attribute_id' => $attributeIds['size'] ?? null,
            ],
        ];

        $insertedIds = $this->insertMissingRows(
            'attribute_options',
            array_values(array_filter($options, fn (array $option): bool => $option['attribute_id'] !== null)),
            ['attribute_id', 'code'],
        );

        $locales = $parameters['allowed_locales'] ?? [$defaultLocale];

        foreach ($locales as $locale) {
            DB::table('attribute_option_translations')->insert($this->rowsOfInserted([
                [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.red', [], $locale),
                    'attribute_option_id' => 1,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.green', [], $locale),
                    'attribute_option_id' => 2,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.yellow', [], $locale),
                    'attribute_option_id' => 3,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.black', [], $locale),
                    'attribute_option_id' => 4,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.white', [], $locale),
                    'attribute_option_id' => 5,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.s', [], $locale),
                    'attribute_option_id' => 6,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.m', [], $locale),
                    'attribute_option_id' => 7,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.l', [], $locale),
                    'attribute_option_id' => 8,
                ], [
                    'locale'              => $locale,
                    'label'               => trans('installer::app.seeders.attribute.attribute-options.xl', [], $locale),
                    'attribute_option_id' => 9,
                ],
            ], 'attribute_option_id', $insertedIds));
        }

        DatabaseSequenceHelper::fixSequences(['attribute_options', 'attribute_option_translations']);
    }
}
