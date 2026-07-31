<?php

namespace Webkul\Installer\Database\Seeders\User;

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
        $this->call(RolesTableSeeder::class, false, ['parameters' => $parameters]);
        $this->call(AdminsTableSeeder::class, false, ['parameters' => $parameters]);
    }
}
