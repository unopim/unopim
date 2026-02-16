<?php

namespace Webkul\Tenant\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Tenant\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;

class TestTenantCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:test:cleanup
                           {--force : Force cleanup without confirmation}
                           {--days=30 : Remove tenants created before X days}
                           {--status=active : Remove tenants with specific status}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up test tenant data and databases';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle(): int
    {
        if (!$this->option('force')) {
            if (!$this->confirm('Are you sure you want to clean up test tenant data? This action cannot be undone.')) {
                $this->info('Cleanup cancelled.');
                return 0;
            }
        }

        $days = (int) $this->option('days');
        $status = $this->option('status');

        $cutoffDate = now()->subDays($days);
        $tenants = Tenant::where('created_at', '<=', $cutoffDate)
            ->where('status', $status)
            ->get();

        if ($tenants->isEmpty()) {
            $this->info('No test tenants found matching the criteria.');
            return 0;
        }

        $this->info("Found {$tenants->count()} tenant(s) to clean up:");
        $tenants->each(fn($tenant) =>
            $this->line("- {$tenant->name} (ID: {$tenant->id}, Status: {$tenant->status})")
        );

        if (!$this->option('force')) {
            if (!$this->confirm('Proceed with cleanup?')) {
                $this->info('Cleanup cancelled.');
                return 0;
            }
        }

        $this->line('');
        $this->info('Starting cleanup...');

        $cleanedCount = 0;
        $errors = [];

        foreach ($tenants as $tenant) {
            try {
                $this->cleanTenantData($tenant);
                $cleanedCount++;
                $this->info("✓ Cleaned up tenant: {$tenant->name}");
            } catch (\Exception $e) {
                $errors[] = "✗ Failed to cleanup {$tenant->name}: {$e->getMessage()}";
                $this->error("Failed to cleanup {$tenant->name}: {$e->getMessage()}");
            }
        }

        $this->line('');
        $this->info("Cleanup complete: {$cleanedCount} tenant(s) cleaned.");

        if (!empty($errors)) {
            $this->warn('Some errors occurred during cleanup:');
            foreach ($errors as $error) {
                $this->line($error);
            }
        }

        return 0;
    }

    /**
     * Clean up tenant data.
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function cleanTenantData(Tenant $tenant): void
    {
        // Clean up tenant database
        $this->cleanTenantDatabase($tenant);

        // Clean up tenant storage
        $this->cleanTenantStorage($tenant);

        // Delete the tenant record
        $tenant->delete();
    }

    /**
     * Clean up tenant database.
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function cleanTenantDatabase(Tenant $tenant): void
    {
        $connectionName = "tenant_{$tenant->id}";
        $databaseName = config("database.connections.{$connectionName}.database");

        if (!config("database.connections.{$connectionName}")) {
            return;
        }

        try {
            // Drop database
            DB::connection($connectionName)->statement("DROP DATABASE IF EXISTS `{$databaseName}`");

            // Remove connection config
            Config::set("database.connections.{$connectionName}", null);

            $this->info("  - Database {$databaseName} dropped");
        } catch (\Exception $e) {
            $this->error("  - Failed to drop database {$databaseName}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Clean up tenant storage.
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function cleanTenantStorage(Tenant $tenant): void
    {
        $diskName = "tenant_{$tenant->id}";
        $storagePath = storage_path("app/tenant/{$tenant->id}");

        if (file_exists($storagePath)) {
            try {
                // Remove all files
                array_map('unlink', glob("$storagePath/*.*"));

                // Remove directory
                rmdir($storagePath);

                $this->info("  - Storage for tenant {$tenant->id} cleaned");
            } catch (\Exception $e) {
                $this->error("  - Failed to cleanup storage for tenant {$tenant->id}: {$e->getMessage()}");
            }
        }

        // Remove disk config if exists
        if (config("filesystems.disks.{$diskName}")) {
            Config::set("filesystems.disks.{$diskName}", null);
        }
    }
}