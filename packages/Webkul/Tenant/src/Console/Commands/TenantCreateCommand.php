<?php

namespace Webkul\Tenant\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Webkul\Tenant\Models\Tenant;
use Webkul\Tenant\Services\TenantSeeder;

class TenantCreateCommand extends Command
{
    protected $signature = 'tenant:create
        {--name= : The tenant name}
        {--domain= : The tenant subdomain (must be unique)}
        {--email= : Admin user email address}
        {--locale=en_US : Default locale code}
        {--currency=USD : Default currency code}';

    protected $description = 'Provision a new tenant with all baseline data';

    public function handle(TenantSeeder $seeder): int
    {
        $name = $this->option('name');
        $domain = $this->option('domain');
        $email = $this->option('email');

        if (! $name || ! $domain || ! $email) {
            $this->error('The --name, --domain, and --email options are required.');

            return self::FAILURE;
        }

        if (Tenant::where('domain', $domain)->exists()) {
            $this->error("Domain '{$domain}' is already taken.");

            return self::FAILURE;
        }

        $this->info("Provisioning tenant: {$name} ({$domain})...");

        $tenant = null;

        try {
            $tenant = Tenant::create([
                'uuid'          => Str::uuid()->toString(),
                'name'          => $name,
                'domain'        => $domain,
                'status'        => Tenant::STATUS_PROVISIONING,
                'es_index_uuid' => Str::uuid()->toString(),
            ]);

            core()->setCurrentTenantId($tenant->id);

            $result = $seeder->seed($tenant, [
                'email'    => $email,
                'locale'   => $this->option('locale'),
                'currency' => $this->option('currency'),
            ]);

            $tenant->transitionTo(Tenant::STATUS_ACTIVE);

            $this->info('');
            $this->info('Tenant provisioned successfully!');
            $this->table(
                ['Property', 'Value'],
                [
                    ['Tenant ID', $tenant->id],
                    ['Name', $name],
                    ['Domain', $domain],
                    ['Status', 'active'],
                    ['Admin Email', $result['admin_email']],
                    ['Admin Password', $result['admin_password']],
                    ['API Client ID', $result['client_id'] ?? 'N/A'],
                    ['API Client Secret', $result['client_secret'] ?? 'N/A'],
                ]
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Provisioning failed: {$e->getMessage()}");

            if ($tenant) {
                $this->warn('Cleaning up failed provisioning...');
                $this->cleanupFailedProvisioning($tenant);
            }

            return self::FAILURE;
        } finally {
            core()->setCurrentTenantId(null);
        }
    }

    private function cleanupFailedProvisioning(Tenant $tenant): void
    {
        try {
            if ($tenant->canTransitionTo(Tenant::STATUS_DELETED)) {
                $tenant->transitionTo(Tenant::STATUS_DELETED);
            }
            $tenant->delete();
            $this->info('Cleanup completed. No orphaned data remains.');
        } catch (\Throwable $cleanupError) {
            $this->error("Cleanup also failed: {$cleanupError->getMessage()}");
            $this->error("Manual intervention required for tenant ID: {$tenant->id}");
            report($cleanupError);
        }
    }
}
