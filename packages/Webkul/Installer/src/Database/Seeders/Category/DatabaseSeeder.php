<?php

namespace Webkul\Installer\Database\Seeders\Category;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @param  array  $parameters
     */
    public function run($parameters = []): void
    {
        $this->call(CategoryTableSeeder::class, false, ['parameters' => $parameters]);
        $this->call(CategoryFieldTableSeeder::class, false, ['parameters' => $parameters]);
    }
}
