<?php

use Webkul\Tenant\Models\Tenant;

it('suspends an active tenant', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);

    $this->artisan('tenant:suspend', [
        '--tenant' => $tenant->id,
        '--reason' => 'Billing overdue',
    ])->assertSuccessful();

    $tenant->refresh();
    expect($tenant->status)->toBe(Tenant::STATUS_SUSPENDED);
    expect($tenant->settings['suspension_reason'])->toBe('Billing overdue');
});

it('warns when suspending an already-suspended tenant', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_SUSPENDED]);

    $this->artisan('tenant:suspend', [
        '--tenant' => $tenant->id,
    ])
        ->expectsOutputToContain('already suspended')
        ->assertSuccessful();
});

it('fails to suspend a provisioning tenant', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_PROVISIONING]);

    $this->artisan('tenant:suspend', [
        '--tenant' => $tenant->id,
    ])->assertFailed();

    expect($tenant->fresh()->status)->toBe(Tenant::STATUS_PROVISIONING);
});

it('reactivates a suspended tenant', function () {
    $tenant = Tenant::factory()->create([
        'status'   => Tenant::STATUS_SUSPENDED,
        'settings' => ['suspension_reason' => 'Testing'],
    ]);

    $this->artisan('tenant:activate', [
        '--tenant' => $tenant->id,
    ])->assertSuccessful();

    $tenant->refresh();
    expect($tenant->status)->toBe(Tenant::STATUS_ACTIVE);
    expect($tenant->settings['suspension_reason'] ?? null)->toBeNull();
});

it('warns when activating an already-active tenant', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);

    $this->artisan('tenant:activate', [
        '--tenant' => $tenant->id,
    ])
        ->expectsOutputToContain('already active')
        ->assertSuccessful();
});

it('fails to activate a deleting tenant', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_DELETING]);

    $this->artisan('tenant:activate', [
        '--tenant' => $tenant->id,
    ])->assertFailed();

    expect($tenant->fresh()->status)->toBe(Tenant::STATUS_DELETING);
});

it('suspended tenant returns 503 via middleware', function () {
    $tenant = Tenant::factory()->create([
        'status' => Tenant::STATUS_ACTIVE,
        'domain' => 'sus-test',
    ]);

    // Suspend the tenant
    $tenant->transitionTo(Tenant::STATUS_SUSPENDED);

    // TenantMiddleware should return 503 for this tenant
    $middleware = new \Webkul\Tenant\Http\Middleware\TenantMiddleware;
    $request = \Illuminate\Http\Request::create('/');
    $request->headers->set('X-Tenant-ID', (string) $tenant->id);

    try {
        $middleware->handle($request, function ($req) {
            return new \Illuminate\Http\Response('OK');
        });
        $this->fail('Expected 503 abort');
    } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        expect($e->getStatusCode())->toBe(503);
    }
});

it('reactivated tenant passes middleware', function () {
    $tenant = Tenant::factory()->create([
        'status' => Tenant::STATUS_ACTIVE,
        'domain' => 'react-test',
    ]);

    // Suspend then reactivate
    $tenant->transitionTo(Tenant::STATUS_SUSPENDED);
    $tenant->transitionTo(Tenant::STATUS_ACTIVE);

    $middleware = new \Webkul\Tenant\Http\Middleware\TenantMiddleware;
    $request = \Illuminate\Http\Request::create('/');
    $request->headers->set('X-Tenant-ID', (string) $tenant->id);

    $response = $middleware->handle($request, function () {
        return new \Illuminate\Http\Response('OK');
    });

    expect($response->getStatusCode())->toBe(200);
    expect(core()->getCurrentTenantId())->toBe($tenant->id);
});

it('requires --tenant option for suspend', function () {
    $this->artisan('tenant:suspend')->assertFailed();
});

it('requires --tenant option for activate', function () {
    $this->artisan('tenant:activate')->assertFailed();
});
