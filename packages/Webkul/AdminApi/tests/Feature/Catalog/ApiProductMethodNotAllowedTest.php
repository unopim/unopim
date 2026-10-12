<?php

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('rejects PATCH on the products listing endpoint with 405 Method Not Allowed', function () {
    $response = $this->withHeaders($this->headers)
        ->call('PATCH', '/api/v1/rest/products', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

    expect($response->status())->toBe(405);
    expect($response->headers->get('Allow'))->not->toBeNull();
});

it('rejects PATCH on the products listing endpoint with trailing slash', function () {
    $response = $this->withHeaders($this->headers)
        ->call('PATCH', '/api/v1/rest/products/', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

    expect($response->status())->toBe(405);
});

it('rejects DELETE on the products listing endpoint with 405', function () {
    $response = $this->withHeaders($this->headers)
        ->call('DELETE', '/api/v1/rest/products', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

    expect($response->status())->toBe(405);
    expect($response->headers->get('Allow'))->not->toBeNull();
});
