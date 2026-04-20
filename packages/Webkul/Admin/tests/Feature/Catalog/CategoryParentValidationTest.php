<?php

use Webkul\Category\Models\Category;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('should reject saving when a grandchild is set as the parent category', function () {
    // A (root) → B (child of A) → C (grandchild of A)
    $a = Category::factory()->create(['parent_id' => null]);
    $b = Category::factory()->create(['parent_id' => $a->id]);
    $c = Category::factory()->create(['parent_id' => $b->id]);

    $response = $this->put(route('admin.catalog.categories.update', $a->id), [
        'locale'    => core()->getDefaultLocaleCodeFromDefaultChannel(),
        'parent_id' => $c->id,
    ]);

    $response->assertSessionHas('error', trans('admin::app.catalog.categories.invalid-parent'));
    $this->assertDatabaseHas('categories', ['id' => $a->id, 'parent_id' => null]);
});

it('should reject saving when a direct child is set as the parent category', function () {
    $parent = Category::factory()->create(['parent_id' => null]);
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    $response = $this->put(route('admin.catalog.categories.update', $parent->id), [
        'locale'    => core()->getDefaultLocaleCodeFromDefaultChannel(),
        'parent_id' => $child->id,
    ]);

    $response->assertSessionHas('error', trans('admin::app.catalog.categories.invalid-parent'));
    $this->assertDatabaseHas('categories', ['id' => $parent->id, 'parent_id' => null]);
});

it('should reject saving when a category is set as its own parent', function () {
    $category = Category::factory()->create(['parent_id' => null]);

    $response = $this->put(route('admin.catalog.categories.update', $category->id), [
        'locale'    => core()->getDefaultLocaleCodeFromDefaultChannel(),
        'parent_id' => $category->id,
    ]);

    $response->assertSessionHas('error', trans('admin::app.catalog.categories.invalid-parent'));
    $this->assertDatabaseHas('categories', ['id' => $category->id, 'parent_id' => null]);
});

it('should allow saving when a valid non-descendant category is set as parent', function () {
    $sibling = Category::factory()->create(['parent_id' => null]);
    $category = Category::factory()->create(['parent_id' => null]);

    $response = $this->put(route('admin.catalog.categories.update', $category->id), [
        'locale'    => core()->getDefaultLocaleCodeFromDefaultChannel(),
        'parent_id' => $sibling->id,
    ]);

    $response->assertSessionHas('success');
    $this->assertDatabaseHas('categories', ['id' => $category->id, 'parent_id' => $sibling->id]);
});
