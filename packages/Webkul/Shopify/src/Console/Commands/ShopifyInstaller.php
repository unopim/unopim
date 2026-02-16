<?php

namespace Webkul\Shopify\Console\Commands;

use Illuminate\Console\Command;

class ShopifyInstaller extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopify-package:install';

    protected $description = 'Install the Shopify package';

    public function handle()
    {
        $this->info('Installing Unopim Shopify connector...');

        if ($this->confirm('Would you like to run the migrations now?', true)) {
            $this->call('migrate');
            $this->call('db:seed', ['--class' => 'Webkul\Shopify\Database\Seeders\ShopifySettingConfigurationValuesSeeder']);
        }

        $this->call('vendor:publish', [
            '--tag' => 'shopify-config',
        ]);

        $this->info('Unopim Shopify connector installed successfully!');
    }
}
