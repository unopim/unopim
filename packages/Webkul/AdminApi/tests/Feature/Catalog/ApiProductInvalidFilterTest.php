<?php

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('returns 422 when filtering products by an invalid parent SKU', function () {
    $filters = json_encode(['parent' => [['operator' => '=', 'value' => 'root']]]);

    $response = $this->withHeaders($this->headers)->getJson('/api/v1/rest/products?filters='.urlencode($filters));

    expect($response->status())->toBe(422);
});
