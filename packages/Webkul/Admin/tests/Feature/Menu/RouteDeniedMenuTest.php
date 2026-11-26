<?php

function adminMenuItems(): array
{
    app()->forgetScopedInstances();

    return app('unopim.admin.menu')['tree']->items;
}

it('hides a leaf whose route needs a narrower permission than the item key, and its emptied parent', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'settings', 'settings.users']);

    $items = adminMenuItems();

    expect($items)->not->toHaveKey('settings')
        ->and($items)->toHaveKey('dashboard');

    $this->get(route('admin.settings.users.index'))->assertForbidden();
});

it('keeps the leaf and its parent once the route permission is granted', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'settings', 'settings.users', 'settings.users.users']);

    expect(adminMenuItems()['settings']['children'])->toHaveKey('users');
});

it('hides the measurements item from a role without the families permission', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'catalog', 'catalog.measurements']);

    expect(adminMenuItems())->not->toHaveKey('catalog');
});

it('lets an also_authorizes permission open a route that has its own narrower permission', function () {
    $this->loginWithPermissions('custom', ['dashboard', 'catalog', 'catalog.families', 'catalog.families.completeness']);

    expect(bouncer()->canAccessRoute('admin.catalog.families.edit'))->toBeTrue();

    $this->loginWithPermissions('custom', ['dashboard', 'catalog', 'catalog.families']);

    expect(bouncer()->canAccessRoute('admin.catalog.families.edit'))->toBeFalse();
});

it('treats a route without an ACL entry as open', function () {
    $this->loginWithPermissions('custom', ['dashboard']);

    expect(bouncer()->canAccessRoute('admin.route.without.acl'))->toBeTrue()
        ->and(bouncer()->canAccessRoute(null))->toBeTrue();
});
