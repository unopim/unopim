<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Category\Models\Category;
use Webkul\Product\Models\Product;

/**Locale Acl Test */
it('should not display the locale list if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('GET', route('admin.api.locales.index'))
        ->assertForbidden();
});

it('should not display the locale get route if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('GET', route('admin.api.locales.get', 'en_US'))
        ->assertForbidden();
});

it('should display the locale list route if it has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.settings.locales']);

    $this->withHeaders($headers)->json('GET', route('admin.api.locales.index'))
        ->assertOk();
});

it('should display the locale get route if it has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.settings.locales']);

    $this->withHeaders($headers)->json('GET', route('admin.api.locales.get', 'en_US'))
        ->assertOk();
});

/** Currency Acl Test */
it('should not display the currencies list if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('GET', route('admin.api.currencies.index'))
        ->assertForbidden();
});

it('should not display the currency by code route if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('GET', route('admin.api.currencies.get', 'USD'))
        ->assertForbidden();
});

it('should display the currencies list route if it has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.settings.currencies']);

    $this->withHeaders($headers)->json('GET', route('admin.api.currencies.index'))
        ->assertOk();
});

it('should display the currency get route if it has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.settings.currencies']);

    $this->withHeaders($headers)->json('GET', route('admin.api.currencies.get', 'USD'))
        ->assertOk();
});

/** Channel Acl Test */
it('should not display the channel list if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('GET', route('admin.api.channels.index'))
        ->assertForbidden();
});

it('should not display the channel by code route if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('GET', route('admin.api.channels.get', 'default'))
        ->assertForbidden();
});

it('should display the channels list route if it has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.settings.channels']);

    $this->withHeaders($headers)->json('GET', route('admin.api.channels.index'))
        ->assertOk();
});

it('should display the channel by code route if it has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.settings.channels']);

    $this->withHeaders($headers)->json('GET', route('admin.api.channels.get', 'default'))
        ->assertOk();
});

/** Attribute Group Acl Tests*/
it('should not display the attribute group list if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $response = $this->withHeaders($headers)->json('GET', route('admin.api.attribute_groups.index'))
        ->assertForbidden();
});

it('should not display the attribute group by code route if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('GET', route('admin.api.attribute_groups.get', 'general'))
        ->assertForbidden();
});

it('should not allow creation of attribute group if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('POST', route('admin.api.attribute_groups.store'))
        ->assertForbidden();
});

it('should not allow update of attribute group if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('PUT', route('admin.api.attribute_groups.update', 'general'))
        ->assertForbidden();
});

/** With permission cases */
it('should display the attribute group list if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.attribute_groups']);

    $this->withHeaders($headers)->json('GET', route('admin.api.attribute_groups.index'))
        ->assertOK();
});

it('should display the attribute group by code route if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.attribute_groups']);

    $this->withHeaders($headers)->json('GET', route('admin.api.attribute_groups.get', 'general'))
        ->assertOK();
});

it('should allow creation of attribute group if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.attribute_groups.create']);

    $response = $this->withHeaders($headers)->json('POST', route('admin.api.attribute_groups.store'), []);

    $this->assertTrue(
        $response->status() == 422,
        'Expected status 200 or 422, but got '.$response->status()
    );
});

it('should allow update of attribute group if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.attribute_groups.edit']);

    $code = AttributeGroup::first()->code;

    $response = $this->withHeaders($headers)->json('PUT', route('admin.api.attribute_groups.update', $code), []);

    $this->assertTrue(
        in_array($response->status(), [200, 422]),
        'Expected status 200 or 422, but got '.$response->status()
    );
});

/** Attribute Api Acl routes */
it('should not display the attribute list if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $response = $this->withHeaders($headers)->json('GET', route('admin.api.attributes.index'))
        ->assertForbidden();
});

it('should not display the attribute by code route if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('GET', route('admin.api.attributes.get', 'general'))
        ->assertForbidden();
});

it('should not allow creation of attribute if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('POST', route('admin.api.attributes.store'))
        ->assertForbidden();
});

it('should not allow update of attribute if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('PUT', route('admin.api.attributes.update', 'sku'))
        ->assertForbidden();
});

/** With permission cases */
it('should display the attribute list if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.attributes']);

    $this->withHeaders($headers)->json('GET', route('admin.api.attributes.index'))
        ->assertOK();
});

it('should display the attribute by code route if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.attributes']);

    $this->withHeaders($headers)->json('GET', route('admin.api.attributes.get', 'sku'))
        ->assertOK();
});

it('should allow creation of attribute if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.attributes.create']);

    $response = $this->withHeaders($headers)->json('POST', route('admin.api.attributes.store'), []);

    $this->assertTrue(
        $response->status() == 422,
        'Expected status 422, but got '.$response->status()
    );
});

it('should allow update of attribute if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.attributes.edit']);

    $response = $this->withHeaders($headers)->json('PUT', route('admin.api.attributes.update', 'sku'), []);

    $this->assertTrue(
        in_array($response->status(), [200, 422]),
        'Expected status 200 or 422, but got '.$response->status()
    );
});

/** Attribute Family API Acl tests */
it('should not display the family list if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $response = $this->withHeaders($headers)->json('GET', route('admin.api.families.index'))
        ->assertForbidden();
});

it('should not display the family by code route if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('GET', route('admin.api.families.get', 'general'))
        ->assertForbidden();
});

it('should not allow creation of family if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('POST', route('admin.api.families.store'))
        ->assertForbidden();
});

it('should not allow update of family if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('PUT', route('admin.api.families.update', 'default'))
        ->assertForbidden();
});

/** With permission cases */
it('should display the family list if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.families']);

    AttributeFamily::factory()->create();

    $this->withHeaders($headers)->json('GET', route('admin.api.families.index'))
        ->assertOK();
});

it('should display the family by code route if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.families']);

    AttributeFamily::factory()->create();

    $this->withHeaders($headers)->json('GET', route('admin.api.families.get', 'default'))
        ->assertOK();
});

it('should allow creation of family if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.families.create']);

    $response = $this->withHeaders($headers)->json('POST', route('admin.api.families.store'), []);

    $this->assertTrue(
        $response->status() == 422,
        'Expected status 422, but got '.$response->status()
    );
});

it('should allow update of family if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.families.edit']);

    $response = $this->withHeaders($headers)->json('PUT', route('admin.api.families.update', 'not_existing_family'), ['code' => 'default', 'attribute_groups' => []]);

    $this->assertTrue(
        in_array($response->status(), [200, 404, 422]),
        'Expected status 200, 404 or 422, but got '.$response->status()
    );
});

/** Category Field Api Acl routes */
it('should not display the category field list if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $response = $this->withHeaders($headers)->json('GET', route('admin.api.category-fields.index'))
        ->assertForbidden();
});

it('should not display the category field by code route if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('GET', route('admin.api.category-fields.get', 'general'))
        ->assertForbidden();
});

it('should not allow creation of category field if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('POST', route('admin.api.category-fields.store'), [])
        ->assertForbidden();
});

it('should not allow update of category field if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('PUT', route('admin.api.category-fields.update', 'name'), [])
        ->assertForbidden();
});

/** With permission cases */
it('should display the category field list if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.category_fields']);

    $this->withHeaders($headers)->json('GET', route('admin.api.category-fields.index'))
        ->assertOK();
});

it('should display the category field by code route if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.category_fields']);

    $this->withHeaders($headers)->json('GET', route('admin.api.category-fields.get', 'name'))
        ->assertOK();
});

it('should allow creation of category field if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.category_fields.create']);

    $response = $this->withHeaders($headers)->json('POST', route('admin.api.category-fields.store'), []);

    $this->assertTrue(
        $response->status() == 422,
        'Expected status 422, but got '.$response->status()
    );
});

it('should allow update of category field if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.category_fields.edit']);

    $response = $this->withHeaders($headers)->json('PUT', route('admin.api.category-fields.update', 'name'), []);

    $this->assertTrue(
        in_array($response->status(), [200, 422]),
        'Expected status 200 or 422, but got '.$response->status()
    );
});

/** Category Api Acl routes */
it('should not display the category list if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $response = $this->withHeaders($headers)->json('GET', route('admin.api.categories.index'))
        ->assertForbidden();
});

it('should not display the category by code route if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('GET', route('admin.api.categories.get', 'general'))
        ->assertForbidden();
});

it('should not allow creation of category if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('POST', route('admin.api.categories.store'), [])
        ->assertForbidden();
});

it('should not allow update of category if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('PUT', route('admin.api.categories.update', 'name'), [])
        ->assertForbidden();
});

/** With permission cases */
it('should display the category list if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.categories']);

    $this->withHeaders($headers)->json('GET', route('admin.api.categories.index'))
        ->assertOK();
});

it('should display the category by code route if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.categories']);

    $code = Category::first()->code;

    $this->withHeaders($headers)->json('GET', route('admin.api.categories.get', $code))
        ->assertOK();
});

it('should allow creation of category if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.categories.create']);

    $response = $this->withHeaders($headers)->json('POST', route('admin.api.categories.store'), ['code' => '', 'parent' => '', 'additional_data' => []]);

    $this->assertTrue(
        $response->status() == 422,
        'Expected status 422, but got '.$response->status()
    );
});

it('should allow update of category if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.categories.edit']);

    $code = Category::first()->code;

    $response = $this->withHeaders($headers)->json('PUT', route('admin.api.categories.update', $code), []);

    $this->assertTrue(
        in_array($response->status(), [200, 422]),
        'Expected status 200, 404 or 422, but got '.$response->status()
    );
});

/** Simple Product Api Acl routes */
it('should not display the simple product list if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $response = $this->withHeaders($headers)->json('GET', route('admin.api.products.index'))
        ->assertForbidden();
});

it('should not display the simple product by code route if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('GET', route('admin.api.products.get', 'test'))
        ->assertForbidden();
});

it('should not allow creation of simple product if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('POST', route('admin.api.products.store'), [])
        ->assertForbidden();
});

it('should not allow update of simple product if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('PUT', route('admin.api.products.update', 'name'), [])
        ->assertForbidden();
});

/** With permission cases */
it('should display the simple product list if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.products']);

    $this->withHeaders($headers)->json('GET', route('admin.api.products.index'))
        ->assertOK();
});

it('should display the simple product by code route if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.products']);

    $code = Product::factory()->simple()->withInitialValues()->create()->sku;

    $this->withHeaders($headers)->json('GET', route('admin.api.products.get', ['code' => $code]))
        ->assertOK();
});

it('should allow creation of simple product if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.products.create']);

    $response = $this->withHeaders($headers)->json('POST', route('admin.api.products.store'), []);

    $this->assertTrue(
        $response->status() == 422,
        'Expected status 422, but got '.$response->status()
    );
});

it('should allow update of simple product if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.products.edit']);

    $code = Product::factory()->simple()->withInitialValues()->create()->sku;

    $response = $this->withHeaders($headers)->json('PUT', route('admin.api.products.update', ['code' => $code]), []);

    $this->assertTrue(
        in_array($response->status(), [200, 422]),
        'Expected status 200, 404 or 422, but got '.$response->status()
    );
});

/** Configurable Product Api Acl routes */
it('should not display the configurable product list if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $response = $this->withHeaders($headers)->json('GET', route('admin.api.configrable_products.index'))
        ->assertForbidden();
});

it('should not display the configurable product by code route if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('GET', route('admin.api.configrable_products.get', 'test'))
        ->assertForbidden();
});

it('should not allow creation of configurable product if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('POST', route('admin.api.configrable_products.store'), [])
        ->assertForbidden();
});

it('should not allow update of configurable product if does not have permission', function () {
    $headers = $this->getAuthenticationHeaders('custom');

    $this->withHeaders($headers)->json('PUT', route('admin.api.configrable_products.update', 'name'), [])
        ->assertForbidden();
});

/** With permission cases */
it('should display the configurable product list if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.products']);

    $this->withHeaders($headers)->json('GET', route('admin.api.configrable_products.index'))
        ->assertOK();
});

it('should display the configurable product by code route if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.products']);

    $code = Product::factory()->configurable()->withInitialValues()->create()->sku;

    $this->withHeaders($headers)->json('GET', route('admin.api.configrable_products.get', ['code' => $code]))
        ->assertOK();
});

it('should allow creation of configurable product if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.products.create']);

    $response = $this->withHeaders($headers)->json('POST', route('admin.api.configrable_products.store'), []);

    $this->assertTrue(
        $response->status() == 422,
        'Expected status 422, but got '.$response->status()
    );
});

it('should allow update of configurable product if has permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.products.edit']);

    $code = Product::factory()->configurable()->withInitialValues()->create()->sku;

    $response = $this->withHeaders($headers)->json('PUT', route('admin.api.configrable_products.update', ['code' => $code]), []);

    $this->assertTrue(
        in_array($response->status(), [200, 422]),
        'Expected status 200, 404 or 422, but got '.$response->status()
    );
});
