<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Category\Models\Category;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

/**
 * @param  array<string, mixed>  $values
 */
function apiProductReferencingCatalogValues(array $values): Product
{
    $product = Product::factory()->create();

    $values['common']['sku'] = $product->sku;

    $product->values = $values;
    $product->save();

    return $product;
}

it('refuses to delete a category assigned to a product', function () {
    $category = Category::factory()->create();

    apiProductReferencingCatalogValues(['categories' => [$category->code]]);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.categories.delete', ['code' => $category->code]))
        ->assertBadRequest()
        ->assertJsonFragment([
            'success' => false,
            'message' => trans('admin::app.catalog.categories.delete-category-in-use'),
        ]);

    $this->assertDatabaseHas($this->getFullTableName(Category::class), ['id' => $category->id]);
});

it('refuses to delete an attribute option used by a product', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $option = $attribute->options()->first();

    apiProductReferencingCatalogValues(['common' => [$attribute->code => $option->code]]);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.attribute_options.delete_option', [$attribute->code, $option->code]))
        ->assertBadRequest()
        ->assertJsonFragment([
            'success' => false,
            'message' => trans('admin::app.catalog.attributes.edit.option.delete-in-use'),
        ]);

    $this->assertDatabaseHas($this->getFullTableName(AttributeOption::class), ['id' => $option->id]);
});

it('refuses to remove a used option through the attribute update', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $option = $attribute->options()->first();

    apiProductReferencingCatalogValues(['common' => [$attribute->code => $option->code]]);

    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.attributes.patch', ['code' => $attribute->code]), [
            'options' => [$option->id => ['isNew' => 'false', 'isDelete' => 'true']],
        ])
        ->assertUnprocessable();

    $this->assertDatabaseHas($this->getFullTableName(AttributeOption::class), ['id' => $option->id]);
});
