<?php

declare(strict_types=1);

namespace Webkul\Installer\Database\Seeders\User;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(array $parameters = []): void
    {
        $this->call(RolesTableSeeder::class, false, ['parameters' => $parameters]);
        $this->call(AdminsTableSeeder::class, false, ['parameters' => $parameters]);
    }
}
