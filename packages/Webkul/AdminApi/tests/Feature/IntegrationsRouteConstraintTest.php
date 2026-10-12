<?php

use Illuminate\Support\Facades\Route;

it('constrains the api-keys {id} route param to numeric so non-numeric ids 404 instead of throwing a 500 TypeError', function (string $name) {
    $route = Route::getRoutes()->getByName($name);

    expect($route)->not->toBeNull();
    expect($route->wheres['id'] ?? null)->toBe('[0-9]+');
})->with([
    'admin.configuration.integrations.edit',
    'admin.configuration.integrations.update',
    'admin.configuration.integrations.delete',
]);
