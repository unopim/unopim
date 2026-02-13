<?php

namespace Webkul\Tenant\Console\Commands;

use Illuminate\Console\Command;
use Webkul\Tenant\Models\Tenant;

class TenantSuspendCommand extends Command
{
    protected $signature = 'tenant:suspend
        {--tenant= : The tenant ID to suspend}
        {--reason= : Reason for suspension}';

    protected $description = 'Suspend a tenant, blocking all access';

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

        if ($tenant->status === Tenant::STATUS_SUSPENDED) {
            $this->warn("Tenant {$tenant->id} ({$tenant->name}) is already suspended.");

            return self::SUCCESS;
        }

        if (! $tenant->canTransitionTo(Tenant::STATUS_SUSPENDED)) {
            $this->error("Cannot suspend tenant in '{$tenant->status}' state.");

            return self::FAILURE;
        }

        $reason = $this->option('reason');
        if ($reason) {
            $settings = $tenant->settings ?? [];
            $settings['suspension_reason'] = $reason;
            $tenant->settings = $settings;
        }

        $tenant->transitionTo(Tenant::STATUS_SUSPENDED);

        $this->info("Tenant {$tenant->id} ({$tenant->name}) has been suspended.");

        if ($reason) {
            $this->info("Reason: {$reason}");
        }

        return self::SUCCESS;
    }
}
