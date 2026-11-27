<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Category\Models\Category;

beforeEach(function (): void {
    $this->headers = $this->getAuthenticationHeaders();
});

it('refuses to delete a category assigned to a product', function (): void {
    $category = Category::factory()->create();

    productReferencingCatalogValues(['categories' => [$category->code]]);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.categories.delete', ['code' => $category->code]))
        ->assertBadRequest()
        ->assertJsonFragment([
            'success' => false,
            'message' => trans('admin::app.catalog.categories.delete-category-in-use'),
        ]);

    $this->assertDatabaseHas($this->getFullTableName(Category::class), ['id' => $category->id]);
});

it('refuses to delete an attribute option used by a product', function (): void {
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $option = $attribute->options()->first();

    productReferencingCatalogValues(['common' => [$attribute->code => $option->code]]);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.attribute_options.delete_option', [$attribute->code, $option->code]))
        ->assertBadRequest()
        ->assertJsonFragment([
            'success' => false,
            'message' => trans('admin::app.catalog.attributes.edit.option.delete-in-use'),
        ]);

    $this->assertDatabaseHas($this->getFullTableName(AttributeOption::class), ['id' => $option->id]);
});

it('refuses to remove a used option through the attribute update', function (): void {
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $option = $attribute->options()->first();

    productReferencingCatalogValues(['common' => [$attribute->code => $option->code]]);

    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.attributes.patch', ['code' => $attribute->code]), [
            'options' => [$option->id => ['isNew' => 'false', 'isDelete' => 'true']],
        ])
        ->assertUnprocessable();

    $this->assertDatabaseHas($this->getFullTableName(AttributeOption::class), ['id' => $option->id]);
});

it('refuses to remove a used option sent with a numeric isNew flag', function (): void {
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $option = $attribute->options()->first();

    productReferencingCatalogValues(['common' => [$attribute->code => $option->code]]);

    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.attributes.patch', ['code' => $attribute->code]), [
            'options' => [$option->id => ['isNew' => 1, 'isDelete' => 'true']],
        ])
        ->assertUnprocessable();

    $this->assertDatabaseHas($this->getFullTableName(AttributeOption::class), ['id' => $option->id]);
});
