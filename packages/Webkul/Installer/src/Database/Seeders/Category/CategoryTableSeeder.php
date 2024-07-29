<?php

namespace Webkul\Installer\Database\Seeders\Category;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/*
 * Category table seeder.
 *
 * Command: php artisan db:seed --class=Webkul\\Category\\Database\\Seeders\\CategoryTableSeeder
 */
class CategoryTableSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @param  array  $parameters
     * @return void
     */
    public function run($parameters = [])
    {
        DB::table('categories')->delete();

        $now = Carbon::now();

        DB::table('categories')->insert([
            [
                'id'         => '1',
                '_lft'       => '1',
                '_rgt'       => '14',
                'code'       => 'root',
                'parent_id'  => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}
