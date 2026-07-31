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
