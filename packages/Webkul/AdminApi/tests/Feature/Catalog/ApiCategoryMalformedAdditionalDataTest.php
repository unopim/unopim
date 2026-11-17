<?php

use Webkul\Category\Models\Category;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

dataset('malformedAdditionalData', [
    'common is a string'                => [['common' => 'not-an-object']],
    'locale_specific is a string'       => [['locale_specific' => 'not-an-object']],
    'locale_specific entry is a string' => [['locale_specific' => ['en_US' => 'not-an-object']]],
    'locale_specific entry is null'     => [['locale_specific' => ['en_US' => null]]],
]);

it('returns 422 instead of a server error for malformed additional_data on create', function (array $additionalData) {
    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.categories.store'), [
            'code'            => 'edge_category_'.uniqid(),
            'additional_data' => $additionalData,
        ])
        ->assertStatus(422);
})->with('malformedAdditionalData');

it('returns 422 instead of a server error for malformed additional_data on update', function (array $additionalData) {
    $category = Category::factory()->create();

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), [
            'additional_data' => $additionalData,
        ])
        ->assertStatus(422);
})->with('malformedAdditionalData');

it('returns 422 instead of a server error for malformed additional_data on partial update', function (array $additionalData) {
    $category = Category::factory()->create();

    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.categories.patch', ['code' => $category->code]), [
            'additional_data' => $additionalData,
        ])
        ->assertStatus(422);
})->with('malformedAdditionalData');
