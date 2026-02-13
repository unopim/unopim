<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Webkul\Completeness\Models\CompletenessSetting;
use Webkul\Completeness\Models\ProductCompletenessScore;
use Webkul\Tenant\Models\Concerns\BelongsToTenant;

beforeEach(function () {
    Mail::fake();
});

/*
|--------------------------------------------------------------------------
| Story 8.2: BelongsToTenant on Completeness Models
|--------------------------------------------------------------------------
|
| Verifies that CompletenessSetting and ProductCompletenessScore models
| use the BelongsToTenant trait and auto-set tenant_id.
|
*/

it('CompletenessSetting uses BelongsToTenant trait', function () {
    $traits = class_uses_recursive(CompletenessSetting::class);
    expect($traits)->toContain(BelongsToTenant::class);
});

it('ProductCompletenessScore uses BelongsToTenant trait', function () {
    $traits = class_uses_recursive(ProductCompletenessScore::class);
    expect($traits)->toContain(BelongsToTenant::class);
});

it('CompletenessSetting has tenant_id in fillable', function () {
    $model = new CompletenessSetting;
    expect($model->getFillable())->toContain('tenant_id');
});

it('ProductCompletenessScore has tenant_id in fillable', function () {
    $model = new ProductCompletenessScore;
    expect($model->getFillable())->toContain('tenant_id');
});

it('isolates CompletenessSetting records between tenants', function () {
    // Need attribute, family, and channel fixtures
    $familyA = $this->fixture($this->tenantA, 'family_id');

    // Create an attribute for tenant A
    $attrId = DB::table('attributes')->insertGetId([
        'code'      => 'cs-attr-'.uniqid(),
        'type'      => 'text',
        'tenant_id' => $this->tenantA->id,
    ]);

    // Create a channel for tenant A
    $channelId = DB::table('channels')->where('tenant_id', $this->tenantA->id)->value('id');
    if (! $channelId) {
        $channelId = DB::table('channels')->insertGetId([
            'code'      => 'cs-chan-'.uniqid(),
            'tenant_id' => $this->tenantA->id,
        ]);
    }

    // In Tenant A context, insert a setting
    core()->setCurrentTenantId($this->tenantA->id);
    DB::table('completeness_settings')->insert([
        'family_id'    => $familyA,
        'attribute_id' => $attrId,
        'channel_id'   => $channelId,
        'tenant_id'    => $this->tenantA->id,
        'created_at'   => now(),
        'updated_at'   => now(),
    ]);

    $countA = CompletenessSetting::count();
    expect($countA)->toBeGreaterThanOrEqual(1);

    // Switch to Tenant B — should not see Tenant A's settings
    core()->setCurrentTenantId($this->tenantB->id);
    $countB = CompletenessSetting::where('attribute_id', $attrId)->count();
    expect($countB)->toBe(0);

    core()->setCurrentTenantId($this->tenantA->id);
});

it('isolates ProductCompletenessScore records between tenants', function () {
    // Insert a product for tenant A
    $productId = DB::table('products')->insertGetId([
        'sku'                 => 'pcs-test-'.uniqid(),
        'type'                => 'simple',
        'attribute_family_id' => $this->fixture($this->tenantA, 'family_id'),
        'tenant_id'           => $this->tenantA->id,
        'created_at'          => now(),
        'updated_at'          => now(),
    ]);

    $channelId = DB::table('channels')->where('tenant_id', $this->tenantA->id)->value('id');
    if (! $channelId) {
        $channelId = DB::table('channels')->insertGetId([
            'code'      => 'pcs-chan-'.uniqid(),
            'tenant_id' => $this->tenantA->id,
        ]);
    }

    $localeId = DB::table('locales')->where('tenant_id', $this->tenantA->id)->value('id');
    if (! $localeId) {
        $localeId = DB::table('locales')->insertGetId([
            'code'      => 'en_US',
            'status'    => 1,
            'tenant_id' => $this->tenantA->id,
        ]);
    }

    // Insert in Tenant A context
    core()->setCurrentTenantId($this->tenantA->id);
    DB::table('product_completeness')->insert([
        'product_id'    => $productId,
        'channel_id'    => $channelId,
        'locale_id'     => $localeId,
        'score'         => 75,
        'missing_count' => 3,
        'tenant_id'     => $this->tenantA->id,
        'created_at'    => now(),
        'updated_at'    => now(),
    ]);

    $countA = ProductCompletenessScore::where('product_id', $productId)->count();
    expect($countA)->toBe(1);

    // Switch to Tenant B
    core()->setCurrentTenantId($this->tenantB->id);
    $countB = ProductCompletenessScore::where('product_id', $productId)->count();
    expect($countB)->toBe(0);

    core()->setCurrentTenantId($this->tenantA->id);
});
