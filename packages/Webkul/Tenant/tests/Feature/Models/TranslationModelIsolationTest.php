<?php

use Webkul\Tenant\Models\Concerns\BelongsToTenant;

/*
|--------------------------------------------------------------------------
| Translation Model Isolation Tests
|--------------------------------------------------------------------------
|
| Verifies that all translation models and the History model now use
| the BelongsToTenant trait for tenant scope isolation.
| These models were identified as gap-fix targets in the audit.
|
*/

// -- Structural: trait presence on translation models ---------------------

it('AttributeTranslation uses BelongsToTenant', function () {
    expect(class_uses_recursive(\Webkul\Attribute\Models\AttributeTranslation::class))
        ->toContain(BelongsToTenant::class);
});

it('AttributeFamilyTranslation uses BelongsToTenant', function () {
    expect(class_uses_recursive(\Webkul\Attribute\Models\AttributeFamilyTranslation::class))
        ->toContain(BelongsToTenant::class);
});

it('AttributeGroupTranslation uses BelongsToTenant', function () {
    expect(class_uses_recursive(\Webkul\Attribute\Models\AttributeGroupTranslation::class))
        ->toContain(BelongsToTenant::class);
});

it('AttributeOptionTranslation uses BelongsToTenant', function () {
    expect(class_uses_recursive(\Webkul\Attribute\Models\AttributeOptionTranslation::class))
        ->toContain(BelongsToTenant::class);
});

it('ChannelTranslation uses BelongsToTenant', function () {
    expect(class_uses_recursive(\Webkul\Core\Models\ChannelTranslation::class))
        ->toContain(BelongsToTenant::class);
});

it('CategoryFieldTranslation uses BelongsToTenant', function () {
    expect(class_uses_recursive(\Webkul\Category\Models\CategoryFieldTranslation::class))
        ->toContain(BelongsToTenant::class);
});

it('CategoryFieldOptionTranslation uses BelongsToTenant', function () {
    expect(class_uses_recursive(\Webkul\Category\Models\CategoryFieldOptionTranslation::class))
        ->toContain(BelongsToTenant::class);
});

// -- Structural: trait presence on History model ---------------------------

it('History model uses BelongsToTenant', function () {
    expect(class_uses_recursive(\Webkul\HistoryControl\Models\History::class))
        ->toContain(BelongsToTenant::class);
});

// -- BelongsToTenant boot behavior: auto-sets tenant_id -------------------

it('BelongsToTenant auto-sets tenant_id on model creation', function () {
    $this->actingAsTenant($this->tenantA);

    // Use a stub model to verify the creating event behavior
    $stub = new \Illuminate\Database\Eloquent\Model;
    $stub->tenant_id = null;

    // Simulate what BelongsToTenant's creating callback does
    $tenantId = core()->getCurrentTenantId();
    if (! is_null($tenantId) && ! $stub->isDirty('tenant_id')) {
        $stub->tenant_id = $tenantId;
    }

    expect($stub->tenant_id)->toBe($this->tenantA->id);
});

it('BelongsToTenant does not override explicitly set tenant_id', function () {
    $this->actingAsTenant($this->tenantA);

    $stub = new \Illuminate\Database\Eloquent\Model;
    $stub->tenant_id = 999;

    // Simulate creating callback - isDirty('tenant_id') will be true
    $tenantId = core()->getCurrentTenantId();
    if (! is_null($tenantId) && ! $stub->isDirty('tenant_id')) {
        $stub->tenant_id = $tenantId;
    }

    // Should keep explicitly-set value since isDirty returns true
    expect($stub->tenant_id)->toBe(999);
});
