<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Webkul\Tenant\Models\Tenant;

beforeEach(function () {
    Mail::fake();
});

/*
|--------------------------------------------------------------------------
| Story 7.3: Tenant-Aware ES Console Commands
|--------------------------------------------------------------------------
|
| Verifies that ProductIndexer, CategoryIndexer, and Reindexer all accept
| the --tenant option, resolve tenant context, and scope operations.
|
*/

it('product indexer command accepts --tenant option', function () {
    $command = Artisan::all()['unopim:product:index'] ?? null;
    expect($command)->not->toBeNull();

    $definition = $command->getDefinition();
    expect($definition->hasOption('tenant'))->toBeTrue();
    expect($definition->getOption('tenant')->getDescription())->toContain('Tenant ID');
});

it('category indexer command accepts --tenant option', function () {
    $command = Artisan::all()['unopim:category:index'] ?? null;
    expect($command)->not->toBeNull();

    $definition = $command->getDefinition();
    expect($definition->hasOption('tenant'))->toBeTrue();
    expect($definition->getOption('tenant')->getDescription())->toContain('Tenant ID');
});

it('reindexer command accepts --tenant option', function () {
    $command = Artisan::all()['unopim:elastic:clear'] ?? null;
    expect($command)->not->toBeNull();

    $definition = $command->getDefinition();
    expect($definition->hasOption('tenant'))->toBeTrue();
    expect($definition->getOption('tenant')->getDescription())->toContain('Tenant ID');
});

it('product indexer rejects inactive tenant', function () {
    $tenant = Tenant::factory()->create([
        'status' => Tenant::STATUS_SUSPENDED,
    ]);

    config(['elasticsearch.enabled' => true]);

    $this->artisan('unopim:product:index', ['--tenant' => $tenant->id])
        ->expectsOutputToContain('not active')
        ->assertExitCode(1);
});

it('category indexer rejects inactive tenant', function () {
    $tenant = Tenant::factory()->create([
        'status' => Tenant::STATUS_SUSPENDED,
    ]);

    config(['elasticsearch.enabled' => true]);

    $this->artisan('unopim:category:index', ['--tenant' => $tenant->id])
        ->expectsOutputToContain('not active')
        ->assertExitCode(1);
});

it('reindexer rejects inactive tenant after confirmation', function () {
    $tenant = Tenant::factory()->create([
        'status' => Tenant::STATUS_SUSPENDED,
    ]);

    config(['elasticsearch.enabled' => true]);

    $this->artisan('unopim:elastic:clear', ['--tenant' => $tenant->id])
        ->expectsConfirmation(
            "This action will clear all indexes for tenant {$tenant->id}. Do you want to continue? (y/n) or",
            'yes'
        )
        ->expectsOutputToContain('not active')
        ->assertExitCode(1);
});
