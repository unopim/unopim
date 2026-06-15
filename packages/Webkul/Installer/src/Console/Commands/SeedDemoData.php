<?php

namespace Webkul\Installer\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Webkul\Installer\Helpers\DemoDataInstaller;

/**
 * Seeds demo extras, demo categories, and sample products.
 *
 * Exists so non-interactive callers (docker-compose entrypoint scripts,
 * CI smoke tests) can opt into the same demo data that the CLI
 * installer's `--with-demo-data` flag and the UI installer's
 * "sample products?" checkbox produce.
 */
class SeedDemoData extends Command
{
    use ConfirmableTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unopim:install:demo-data
        { --force : Skip confirmation and re-seed even when demo data is already present (use for CI / Docker). }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed demo extras, categories, and sample products into an installed UnoPim database.';

    /**
     * Execute the command.
     */
    public function handle(DemoDataInstaller $installer): int
    {
        if (! $this->getLaravel()->environment('production')) {
            $this->components->warn('Your existing data will be removed and replaced with demo data.');
        }

        if (! $this->confirmToProceed()) {
            return self::FAILURE;
        }

        $result = $installer->seed(
            fn (string $message) => $this->warn('Step: '.$message),
            true,
        );

        if (! ($result['success'] ?? false)) {
            $this->error("Failed to seed sample data: {$result['error']}");

            return self::FAILURE;
        }

        $this->info('Sample products seeded successfully.');

        return self::SUCCESS;
    }
}
