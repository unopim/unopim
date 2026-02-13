<?php

use Webkul\Tenant\Exceptions\TenantStateTransitionException;
use Webkul\Tenant\Models\Tenant;

it('allows valid transition from provisioning to active', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_PROVISIONING]);

    $tenant->transitionTo(Tenant::STATUS_ACTIVE);

    expect($tenant->fresh()->status)->toBe(Tenant::STATUS_ACTIVE);
});

it('allows valid transition from active to suspended', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);

    $tenant->transitionTo(Tenant::STATUS_SUSPENDED);

    expect($tenant->fresh()->status)->toBe(Tenant::STATUS_SUSPENDED);
});

it('allows valid transition from suspended to active', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_SUSPENDED]);

    $tenant->transitionTo(Tenant::STATUS_ACTIVE);

    expect($tenant->fresh()->status)->toBe(Tenant::STATUS_ACTIVE);
});

it('allows valid transition from active to deleting', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);

    $tenant->transitionTo(Tenant::STATUS_DELETING);

    expect($tenant->fresh()->status)->toBe(Tenant::STATUS_DELETING);
});

it('allows valid transition from deleting to deleted', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_DELETING]);

    $tenant->transitionTo(Tenant::STATUS_DELETED);

    expect($tenant->fresh()->status)->toBe(Tenant::STATUS_DELETED);
});

it('allows valid transition from provisioning to deleted (cleanup)', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_PROVISIONING]);

    $tenant->transitionTo(Tenant::STATUS_DELETED);

    expect($tenant->fresh()->status)->toBe(Tenant::STATUS_DELETED);
});

it('throws TenantStateTransitionException for invalid transition', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);

    $tenant->transitionTo(Tenant::STATUS_PROVISIONING);
})->throws(TenantStateTransitionException::class, "Invalid tenant state transition from 'active' to 'provisioning'.");

it('throws exception when transitioning from deleted', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_DELETED]);

    $tenant->transitionTo(Tenant::STATUS_ACTIVE);
})->throws(TenantStateTransitionException::class);

it('logs transitions in settings JSON', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_PROVISIONING]);

    $tenant->transitionTo(Tenant::STATUS_ACTIVE, 42);

    $tenant->refresh();
    $log = $tenant->settings['transition_log'] ?? [];

    expect($log)->toHaveCount(1);
    expect($log[0]['from'])->toBe('provisioning');
    expect($log[0]['to'])->toBe('active');
    expect($log[0]['operator_id'])->toBe(42);
    expect($log[0]['at'])->not->toBeNull();
});

it('accumulates multiple transition log entries', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_PROVISIONING]);

    $tenant->transitionTo(Tenant::STATUS_ACTIVE);
    $tenant->transitionTo(Tenant::STATUS_SUSPENDED);
    $tenant->transitionTo(Tenant::STATUS_ACTIVE);

    $tenant->refresh();
    $log = $tenant->settings['transition_log'];

    expect($log)->toHaveCount(3);
    expect($log[0]['to'])->toBe('active');
    expect($log[1]['to'])->toBe('suspended');
    expect($log[2]['to'])->toBe('active');
});

it('reports canTransitionTo correctly', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);

    expect($tenant->canTransitionTo(Tenant::STATUS_SUSPENDED))->toBeTrue();
    expect($tenant->canTransitionTo(Tenant::STATUS_DELETING))->toBeTrue();
    expect($tenant->canTransitionTo(Tenant::STATUS_PROVISIONING))->toBeFalse();
    expect($tenant->canTransitionTo(Tenant::STATUS_DELETED))->toBeFalse();
});
