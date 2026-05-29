<?php

declare(strict_types=1);

namespace Webkul\Installer\Database\Seeders\Category;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(array $parameters = []): void
    {
        $this->call(CategoryTableSeeder::class, false, ['parameters' => $parameters]);
        $this->call(CategoryFieldTableSeeder::class, false, ['parameters' => $parameters]);
    }
}
