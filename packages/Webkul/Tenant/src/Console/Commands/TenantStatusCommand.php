<?php

namespace Webkul\Tenant\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Webkul\Tenant\Models\Tenant;

class TenantStatusCommand extends Command
{
    protected $signature = 'tenant:status
        {--tenant= : Show details for a specific tenant (omit for all tenants)}';

    protected $description = 'Display tenant status and health information';

    public function handle(): int
    {
        $tenantId = $this->option('tenant');

        if ($tenantId) {
            return $this->showTenantDetail((int) $tenantId);
        }

        return $this->showAllTenants();
    }

    private function showTenantDetail(int $tenantId): int
    {
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->error("Tenant [{$tenantId}] not found.");

            return self::FAILURE;
        }

        $this->info("=== Tenant: {$tenant->name} ===");
        $this->table(
            ['Property', 'Value'],
            [
                ['ID', $tenant->id],
                ['UUID', $tenant->uuid],
                ['Name', $tenant->name],
                ['Domain', $tenant->domain],
                ['Status', $tenant->status],
                ['Created', $tenant->created_at?->toDateTimeString()],
            ]
        );

        $this->info('');
        $this->info('--- Data Counts ---');
        $counts = $this->getDataCounts($tenantId);
        $this->table(
            ['Entity', 'Count'],
            collect($counts)->map(fn ($v, $k) => [$k, $v])->values()->toArray()
        );

        $this->info('');
        $this->info('--- Provisioning Completeness ---');
        $completeness = $this->checkProvisioningCompleteness($tenantId);
        $this->table(
            ['Check', 'Status'],
            collect($completeness)->map(fn ($v, $k) => [$k, $v ? 'OK' : 'MISSING'])->values()->toArray()
        );

        return self::SUCCESS;
    }

    private function showAllTenants(): int
    {
        $tenants = Tenant::withTrashed()->get();

        if ($tenants->isEmpty()) {
            $this->info('No tenants found.');

            return self::SUCCESS;
        }

        $rows = $tenants->map(function ($tenant) {
            return [
                $tenant->id,
                $tenant->name,
                $tenant->domain,
                $tenant->status,
                $this->safeCount('products', $tenant->id),
                $tenant->created_at?->toDateString(),
            ];
        })->toArray();

        $this->table(
            ['ID', 'Name', 'Domain', 'Status', 'Products', 'Created'],
            $rows
        );

        return self::SUCCESS;
    }

    private function getDataCounts(int $tenantId): array
    {
        return [
            'Products'   => $this->safeCount('products', $tenantId),
            'Categories' => $this->safeCount('categories', $tenantId),
            'Attributes' => $this->safeCount('attributes', $tenantId),
            'Channels'   => $this->safeCount('channels', $tenantId),
            'Users'      => $this->safeCount('admins', $tenantId),
            'Imports'    => $this->safeCount('job_instances', $tenantId),
        ];
    }

    private function checkProvisioningCompleteness(int $tenantId): array
    {
        return [
            'Admin User'      => $this->safeCount('admins', $tenantId) > 0,
            'Root Category'   => $this->safeCount('categories', $tenantId) > 0,
            'Default Channel' => $this->safeCount('channels', $tenantId) > 0,
            'Locale'          => $this->safeCount('locales', $tenantId) > 0,
            'Currency'        => $this->safeCount('currencies', $tenantId) > 0,
            'API Key'         => $this->safeCount('api_keys', $tenantId) > 0,
        ];
    }

    private function safeCount(string $table, int $tenantId): int
    {
        try {
            return DB::table($table)->where('tenant_id', $tenantId)->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
