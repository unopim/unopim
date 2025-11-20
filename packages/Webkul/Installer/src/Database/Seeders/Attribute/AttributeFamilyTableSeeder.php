<?php

namespace Webkul\Installer\Database\Seeders\Attribute;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Helpers\Database\DatabaseSequenceHelper;

class AttributeFamilyTableSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @param  array  $parameters
     * @return void
     */
    public function run($parameters = [])
    {
        DB::table('attribute_families')->delete();

        $defaultLocale = $parameters['default_locale'] ?? config('app.locale');

        DB::table('attribute_families')->insert([
            [
                'id'              => 1,
                'code'            => 'default',
                'status'          => 0,
            ],
        ]);

        $locales = $parameters['allowed_locales'] ?? [$defaultLocale];

        foreach ($locales as $locale) {
            DB::table('attribute_family_translations')->insert([
                [
                    'locale'              => $locale,
                    'name'                => trans('installer::app.seeders.attribute.attribute-families.default', [], $locale),
                    'attribute_family_id' => 1,
                ],
            ]);
        }

        DB::table('attribute_family_group_mappings')->insert([
            [
                'id'                  => 1,
                'attribute_family_id' => 1,
                'attribute_group_id'  => 1,
                'position'            => 1,
            ], [
                'id'                  => 2,
                'attribute_family_id' => 1,
                'attribute_group_id'  => 2,
                'position'            => 2,
            ], [
                'id'                  => 3,
                'attribute_family_id' => 1,
                'attribute_group_id'  => 3,
                'position'            => 3,
            ], [
                'id'                  => 4,
                'attribute_family_id' => 1,
                'attribute_group_id'  => 4,
                'position'            => 4,
            ], [
                'id'                  => 5,
                'attribute_family_id' => 1,
                'attribute_group_id'  => 5,
                'position'            => 5,
            ],
        ]);

        DB::table('attribute_group_mappings')->insert([
            /**
             * General Group Attributes
             */
            [
                'attribute_id'              => 1,
                'attribute_family_group_id' => 1,
                'position'                  => 1,
            ], [
                'attribute_id'              => 27,
                'attribute_family_group_id' => 1,
                'position'                  => 2,
            ], [
                'attribute_id'              => 2,
                'attribute_family_group_id' => 1,
                'position'                  => 3,
            ], [
                'attribute_id'              => 3,
                'attribute_family_group_id' => 1,
                'position'                  => 4,
            ], [
                'attribute_id'              => 4,
                'attribute_family_group_id' => 1,
                'position'                  => 5,
            ], [
                'attribute_id'              => 23,
                'attribute_family_group_id' => 1,
                'position'                  => 6,
            ], [
                'attribute_id'              => 24,
                'attribute_family_group_id' => 1,
                'position'                  => 7,
            ], [
                'attribute_id'              => 25,
                'attribute_family_group_id' => 1,
                'position'                  => 8,
            ],

            /**
             * Description Group Attributes
             */
            [
                'attribute_id'              => 9,
                'attribute_family_group_id' => 2,
                'position'                  => 1,
            ], [
                'attribute_id'              => 10,
                'attribute_family_group_id' => 2,
                'position'                  => 2,
            ],

            /**
             * Meta Description Group Attributes
             */
            [
                'attribute_id'              => 11,
                'attribute_family_group_id' => 4,
                'position'                  => 1,
            ], [
                'attribute_id'              => 12,
                'attribute_family_group_id' => 4,
                'position'                  => 2,
            ],

            /**
             * Price Group Attributes
             */
            [
                'attribute_id'              => 16,
                'attribute_family_group_id' => 3,
                'position'                  => 1,
            ], [
                'attribute_id'              => 17,
                'attribute_family_group_id' => 3,
                'position'                  => 2,
            ], [
                'attribute_id'              => 18,
                'attribute_family_group_id' => 3,
                'position'                  => 3,
            ],

            /**
             * Technical Group Attributes
             */
            [
                'attribute_id'              => 8,
                'attribute_family_group_id' => 5,
                'position'                  => 5,
            ],
        ]);

        DatabaseSequenceHelper::fixSequences([
            'attribute_families',
            'attribute_family_translations',
            'attribute_family_group_mappings',
        ]);
    }
}
