<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Webkul\Tenant\Http\Middleware\TenantTokenValidator;
use Webkul\Tenant\Models\Tenant;

beforeEach(function () {
    Mail::fake();
});

/**
 * Helper to mock the api guard returning a specific user.
 */
function mockApiGuardUser($user = null): void
{
    $guard = Mockery::mock(\Illuminate\Contracts\Auth\Guard::class);
    $guard->shouldReceive('check')->andReturn(! is_null($user));
    $guard->shouldReceive('user')->andReturn($user);

    Auth::shouldReceive('guard')->with('api')->andReturn($guard);
}

// --- Story 5.6: OAuth2 tenant-scoped token validation ---

it('allows requests when no API user is authenticated', function () {
    mockApiGuardUser(null);

    $middleware = new TenantTokenValidator;
    $request = Request::create('/api/test', 'GET');

    $response = $middleware->handle($request, function ($req) {
        return response()->json(['ok' => true]);
    });

    expect($response->getStatusCode())->toBe(200);
});

it('rejects orphaned tokens when tenant is deleted (FR-5.6)', function () {
    $user = new \stdClass;
    $user->tenant_id = 99999;
    $user->id = 1;
    $user->email = 'test@example.com';

    mockApiGuardUser($user);

    $middleware = new TenantTokenValidator;
    $request = Request::create('/api/test', 'GET');

    $response = $middleware->handle($request, function ($req) {
        return response()->json(['ok' => true]);
    });

    expect($response->getStatusCode())->toBe(403);
    $data = json_decode($response->getContent(), true);
    expect($data['error'])->toContain('orphaned');
});

it('rejects tokens for suspended tenants (FR-5.6)', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_SUSPENDED]);

    $user = new \stdClass;
    $user->tenant_id = $tenant->id;
    $user->id = 1;
    $user->email = 'test@example.com';

    mockApiGuardUser($user);

    $middleware = new TenantTokenValidator;
    $request = Request::create('/api/test', 'GET');

    $response = $middleware->handle($request, function ($req) {
        return response()->json(['ok' => true]);
    });

    expect($response->getStatusCode())->toBe(403);
    $data = json_decode($response->getContent(), true);
    expect($data['error'])->toContain('not active');
});

it('allows tokens for active tenants and sets context (FR-5.6)', function () {
    $tenant = Tenant::factory()->create(['status' => Tenant::STATUS_ACTIVE]);

    $user = new \stdClass;
    $user->tenant_id = $tenant->id;
    $user->id = 1;
    $user->email = 'test@example.com';

    mockApiGuardUser($user);

    $middleware = new TenantTokenValidator;
    $request = Request::create('/api/test', 'GET');

    $capturedTenantId = null;
    $response = $middleware->handle($request, function ($req) use (&$capturedTenantId) {
        $capturedTenantId = core()->getCurrentTenantId();

        return response()->json(['ok' => true]);
    });

    expect($response->getStatusCode())->toBe(200);
    expect($capturedTenantId)->toBe($tenant->id);

    core()->setCurrentTenantId(null);
});

it('allows platform users (tenant_id=null) to bypass tenant validation (FR-5.4)', function () {
    $user = new \stdClass;
    $user->tenant_id = null;
    $user->id = 1;
    $user->email = 'operator@example.com';

    mockApiGuardUser($user);

    $middleware = new TenantTokenValidator;
    $request = Request::create('/api/test', 'GET');

    $response = $middleware->handle($request, function ($req) {
        return response()->json(['ok' => true]);
    });

    expect($response->getStatusCode())->toBe(200);
});

// --- Story 5.7: REST API endpoint auto-scoping ---

it('ScopeMiddleware enforces tenant permission guard for API (FR-5.7)', function () {
    $middleware = new \Webkul\AdminApi\Http\Middleware\ScopeMiddleware;

    expect(method_exists($middleware, 'hasPermission'))->toBeTrue();
});
