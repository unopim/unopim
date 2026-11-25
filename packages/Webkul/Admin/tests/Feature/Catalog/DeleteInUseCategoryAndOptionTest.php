<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Category\Models\Category;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();
});

/**
 * @param  array<string, mixed>  $values
 */
function productWithValues(array $values): Product
{
    $product = Product::factory()->create();

    $values['common']['sku'] = $product->sku;

    $product->values = $values;
    $product->save();

    return $product;
}

it('refuses to delete a category assigned to a product', function () {
    $category = Category::factory()->create();

    productWithValues(['categories' => [$category->code]]);

    $this->deleteJson(route('admin.catalog.categories.delete', $category->id))
        ->assertBadRequest()
        ->assertJsonFragment(['message' => trans('admin::app.catalog.categories.delete-category-in-use')]);

    $this->assertDatabaseHas($this->getFullTableName(Category::class), ['id' => $category->id]);
});

it('refuses to delete a category whose child category is assigned to a product', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    productWithValues(['categories' => [$child->code]]);

    $this->deleteJson(route('admin.catalog.categories.delete', $parent->id))->assertBadRequest();

    $this->assertDatabaseHas($this->getFullTableName(Category::class), ['id' => $parent->id]);
    $this->assertDatabaseHas($this->getFullTableName(Category::class), ['id' => $child->id]);
});

it('refuses to mass delete categories when one is assigned to a product', function () {
    $category = Category::factory()->create();

    productWithValues(['categories' => [$category->code]]);

    $this->postJson(route('admin.catalog.categories.mass_delete', ['indices' => [$category->id]]))
        ->assertBadRequest()
        ->assertJsonFragment(['message' => trans('admin::app.catalog.categories.delete-category-in-use')]);

    $this->assertDatabaseHas($this->getFullTableName(Category::class), ['id' => $category->id]);
});

it('still deletes a category no product uses', function () {
    $category = Category::factory()->create();

    productWithValues(['categories' => [Category::factory()->create()->code]]);

    $this->deleteJson(route('admin.catalog.categories.delete', $category->id))->assertOk();

    $this->assertDatabaseMissing($this->getFullTableName(Category::class), ['id' => $category->id]);
});

it('refuses to delete an attribute option used by a product', function (string $type, string $scope) {
    $attribute = Attribute::factory()->create(['type' => $type]);
    $option = $attribute->options()->first();

    $value = $type === 'select' ? $option->code : [$option->code];

    productWithValues(match ($scope) {
        'common'                  => ['common' => [$attribute->code => $value]],
        'channel_locale_specific' => ['channel_locale_specific' => ['default' => ['en_US' => [$attribute->code => $value]]]],
    });

    $this->deleteJson(route('admin.catalog.attributes.options.delete', [$attribute->id, $option->id]))
        ->assertBadRequest()
        ->assertJsonFragment(['message' => trans('admin::app.catalog.attributes.edit.option.delete-in-use')]);

    $this->assertDatabaseHas($this->getFullTableName(AttributeOption::class), ['id' => $option->id]);
})->with([
    'select in common'                  => ['select', 'common'],
    'multiselect in common'             => ['multiselect', 'common'],
    'checkbox in channel locale values' => ['checkbox', 'channel_locale_specific'],
]);

it('still deletes an attribute option whose code only appears in another attribute', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $option = $attribute->options()->first();

    productWithValues(['common' => ['other_attribute' => $option->code]]);

    $this->deleteJson(route('admin.catalog.attributes.options.delete', [$attribute->id, $option->id]))->assertOk();

    $this->assertDatabaseMissing($this->getFullTableName(AttributeOption::class), ['id' => $option->id]);
});
