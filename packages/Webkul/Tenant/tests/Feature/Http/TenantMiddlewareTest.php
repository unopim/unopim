<?php

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Webkul\Tenant\Http\Middleware\TenantMiddleware;
use Webkul\Tenant\Models\Tenant;

/*
|--------------------------------------------------------------------------
| TenantMiddleware Tests
|--------------------------------------------------------------------------
|
| Verifies the three-strategy resolution chain (subdomain → header → token)
| and lifecycle status enforcement (suspended/deleting/deleted → 503).
|
*/

function callMiddleware(Request $request): Response
{
    $middleware = new TenantMiddleware;

    return $middleware->handle($request, function ($req) {
        return new Response('OK', 200);
    });
}

beforeEach(function () {
    // Clear tenant context set by TenantTestCase::setUp() so middleware
    // tests start from a clean slate and only rely on resolution strategies.
    core()->setCurrentTenantId(null);
});

// -- Subdomain resolution --------------------------------------------------

it('resolves tenant from subdomain', function () {
    config(['app.url' => 'http://app.example.com']);

    $request = Request::create('http://app.example.com/admin');
    $request->headers->set('HOST', $this->tenantA->domain.'.app.example.com');

    $response = callMiddleware($request);

    expect($response->getStatusCode())->toBe(200);
    expect(core()->getCurrentTenantId())->toBe($this->tenantA->id);
});

it('does not resolve tenant when host matches app host exactly', function () {
    config(['app.url' => 'http://app.example.com']);

    $request = Request::create('http://app.example.com/admin');
    $request->headers->set('HOST', 'app.example.com');

    $response = callMiddleware($request);

    expect($response->getStatusCode())->toBe(200);
    expect(core()->getCurrentTenantId())->toBeNull();
});

// -- Header resolution -----------------------------------------------------

it('resolves tenant from X-Tenant-ID header when user is authenticated', function () {
    config(['app.url' => 'http://localhost']);

    // Header resolution requires an authenticated user (security fix)
    $admin = \Webkul\User\Models\Admin::withoutGlobalScopes()->find($this->fixture($this->tenantB, 'admin_id'));
    auth('admin')->login($admin);

    $request = Request::create('http://localhost/api/v1/products');
    $request->headers->set('X-Tenant-ID', (string) $this->tenantB->id);

    $response = callMiddleware($request);

    expect($response->getStatusCode())->toBe(200);
    expect(core()->getCurrentTenantId())->toBe($this->tenantB->id);

    auth('admin')->logout();
});

it('returns null for invalid X-Tenant-ID header', function () {
    config(['app.url' => 'http://localhost']);

    $request = Request::create('http://localhost/api/v1/products');
    $request->headers->set('X-Tenant-ID', '99999');

    $response = callMiddleware($request);

    expect($response->getStatusCode())->toBe(200);
    expect(core()->getCurrentTenantId())->toBeNull();
});

// -- Priority chain --------------------------------------------------------

it('prefers subdomain over header when both present', function () {
    config(['app.url' => 'http://app.example.com']);

    $request = Request::create('http://app.example.com/admin');
    $request->headers->set('HOST', $this->tenantA->domain.'.app.example.com');
    $request->headers->set('X-Tenant-ID', (string) $this->tenantB->id);

    callMiddleware($request);

    // Subdomain wins
    expect(core()->getCurrentTenantId())->toBe($this->tenantA->id);
});

// -- Lifecycle status enforcement ------------------------------------------

it('returns 503 for suspended tenant', function () {
    $this->tenantA->update(['status' => Tenant::STATUS_SUSPENDED]);

    config(['app.url' => 'http://app.example.com']);
    $request = Request::create('http://app.example.com/admin');
    $request->headers->set('HOST', $this->tenantA->domain.'.app.example.com');

    callMiddleware($request);
})->throws(HttpException::class, 'Tenant is not available.');

it('returns 503 for deleting tenant', function () {
    $this->tenantA->update(['status' => Tenant::STATUS_DELETING]);

    config(['app.url' => 'http://app.example.com']);
    $request = Request::create('http://app.example.com/admin');
    $request->headers->set('HOST', $this->tenantA->domain.'.app.example.com');

    callMiddleware($request);
})->throws(HttpException::class, 'Tenant is not available.');

it('returns 503 for deleted tenant', function () {
    // Soft-deleted tenants are still findable via subdomain lookup.
    // This test covers the status check for the 'deleted' status.
    $this->tenantA->update(['status' => Tenant::STATUS_DELETED]);

    config(['app.url' => 'http://app.example.com']);
    $request = Request::create('http://app.example.com/admin');
    $request->headers->set('HOST', $this->tenantA->domain.'.app.example.com');

    callMiddleware($request);
})->throws(HttpException::class, 'Tenant is not available.');

// -- Platform bypass -------------------------------------------------------

it('proceeds without tenant context when no resolution succeeds', function () {
    config(['app.url' => 'http://localhost']);

    $request = Request::create('http://localhost/admin');
    // No subdomain, no header, no authenticated user

    $response = callMiddleware($request);

    expect($response->getStatusCode())->toBe(200);
    expect(core()->getCurrentTenantId())->toBeNull();
});

it('allows active tenant through', function () {
    config(['app.url' => 'http://app.example.com']);

    $request = Request::create('http://app.example.com/admin');
    $request->headers->set('HOST', $this->tenantA->domain.'.app.example.com');

    $response = callMiddleware($request);

    expect($response->getStatusCode())->toBe(200);
    expect(core()->getCurrentTenantId())->toBe($this->tenantA->id);
});
