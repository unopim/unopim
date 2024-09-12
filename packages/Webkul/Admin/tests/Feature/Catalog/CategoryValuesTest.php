<?php

use Illuminate\Http\UploadedFile;
use Webkul\Category\Models\Category;
use Webkul\Category\Models\CategoryField;
use Webkul\Core\Models\Locale;

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

/** Create cases for the category values different types */
it('should store the boolean type value when creating category', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create(['type' => 'boolean']);

    $data = Category::factory()->definition();

    $data['additional_data']['common'][$categoryField->code] = 'true';

    $this->post(route('admin.catalog.categories.store'), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.categories.create-success'));

    $category = Category::where('code', $data['code'])->first();

    $this->assertNotEmpty($category->additional_data['common'][$categoryField->code] ?? '');

    $this->assertEquals($category->additional_data['common'][$categoryField->code], 'true');
});

it('should store the select type value when creating category', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create(['type' => 'select']);

    $categoryFieldCode = $categoryField->code;

    $data = Category::factory()->definition();

    $categoryFieldOption = $categoryField->options->first()->code;

    $data['additional_data']['common'][$categoryFieldCode] = $categoryFieldOption;

    $this->post(route('admin.catalog.categories.store'), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.categories.create-success'));

    $category = Category::where('code', $data['code'])->first();

    $this->assertNotEmpty($category->additional_data['common'][$categoryFieldCode] ?? '');

    $this->assertEquals($category->additional_data['common'][$categoryFieldCode], $categoryFieldOption);
});

it('should store the multi select type value when creating category', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create(['type' => 'multiselect']);

    $categoryFieldCode = $categoryField->code;

    $data = Category::factory()->definition();

    $categoryFieldOptions = $categoryField->options->pluck('code')->toArray();

    $data['additional_data']['common'][$categoryFieldCode] = $categoryFieldOptions;

    $this->post(route('admin.catalog.categories.store'), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.categories.create-success'));

    $category = Category::where('code', $data['code'])->first();

    $this->assertNotEmpty($category->additional_data['common'][$categoryFieldCode] ?? '');

    $this->assertEquals($category->additional_data['common'][$categoryFieldCode], implode(',', $categoryFieldOptions));
});

it('should store the datetime type value when creating category', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create(['type' => 'datetime']);

    $categoryFieldCode = $categoryField->code;

    $data = Category::factory()->definition();

    $data['additional_data']['common'][$categoryFieldCode] = '2024-09-02 12:00:00';

    $this->post(route('admin.catalog.categories.store'), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.categories.create-success'));

    $category = Category::where('code', $data['code'])->first();

    $this->assertNotEmpty($category->additional_data['common'][$categoryFieldCode] ?? '');

    $this->assertEquals($category->additional_data['common'][$categoryFieldCode], '2024-09-02 12:00:00');
});

it('should store the date type value when creating category', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create(['type' => 'datetime']);

    $categoryFieldCode = $categoryField->code;

    $data = Category::factory()->definition();

    $data['additional_data']['common'][$categoryFieldCode] = '2024-09-02';

    $this->post(route('admin.catalog.categories.store'), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.categories.create-success'));

    $category = Category::where('code', $data['code'])->first();

    $this->assertNotEmpty($category->additional_data['common'][$categoryFieldCode] ?? '');

    $this->assertEquals($category->additional_data['common'][$categoryFieldCode], '2024-09-02');
});

it('should store the checkbox type value when creating category', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create(['type' => 'multiselect']);

    $categoryFieldCode = $categoryField->code;

    $data = Category::factory()->definition();

    $categoryFieldOptions = $categoryField->options->pluck('code')->toArray();

    $data['additional_data']['common'][$categoryFieldCode] = $categoryFieldOptions;

    $this->post(route('admin.catalog.categories.store'), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.categories.create-success'));

    $category = Category::where('code', $data['code'])->first();

    $this->assertNotEmpty($category->additional_data['common'][$categoryFieldCode] ?? '');

    $this->assertEquals($category->additional_data['common'][$categoryFieldCode], implode(',', $categoryFieldOptions));
});

it('should store the image type field value when creating category', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create(['type' => 'image']);

    $data = Category::factory()->definition();

    Storage::fake();

    $data['additional_data']['common'][$categoryField->code] = [UploadedFile::fake()->image('category.jpg')];

    $this->post(route('admin.catalog.categories.store'), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.categories.create-success'));

    $category = Category::where('code', $data['code'])->first();

    $this->assertNotEmpty($category->additional_data['common'][$categoryField->code] ?? '');

    $this->assertTrue(Storage::exists($category->additional_data['common'][$categoryField->code]));
});

it('should store the file type field value when creating category', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create(['type' => 'file']);

    $data = Category::factory()->definition();

    Storage::fake();

    $data['additional_data']['common'][$categoryField->code] = [UploadedFile::fake()->create('category.pdf', 100)];

    $this->post(route('admin.catalog.categories.store'), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.categories.create-success'));

    $category = Category::where('code', $data['code'])->first();

    $this->assertNotEmpty($category->additional_data['common'][$categoryField->code] ?? '');

    $this->assertTrue(Storage::exists($category->additional_data['common'][$categoryField->code]));
});

/** Update cases for the category values different types */
it('should store the boolean type value when updating category', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create(['type' => 'boolean']);

    $category = Category::first();

    $data = [
        'parent_id'       => $category->parent_id,
        'additional_data' => [
            'common' => [
                $categoryField->code => 'true',
            ],
        ],
    ];

    $this->put(route('admin.catalog.categories.update', $category->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.categories.update-success'));

    $category->refresh();

    $this->assertNotEmpty($category->additional_data['common'][$categoryField->code] ?? '');

    $this->assertEquals($category->additional_data['common'][$categoryField->code], 'true');
});

it('should store the select type value when updating category', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create(['type' => 'select']);

    $categoryFieldCode = $categoryField->code;

    $categoryFieldOption = $categoryField->options->first()->code;

    $category = Category::first();

    $data = [
        'parent_id'       => $category->parent_id,
        'additional_data' => [
            'common' => [
                $categoryFieldCode => $categoryFieldOption,
            ],
        ],
    ];

    $this->put(route('admin.catalog.categories.update', $category->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.categories.update-success'));

    $category->refresh();

    $this->assertNotEmpty($category->additional_data['common'][$categoryFieldCode] ?? '');

    $this->assertEquals($category->additional_data['common'][$categoryFieldCode], $categoryFieldOption);
});

it('should store the multi select type value when updating category', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create(['type' => 'multiselect']);

    $categoryFieldCode = $categoryField->code;

    $categoryFieldOptions = $categoryField->options->pluck('code')->toArray();

    $category = Category::first();

    $data = [
        'parent_id'       => $category->parent_id,
        'additional_data' => [
            'common' => [
                $categoryFieldCode => $categoryFieldOptions,
            ],
        ],
    ];

    $this->put(route('admin.catalog.categories.update', $category->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.categories.update-success'));

    $category->refresh();

    $this->assertNotEmpty($category->additional_data['common'][$categoryFieldCode] ?? '');

    $this->assertEquals($category->additional_data['common'][$categoryFieldCode], implode(',', $categoryFieldOptions));
});

it('should store the datetime type value when updating category', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create(['type' => 'datetime']);

    $categoryFieldCode = $categoryField->code;

    $category = Category::first();

    $data = [
        'parent_id'       => $category->parent_id,
        'additional_data' => [
            'common' => [
                $categoryFieldCode => '2024-09-02 12:00:00',
            ],
        ],
    ];

    $this->put(route('admin.catalog.categories.update', $category->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.categories.update-success'));

    $category->refresh();

    $this->assertNotEmpty($category->additional_data['common'][$categoryFieldCode] ?? '');

    $this->assertEquals($category->additional_data['common'][$categoryFieldCode], '2024-09-02 12:00:00');
});

it('should store the date type value when updating category', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create(['type' => 'datetime']);

    $categoryFieldCode = $categoryField->code;

    $category = Category::first();

    $data = [
        'parent_id'       => $category->parent_id,
        'additional_data' => [
            'common' => [
                $categoryFieldCode => '2024-09-02',
            ],
        ],
    ];

    $this->put(route('admin.catalog.categories.update', $category->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.categories.update-success'));

    $category->refresh();

    $this->assertNotEmpty($category->additional_data['common'][$categoryFieldCode] ?? '');

    $this->assertEquals($category->additional_data['common'][$categoryFieldCode], '2024-09-02');
});

it('should store the checkbox type value when updating category', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create(['type' => 'multiselect']);

    $categoryFieldCode = $categoryField->code;

    $categoryFieldOptions = $categoryField->options->pluck('code')->toArray();

    $category = Category::first();

    $data = [
        'parent_id'       => $category->parent_id,
        'additional_data' => [
            'common' => [
                $categoryFieldCode => $categoryFieldOptions,
            ],
        ],
    ];

    $this->put(route('admin.catalog.categories.update', $category->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.categories.update-success'));

    $category->refresh();

    $this->assertNotEmpty($category->additional_data['common'][$categoryFieldCode] ?? '');

    $this->assertEquals($category->additional_data['common'][$categoryFieldCode], implode(',', $categoryFieldOptions));
});

it('should store the image type field value when updating category', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create(['type' => 'image']);

    $category = Category::first();

    $categoryFieldCode = $categoryField->code;

    Storage::fake();

    $data = [
        'parent_id'       => $category->parent_id,
        'additional_data' => [
            'common' => [
                $categoryFieldCode => [UploadedFile::fake()->image('category.jpg')],
            ],
        ],
    ];

    $this->put(route('admin.catalog.categories.update', $category->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.categories.update-success'));

    $category->refresh();

    $this->assertNotEmpty($category->additional_data['common'][$categoryFieldCode] ?? '');

    $this->assertTrue(Storage::exists($category->additional_data['common'][$categoryFieldCode]));
});

it('should store the file type field value when updating category', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create(['type' => 'file']);

    $category = Category::first();

    $categoryFieldCode = $categoryField->code;

    Storage::fake();

    $data = [
        'parent_id'       => $category->parent_id,
        'additional_data' => [
            'common' => [
                $categoryFieldCode => [UploadedFile::fake()->create('category.pdf', 100)],
            ],
        ],
    ];

    $this->put(route('admin.catalog.categories.update', $category->id), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.categories.update-success'));

    $category->refresh();

    $this->assertNotEmpty($category->additional_data['common'][$categoryFieldCode] ?? '');

    $this->assertTrue(Storage::exists($category->additional_data['common'][$categoryFieldCode]));
});
