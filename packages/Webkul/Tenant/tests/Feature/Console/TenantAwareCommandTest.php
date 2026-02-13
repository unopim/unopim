<?php

use Illuminate\Console\Command;
use Webkul\Tenant\Console\Commands\TenantAwareCommand;
use Webkul\Tenant\Models\Tenant;

/*
|--------------------------------------------------------------------------
| TenantAwareCommand Tests
|--------------------------------------------------------------------------
|
| Verifies that the --tenant=N CLI infrastructure works:
| - Valid tenant flag sets context
| - Missing flag aborts in production
| - Invalid/inactive tenant aborts
| - forAllTenants() iterates active tenants
|
*/

// Register a concrete test command at runtime
beforeEach(function () {
    core()->setCurrentTenantId(null);

    // Register a minimal concrete command for testing
    $this->app->singleton('test.tenant-command', function () {
        return new class extends TenantAwareCommand
        {
            protected $signature = 'test:tenant-aware {--tenant=}';

            protected $description = 'Test command for TenantAwareCommand';

            public ?int $resolvedTenantId = null;

            public array $iteratedTenantIds = [];

            protected function handleForTenant(?Tenant $tenant): int
            {
                $this->resolvedTenantId = $tenant?->id;

                return Command::SUCCESS;
            }

            public function runForAll(): void
            {
                $this->forAllTenants(function (Tenant $tenant) {
                    $this->iteratedTenantIds[] = $tenant->id;
                });
            }
        };
    });

    // Resolve the command and register it
    $command = $this->app->make('test.tenant-command');
    \Illuminate\Support\Facades\Artisan::registerCommand($command);
});

it('sets tenant context when --tenant flag is provided with valid active tenant', function () {
    $this->artisan('test:tenant-aware', ['--tenant' => $this->tenantA->id])
        ->expectsOutputToContain('Running in tenant context')
        ->assertExitCode(Command::SUCCESS);

    expect(core()->getCurrentTenantId())->toBe($this->tenantA->id);
});

it('fails when --tenant references a nonexistent tenant', function () {
    $this->artisan('test:tenant-aware', ['--tenant' => 99999])
        ->expectsOutputToContain('not found')
        ->assertExitCode(Command::FAILURE);
});

it('fails when --tenant references an inactive tenant', function () {
    $this->tenantA->update(['status' => Tenant::STATUS_SUSPENDED]);

    $this->artisan('test:tenant-aware', ['--tenant' => $this->tenantA->id])
        ->expectsOutputToContain('not active')
        ->assertExitCode(Command::FAILURE);
});

it('fails in production when --tenant is not provided', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->artisan('test:tenant-aware')
        ->expectsOutputToContain('--tenant option is required')
        ->assertExitCode(Command::FAILURE);
});

it('runs in platform mode (without tenant) in non-production', function () {
    // Default environment is "testing" — not production
    $this->artisan('test:tenant-aware')
        ->expectsOutputToContain('platform mode')
        ->assertExitCode(Command::SUCCESS);

    expect(core()->getCurrentTenantId())->toBeNull();
});

it('iterates all active tenants with forAllTenants()', function () {
    // Ensure both tenants are active
    $this->tenantA->update(['status' => Tenant::STATUS_ACTIVE]);
    $this->tenantB->update(['status' => Tenant::STATUS_ACTIVE]);

    // Create a third suspended tenant that should NOT be iterated
    $suspended = Tenant::factory()->create([
        'domain' => 'suspended-t',
        'status' => Tenant::STATUS_SUSPENDED,
    ]);

    $command = $this->app->make('test.tenant-command');
    $command->setLaravel($this->app);

    // Use OutputStyle (required by Laravel's Command class)
    $buffered = new \Symfony\Component\Console\Output\BufferedOutput;
    $input = new \Symfony\Component\Console\Input\ArrayInput([]);
    $command->setOutput(new \Illuminate\Console\OutputStyle($input, $buffered));
    $command->runForAll();

    expect($command->iteratedTenantIds)->toContain($this->tenantA->id);
    expect($command->iteratedTenantIds)->toContain($this->tenantB->id);
    expect($command->iteratedTenantIds)->not->toContain($suspended->id);

    // After iteration, context should be cleared
    expect(core()->getCurrentTenantId())->toBeNull();
});
