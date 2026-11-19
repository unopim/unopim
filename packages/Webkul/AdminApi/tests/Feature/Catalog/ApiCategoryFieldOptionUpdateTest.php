<?php

use Webkul\Category\Models\CategoryField;
use Webkul\Category\Models\CategoryFieldOption;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should store a single category field option sent as one object instead of an array', function () {
    $field = CategoryField::factory()->create(['type' => 'select']);

    $code = 'single_option_'.uniqid();

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.category-fields-options.store_option', ['code' => $field->code]), [
            'code'       => $code,
            'sort_order' => 1,
        ])
        ->assertCreated()
        ->assertJsonFragment(['success' => true]);

    $this->assertDatabaseHas('category_field_options', [
        'category_field_id' => $field->id,
        'code'              => $code,
    ]);
});

it('should update a single category field option sent as one object instead of an array', function () {
    $field = CategoryField::factory()->create(['type' => 'select']);

    $option = CategoryFieldOption::factory()->create([
        'category_field_id' => $field->id,
        'code'              => 'single_update_'.uniqid(),
    ]);

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.category-fields-options.update_option', ['code' => $field->code]), [
            'code'       => $option->code,
            'sort_order' => 5,
        ])
        ->assertOk()
        ->assertJsonFragment(['success' => true]);

    $this->assertDatabaseHas('category_field_options', [
        'id'         => $option->id,
        'sort_order' => 5,
    ]);
});

it('should reject unknown option codes when updating category field options (Issue #732)', function () {
    $field = CategoryField::where('type', 'select')->first() ?? CategoryField::factory()->create(['type' => 'select']);

    $response = $this->withHeaders($this->headers)
        ->putJson(
            route('admin.api.category-fields-options.update_option', ['code' => $field->code]),
            [
                ['code' => 'nonexistent-option-'.uniqid(), 'label' => 'Whatever'],
            ]
        );

    $response->assertStatus(422);
});

it('should return validation errors when category field options are sent as a list of strings on store', function () {
    $field = CategoryField::factory()->create(['type' => 'select']);

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.category-fields-options.store_option', ['code' => $field->code]), ['bainbridge'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['0']);

    $this->assertDatabaseMissing('category_field_options', [
        'category_field_id' => $field->id,
        'code'              => 'bainbridge',
    ]);
});

it('should return validation errors when category field options are sent as a list of strings on update', function () {
    $field = CategoryField::factory()->create(['type' => 'select']);

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.category-fields-options.update_option', ['code' => $field->code]), [1, 2])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['0', '1']);
});

it('should return required validation when a single category field option is updated without a code', function () {
    $field = CategoryField::factory()->create(['type' => 'select']);

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.category-fields-options.update_option', ['code' => $field->code]), ['sort_order' => 1])
        ->assertUnprocessable()
        ->assertJsonStructure([
            'errors' => [
                '*' => [
                    'code',
                ],
            ],
        ]);
});
