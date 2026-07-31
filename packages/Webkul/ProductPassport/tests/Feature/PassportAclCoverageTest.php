<?php

/**
 * A route absent from the ACL config never appears in the role permission tree,
 * so an action can end up ungoverned even while its request authorizes correctly.
 */
it('maps every state-changing passport route to a permission key', function (): void {
    $keysByRoute = collect(config('acl'))
        ->filter(fn (array $entry): bool => ! empty($entry['route']))
        ->mapWithKeys(fn (array $entry): array => [$entry['route'] => $entry['key']]);

    expect($keysByRoute)
        ->toHaveKey('admin.catalog.passports.publish', 'catalog.passport.publish')
        ->toHaveKey('admin.catalog.passports.republish', 'catalog.passport.publish')
        ->toHaveKey('admin.catalog.passports.mass_publish', 'catalog.passport.publish')
        ->toHaveKey('admin.catalog.passports.bulk-publish', 'catalog.passport.publish')
        ->toHaveKey('admin.catalog.passports.withdraw', 'catalog.passport.withdraw')
        ->toHaveKey('admin.catalog.passports.reinstate', 'catalog.passport.withdraw')
        ->toHaveKey('admin.catalog.passports.mass_transition', 'catalog.passport.withdraw')
        ->toHaveKey('admin.catalog.passports.templates.store', 'catalog.passport.template.create')
        ->toHaveKey('admin.catalog.passports.templates.update', 'catalog.passport.template.edit')
        ->toHaveKey('admin.catalog.passports.templates.delete', 'catalog.passport.template.delete');
});

/**
 * Tree::add() seeds an undeclared ancestor as a headless node, and
 * Core::sortItems() drops headless nodes together with their subtree — so a
 * permission whose parent group is missing silently vanishes from the role form.
 */
it('declares a group entry for every ancestor of an acl key', function (): void {
    $keys = collect(config('acl'))->pluck('key')->unique();

    $orphans = $keys->filter(function (string $key) use ($keys): bool {
        $parts = explode('.', $key);
        array_pop($parts);

        return $parts !== [] && ! $keys->contains(implode('.', $parts));
    })->values();

    expect($orphans)->toBeEmpty();
});

it('keeps the passport template permissions in the built acl tree', function (): void {
    $passport = app('acl')->items['catalog']['children']['passport'] ?? [];

    expect(array_keys($passport['children']['template']['children'] ?? []))
        ->toBe(['view', 'create', 'edit', 'delete']);
});
