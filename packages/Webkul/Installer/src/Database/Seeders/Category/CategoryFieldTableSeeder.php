<?php

namespace Webkul\Installer\Database\Seeders\Category;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Helpers\Database\DatabaseSequenceHelper;

/*
 * CategoryField table seeder.
 *
 * Command: php artisan db:seed --class=Webkul\\Installer\\Database\\Seeders\\Category\\CategoryFieldTableSeeder
 */
class CategoryFieldTableSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @param  array  $parameters
     * @return void
     */
    public function run($parameters = [])
    {
        DB::table('category_fields')->delete();

        $now = Carbon::now();

        DB::table('category_fields')->insert([
            [
                'id'               => 1,
                'code'             => 'name',
                'type'             => 'text',
                'validation'       => null,
                'position'         => 0,
                'is_required'      => 1,
                'is_unique'        => 0,
                'status'           => 1,
                'section'          => 'left',
                'value_per_locale' => 1,
                'enable_wysiwyg'   => 0,
                'regex_pattern'    => null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ], [
                'id'               => 2,
                'code'             => 'description',
                'type'             => 'textarea',
                'validation'       => null,
                'position'         => 1,
                'is_required'      => 0,
                'is_unique'        => 0,
                'status'           => 1,
                'section'          => 'left',
                'value_per_locale' => 1,
                'enable_wysiwyg'   => 1,
                'regex_pattern'    => null,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
        ]);

        $defaultLocale = $parameters['default_locale'] ?? config('app.locale');

        $locales = $parameters['allowed_locales'] ?? [$defaultLocale];

        foreach ($locales as $locale) {
            DB::table('category_field_translations')->insert([
                [
                    'category_field_id' => 1,
                    'locale'            => $locale,
                    'name'              => trans('installer::app.seeders.category.category_fields.name', [], $locale),
                ],
                [
                    'category_field_id' => 2,
                    'locale'            => $locale,
                    'name'              => trans('installer::app.seeders.category.category_fields.description', [], $locale),
                ],
            ]);
        }

        DatabaseSequenceHelper::fixSequences(['category_fields', 'category_field_translations']);
    }
}
