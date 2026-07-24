<?php

namespace Webkul\ProductPassport\Console;

use Illuminate\Console\Command;
use Webkul\ProductPassport\Database\Seeders\DppAttributeSeeder;

class InstallPassportAttributesCommand extends Command
{
    protected $signature = 'unopim:passport:install-attributes';

    protected $description = 'Seed the dpp attribute group and its attributes, idempotently.';

    public function handle(DppAttributeSeeder $seeder): int
    {
        $seeder->run();

        $this->info(trans('passport::app.console.install-attributes.success'));

        return self::SUCCESS;
    }
}
