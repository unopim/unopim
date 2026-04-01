<?php

use Webkul\Core\Models\Locale;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should return paginated locales with limit parameter', function () {
    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.locales.index', ['limit' => 2]))
        ->assertOk()
        ->assertJsonStructure([
            'data',
            'current_page',
            'last_page',
            'total',
            'links',
        ])
        ->assertJsonCount(2, 'data');
});

it('should return second page of locales', function () {
    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.locales.index', ['limit' => 1, 'page' => 2]))
        ->assertOk()
        ->assertJsonFragment(['current_page' => 2])
        ->assertJsonCount(1, 'data');
});

it('should return paginated attributes with limit', function () {
    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.attributes.index', ['limit' => 5]))
        ->assertOk()
        ->assertJsonStructure([
            'data',
            'current_page',
            'last_page',
            'total',
            'links',
        ]);

    $data = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.attributes.index', ['limit' => 5]))
        ->json('data');

    $this->assertLessThanOrEqual(5, count($data));
});

it('should return paginated categories with limit', function () {
    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.categories.index', ['limit' => 5]))
        ->assertOk()
        ->assertJsonStructure([
            'data',
            'current_page',
            'last_page',
            'total',
            'links',
        ]);
});

it('should return paginated products with limit', function () {
    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.products.index', ['limit' => 5]))
        ->assertOk()
        ->assertJsonStructure([
            'data',
            'current_page',
            'last_page',
            'total',
            'links',
        ]);
});

it('should filter locales with equals operator', function () {
    $filters = json_encode([
        'status' => [
            ['operator' => '=', 'value' => '1'],
        ],
    ]);

    $response = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.locales.index', ['filters' => $filters]))
        ->assertOk();

    $data = $response->json('data');

    collect($data)->each(function ($locale) {
        expect($locale['status'])->toBe(1);
    });
});

it('should filter locales with status equals false', function () {
    $filters = json_encode([
        'status' => [
            ['operator' => '=', 'value' => '0'],
        ],
    ]);

    $response = $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.locales.index', ['filters' => $filters]))
        ->assertOk();

    $inactiveCount = Locale::where('status', 0)->count();

    $response->assertJsonFragment(['total' => $inactiveCount]);
});

it('should filter currencies with status equals true', function () {
    $filters = json_encode([
        'status' => [
            ['operator' => '=', 'value' => '1'],
        ],
    ]);

    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.currencies.index', ['filters' => $filters]))
        ->assertOk()
        ->assertJsonStructure([
            'data',
            'current_page',
            'total',
        ]);
});

it('should return error for unsupported filter field on channels', function () {
    $filters = json_encode([
        'code' => [
            ['operator' => '=', 'value' => 'default'],
        ],
    ]);

    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.channels.index', ['filters' => $filters]))
        ->assertBadRequest()
        ->assertJsonStructure(['error']);
});

it('should return error for invalid filter format', function () {
    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.locales.index', ['filters' => 'invalid-json']))
        ->assertBadRequest()
        ->assertJsonStructure(['error']);
});

it('should return empty data for filter matching nothing', function () {
    $filters = json_encode([
        'code' => [
            ['operator' => '=', 'value' => 'nonexistent_locale_xyz'],
        ],
    ]);

    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.locales.index', ['filters' => $filters]))
        ->assertOk()
        ->assertJsonFragment(['total' => 0]);
});
