<?php

declare(strict_types=1);

namespace Webkul\Installer\Database\Seeders\Core;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(array $parameters = []): void
    {
        $this->call(LocalesTableSeeder::class, false, ['parameters' => $parameters]);
        $this->call(CurrencyTableSeeder::class, false, ['parameters' => $parameters]);
        $this->call(ChannelTableSeeder::class, false, ['parameters' => $parameters]);
    }
}
