<?php

use Webkul\Category\Models\Category;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('filters categories with the NOT IN operator without a server error', function () {
    Category::factory()->create(['code' => 'not_in_filter_category']);

    $filters = json_encode([
        'code' => [
            ['operator' => 'NOT IN', 'value' => ['nonexistent_code_xyz']],
        ],
    ]);

    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.categories.index', ['filters' => $filters]))
        ->assertOk()
        ->assertJsonStructure(['data', 'current_page', 'total']);
});

it('returns 422 instead of 500 when attribute family attribute_groups is not an array', function () {
    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.families.store'), [
            'code'             => 'malformed_family_'.uniqid(),
            'attribute_groups' => 'not-an-array',
        ])
        ->assertUnprocessable()
        ->assertJsonStructure(['errors' => ['attribute_groups']]);
});

it('returns 422 instead of 500 when category additional_data is not an array', function () {
    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.categories.store'), [
            'code'            => 'malformed_category_'.uniqid(),
            'additional_data' => 'not-an-array',
        ])
        ->assertUnprocessable()
        ->assertJsonStructure(['errors' => ['additional_data']]);
});

it('accepts a charset-qualified application/json Accept header', function () {
    $headers = array_merge($this->headers, ['Accept' => 'application/json; charset=utf-8']);

    $this->withHeaders($headers)
        ->json('GET', route('admin.api.locales.index'))
        ->assertOk();
});
