<?php

declare(strict_types=1);

namespace Webkul\Installer\Database\Seeders\Attribute;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(array $parameters = []): void
    {
        $this->call(AttributeGroupTableSeeder::class, false, ['parameters' => $parameters]);
        $this->call(AttributeTableSeeder::class, false, ['parameters' => $parameters]);
        $this->call(AttributeOptionTableSeeder::class, false, ['parameters' => $parameters]);
        $this->call(AttributeFamilyTableSeeder::class, false, ['parameters' => $parameters]);
    }
}
