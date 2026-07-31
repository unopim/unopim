<?php

use Illuminate\Support\Facades\Route;

it('constrains the webhook logs {id} route param to numeric so non-numeric ids 404 instead of throwing a 500 TypeError', function (string $name) {
    $route = Route::getRoutes()->getByName($name);

    expect($route)->not->toBeNull();
    expect($route->wheres['id'] ?? null)->toBe('[0-9]+');
})->with([
    'webhook.logs.show',
    'webhook.logs.delete',
]);
