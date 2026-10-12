<?php

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('marks the legacy configrable-products alias as deprecated', function () {
    $response = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.configrable_products.index'))
        ->assertOk();

    expect($response->headers->get('Deprecation'))->toBe('true');
    expect($response->headers->get('Link'))->toContain('rel="successor-version"');
});

it('does not mark the correct configurable-products route as deprecated', function () {
    $response = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.configurable_products.index'))
        ->assertOk();

    expect($response->headers->get('Deprecation'))->toBeNull();
});
