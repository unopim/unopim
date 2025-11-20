<?php

namespace Webkul\Installer\Database\Seeders\Attribute;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Helpers\Database\DatabaseSequenceHelper;

class AttributeTableSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @param  array  $parameters
     * @return void
     */
    public function run($parameters = [])
    {
        DB::table('attributes')->delete();

        DB::table('attribute_translations')->delete();

        $now = Carbon::now();

        $defaultLocale = $parameters['default_locale'] ?? config('app.locale');

        DB::table('attributes')->insert([
            [
                'id'                  => 1,
                'code'                => 'sku',
                'type'                => 'text',
                'validation'          => null,
                'position'            => 1,
                'is_required'         => 1,
                'is_unique'           => 1,
                'value_per_locale'    => 0,
                'value_per_channel'   => 0,
                'is_filterable'       => 1,
                'default_value'       => null,
                'enable_wysiwyg'      => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ], [
                'id'                  => 2,
                'code'                => 'name',

                'type'                => 'text',
                'validation'          => null,
                'position'            => 3,
                'is_required'         => 1,
                'is_unique'           => 0,
                'value_per_locale'    => 1,
                'value_per_channel'   => 1,
                'is_filterable'       => 1,
                'default_value'       => null,

                'enable_wysiwyg'      => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ], [
                'id'                  => 3,
                'code'                => 'url_key',

                'type'                => 'text',
                'validation'          => null,
                'position'            => 4,
                'is_required'         => 1,
                'is_unique'           => 1,
                'value_per_locale'    => 0,
                'value_per_channel'   => 0,
                'is_filterable'       => 0,
                'default_value'       => null,

                'enable_wysiwyg'      => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ], [
                'id'                  => 4,
                'code'                => 'tax_category_id',

                'type'                => 'select',
                'validation'          => null,
                'position'            => 5,
                'is_required'         => 0,
                'is_unique'           => 0,
                'value_per_locale'    => 0,
                'value_per_channel'   => 1,
                'is_filterable'       => 0,
                'default_value'       => null,

                'enable_wysiwyg'      => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ], [
                'id'                  => 8,
                'code'                => 'image',

                'type'                => 'image',
                'validation'          => null,
                'position'            => 10,
                'is_required'         => 0,
                'is_unique'           => 0,
                'value_per_locale'    => 0,
                'value_per_channel'   => 0,
                'is_filterable'       => 0,
                'default_value'       => null,

                'enable_wysiwyg'      => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ], [
                'id'                  => 9,
                'code'                => 'short_description',

                'type'                => 'textarea',
                'validation'          => null,
                'position'            => 11,
                'is_required'         => 1,
                'is_unique'           => 0,
                'value_per_locale'    => 1,
                'value_per_channel'   => 1,
                'is_filterable'       => 0,
                'default_value'       => null,

                'enable_wysiwyg'      => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ], [
                'id'                  => 10,
                'code'                => 'description',

                'type'                => 'textarea',
                'validation'          => null,
                'position'            => 12,
                'is_required'         => 1,
                'is_unique'           => 0,
                'value_per_locale'    => 1,
                'value_per_channel'   => 1,
                'is_filterable'       => 0,
                'default_value'       => null,

                'enable_wysiwyg'      => 1,
                'created_at'          => $now,
                'updated_at'          => $now,
            ], [
                'id'                  => 11,
                'code'                => 'price',

                'type'                => 'price',
                'validation'          => 'decimal',
                'position'            => 13,
                'is_required'         => 1,
                'is_unique'           => 0,
                'value_per_locale'    => 1,
                'value_per_channel'   => 1,
                'is_filterable'       => 1,
                'default_value'       => null,

                'enable_wysiwyg'      => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ], [
                'id'                  => 12,
                'code'                => 'cost',

                'type'                => 'price',
                'validation'          => 'decimal',
                'position'            => 14,
                'is_required'         => 0,
                'is_unique'           => 0,
                'value_per_locale'    => 0,
                'value_per_channel'   => 1,
                'is_filterable'       => 0,
                'default_value'       => null,

                'enable_wysiwyg'      => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ], [
                'id'                  => 16,
                'code'                => 'meta_title',

                'type'                => 'textarea',
                'validation'          => null,
                'position'            => 18,
                'is_required'         => 0,
                'is_unique'           => 0,
                'value_per_locale'    => 1,
                'value_per_channel'   => 1,
                'is_filterable'       => 0,
                'default_value'       => null,

                'enable_wysiwyg'      => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ], [
                'id'                  => 17,
                'code'                => 'meta_keywords',

                'type'                => 'textarea',
                'validation'          => null,
                'position'            => 20,
                'is_required'         => 0,
                'is_unique'           => 0,
                'value_per_locale'    => 1,
                'value_per_channel'   => 1,
                'is_filterable'       => 0,
                'default_value'       => null,

                'enable_wysiwyg'      => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ], [
                'id'                  => 18,
                'code'                => 'meta_description',

                'type'                => 'textarea',
                'validation'          => null,
                'position'            => 21,
                'is_required'         => 0,
                'is_unique'           => 0,
                'value_per_locale'    => 1,
                'value_per_channel'   => 1,
                'is_filterable'       => 0,
                'default_value'       => null,

                'enable_wysiwyg'      => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ], [
                'id'                  => 19,
                'code'                => 'length',

                'type'                => 'text',
                'validation'          => 'decimal',
                'position'            => 22,
                'is_required'         => 0,
                'is_unique'           => 0,
                'value_per_locale'    => 0,
                'value_per_channel'   => 0,
                'is_filterable'       => 0,
                'default_value'       => null,

                'enable_wysiwyg'      => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ], [
                'id'                  => 20,
                'code'                => 'width',

                'type'                => 'text',
                'validation'          => 'decimal',
                'position'            => 23,
                'is_required'         => 0,
                'is_unique'           => 0,
                'value_per_locale'    => 0,
                'value_per_channel'   => 0,
                'is_filterable'       => 0,
                'default_value'       => null,

                'enable_wysiwyg'      => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ], [
                'id'                  => 21,
                'code'                => 'height',

                'type'                => 'text',
                'validation'          => 'decimal',
                'position'            => 24,
                'is_required'         => 0,
                'is_unique'           => 0,
                'value_per_locale'    => 0,
                'value_per_channel'   => 0,
                'is_filterable'       => 0,
                'default_value'       => null,

                'enable_wysiwyg'      => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ], [
                'id'                  => 22,
                'code'                => 'weight',

                'type'                => 'text',
                'validation'          => 'decimal',
                'position'            => 25,
                'is_required'         => 1,
                'is_unique'           => 0,
                'value_per_locale'    => 0,
                'value_per_channel'   => 0,
                'is_filterable'       => 0,
                'default_value'       => null,

                'enable_wysiwyg'      => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ], [
                'id'                  => 23,
                'code'                => 'color',

                'type'                => 'select',
                'validation'          => null,
                'position'            => 26,
                'is_required'         => 0,
                'is_unique'           => 0,
                'value_per_locale'    => 0,
                'value_per_channel'   => 0,
                'is_filterable'       => 0,
                'default_value'       => null,

                'enable_wysiwyg'      => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ], [
                'id'                  => 24,
                'code'                => 'size',

                'type'                => 'select',
                'validation'          => null,
                'position'            => 27,
                'is_required'         => 0,
                'is_unique'           => 0,
                'value_per_locale'    => 0,
                'value_per_channel'   => 0,
                'is_filterable'       => 0,
                'default_value'       => null,

                'enable_wysiwyg'      => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ], [
                'id'                  => 25,
                'code'                => 'brand',

                'type'                => 'select',
                'validation'          => null,
                'position'            => 28,
                'is_required'         => 0,
                'is_unique'           => 0,
                'value_per_locale'    => 0,
                'value_per_channel'   => 0,
                'is_filterable'       => 0,
                'default_value'       => null,

                'enable_wysiwyg'      => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ], [
                'id'                  => 27,
                'code'                => 'product_number',

                'type'                => 'text',
                'validation'          => null,
                'position'            => 2,
                'is_required'         => 0,
                'is_unique'           => 1,
                'value_per_locale'    => 0,
                'value_per_channel'   => 0,
                'is_filterable'       => 0,
                'default_value'       => null,

                'enable_wysiwyg'      => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ], [
                'id'                  => 28,
                'code'                => 'manage_stock',

                'type'                => 'boolean',
                'validation'          => null,
                'position'            => 1,
                'is_required'         => 0,
                'is_unique'           => 0,
                'value_per_locale'    => 0,
                'value_per_channel'   => 1,
                'is_filterable'       => 0,
                'default_value'       => 1,

                'enable_wysiwyg'      => 0,
                'created_at'          => $now,
                'updated_at'          => $now,
            ],
        ]);

        $locales = $parameters['allowed_locales'] ?? [$defaultLocale];

        foreach ($locales as $locale) {
            DB::table('attribute_translations')->insert([
                [
                    'locale'       => $locale,
                    'name'         => trans('installer::app.seeders.attribute.attributes.sku', [], $locale),
                    'attribute_id' => 1,
                ], [
                    'locale'       => $locale,
                    'name'         => trans('installer::app.seeders.attribute.attributes.name', [], $locale),
                    'attribute_id' => 2,
                ], [
                    'locale'       => $locale,
                    'name'         => trans('installer::app.seeders.attribute.attributes.url-key', [], $locale),
                    'attribute_id' => 3,
                ], [
                    'locale'       => $locale,
                    'name'         => trans('installer::app.seeders.attribute.attributes.tax-category', [], $locale),
                    'attribute_id' => 4,
                ], [
                    'locale'       => $locale,
                    'name'         => trans('installer::app.seeders.attribute.attributes.image', [], $locale),
                    'attribute_id' => 8,
                ], [
                    'locale'       => $locale,
                    'name'         => trans('installer::app.seeders.attribute.attributes.short-description', [], $locale),
                    'attribute_id' => 9,
                ], [
                    'locale'       => $locale,
                    'name'         => trans('installer::app.seeders.attribute.attributes.description', [], $locale),
                    'attribute_id' => 10,
                ], [
                    'locale'       => $locale,
                    'name'         => trans('installer::app.seeders.attribute.attributes.price', [], $locale),
                    'attribute_id' => 11,
                ], [
                    'locale'       => $locale,
                    'name'         => trans('installer::app.seeders.attribute.attributes.cost', [], $locale),
                    'attribute_id' => 12,
                ], [
                    'locale'       => $locale,
                    'name'         => trans('installer::app.seeders.attribute.attributes.meta-title', [], $locale),
                    'attribute_id' => 16,
                ], [
                    'locale'       => $locale,
                    'name'         => trans('installer::app.seeders.attribute.attributes.meta-keywords', [], $locale),
                    'attribute_id' => 17,
                ], [
                    'locale'       => $locale,
                    'name'         => trans('installer::app.seeders.attribute.attributes.meta-description', [], $locale),
                    'attribute_id' => 18,
                ], [
                    'locale'       => $locale,
                    'name'         => trans('installer::app.seeders.attribute.attributes.length', [], $locale),
                    'attribute_id' => 19,
                ], [
                    'locale'       => $locale,
                    'name'         => trans('installer::app.seeders.attribute.attributes.width', [], $locale),
                    'attribute_id' => 20,
                ], [
                    'locale'       => $locale,
                    'name'         => trans('installer::app.seeders.attribute.attributes.height', [], $locale),
                    'attribute_id' => 21,
                ], [
                    'locale'       => $locale,
                    'name'         => trans('installer::app.seeders.attribute.attributes.weight', [], $locale),
                    'attribute_id' => 22,
                ], [
                    'locale'       => $locale,
                    'name'         => trans('installer::app.seeders.attribute.attributes.color', [], $locale),
                    'attribute_id' => 23,
                ], [
                    'locale'       => $locale,
                    'name'         => trans('installer::app.seeders.attribute.attributes.size', [], $locale),
                    'attribute_id' => 24,
                ], [
                    'locale'       => $locale,
                    'name'         => trans('installer::app.seeders.attribute.attributes.brand', [], $locale),
                    'attribute_id' => 25,
                ], [
                    'locale'       => $locale,
                    'name'         => trans('installer::app.seeders.attribute.attributes.product-number', [], $locale),
                    'attribute_id' => 27,
                ], [
                    'locale'       => $locale,
                    'name'         => trans('installer::app.seeders.attribute.attributes.manage-stock', [], $locale),
                    'attribute_id' => 28,
                ],
            ]);
        }

        DatabaseSequenceHelper::fixSequences(['attributes', 'attribute_translations']);
    }
}
