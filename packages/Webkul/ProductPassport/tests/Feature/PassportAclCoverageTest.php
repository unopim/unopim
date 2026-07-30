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
        ->toHaveKey('admin.catalog.passports.withdraw', 'catalog.passport.withdraw');
});
