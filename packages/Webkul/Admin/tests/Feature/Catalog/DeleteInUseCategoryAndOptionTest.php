<?php

use Webkul\Attribute\Enums\SwatchTypeEnum;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Category\Models\Category;

beforeEach(function (): void {
    $this->loginAsAdmin();
});

it('refuses to delete a category assigned to a product', function (): void {
    $category = Category::factory()->create();

    productReferencingCatalogValues(['categories' => [$category->code]]);

    $this->deleteJson(route('admin.catalog.categories.delete', $category->id))
        ->assertBadRequest()
        ->assertJsonFragment(['message' => trans('admin::app.catalog.categories.delete-category-in-use')]);

    $this->assertDatabaseHas($this->getFullTableName(Category::class), ['id' => $category->id]);
});

it('refuses to delete a category whose child category is assigned to a product', function (): void {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    productReferencingCatalogValues(['categories' => [$child->code]]);

    $this->deleteJson(route('admin.catalog.categories.delete', $parent->id))->assertBadRequest();

    $this->assertDatabaseHas($this->getFullTableName(Category::class), ['id' => $parent->id]);
    $this->assertDatabaseHas($this->getFullTableName(Category::class), ['id' => $child->id]);
});

it('refuses to mass delete categories when one is assigned to a product', function (): void {
    $category = Category::factory()->create();

    productReferencingCatalogValues(['categories' => [$category->code]]);

    $this->postJson(route('admin.catalog.categories.mass_delete', ['indices' => [$category->id]]))
        ->assertBadRequest()
        ->assertJsonFragment(['message' => trans('admin::app.catalog.categories.delete-category-in-use')]);

    $this->assertDatabaseHas($this->getFullTableName(Category::class), ['id' => $category->id]);
});

it('deletes none of the selected categories when a later one is assigned to a product', function (): void {
    $unused = Category::factory()->create();
    $used = Category::factory()->create();

    productReferencingCatalogValues(['categories' => [$used->code]]);

    $this->postJson(route('admin.catalog.categories.mass_delete', ['indices' => [$unused->id, $used->id]]))
        ->assertBadRequest();

    $this->assertDatabaseHas($this->getFullTableName(Category::class), ['id' => $unused->id]);
    $this->assertDatabaseHas($this->getFullTableName(Category::class), ['id' => $used->id]);
});

it('still deletes a category no product uses', function (): void {
    $category = Category::factory()->create();

    productReferencingCatalogValues(['categories' => [Category::factory()->create()->code]]);

    $this->deleteJson(route('admin.catalog.categories.delete', $category->id))->assertOk();

    $this->assertDatabaseMissing($this->getFullTableName(Category::class), ['id' => $category->id]);
});

it('refuses to delete an attribute option used by a product', function (string $type, string $scope): void {
    $attribute = Attribute::factory()->create(['type' => $type]);
    $option = $attribute->options()->first();

    $value = $type === 'select' ? $option->code : 'other_code,'.$option->code;

    productReferencingCatalogValues(match ($scope) {
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

it('refuses to remove a used option through the attribute edit form', function (): void {
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $option = $attribute->options()->first();

    productReferencingCatalogValues(['common' => [$attribute->code => $option->code]]);

    $this->putJson(route('admin.catalog.attributes.update', $attribute->id), [
        'code'        => $attribute->code,
        'type'        => $attribute->type,
        'swatch_type' => SwatchTypeEnum::TEXT->value,
        'options'     => [$option->id => ['isNew' => 'false', 'isDelete' => 'true']],
    ])
        ->assertUnprocessable()
        ->assertJsonPath('errors.options.0', trans('admin::app.catalog.attributes.edit.option.delete-in-use'));

    $this->assertDatabaseHas($this->getFullTableName(AttributeOption::class), ['id' => $option->id]);
});

it('still deletes an attribute option whose code only appears in another attribute', function (): void {
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $option = $attribute->options()->first();

    productReferencingCatalogValues(['common' => ['other_attribute' => $option->code]]);

    $this->deleteJson(route('admin.catalog.attributes.options.delete', [$attribute->id, $option->id]))->assertOk();

    $this->assertDatabaseMissing($this->getFullTableName(AttributeOption::class), ['id' => $option->id]);
});
