<?php

use Webkul\Category\Models\CategoryField;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should reject an input validation on a non-text category field', function (string $type) {
    $data = [
        'code'    => 'api_iv_'.uniqid(),
        'type'    => $type,
        'section' => 'left',
    ];

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.category-fields.store'), $data + ['validation' => 'email'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('validation');
})->with(['textarea', 'boolean', 'select', 'multiselect', 'datetime', 'date', 'image', 'file', 'checkbox']);

it('should accept an input validation on a text category field', function () {
    $code = 'api_tv_'.uniqid();

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.category-fields.store'), [
            'code'       => $code,
            'type'       => 'text',
            'section'    => 'left',
            'validation' => 'email',
        ])
        ->assertCreated();

    expect(CategoryField::where('code', $code)->value('validation'))->toBe('email');
});

it('should reject an input validation when updating a non-text category field', function () {
    $categoryField = CategoryField::factory()->create(['type' => 'boolean', 'validation' => '']);

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.category-fields.update', $categoryField->code), [
            'section'    => 'left',
            'validation' => 'decimal',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('validation');
});

it('should accept an input validation when updating a text category field', function () {
    $categoryField = CategoryField::factory()->create(['type' => 'text', 'validation' => '']);

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.category-fields.update', $categoryField->code), [
            'section'    => 'left',
            'validation' => 'url',
        ])
        ->assertOk();

    expect($categoryField->refresh()->validation)->toBe('url');
});

it('should require a regex pattern when updating a text field to regex validation', function () {
    $categoryField = CategoryField::factory()->create(['type' => 'text', 'validation' => '']);

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.category-fields.update', $categoryField->code), [
            'section'    => 'left',
            'validation' => 'regex',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('regex_pattern');
});
