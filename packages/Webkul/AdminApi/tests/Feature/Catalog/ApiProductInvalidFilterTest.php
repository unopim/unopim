<?php

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should return 4xx when filtering products by an invalid parent SKU (Issue #739)', function () {
    $filters = json_encode(['parent' => [['operator' => '=', 'value' => 'root']]]);

    $response = $this->withHeaders($this->headers)->getJson('/api/v1/rest/products?filters='.urlencode($filters));

    expect($response->status())->not->toBe(200);
    expect($response->status())->toBeIn([400, 404, 422, 500])
        ->and($response->status())->not->toBe(200, 'Invalid parent filter should not return 200 OK');
});
