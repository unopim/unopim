<?php

namespace Webkul\Core\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Webkul\Core\Providers\CoreServiceProvider;
use Webkul\Product\Providers\ProductServiceProvider;

#[Description('Publish the available assets')]
#[Signature('unopim:publish { --force : Overwrite any existing files }')]
class UnoPimPublish extends Command
{
    /**
     * List of providers.
     *
     * @var array
     */
    protected $providers = [
        /**
         * UnoPim providers.
         */
        [
            'name'     => 'Core',
            'provider' => CoreServiceProvider::class,
        ],
        [
            'name'     => 'Product',
            'provider' => ProductServiceProvider::class,
        ],
    ];

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->publishAllPackages();
    }

    /**
     * Publish all packages.
     */
    public function publishAllPackages(): void
    {
        collect($this->providers)->each(function (array $provider): void {
            $this->publishPackage($provider);
        });
    }

    /**
     * Publish package.
     */
    public function publishPackage(array $provider): void
    {
        $this->line('');
        $this->line('-----------------------------------------');
        $this->info('Publishing '.$provider['name']);
        $this->line('-----------------------------------------');

        $this->call('vendor:publish', [
            '--provider' => $provider['provider'],
            '--force'    => $this->option('force'),
        ]);
    }
}
