<?php

use Webkul\Category\Models\CategoryField;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
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
