<?php

use Illuminate\Support\Facades\Route;

it('constrains the completeness {family_id} route param to numeric so non-numeric ids 404', function () {
    $route = Route::getRoutes()->getByName('admin.catalog.families.completeness.edit');

    expect($route)->not->toBeNull();
    expect($route->wheres['family_id'] ?? null)->toBe('[0-9]+');
});

it('registers completeness routes with the web group so the session is started before the admin guard', function () {
    foreach ([
        'admin.catalog.families.completeness.edit',
        'admin.catalog.families.completeness.update',
        'admin.catalog.families.completeness.mass_update',
    ] as $name) {
        $route = Route::getRoutes()->getByName($name);

        expect($route)->not->toBeNull();
        expect($route->gatherMiddleware())->toContain('web');
    }
});
