<?php

declare(strict_types=1);

namespace Webkul\Installer\Console\Commands;

use Illuminate\Console\Command;
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
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'unopim:install:demo-data
        { --force : Re-seed even when demo data is already present. }';

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
        $result = $installer->seed(
            fn (string $message) => $this->warn('Step: '.$message),
            (bool) $this->option('force'),
        );

        if (! ($result['success'] ?? false)) {
            $this->error("Failed to seed sample data: {$result['error']}");

            return self::FAILURE;
        }

        if ($result['skipped'] ?? false) {
            $this->info('Demo data is already seeded — nothing to do. Re-run with --force to re-seed.');

            return self::SUCCESS;
        }

        $this->info('Sample products seeded successfully.');

        return self::SUCCESS;
    }
}
