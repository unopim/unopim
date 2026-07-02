<?php

use Illuminate\Support\Facades\Route;

/**
 * Regression coverage for the fail-open API scope bypass.
 *
 * The configurable-product mutating routes were registered but absent from
 * api-acl.php, so ScopeMiddleware short-circuited and allowed them with no
 * scope. The fix maps every configurable-product route (both the correct
 * "configurable_products" prefix and the legacy "configrable_products" typo)
 * and makes ScopeMiddleware fail closed for any unmapped state-changing route.
 */
it('maps every mutating API scope route in api-acl so no write route fails open', function () {
    $roles = app('api-acl')->roles;

    $unmapped = [];

    foreach (Route::getRoutes() as $route) {
        $name = $route->getName();

        if (! $name || ! str_starts_with($name, 'admin.api.')) {
            continue;
        }

        $hasScope = collect($route->gatherMiddleware())
            ->contains(fn ($m) => str_contains($m, 'ScopeMiddleware') || $m === 'api.scope');

        $isWrite = (bool) array_intersect($route->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']);

        if ($hasScope && $isWrite && ! isset($roles[str_replace('.get', '.index', $name)])) {
            $unmapped[] = $name;
        }
    }

    expect($unmapped)->toBe([]);
});

dataset('configurable write routes', [
    'store (correct prefix)'  => ['POST', 'admin.api.configurable_products.store', null],
    'update (correct prefix)' => ['PUT', 'admin.api.configurable_products.update', 'sku-x'],
    'patch (correct prefix)'  => ['PATCH', 'admin.api.configurable_products.patch', 'sku-x'],
    'delete (correct prefix)' => ['DELETE', 'admin.api.configurable_products.delete', 'sku-x'],
    'delete (legacy typo)'    => ['DELETE', 'admin.api.configrable_products.delete', 'sku-x'],
    'store (legacy typo)'     => ['POST', 'admin.api.configrable_products.store', null],
]);

it('forbids configurable-product writes for a key without the product scope', function (string $method, string $routeName, ?string $param) {
    $headers = $this->getAuthenticationHeaders('custom', ['api.settings.locales']);

    $url = $param ? route($routeName, $param) : route($routeName);

    $this->withHeaders($headers)->json($method, $url)->assertForbidden();
})->with('configurable write routes');

it('allows a configurable-product write past the scope check when the key holds the product scope', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.products.create']);

    $response = $this->withHeaders($headers)->json('POST', route('admin.api.configurable_products.store'), []);

    expect($response->getStatusCode())->not->toBe(403);
});
