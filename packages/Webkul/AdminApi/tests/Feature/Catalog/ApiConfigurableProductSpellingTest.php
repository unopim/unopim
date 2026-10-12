<?php

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should expose POST store on the correctly spelled configurable-products URL', function () {
    // Without a valid payload the route should still RESOLVE — response 422 proves the route is wired.
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/rest/configurable-products', []);

    expect(in_array($response->status(), [422, 201]))->toBeTrue("Got {$response->status()}");
});

it('should keep supporting the legacy configrable-products spelling', function () {
    $response = $this->withHeaders($this->headers)->postJson('/api/v1/rest/configrable-products', []);

    expect(in_array($response->status(), [422, 201]))->toBeTrue("Got {$response->status()}");
});
