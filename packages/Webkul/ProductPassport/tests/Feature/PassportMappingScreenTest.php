<?php

use Webkul\Attribute\Models\AttributeFamilyProxy;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Attribute\Models\AttributeProxy;
use Webkul\Core\Models\CoreConfig;
use Webkul\ProductPassport\Database\Seeders\DppAttributeSeeder;

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

it('lists a custom attribute added to the dpp group as a mappable field', function (): void {
    resolve(DppAttributeSeeder::class)->run();

    $dppGroup = AttributeGroup::where('code', 'dpp')->firstOrFail();

    $custom = AttributeProxy::factory()->create(['code' => 'eco_label', 'type' => 'text']);

    $family = AttributeFamilyProxy::factory()->withMinimalAttributesForProductTypes()->create();
    $family->familyGroups()->attach($dppGroup->id);
    $family->attributeFamilyGroupMappings()->where('attribute_group_id', $dppGroup->id)->first()
        ?->customAttributes()->attach($custom);

    $this->setPassportConfig(['enabled' => '1']);

    $this->loginWithPermissions('all');

    $this->get(route('admin.catalog.passports.mapping.edit'))
        ->assertOk()
        ->assertSee('mapping[eco_label]', false);
});

it('accepts a document source for a document passport field', function (): void {
    resolve(DppAttributeSeeder::class)->run();

    AttributeProxy::factory()->create(['code' => 'product_gallery', 'type' => 'image']);

    $this->setPassportConfig(['enabled' => '1']);

    $this->loginWithPermissions('all');

    $this->put(route('admin.catalog.passports.mapping.update'), [
        'mapping' => ['dpp_certificates' => 'product_gallery'],
    ])->assertOk();

    expect(
        CoreConfig::query()
            ->where('code', 'catalog.product_passport.mapping.dpp_certificates')
            ->where('value', 'product_gallery')
            ->exists()
    )->toBeTrue();
});

it('rejects a value source for a document passport field', function (): void {
    resolve(DppAttributeSeeder::class)->run();

    AttributeProxy::factory()->create(['code' => 'country', 'type' => 'text']);

    $this->setPassportConfig(['enabled' => '1']);

    $this->loginWithPermissions('all');

    $this->put(route('admin.catalog.passports.mapping.update'), [
        'mapping' => ['dpp_certificates' => 'country'],
    ])->assertSessionHasErrors('mapping.dpp_certificates');

    expect(
        CoreConfig::query()
            ->where('code', 'catalog.product_passport.mapping.dpp_certificates')
            ->exists()
    )->toBeFalse();
});
