<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Webkul\Tenant\Http\Middleware\TenantSafeErrorHandler;

it('converts ModelNotFoundException to normalized 404 JSON', function () {
    $middleware = new TenantSafeErrorHandler;
    $request = Request::create('/api/products/99999');

    $response = $middleware->handle($request, function () {
        throw new ModelNotFoundException('No query results for model [Product].');
    });

    expect($response->getStatusCode())->toBe(404);

    $body = json_decode($response->getContent(), true);
    expect($body['error'])->toBe('not_found');
    expect($body['message'])->toBe('The requested resource was not found.');
    // Should NOT reveal the model name
    expect($body['message'])->not->toContain('Product');
});

it('converts AuthorizationException to normalized 404 JSON', function () {
    $middleware = new TenantSafeErrorHandler;
    $request = Request::create('/api/products/1');

    $response = $middleware->handle($request, function () {
        throw new \Illuminate\Auth\Access\AuthorizationException('This action is unauthorized.');
    });

    expect($response->getStatusCode())->toBe(404);

    $body = json_decode($response->getContent(), true);
    expect($body['error'])->toBe('not_found');
    // Must NOT reveal that it was an authorization error
    expect($body['message'])->not->toContain('unauthorized');
});

it('passes through normal responses unchanged', function () {
    $middleware = new TenantSafeErrorHandler;
    $request = Request::create('/api/products');

    $response = $middleware->handle($request, function () {
        return new \Illuminate\Http\Response('OK', 200);
    });

    expect($response->getStatusCode())->toBe(200);
    expect($response->getContent())->toBe('OK');
});

it('converts NotFoundHttpException to normalized 404', function () {
    $middleware = new TenantSafeErrorHandler;
    $request = Request::create('/nonexistent');

    $response = $middleware->handle($request, function () {
        throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException('Not Found');
    });

    expect($response->getStatusCode())->toBe(404);
    $body = json_decode($response->getContent(), true);
    expect($body['error'])->toBe('not_found');
});

it('enforces minimum response time for error responses', function () {
    $middleware = new TenantSafeErrorHandler;
    $request = Request::create('/api/products/99999');

    $start = hrtime(true);
    $middleware->handle($request, function () {
        throw new ModelNotFoundException;
    });
    $elapsed = (hrtime(true) - $start) / 1_000_000;

    // Should take at least ~50ms (the timing floor)
    expect($elapsed)->toBeGreaterThanOrEqual(45); // Allow small margin
});
