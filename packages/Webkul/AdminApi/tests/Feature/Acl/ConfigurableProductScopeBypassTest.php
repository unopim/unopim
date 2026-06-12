<?php

use Illuminate\Support\Facades\Route;

/**
 * Regression coverage for the fail-open API scope check (audit finding #2).
 *
 * On 2.0 every existing mutating API route is already mapped in api-acl.php, so
 * the originally-reported configurable-product bypass is not reachable on this
 * branch. What this test pins down is the underlying mechanism that the fix
 * hardens: ScopeMiddleware must fail *closed* — any state-changing
 * (POST/PUT/PATCH/DELETE) API route that is NOT present in api-acl.php must be
 * rejected (403), never silently allowed.
 *
 * Before the fix, an unmapped mutating route fell through to the controller
 * (fail-open). After the fix it is forbidden.
 */
define('SCOPE_FAILCLOSED_PROBE_URI', 'v1/rest/_scope_failclosed_probe');

function registerUnmappedWriteProbe(): void
{
    // Registered at runtime with the real API middleware stack but deliberately
    // absent from api-acl.php — i.e. an unmapped mutating route. Dispatched by
    // raw URL because the compiled route-name lookup is not refreshed in-test.
    Route::post(SCOPE_FAILCLOSED_PROBE_URI, fn () => response()->json(['ok' => true]))
        ->middleware(['auth:api', 'api.scope', 'accept.json', 'request.locale']);
}

it('keeps every existing mutating API route mapped in api-acl', function () {
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

it('fails closed: an unmapped mutating API route is forbidden, not allowed', function () {
    registerUnmappedWriteProbe();

    $headers = $this->getAuthenticationHeaders('custom', ['api.settings.locales']);

    $response = $this->withHeaders($headers)->json('POST', '/'.SCOPE_FAILCLOSED_PROBE_URI, []);

    $response->assertForbidden();
});
