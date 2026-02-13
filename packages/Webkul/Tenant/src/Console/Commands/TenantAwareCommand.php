<?php

namespace Webkul\Tenant\Console\Commands;

use Closure;
use Illuminate\Console\Command;
use Webkul\Tenant\Models\Tenant;

abstract class TenantAwareCommand extends Command
{
    /**
     * Execute the console command with tenant context.
     *
     * Subclasses implement handleForTenant() instead of handle().
     */
    public function handle(): int
    {
        $tenantId = $this->option('tenant');

        if (is_null($tenantId) && app()->environment('production')) {
            $this->error('The --tenant option is required in production.');

            return self::FAILURE;
        }

        if (! is_null($tenantId)) {
            $tenant = Tenant::find((int) $tenantId);

            if (! $tenant) {
                $this->error("Tenant [{$tenantId}] not found.");

                return self::FAILURE;
            }

            if ($tenant->status !== Tenant::STATUS_ACTIVE) {
                $this->error("Tenant [{$tenantId}] is not active (status: {$tenant->status}).");

                return self::FAILURE;
            }

            core()->setCurrentTenantId($tenant->id);
            $this->info("Running in tenant context: {$tenant->name} (ID: {$tenant->id})");

            return $this->handleForTenant($tenant);
        }

        // Non-production without --tenant: run in platform context
        $this->warn('Running without tenant context (platform mode).');

        return $this->handleForTenant(null);
    }

    /**
     * Execute the command logic within the resolved tenant context.
     *
     * Subclasses MUST implement this instead of handle().
     */
    abstract protected function handleForTenant(?Tenant $tenant): int;

    /**
     * Iterate over all active tenants and run a callback for each.
     *
     * Useful for batch operations like reindexing, cleanup, etc.
     */
    protected function forAllTenants(Closure $callback): void
    {
        Tenant::active()->each(function (Tenant $tenant) use ($callback) {
            core()->setCurrentTenantId($tenant->id);
            $this->info("Processing tenant: {$tenant->name} (ID: {$tenant->id})");

            $callback($tenant);

            core()->setCurrentTenantId(null);
        });
    }

    /**
     * Get the console command options — merges --tenant with subclass options.
     */
    protected function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            ['tenant', null, \Symfony\Component\Console\Input\InputOption::VALUE_OPTIONAL, 'The tenant ID to run the command for'],
        ]);
    }
}
