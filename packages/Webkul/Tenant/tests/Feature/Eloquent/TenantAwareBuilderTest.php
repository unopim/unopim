<?php

use Illuminate\Support\Facades\Mail;
use Webkul\Tenant\Models\Tenant;

beforeEach(function () {
    Mail::fake();
});

it('uses TenantAwareBuilder for models with BelongsToTenant', function () {
    $channel = new \Webkul\Core\Models\Channel;
    $builder = $channel->newQuery();

    expect($builder)->toBeInstanceOf(\Webkul\Tenant\Eloquent\TenantAwareBuilder::class);
});

it('allows query to proceed when TenantScope is bypassed (log + allow)', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
    core()->setCurrentTenantId($tenant->id);

    // withoutGlobalScope should NOT throw — it logs and allows
    $result = \Webkul\Core\Models\Channel::withoutGlobalScope(\Webkul\Tenant\Models\Scopes\TenantScope::class)->get();
    expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);

    core()->setCurrentTenantId(null);
});

it('allows query to proceed when all scopes are bypassed', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
    core()->setCurrentTenantId($tenant->id);

    $result = \Webkul\Core\Models\Channel::withoutGlobalScopes()->get();
    expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);

    core()->setCurrentTenantId(null);
});

it('returns unscoped data when TenantScope is bypassed', function () {
    $tenant1 = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);
    $tenant2 = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);

    // Set tenant context to tenant1
    core()->setCurrentTenantId($tenant1->id);

    // Normal scoped query
    $scoped = \Webkul\Core\Models\Locale::all();

    // Bypassed query — should see ALL locales
    $unscoped = \Webkul\Core\Models\Locale::withoutGlobalScope(\Webkul\Tenant\Models\Scopes\TenantScope::class)->get();

    expect($unscoped->count())->toBeGreaterThanOrEqual($scoped->count());

    core()->setCurrentTenantId(null);
});
