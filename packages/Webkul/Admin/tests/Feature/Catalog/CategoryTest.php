<?php

use Webkul\Category\Models\Category;
use Webkul\Category\Models\CategoryField;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;

it('should return the category index page', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.catalog.categories.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.categories.index.title'));
});

it('should return the category create page', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.catalog.categories.create'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.categories.create.title'))
        ->assertSeeText(trans('admin::app.catalog.categories.create.save-btn'))
        ->assertSeeText(trans('admin::app.catalog.categories.create.save-btn'))
        ->assertSeeText(trans('admin::app.catalog.categories.create.code'));
});

it('should create a category successfully', function () {
    $this->loginAsAdmin();

    $localeCode = core()->getRequestedLocaleCode();

    $data = [
        'code'            => 'test_category_1_0_0',
        'parent_id'       => null,
        'additional_data' => [
            'locale_specific' => [
                $localeCode => [
                    'name' => 'Test Category',
                ],
            ],
        ],
    ];

    $this->post(route('admin.catalog.categories.store'), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.categories.create-success'))
        ->assertRedirect(route('admin.catalog.categories.index'));

    $this->assertDatabaseHas($this->getFullTableName(Category::class), [
        'code'      => 'test_category_1_0_0',
        'parent_id' => null,
    ]);
});

it('should return the category edit page', function () {
    $this->loginAsAdmin();

    $categoryId = Category::factory()->create()->id;

    $this->get(route('admin.catalog.categories.edit', $categoryId))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.categories.edit.title'))
        ->assertSeeText(trans('admin::app.catalog.categories.edit.save-btn'))
        ->assertSeeText(trans('admin::app.catalog.categories.edit.save-btn'))
        ->assertSeeText(trans('admin::app.catalog.categories.edit.code'));
});

it('should update the category successfully', function () {
    $this->loginAsAdmin();

    $rootCategoryId = Category::where('parent_id', null)->first()->id;

    $category = Category::factory()->create(['parent_id' => null]);

    $categoryId = $category->id;

    $data = [
        'parent_id'       => $rootCategoryId,
        'additional_data' => $category->additional_data,
    ];

    $this->put(route('admin.catalog.categories.update', $categoryId), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.categories.update-success'))
        ->assertRedirect(route('admin.catalog.categories.edit', ['id' => $categoryId, 'locale' => core()->getRequestedLocaleCode()]));

    $this->assertDatabaseHas($this->getFullTableName(Category::class), [
        'id'        => $categoryId,
        'code'      => $category->code,
        'parent_id' => $rootCategoryId,
    ]);
});

it('should not update the code of the category', function () {
    $this->loginAsAdmin();

    $category = Category::factory()->create(['parent_id' => null]);

    $categoryId = $category->id;

    $data = [
        'code'            => 'testing_category_2212',
        'parent_id'       => $category->parent_id,
        'additional_data' => $category->additional_data,
    ];

    $this->put(route('admin.catalog.categories.update', $categoryId), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.categories.update-success'))
        ->assertRedirect(route('admin.catalog.categories.edit', ['id' => $categoryId, 'locale' => core()->getRequestedLocaleCode()]));

    $this->assertDatabaseHas($this->getFullTableName(Category::class), [
        'id'        => $categoryId,
        'code'      => $category->code,
        'parent_id' => $data['parent_id'],
    ]);

    $this->assertDatabaseMissing($this->getFullTableName(Category::class), [
        'id'        => $categoryId,
        'code'      => 'testing_category_2212',
        'parent_id' => $data['parent_id'],
    ]);
});

it('should delete a category successfully', function () {
    $this->loginAsAdmin();

    $categoryId = Category::factory()->create(['parent_id' => null])->id;

    $this->delete(route('admin.catalog.categories.delete', $categoryId))
        ->assertOk()
        ->assertJsonFragment([
            'message' => trans('admin::app.catalog.categories.delete-success', [
                'name' => trans('admin::app.catalog.categories.category'),
            ]),
        ]);

    $this->assertDatabaseMissing($this->getFullTableName(Category::class), [
        'id' => $categoryId,
    ]);
});

it('should not delete a category linked to a channel', function () {
    $this->loginAsAdmin();

    $categoryId = Channel::first()->root_category_id;

    $this->delete(route('admin.catalog.categories.delete', $categoryId))
        ->assertBadRequest()
        ->assertJsonFragment([
            'message' => trans('admin::app.catalog.categories.delete-category-root'),
        ]);

    $this->assertDatabaseHas($this->getFullTableName(Category::class), [
        'id' => $categoryId,
    ]);
});

it('should mass delete categories successfully', function () {
    $this->loginAsAdmin();

    $categoryIds = Category::factory()->count(3)->create()->pluck('id')->toArray();

    $this->post(route('admin.catalog.categories.mass_delete', ['indices' => $categoryIds]))
        ->assertOk()
        ->assertJsonFragment([
            'message' => trans('admin::app.catalog.categories.delete-success'),
        ]);

    foreach ($categoryIds as $id) {
        $this->assertDatabaseMissing($this->getFullTableName(Category::class), [
            'id' => $id,
        ]);
    }
});

it('should not mass delete a category linked to a channel', function () {
    $this->loginAsAdmin();

    $categoryIds = Category::factory()->count(3)->create()->pluck('id')->toArray();

    $channelLinkedCategory = Channel::first()->root_category_id;

    $this->post(route('admin.catalog.categories.mass_delete', ['indices' => [...$categoryIds, $channelLinkedCategory]]))
        ->assertBadRequest()
        ->assertJsonFragment([
            'message' => trans('admin::app.catalog.categories.delete-category-root'),
        ]);

    $this->assertDatabaseHas($this->getFullTableName(Category::class), [
        'id' => $channelLinkedCategory,
    ]);
});

/**Category value tests */
it('should store category values per locale correctly and not remove other locale value.', function () {
    $this->loginAsAdmin();

    $defaultLocaleCode = core()->getRequestedLocaleCode();

    Locale::where('code', 'fr_FR')->update(['status' => 1]);

    $newLocale = 'fr_FR';

    $category = Category::factory()->create();

    $categoryId = $category->id;

    $originalCategoryValues = $category->additional_data;

    $data = [
        'parent_id'       => $category->parent_id,
        'additional_data' => [
            'locale_specific' => [
                $newLocale => [
                    'name' => 'New Locale Name',
                ],
            ],
        ],
    ];

    $this->put(route('admin.catalog.categories.update', ['id' => $categoryId, 'locale' => $newLocale]), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.categories.update-success'))
        ->assertRedirect(route('admin.catalog.categories.edit', ['id' => $categoryId, 'locale' => $newLocale]));

    $category->refresh();

    $this->assertEquals('New Locale Name', $category->additional_data['locale_specific'][$newLocale]['name'] ?? '');

    $this->assertArrayHasKey($defaultLocaleCode, $category->additional_data['locale_specific']);

    $this->assertEquals($originalCategoryValues['locale_specific'][$defaultLocaleCode]['name'], $category->additional_data['locale_specific'][$defaultLocaleCode]['name']);
});

it('should return validation error for unique values when creating category', function () {
    $this->loginAsAdmin();

    $categoryFieldCode = 'categoryValues_uniqueField';

    CategoryField::factory()->create(['code' => $categoryFieldCode, 'is_unique' => 1, 'value_per_locale' => 0, 'type' => 'text']);

    Category::factory()->create(['additional_data' => ['common' => [$categoryFieldCode => 'Already Present Value']]]);

    $data = [
        'code'            => 'test_category_1_0_0',
        'parent_id'       => null,
        'additional_data' => [
            'common' => [
                $categoryFieldCode => 'Already Present Value',
            ],
        ],
        'uniqueFields' => [
            'additional_data.common.'.$categoryFieldCode => 'additional_data[common]['.$categoryFieldCode.']',
        ],
    ];

    $this->post(route('admin.catalog.categories.store'), $data)
        ->assertInvalid('additional_data.common.categoryValues_uniqueField');

    $this->assertDatabaseMissing($this->getFullTableName(Category::class), [
        'code' => $data['code'],
    ]);
});

it('should return validation error for unique values when updating category', function () {
    $this->loginAsAdmin();

    $categoryFieldCode = 'categoryValues_uniqueField';

    CategoryField::factory()->create(['code' => $categoryFieldCode, 'is_unique' => 1, 'value_per_locale' => 0, 'type' => 'text']);

    $category = Category::first();

    Category::factory()->create(['additional_data' => ['common' => [$categoryFieldCode => 'Already Present Value']]]);

    $data = [
        'parent_id'       => $category->parent_id,
        'additional_data' => [
            'common' => [
                $categoryFieldCode => 'Already Present Value',
            ],
        ],
        'uniqueFields' => [
            'additional_data.common.'.$categoryFieldCode => 'additional_data[common]['.$categoryFieldCode.']',
        ],
    ];

    $this->put(route('admin.catalog.categories.update', $category->id), $data)
        ->assertInvalid('additional_data.common.categoryValues_uniqueField');

    $category->refresh();

    $this->assertNotEquals('Already Present Value', ($category->additional_data['common'][$categoryFieldCode] ?? ''));
});
