<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Category\Models\CategoryField;
use Webkul\Category\Models\CategoryFieldOption;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('deletes an attribute option', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $option = AttributeOption::factory()->create(['attribute_id' => $attribute->id]);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.attribute_options.delete_option', [$attribute->code, $option->code]))
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing($this->getFullTableName(AttributeOption::class), ['id' => $option->id]);
});

it('returns 404 deleting an unknown attribute option', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.attribute_options.delete_option', [$attribute->code, 'nope']))
        ->assertNotFound();
});

it('returns 404 deleting an option of an unknown attribute', function () {
    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.attribute_options.delete_option', ['no_attr', 'nope']))
        ->assertNotFound();
});

it('forbids attribute option delete without permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.attributes']);
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $option = AttributeOption::factory()->create(['attribute_id' => $attribute->id]);

    $this->withHeaders($headers)
        ->json('DELETE', route('admin.api.attribute_options.delete_option', [$attribute->code, $option->code]))
        ->assertForbidden();
});

it('deletes a category field option', function () {
    $field = CategoryField::factory()->create(['type' => 'select']);
    $option = CategoryFieldOption::factory()->create(['category_field_id' => $field->id]);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.category-fields-options.delete_option', [$field->code, $option->code]))
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing($this->getFullTableName(CategoryFieldOption::class), ['id' => $option->id]);
});

it('returns 404 deleting an unknown category field option', function () {
    $field = CategoryField::factory()->create(['type' => 'select']);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.category-fields-options.delete_option', [$field->code, 'nope']))
        ->assertNotFound();
});

it('rejects unauthenticated option delete', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $option = AttributeOption::factory()->create(['attribute_id' => $attribute->id]);

    $this->json('DELETE', route('admin.api.attribute_options.delete_option', [$attribute->code, $option->code]), [], [
        'Accept' => 'application/json',
    ])->assertUnauthorized();
});
