<?php

/**
 * Regression cover for ACL route-name bypass: write-verb routes
 * (.store/.update) must enforce the same permission key as their
 * GET form siblings (.create/.edit). Without these mappings, the
 * Bouncer middleware silently allows the write because the route
 * name is absent from the ACL map.
 */
it('denies catalog.products.update without products.edit permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->put(route('admin.catalog.products.update', ['id' => 1]), [])
        ->assertStatus(403);
});

it('denies catalog.categories.store without categories.create permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->post(route('admin.catalog.categories.store'), [])
        ->assertStatus(403);
});

it('denies catalog.categories.update without categories.edit permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->put(route('admin.catalog.categories.update', ['id' => 1]), [])
        ->assertStatus(403);
});

it('denies catalog.category_fields.store without category_fields.create permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->post(route('admin.catalog.category_fields.store'), [])
        ->assertStatus(403);
});

it('denies catalog.category_fields.update without category_fields.edit permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->put(route('admin.catalog.category_fields.update', ['id' => 1]), [])
        ->assertStatus(403);
});

it('denies catalog.attributes.store without attributes.create permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->post(route('admin.catalog.attributes.store'), [])
        ->assertStatus(403);
});

it('denies catalog.attributes.update without attributes.edit permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->put(route('admin.catalog.attributes.update', ['id' => 1]), [])
        ->assertStatus(403);
});

it('denies catalog.attributes.options.store without attributes.edit permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->post(route('admin.catalog.attributes.options.store', ['attribute_id' => 1]), [])
        ->assertStatus(403);
});

it('denies catalog.attribute.groups.store without attribute_groups.create permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->post(route('admin.catalog.attribute.groups.store'), [])
        ->assertStatus(403);
});

it('denies catalog.attribute.groups.update without attribute_groups.edit permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->put(route('admin.catalog.attribute.groups.update', ['id' => 1]), [])
        ->assertStatus(403);
});

it('denies catalog.families.store without families.create permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->post(route('admin.catalog.families.store'), [])
        ->assertStatus(403);
});

it('denies catalog.families.update without families.edit permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->put(route('admin.catalog.families.update', ['id' => 1]), [])
        ->assertStatus(403);
});

it('denies settings.channels.store without channels.create permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->post(route('admin.settings.channels.store'), [])
        ->assertStatus(403);
});

it('denies settings.channels.update without channels.edit permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->put(route('admin.settings.channels.update', ['id' => 1]), [])
        ->assertStatus(403);
});

it('denies settings.currencies.store without currencies.create permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->post(route('admin.settings.currencies.store'), [])
        ->assertStatus(403);
});

it('denies settings.currencies.update without currencies.edit permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->put(route('admin.settings.currencies.update', ['id' => 1]), [])
        ->assertStatus(403);
});

it('denies settings.locales.update without locales.edit permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->put(route('admin.settings.locales.update', ['code' => 'en_US']), [])
        ->assertStatus(403);
});

it('denies data_transfer.imports.store without imports.create permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->post(route('admin.settings.data_transfer.imports.store'), [])
        ->assertStatus(403);
});

it('denies data_transfer.imports.update without imports.edit permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->put(route('admin.settings.data_transfer.imports.update', ['id' => 1]), [])
        ->assertStatus(403);
});

it('denies data_transfer.exports.store without export.create permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->post(route('admin.settings.data_transfer.exports.store'), [])
        ->assertStatus(403);
});

it('denies data_transfer.exports.update without export.edit permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->put(route('admin.settings.data_transfer.exports.update', ['id' => 1]), [])
        ->assertStatus(403);
});

/**
 * Integration / OAuth API key write routes. These create and issue credentials
 * for a chosen admin account, so a restricted user must not be able to reach
 * them by posting directly to the write verb. (Reported privilege escalation.)
 */
it('denies configuration.integrations.store without integrations.create permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->post(route('admin.configuration.integrations.store'), [])
        ->assertStatus(403);
});

it('denies configuration.integrations.update without integrations.edit permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->put(route('admin.configuration.integrations.update', ['id' => 1]), [])
        ->assertStatus(403);
});

it('denies configuration.integrations.generate_key without integrations.edit permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->post(route('admin.configuration.integrations.generate_key'), [])
        ->assertStatus(403);
});

it('denies configuration.integrations.re_generate_secret_key without integrations.edit permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->post(route('admin.configuration.integrations.re_generate_secret_key'), [])
        ->assertStatus(403);
});

/**
 * Product bulk-edit save routes modify products and must require product edit.
 */
it('denies catalog.products.bulk-edit.save without products.edit permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->post(route('admin.catalog.products.bulk-edit.save'), [])
        ->assertStatus(403);
});

it('denies catalog.products.bulk-edit.save-media without products.edit permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->post(route('admin.catalog.products.bulk-edit.save-media'), [])
        ->assertStatus(403);
});

/**
 * Family completeness settings write routes must require family edit.
 */
it('denies catalog.families.completeness.update without families.edit permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->post(route('admin.catalog.families.completeness.update'), [])
        ->assertStatus(403);
});

it('denies catalog.families.completeness.mass_update without families.edit permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->post(route('admin.catalog.families.completeness.mass_update'), [])
        ->assertStatus(403);
});

/**
 * Core configuration save must require the configuration permission so a
 * restricted admin cannot persist global system settings.
 */
it('denies configuration.store without configuration permission', function () {
    $this->loginWithPermissions(permissions: ['dashboard']);

    $this->post(route('admin.configuration.store', ['slug' => 'general', 'slug2' => 'general']), [])
        ->assertStatus(403);
});
