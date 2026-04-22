<?php

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should reject PATCH on the products listing endpoint with 405 Method Not Allowed (Issue #742)', function () {
    $response = $this->withHeaders($this->headers)
        ->call('PATCH', '/api/v1/rest/products', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

    expect($response->status())->toBeIn([404, 405]);
    expect($response->status())->not->toBe(200);
});

it('should reject PATCH on the products listing endpoint with trailing slash (Issue #742)', function () {
    $response = $this->withHeaders($this->headers)
        ->call('PATCH', '/api/v1/rest/products/', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

    expect($response->status())->not->toBe(200);
});

it('should reject DELETE on the products listing endpoint (Issue #741)', function () {
    $response = $this->withHeaders($this->headers)
        ->call('DELETE', '/api/v1/rest/products', [], [], [], [
            'HTTP_ACCEPT' => 'application/json',
        ]);

    expect($response->status())->toBeIn([404, 405]);
    expect($response->status())->not->toBe(200);
});
