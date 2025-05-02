<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Webkul\Category\Models\Category;
use Webkul\Category\Models\CategoryField;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should return the list of all categories', function () {
    $category = Category::first();

    $this->withHeaders($this->headers)->json('GET', route('admin.api.categories.index'))
        ->assertOK()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'code',
                    'parent',
                    'additional_data',
                ],
            ],
            'current_page',
            'last_page',
            'total',
            'links' => [
                'first',
                'last',
                'next',
                'prev',
            ],
        ])
        ->assertJsonFragment(['code' => $category->code])
        ->assertJsonFragment(['total' => Category::count()]);
});

it('should return the category using the code', function () {
    $category = Category::first();

    $this->withHeaders($this->headers)->json('GET', route('admin.api.categories.get', ['code' => $category->code]))
        ->assertOK()
        ->assertJsonStructure([
            'code',
            'parent',
            'additional_data',
        ])
        ->assertJsonFragment(['code' => $category->code, 'parent' => $category->parent?->code, 'additional_data' => $category->additional_data]);
});

it('should return the message when code does not exists in categories', function () {
    $this->withHeaders($this->headers)->json('GET', route('admin.api.categories.get', ['code' => 'abcxyz']))
        ->assertStatus(404)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => false]);
});

it('should create the category', function () {
    $locale = Locale::where('status', 1)->first();

    $category = [
        'code'            => 'testCategory',
        'parent'          => '',
        'additional_data' => [
            'locale_specific' => [
                $locale->code => [
                    'name' => 'Test Category',
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.categories.store'), $category)
        ->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $this->assertDatabaseHas($this->getFullTableName(Category::class), ['code' => $category['code']]);
});

it('should give validation message for code in creating category', function () {
    $locale = Locale::where('status', 1)->first();

    $category = [
        'code'            => '',
        'parent'          => '',
        'additional_data' => [
            'locale_specific' => [
                $locale->code => [
                    'name' => 'Test Category',
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.categories.store'), $category)
        ->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => [
                'code',
            ],
        ])
        ->assertJsonFragment(['success' => false]);
});

it('should update the parent of the category', function () {
    $category = Category::factory()->create();
    $parent = Category::factory()->create();
    $locale = Locale::where('status', 1)->first();

    $updatedCategory = [
        'code'            => $category->code,
        'parent'          => $parent->code,
        'additional_data' => [
            'locale_specific' => [
                $locale->code => [
                    'name' => 'Test Category',
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), $updatedCategory)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $this->assertDatabaseHas($this->getFullTableName(Category::class), [
        'code'      => $category->code,
        'parent_id' => $parent->id,
    ]);
});

it('should update the name of the category', function () {
    $category = Category::factory()->create();
    $locale = Locale::where('status', 1)->first();

    $updatedCategory = [
        'code'            => $category->code,
        'parent'          => $category->parent->code,
        'additional_data' => [
            'locale_specific' => [
                $locale->code => [
                    'name' => 'TestCategory',
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), $updatedCategory)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);
});

it('should delete the category', function () {
    $rootCategory = Category::factory()->create(['parent_id' => null]);
    $category = Category::factory()->create(['parent_id' => $rootCategory->id]);
    $response = $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.categories.delete', ['code' => $category->code]));
    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);
    $this->assertDatabaseMissing($this->getFullTableName(Category::class), [
        'code' => $category->code,
    ]);
});

it('should return 404 if category not found for delete', function () {
    $nonExistingCode = 'non-existing-code';
    $response = $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.categories.delete', ['code' => 'non-existing-code']));
    $response->assertStatus(404)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => false])
        ->assertJsonFragment(['message' => trans('admin::app.catalog.categories.not-found', ['code' => (string) $nonExistingCode])]);
});

it('should partially update the category and its parent', function () {
    $category = Category::factory()->create();
    $parent = Category::factory()->create();
    $locale = Locale::where('status', 1)->first();
    $updatedData = [
        'parent'          => $parent->code,
        'additional_data' => [
            'locale_specific' => [
                $locale->code => [
                    'name' => 'Updated Category Name',
                ],
            ],
        ],
    ];
    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.categories.patch', ['code' => $category->code]), $updatedData)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);
    $this->assertDatabaseHas($this->getFullTableName(Category::class), [
        'code'      => $category->code,
        'parent_id' => $parent->id,
    ]);
    $actualAdditionalData = Category::where('code', $category->code)->value('additional_data');
    $expectedAdditionalData = json_encode($updatedData['additional_data']);
    if (! is_string($actualAdditionalData)) {
        $actualAdditionalData = json_encode($actualAdditionalData);
    }
    $this->assertEquals($expectedAdditionalData, $actualAdditionalData);
});

it('should return 404 if category not found for patch', function () {
    $nonExistingCode = 'non-existing-code';
    $response = $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.categories.patch', ['code' => 'non-existing-code']), [
            'parent' => 'null',
        ]);
    $response->assertStatus(404)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => false])
        ->assertJsonFragment(['message' => trans('admin::app.catalog.categories.not-found', ['code' => (string) $nonExistingCode])]);
});

it('should successfully patch the category', function () {
    $category = Category::factory()->create(['code' => 'existing-code']);
    $response = $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.categories.patch', ['code' => 'existing-code']), [
            'parent' => 'null',
        ]);
    $response->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true])
        ->assertJsonFragment(['message' => trans('admin::app.catalog.categories.update-success')]);
});

it('should return 401 if user is unauthorized', function () {
    $this->headers['Authorization'] = 'invalid-token';

    $response = $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.categories.patch', ['code' => 'existing-code']), [
            'parent' => 'null',
        ]);
    $response->assertStatus(401)
        ->assertJsonFragment(['message' => 'Unauthenticated.']);
});

it('should patch the data for all locales for category patch', function () {
    $category = Category::factory()->create();

    Locale::whereIn('code', ['fr_FR', 'es_ES', 'de_DE'])->update(['status' => 1]);
    $locales = Locale::where('status', 1)->limit(3)->pluck('code')->toArray();

    $data = [];
    foreach ($locales as $locale) {
        $data[$locale] = ['name' => 'Updated name '.$locale];
    }

    $updatedCategory = [
        'parent'          => $category->parent->code,
        'additional_data' => [
            'locale_specific' => $data,
        ],
    ];

    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.categories.patch', ['code' => $category->code]), $updatedCategory)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $category = Category::where('code', $category->code)->first();

    $this->assertEquals($updatedCategory['additional_data'], $category->additional_data);
});

it('should patch the data for specific locales without affecting other locales data', function () {

    Locale::whereIn('code', ['fr_FR', 'es_ES', 'de_DE'])->update(['status' => 1]);
    $locales = Locale::where('status', 1)->limit(3)->pluck('code')->toArray();

    $category = Category::factory()->create([
        'additional_data' => [
            'locale_specific' => [
                $locales[0] => ['name' => 'Existing name for '.$locales[0]],
                $locales[1] => ['name' => 'Existing name for '.$locales[1]],
            ],
        ],
    ]);

    $data = [
        $locales[1] => ['name' => 'Updated name for '.$locales[1]],
        $locales[2] => ['name' => 'Updated name for '.$locales[2]],
    ];

    $updatedCategory = [
        'parent'          => $category->parent->code,
        'additional_data' => [
            'locale_specific' => $data,
        ],
    ];

    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.categories.patch', ['code' => $category->code]), $updatedCategory)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $updatedCategoryFromDb = Category::where('code', $category->code)->first();

    $expectedAdditionalData = [
        'locale_specific' => [
            $locales[0] => ['name' => 'Existing name for '.$locales[0]], // Should remain unchanged
            $locales[1] => ['name' => 'Updated name for '.$locales[1]], // Should be updated
            $locales[2] => ['name' => 'Updated name for '.$locales[2]], // Should be added
        ],
    ];

    $this->assertEquals($expectedAdditionalData, $updatedCategoryFromDb->additional_data);
});

it('should patch the checkbox type category fields value in the category', function () {
    $category = Category::factory()->create();

    $categoryField = CategoryField::factory()->create(['value_per_locale' => false, 'type' => 'checkbox', 'status' => 1]);

    $options = $categoryField->options()->pluck('code')->implode(',');

    $updatedCategory = [
        'code'            => $category->code,
        'parent'          => $category->parent->code,
        'additional_data' => [
            'common' => [
                $categoryField->code => $options,
            ],
            'locale_specific' => [],
        ],
    ];

    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.categories.patch', ['code' => $category->code]), $updatedCategory)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $updatedCategoryFromDb = Category::where('code', $updatedCategory['code'])->first();
    $fieldValue = $updatedCategory['additional_data']['common'][$categoryField->code];
    $updatedValue = $updatedCategoryFromDb->additional_data['common'][$categoryField->code];

    $this->assertEquals($fieldValue, $updatedValue);
});

it('should patch the textarea type category fields value in the category', function () {
    $category = Category::factory()->create();
    $locale = Locale::where('status', 1)->first();

    $categoryField = CategoryField::factory()->create(['status' => 1, 'value_per_locale' => false, 'type' => 'textarea']);

    $updatedCategory = [
        'code'            => $category->code,
        'parent'          => $category->parent->code,
        'additional_data' => [
            'common' => [
                $categoryField->code => fake()->sentence(),
            ],
            'locale_specific' => [
                $locale->code => [
                    'name' => 'TestCategory',
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.categories.patch', ['code' => $category->code]), $updatedCategory)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $updatedCategoryFromDb = Category::where('code', $updatedCategory['code'])->first();

    $fieldValue = $updatedCategory['additional_data']['common'][$categoryField->code];
    $updatedValue = $updatedCategoryFromDb->additional_data['common'][$categoryField->code];

    $this->assertEquals($fieldValue, $updatedValue);
    $this->assertDatabaseHas($this->getFullTableName(Category::class), ['code' => $category->code]);
});

it('should give validation message if category trying to add parent to a root category', function () {
    $locale = Locale::where('status', 1)->first();
    $channel = Channel::factory()->create();
    $parentCategory = Category::factory()->create();
    $category = Category::where('id', $channel->root_category_id)->first();

    $updatedCategory = [
        'code'            => $category->code,
        'parent'          => $parentCategory->code,
        'additional_data' => [
            'locale_specific' => [
                $locale->code => [
                    'name' => 'TestCategory',
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), $updatedCategory)
        ->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'message',
            'errors',
        ])
        ->assertJsonFragment(['success' => false]);

    $this->assertDatabaseMissing($this->getFullTableName(Category::class), [
        'code'      => $category->code,
        'parent_id' => $parentCategory->code,
    ]);
});

it('should update the data for all locales for category update', function () {
    $category = Category::factory()->create();
    Locale::whereIn('code', ['fr_FR', 'es_ES', 'de_DE'])->update(['status' => 1]);
    $locales = Locale::where('status', 1)->limit(3)->pluck('code')->toArray();

    $data = [];
    foreach ($locales as $locale) {
        $data[$locale] = ['name' => 'Test name'.$locale];
    }

    $updatedCategory = [
        'code'            => $category->code,
        'parent'          => $category->parent->code,
        'additional_data' => [
            'locale_specific' => $data,
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), $updatedCategory)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $category = Category::where('code', $updatedCategory['code'])->first();
    $this->assertEquals($updatedCategory['additional_data'], $category->additional_data);
});

it('should update the data for all locales without effecting other locales data', function () {
    Locale::whereIn('code', ['fr_FR', 'es_ES', 'de_DE'])->update(['status' => 1]);
    $locales = Locale::where('status', 1)->limit(3)->pluck('code')->toArray();
    $category = Category::factory()->create([
        'additional_data' => [
            'locale_specific' => [
                $locales[0] => ['name' => 'Test name'],
            ],
        ],
    ]);

    $data = [];
    foreach ($locales as $key => $locale) {
        if ($key == 0) {
            continue;
        }

        $data[$locale] = ['name' => 'Test name'.$locale];
    }

    $updatedCategory = [
        'code'            => $category->code,
        'parent'          => $category->parent->code,
        'additional_data' => [
            'locale_specific' => $data,
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), $updatedCategory)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $category = Category::where('code', $updatedCategory['code'])->first();
    $updatedCategory['additional_data']['locale_specific'][$locales[0]] = ['name' => 'Test name'];

    $this->assertEquals($updatedCategory['additional_data'], $category->additional_data);
});

it('should not update the code of the category', function () {
    $category = Category::factory()->create();
    $locale = Locale::where('status', 1)->first();

    $updatedCategory = [
        'code'            => 'codeUpdated',
        'parent'          => $category->parent->code,
        'additional_data' => [
            'locale_specific' => [
                $locale->code => [
                    'name' => 'TestCategory',
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), $updatedCategory)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $this->assertDatabaseHas($this->getFullTableName(Category::class), ['code' => $category->code]);
});

it('should update the locale specific category fields of the category', function () {
    $category = Category::factory()->create();
    $locale = Locale::where('status', 1)->first();
    $categoryField = CategoryField::factory()->create(['status' => 1, 'value_per_locale' => true, 'type' => 'text']);

    $updatedCategory = [
        'code'            => $category->code,
        'parent'          => $category->parent->code,
        'additional_data' => [
            'locale_specific' => [
                $locale->code => [
                    'name'               => 'TestCategory',
                    $categoryField->code => fake()->word(),
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), $updatedCategory)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $category = Category::where('code', $updatedCategory['code'])->first();

    $fieldValue = $updatedCategory['additional_data']['locale_specific'][$locale->code][$categoryField->code];
    $updatedValue = $category->additional_data['locale_specific'][$locale->code][$categoryField->code];

    $this->assertEquals($fieldValue, $updatedValue);
    $this->assertDatabaseHas($this->getFullTableName(Category::class), ['code' => $category->code]);
});

it('should update the textarea type category fields value in the category', function () {
    $category = Category::factory()->create();
    $locale = Locale::where('status', 1)->first();
    $categoryField = CategoryField::factory()->create(['status' => 1, 'value_per_locale' => false, 'type' => 'textarea']);

    $updatedCategory = [
        'code'            => $category->code,
        'parent'          => $category->parent->code,
        'additional_data' => [
            'common' => [
                $categoryField->code => fake()->word(),
            ],
            'locale_specific' => [
                $locale->code => [
                    'name' => 'TestCategory',
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), $updatedCategory)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $category = Category::where('code', $updatedCategory['code'])->first();

    $fieldValue = $updatedCategory['additional_data']['common'][$categoryField->code];
    $updatedValue = $category->additional_data['common'][$categoryField->code];

    $this->assertEquals($fieldValue, $updatedValue);
    $this->assertDatabaseHas($this->getFullTableName(Category::class), ['code' => $category->code]);
});

it('should update the boolean type category fields value in the category', function () {
    $category = Category::factory()->create();
    $locale = Locale::where('status', 1)->first();
    $categoryField = CategoryField::factory()->create(['status' => 1, 'value_per_locale' => false, 'type' => 'boolean']);

    $updatedCategory = [
        'code'            => $category->code,
        'parent'          => $category->parent->code,
        'additional_data' => [
            'common' => [
                $categoryField->code => 'true',
            ],
            'locale_specific' => [
                $locale->code => [
                    'name' => 'TestCategory',
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), $updatedCategory)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $category = Category::where('code', $updatedCategory['code'])->first();

    $fieldValue = $updatedCategory['additional_data']['common'][$categoryField->code];
    $updatedValue = $category->additional_data['common'][$categoryField->code];

    $this->assertEquals($fieldValue, $updatedValue);
    $this->assertDatabaseHas($this->getFullTableName(Category::class), ['code' => $category->code]);
});

it('should give validation message for boolean type field in the category', function () {
    $category = Category::factory()->create();
    $categoryField = CategoryField::factory()->create(['value_per_locale' => false, 'type' => 'boolean', 'status' => 1]);

    $updatedCategory = [
        'code'            => $category->code,
        'parent'          => $category->parent->code,
        'additional_data' => [
            'common' => [
                $categoryField->code => 'string',
            ],
            'locale_specific' => [

            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), $updatedCategory)
        ->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => [
                'additional_data.common.'.$categoryField->code,
            ],
        ])
        ->assertJsonFragment(['success' => false]);
});

it('should update the select type category fields value in the category', function () {
    $category = Category::factory()->create();
    $categoryField = CategoryField::factory()->create(['value_per_locale' => false, 'type' => 'select', 'status' => 1]);

    $updatedCategory = [
        'code'            => $category->code,
        'parent'          => $category->parent->code,
        'additional_data' => [
            'common' => [
                $categoryField->code => $categoryField->options()->first()->code,
            ],
            'locale_specific' => [

            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), $updatedCategory)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $category = Category::where('code', $updatedCategory['code'])->first();

    $fieldValue = $updatedCategory['additional_data']['common'][$categoryField->code];
    $updatedValue = $category->additional_data['common'][$categoryField->code];

    $this->assertEquals($fieldValue, $updatedValue);
});

it('should give validation message if code not exists in the select type category field', function () {
    $category = Category::factory()->create();
    $categoryField = CategoryField::factory()->create(['value_per_locale' => false, 'type' => 'select', 'status' => 1]);

    $updatedCategory = [
        'code'            => $category->code,
        'parent'          => $category->parent->code,
        'additional_data' => [
            'common' => [
                $categoryField->code => fake()->word(),
            ],
            'locale_specific' => [

            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), $updatedCategory)
        ->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => [
                'additional_data.common.'.$categoryField->code,
            ],
        ])
        ->assertJsonFragment(['success' => false]);
});

it('should update the multiselect type category fields value in the category', function () {
    $category = Category::factory()->create();
    $categoryField = CategoryField::factory()->create(['value_per_locale' => false, 'type' => 'multiselect', 'status' => 1]);

    $options = '';

    foreach ($categoryField->options()->getResults() as $option) {
        if (empty($options)) {
            $options = $options.$option->code;
        } else {
            $options = $options.','.$option->code;
        }
    }

    $updatedCategory = [
        'code'            => $category->code,
        'parent'          => $category->parent->code,
        'additional_data' => [
            'common' => [
                $categoryField->code => $options,
            ],
            'locale_specific' => [],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), $updatedCategory)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $category = Category::where('code', $updatedCategory['code'])->first();

    $fieldValue = $updatedCategory['additional_data']['common'][$categoryField->code];
    $updatedValue = $category->additional_data['common'][$categoryField->code];

    $this->assertEquals($fieldValue, $updatedValue);
});

it('should update the checkbox type category fields value in the category', function () {
    $category = Category::factory()->create();
    $categoryField = CategoryField::factory()->create(['value_per_locale' => false, 'type' => 'checkbox', 'status' => 1]);

    $options = '';
    foreach ($categoryField->options()->getResults() as $option) {
        if (empty($options)) {
            $options = $options.$option->code;
        } else {
            $options = $options.','.$option->code;
        }
    }

    $updatedCategory = [
        'code'            => $category->code,
        'parent'          => $category->parent->code,
        'additional_data' => [
            'common' => [
                $categoryField->code => $options,
            ],
            'locale_specific' => [],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), $updatedCategory)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $category = Category::where('code', $updatedCategory['code'])->first();

    $fieldValue = $updatedCategory['additional_data']['common'][$categoryField->code];
    $updatedValue = $category->additional_data['common'][$categoryField->code];

    $this->assertEquals($fieldValue, $updatedValue);
});

it('should update the datetime type category fields value in the category', function () {
    $category = Category::factory()->create();
    $locale = Locale::where('status', 1)->first();
    $categoryField = CategoryField::factory()->create(['status' => 1, 'value_per_locale' => false, 'type' => 'datetime']);

    $dateTime = now()->format('Y-m-d H:i:s');

    $updatedCategory = [
        'code'            => $category->code,
        'parent'          => $category->parent->code,
        'additional_data' => [
            'common' => [
                $categoryField->code => $dateTime,
            ],
            'locale_specific' => [
                $locale->code => [
                    'name' => 'TestCategory',
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), $updatedCategory)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $category = Category::where('code', $updatedCategory['code'])->first();

    $updatedValue = $category->additional_data['common'][$categoryField->code];

    $this->assertEquals($dateTime, $updatedValue);
    $this->assertDatabaseHas($this->getFullTableName(Category::class), ['code' => $category->code]);
});

it('should give validation message for incorrect datetime format during category update', function () {
    $category = Category::factory()->create();
    $locale = Locale::where('status', 1)->first();
    $categoryField = CategoryField::factory()->create(['status' => 1, 'value_per_locale' => false, 'type' => 'datetime']);

    $dateTime = now()->format('H:i:s Y-m-d');

    $updatedCategory = [
        'code'            => $category->code,
        'parent'          => $category->parent->code,
        'additional_data' => [
            'common' => [
                $categoryField->code => $dateTime,
            ],
            'locale_specific' => [
                $locale->code => [
                    'name' => 'TestCategory',
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), $updatedCategory)
        ->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => [
                'additional_data.common.'.$categoryField->code,
            ],
        ])
        ->assertJsonFragment(['success' => false]);
});

it('should update the date type category fields value in the category', function () {
    $category = Category::factory()->create();
    $locale = Locale::where('status', 1)->first();
    $categoryField = CategoryField::factory()->create(['status' => 1, 'value_per_locale' => false, 'type' => 'date']);

    $dateTime = now()->format('Y-m-d');

    $updatedCategory = [
        'code'            => $category->code,
        'parent'          => $category->parent->code,
        'additional_data' => [
            'common' => [
                $categoryField->code => $dateTime,
            ],
            'locale_specific' => [
                $locale->code => [
                    'name' => 'TestCategory',
                ],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), $updatedCategory)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $category = Category::where('code', $updatedCategory['code'])->first();

    $updatedValue = $category->additional_data['common'][$categoryField->code];

    $this->assertEquals($dateTime, $updatedValue);
    $this->assertDatabaseHas($this->getFullTableName(Category::class), ['code' => $category->code]);
});

it('should update the image type category fields value in the category', function () {
    $category = Category::factory()->create();
    $locale = Locale::where('status', 1)->first();
    $categoryField = CategoryField::factory()->create(['status' => 1, 'value_per_locale' => false, 'type' => 'image']);

    Storage::fake();

    $updatedCategory = [
        'code'           => $category->code,
        'file'           => UploadedFile::fake()->image('category.jpg'),
        'category_field' => $categoryField->code,
    ];

    $response = $this->withHeaders($this->headers)->json('POST', route('admin.api.media-files.category.store'), $updatedCategory);
    $response->assertStatus(200);

    if ($response->status() === 200) {
        $updatedCategory = [
            'code'            => $category->code,
            'parent'          => $category->parent->code,
            'additional_data' => [
                'common' => [
                    $categoryField->code => $response->json()['data']['filePath'],
                ],
                'locale_specific' => [
                    $locale->code => [
                        'name' => 'TestCategory',
                    ],
                ],
            ],
        ];

        $this->withHeaders($this->headers)->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), $updatedCategory)
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJsonFragment(['success' => true]);

        $category = Category::where('code', $updatedCategory['code'])->first();
        $this->assertTrue(Storage::exists($category->additional_data['common'][$categoryField->code]));
    }
});

it('should update the file type category fields value in the category', function () {
    $category = Category::factory()->create();
    $locale = Locale::where('status', 1)->first();
    $categoryField = CategoryField::factory()->create(['status' => 1, 'value_per_locale' => false, 'type' => 'file']);

    Storage::fake();

    $updatedCategory = [
        'code'           => $category->code,
        'file'           => UploadedFile::fake()->create('category.pdf', 100),
        'category_field' => $categoryField->code,
    ];

    $response = $this->withHeaders($this->headers)->json('POST', route('admin.api.media-files.category.store'), $updatedCategory);
    $response->assertStatus(200);

    if ($response->status() === 200) {
        $updatedCategory = [
            'code'            => $category->code,
            'parent'          => $category->parent->code,
            'additional_data' => [
                'common' => [
                    $categoryField->code => $response->json()['data']['filePath'],
                ],
                'locale_specific' => [
                    $locale->code => [
                        'name' => 'TestCategory',
                    ],
                ],
            ],
        ];

        $this->withHeaders($this->headers)->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), $updatedCategory)
            ->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJsonFragment(['success' => true]);

        $category = Category::where('code', $updatedCategory['code'])->first();
        $this->assertTrue(Storage::exists($category->additional_data['common'][$categoryField->code]));
    }
});

it('should sanitize textarea fields when creating a category', function () {
    CategoryField::factory()->create([
        'code'   => 'description_test',
        'type'   => 'textarea',
        'status' => 1,
    ]);

    $categoryData = [
        'code'            => 'clothing_test',
        'parent'          => 'root',
        'additional_data' => [
            'locale_specific' => [
                'en_US' => [
                    'name'        => 'Clothing2',
                    'description' => '<h2>Premium Leather Backpack</h2>\n<p>This <strong>high-quality leather backpack</strong> is perfect for daily use or travel. Made from genuine leather with <em>water-resistant</em> treatment.</p>\n<p> </p>\n<p>Click me <img src="https://devdocs.unopim.com/logo.png" alt="logo.png" /></p>\n<p> </p>\n<h3>Key Features:</h3>\n<ul>\n<li>Genuine full-grain leather</li>\n<li>Padded laptop compartment (fits up to 15\")</li>\n<li>Water-resistant exterior</li>\n<li>Adjustable shoulder straps</li>\n</ul>\n<p> </p>\n<p>Available in multiple colors:</p>',
                ],
            ],
        ],
    ];

    $response = $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.categories.store'), $categoryData);
    $response->assertStatus(201);

    $category = Category::where('code', $categoryData['code'])->first();
    $description = $category->additional_data['locale_specific']['en_US']['description'];

    $this->assertStringNotContainsString('<script>', $description);
    $this->assertStringNotContainsString('alert(\'malicious code\')', $description);
    $this->assertStringNotContainsString('<iframe', $description);
    $this->assertStringNotContainsString('javascript:', $description);

    $this->assertStringContainsString('<h2>Premium Leather Backpack</h2>', $description);
    $this->assertStringContainsString('<strong>high-quality leather backpack</strong>', $description);

    $this->assertStringContainsString('<img', $description);
    $this->assertStringContainsString('alt="logo.png"', $description);
});

it('should sanitize textarea fields when updating a category', function () {
    $category = Category::factory()->create();

    CategoryField::factory()->create([
        'code'   => 'description_test',
        'type'   => 'textarea',
        'status' => 1,
    ]);

    $updateData = [
        'code'            => $category->code,
        'parent'          => $category->parent ? $category->parent->code : null,
        'additional_data' => [
            'locale_specific' => [
                'en_US' => [
                    'name'        => 'Updated Category',
                    'description' => '<p>Updated description with <script>alert("XSS")</script> and <h3>Heading</h3></p>',
                ],
            ],
        ],
    ];

    $response = $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.categories.update', ['code' => $category->code]), $updateData);

    $response->assertStatus(200);

    $category = Category::where('code', $category->code)->first();
    $description = $category->additional_data['locale_specific']['en_US']['description'];

    $this->assertStringNotContainsString('<script>', $description);
    $this->assertStringNotContainsString('alert("XSS")', $description);
    $this->assertStringContainsString('<h3>Heading</h3>', $description);
});

it('should patch sanitize textarea fields when updating a category', function () {
    $category = Category::factory()->create();

    CategoryField::factory()->create([
        'code'   => 'description_test',
        'type'   => 'textarea',
        'status' => 1,
    ]);

    $updateData = [
        'code'            => $category->code,
        'parent'          => $category->parent ? $category->parent->code : null,
        'additional_data' => [
            'locale_specific' => [
                'en_US' => [
                    'name'        => 'Updated Category',
                    'description' => '<p>Updated description with <script>alert("XSS")</script> and <h3>Heading</h3></p>',
                ],
            ],
        ],
    ];

    $response = $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.categories.patch', ['code' => $category->code]), $updateData);

    $response->assertStatus(200);

    $category = Category::where('code', $category->code)->first();
    $description = $category->additional_data['locale_specific']['en_US']['description'];

    $this->assertStringNotContainsString('<script>', $description);
    $this->assertStringNotContainsString('alert("XSS")', $description);
    $this->assertStringContainsString('<h3>Heading</h3>', $description);
});
