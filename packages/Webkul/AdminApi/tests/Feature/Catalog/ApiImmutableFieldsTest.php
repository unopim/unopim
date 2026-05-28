<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Category\Models\CategoryField;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should reject PUT on a category field when body includes the immutable type field (Issue #730)', function () {
    $field = CategoryField::where('type', 'text')->first() ?? CategoryField::factory()->create(['type' => 'text']);

    $response = $this->withHeaders($this->headers)
        ->putJson(route('admin.api.category-fields.update', ['code' => $field->code]), [
            'type'   => 'file',
            'status' => 1,
        ]);

    $response->assertStatus(422);
});

it('should reject PUT on an attribute when body includes the immutable type field (Issue #730)', function () {
    $attribute = Attribute::where('type', 'text')->first() ?? Attribute::factory()->create(['type' => 'text']);

    $response = $this->withHeaders($this->headers)
        ->putJson(route('admin.api.attributes.update', ['code' => $attribute->code]), [
            'type'   => 'textarea',
            'status' => 1,
        ]);

    $response->assertStatus(422);
});
