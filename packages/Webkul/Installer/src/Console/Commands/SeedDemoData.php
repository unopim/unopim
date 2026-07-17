<?php

namespace Webkul\Installer\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
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
#[Description('Seed demo extras, categories, and sample products into an installed UnoPim database.')]
#[Signature('unopim:install:demo-data
        { --force : Re-seed even when demo data is already present. }')]
class SeedDemoData extends Command
{
    /**
     * Execute the command.
     */
    public function handle(DemoDataInstaller $installer): int
    {
        $force = (bool) $this->option('force');

        if (! $force && $installer->isAlreadySeeded()) {
            $this->info('Demo data is already seeded — nothing to do. Re-run with --force to re-seed.');

            return self::SUCCESS;
        }

        $this->components->warn('This deletes existing products, categories, channels, attributes, families and core config, then loads demo data.');

        if ($this->getLaravel()->environment('production')) {
            $this->components->alert('Application In Production');

            if (! $this->components->confirm('Are you sure you want to run this command?', false)) {
                $this->components->warn('Command cancelled.');

                return self::FAILURE;
            }
        }

        $result = $installer->seed(
            fn (string $message) => $this->warn('Step: '.$message),
            $force,
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
