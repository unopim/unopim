<?php

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Webkul\Tenant\Models\Tenant;

beforeEach(function () {
    Mail::fake();
});

/*
|--------------------------------------------------------------------------
| Story 8.4: Tenant-Aware RecalculateCompletenessCommand
|--------------------------------------------------------------------------
|
| Verifies that the recalculate command accepts --tenant option,
| validates the tenant, and sets context.
|
*/

it('recalculate command accepts --tenant option', function () {
    $command = Artisan::all()['unopim:completeness:recalculate'] ?? null;
    expect($command)->not->toBeNull();

    $definition = $command->getDefinition();
    expect($definition->hasOption('tenant'))->toBeTrue();
    expect($definition->getOption('tenant')->getDescription())->toContain('Tenant ID');
});

it('recalculate command rejects inactive tenant', function () {
    $tenant = Tenant::factory()->create([
        'status' => Tenant::STATUS_SUSPENDED,
    ]);

    $this->artisan('unopim:completeness:recalculate', [
        '--tenant' => $tenant->id,
        '--all'    => true,
    ])
        ->expectsOutputToContain('not active')
        ->assertExitCode(Command::FAILURE);
});

it('recalculate command sets tenant context for active tenant', function () {
    $this->artisan('unopim:completeness:recalculate', [
        '--tenant' => $this->tenantA->id,
        '--all'    => true,
    ])
        ->expectsOutputToContain('tenant context')
        ->assertExitCode(Command::SUCCESS);

    expect(core()->getCurrentTenantId())->toBe($this->tenantA->id);
});

it('recalculate command rejects nonexistent tenant', function () {
    $this->artisan('unopim:completeness:recalculate', [
        '--tenant' => 99999,
        '--all'    => true,
    ])
        ->expectsOutputToContain('not found')
        ->assertExitCode(Command::FAILURE);
});
