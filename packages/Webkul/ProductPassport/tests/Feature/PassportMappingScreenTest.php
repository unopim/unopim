<?php

use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Core\Models\CoreConfig;

it('persists a core_config row when the mapping is updated', function (): void {
    AttributeProxy::factory()->create(['code' => 'country', 'type' => 'text']);

    $this->setPassportConfig(['enabled' => '1']);

    $this->loginWithPermissions('all');

    $this->put(route('admin.catalog.passports.mapping.update'), [
        'mapping' => ['dpp_country_of_origin' => 'country'],
    ])->assertOk();

    expect(
        CoreConfig::query()
            ->where('code', 'catalog.product_passport.mapping.dpp_country_of_origin')
            ->where('value', 'country')
            ->exists()
    )->toBeTrue();
});

it('forbids the update without the mapping permission', function (): void {
    $this->setPassportConfig(['enabled' => '1']);

    $this->loginWithPermissions('custom', ['dashboard']);

    $this->put(route('admin.catalog.passports.mapping.update'), [
        'mapping' => ['dpp_country_of_origin' => 'country'],
    ])->assertForbidden();
});

it('rejects an unknown source attribute code', function (): void {
    $this->setPassportConfig(['enabled' => '1']);

    $this->loginWithPermissions('all');

    $this->put(route('admin.catalog.passports.mapping.update'), [
        'mapping' => ['dpp_country_of_origin' => 'nonexistent_attribute'],
    ])->assertSessionHasErrors('mapping.dpp_country_of_origin');

    expect(
        CoreConfig::query()
            ->where('code', 'catalog.product_passport.mapping.dpp_country_of_origin')
            ->exists()
    )->toBeFalse();
});
