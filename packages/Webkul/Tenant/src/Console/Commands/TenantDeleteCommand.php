<?php

namespace Webkul\Tenant\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Tenant\Models\Tenant;
use Webkul\Tenant\Services\DeletionReport;
use Webkul\Tenant\Services\TenantPurger;

class TenantDeleteCommand extends Command
{
    protected $signature = 'tenant:delete
        {--tenant= : The tenant ID to delete}
        {--confirm : Skip confirmation prompt}
        {--report-file= : Optional path to save the deletion report JSON}';

    protected $description = 'Permanently delete a tenant and purge all data';

    public function handle(TenantPurger $purger): int
    {
        $tenantId = $this->option('tenant');

        if (! $tenantId) {
            $this->error('The --tenant option is required.');

            return self::FAILURE;
        }

        $tenant = Tenant::find((int) $tenantId);

        if (! $tenant) {
            $this->error("Tenant [{$tenantId}] not found.");

            return self::FAILURE;
        }

        if (! $tenant->canTransitionTo(Tenant::STATUS_DELETING)) {
            $this->error("Cannot delete tenant in '{$tenant->status}' state. Must be 'active' or 'suspended'.");

            return self::FAILURE;
        }

        if (! $this->option('confirm')) {
            $tables = $purger->findTenantScopedTables();
            $this->warn("You are about to permanently delete tenant: {$tenant->name} (ID: {$tenant->id})");
            $this->warn('This will purge data from '.count($tables).' tables.');

            if (! $this->confirm('Do you want to proceed?')) {
                $this->info('Deletion cancelled.');

                return self::SUCCESS;
            }
        }

        $tenant->transitionTo(Tenant::STATUS_DELETING);
        $this->info("Deleting tenant: {$tenant->name} (ID: {$tenant->id})...");

        $purgeResult = $purger->purge($tenant);
        $verification = $purger->verify($tenant->id);

        $report = new DeletionReport($purgeResult, $verification);

        foreach ($report->toConsoleOutput() as $line) {
            $this->line($line);
        }

        $reportFile = $this->option('report-file');
        if ($reportFile) {
            file_put_contents($reportFile, $report->toJson());
            $this->info("Report saved to: {$reportFile}");
        }

        $tenant->transitionTo(Tenant::STATUS_DELETED);
        $tenant->delete();

        if ($report->isComplete()) {
            $this->info('Tenant deleted successfully. All data purged.');
        } else {
            $this->warn('Tenant deleted but some residual data was detected. Check the report.');
        }

        return self::SUCCESS;
    }
}
