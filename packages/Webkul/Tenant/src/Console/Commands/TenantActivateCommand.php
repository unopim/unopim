<?php

namespace Webkul\Tenant\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Tenant\Models\Tenant;

class TenantActivateCommand extends Command
{
    protected $signature = 'tenant:activate
        {--tenant= : The tenant ID to reactivate}';

    protected $description = 'Reactivate a suspended tenant';

    public function handle(): int
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

        if ($tenant->status === Tenant::STATUS_ACTIVE) {
            $this->warn("Tenant {$tenant->id} ({$tenant->name}) is already active.");

            return self::SUCCESS;
        }

        if (! $tenant->canTransitionTo(Tenant::STATUS_ACTIVE)) {
            $this->error("Cannot activate tenant in '{$tenant->status}' state.");

            return self::FAILURE;
        }

        $settings = $tenant->settings ?? [];
        unset($settings['suspension_reason']);
        $tenant->settings = $settings;

        $tenant->transitionTo(Tenant::STATUS_ACTIVE);

        $this->info("Tenant {$tenant->id} ({$tenant->name}) has been reactivated.");

        return self::SUCCESS;
    }
}
