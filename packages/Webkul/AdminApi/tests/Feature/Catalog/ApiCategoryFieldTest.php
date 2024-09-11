<?php

use Webkul\Category\Models\CategoryField;
use Webkul\Category\Models\CategoryFieldOption;
use Webkul\Category\Models\CategoryFieldOptionTranslation;
use Webkul\Category\Models\CategoryFieldTranslation;
use Webkul\Core\Models\Locale;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should return the list of all category fields', function () {
    $response = $this->withHeaders($this->headers)->json('GET', route('admin.api.category-fields.index'))
        ->assertOK()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'code',
                    'type',
                    'validation',
                    'regex_pattern',
                    'position',
                    'is_required',
                    'is_unique',
                    'value_per_locale',
                    'enable_wysiwyg',
                    'section',
                    'labels',
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
        ->assertJsonFragment(['total' => CategoryField::count()])
        ->json('data');

    $categoryField = CategoryField::first();

    $fieldData = [
        'code'             => $categoryField->code,
        'type'             => $categoryField->type,
        'status'           => $categoryField->status,
        'validation'       => $categoryField->validation,
        'regex_pattern'    => $categoryField->regex_pattern,
        'position'         => $categoryField->position,
        'is_required'      => $categoryField->is_required,
        'is_unique'        => $categoryField->is_unique,
        'value_per_locale' => $categoryField->value_per_locale,
        'enable_wysiwyg'   => $categoryField->enable_wysiwyg,
        'section'          => $categoryField->section,
        'labels'           => $categoryField->translations()->pluck('name', 'locale')->filter()->toArray(),
    ];

    $this->assertTrue(
        collect($response)->contains($fieldData),
    );
});

it('should return the category field by code', function () {
    $categoryField = CategoryField::first();

    $fieldData = [
        'code'             => $categoryField->code,
        'type'             => $categoryField->type,
        'status'           => $categoryField->status,
        'validation'       => $categoryField->validation,
        'regex_pattern'    => $categoryField->regex_pattern,
        'position'         => $categoryField->position,
        'is_required'      => $categoryField->is_required,
        'is_unique'        => $categoryField->is_unique,
        'value_per_locale' => $categoryField->value_per_locale,
        'enable_wysiwyg'   => $categoryField->enable_wysiwyg,
        'section'          => $categoryField->section,
        'labels'           => $categoryField->translations()->pluck('name', 'locale')->filter()->toArray(),
    ];

    $this->withHeaders($this->headers)->json('GET', route('admin.api.category-fields.get', $fieldData['code']))
        ->assertOk()
        ->assertJsonStructure([
            'code',
            'type',
            'validation',
            'regex_pattern',
            'position',
            'is_required',
            'is_unique',
            'value_per_locale',
            'enable_wysiwyg',
            'section',
            'labels',
        ])
        ->assertJson($fieldData);
});

it('should return 404 error when fetching categoryField by code', function () {
    $this->withHeaders($this->headers)->json('GET', route('admin.api.category-fields.get', 'non_existing_category_field_'))
        ->assertNotFound()
        ->assertJsonStructure([
            'success',
            'message',
        ]);
});

it('should create a new category field successfully', function () {
    $data = [
        'code'             => 'testing_field_00_0',
        'type'             => 'text',
        'status'           => 1,
        'section'          => 'left',
        'validation'       => null,
        'regex_pattern'    => null,
        'is_required'      => 0,
        'is_unique'        => 0,
        'value_per_locale' => 0,
        'enable_wysiwyg'   => 0,
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.category-fields.store'), $data)
        ->assertCreated()
        ->assertJsonFragment(['success' => true, 'message' => trans('admin::app.catalog.category_fields.create-success')]);

    $this->assertDatabaseHas($this->getFullTableName(CategoryField::class), $data);
});

it('should create category fields with certain types', function () {
    $attributeTypes = [
        'text',
        'textarea',
        'boolean',
        'select',
        'multiselect',
        'datetime',
        'date',
        'image',
        'file',
        'checkbox',
    ];

    foreach ($attributeTypes as $type) {
        $data = [
            'code'             => 'testing_category_field_090_9'.$type,
            'type'             => $type,
            'status'           => 1,
            'section'          => 'right',
            'validation'       => null,
            'regex_pattern'    => null,
            'is_required'      => 0,
            'is_unique'        => 0,
            'value_per_locale' => 0,
            'enable_wysiwyg'   => 0,
        ];

        try {
            $this->withHeaders($this->headers)->json('POST', route('admin.api.category-fields.store'), $data)
                ->assertCreated()
                ->assertJsonFragment(['success' => true, 'message' => trans('admin::app.catalog.category_fields.create-success')]);

            $this->assertDatabaseHas($this->getFullTableName(CategoryField::class), $data);
        } catch (\Exception $e) {
            throw new \Exception('Failed with categoryField code: '.$code.'. '.$e->getMessage());
        }
    }
});

it('should create a unique category field successfully', function () {
    $data = [
        'code'             => 'testing_category_field_090_9',
        'type'             => 'text',
        'status'           => 1,
        'section'          => 'right',
        'validation'       => null,
        'regex_pattern'    => null,
        'is_required'      => 0,
        'is_unique'        => 1,
        'value_per_locale' => 0,
        'enable_wysiwyg'   => 0,
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.category-fields.store'), $data)
        ->assertCreated()
        ->assertJsonFragment(['success' => true, 'message' => trans('admin::app.catalog.category_fields.create-success')]);

    $this->assertDatabaseHas($this->getFullTableName(CategoryField::class), $data);
});

it('should return unique validation when creating with duplicate code', function () {
    CategoryField::factory()->create(['code' => 'testing_category_field_00_0', 'type' => 'textarea']);

    $data = [
        'code'             => 'testing_category_field_00_0',
        'type'             => 'text',
        'section'          => 'right',
        'validation'       => null,
        'regex_pattern'    => null,
        'is_required'      => 0,
        'is_unique'        => 0,
        'value_per_locale' => 0,
        'enable_wysiwyg'   => 0,
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.category-fields.store'), $data)
        ->assertUnprocessable()
        ->assertJsonFragment(['message' => 'Validation failed.', 'success' => false])
        ->assertJsonStructure([
            'errors' => [
                'code',
            ],
        ]);

    $this->assertDatabaseMissing($this->getFullTableName(CategoryField::class), $data);
});

it('should not create category fields with certain codes', function () {
    $restrictedFields = [
        'code',
        'parent',
        'locale',
    ];

    $data = [
        'type'             => 'text',
        'section'          => 'right',
        'validation'       => null,
        'regex_pattern'    => null,
        'is_required'      => 0,
        'is_unique'        => 0,
        'value_per_locale' => 0,
        'enable_wysiwyg'   => 0,
        'labels'           => [],
    ];

    foreach ($restrictedFields as $code) {
        $data['code'] = $code;

        try {
            $this->withHeaders($this->headers)->json('POST', route('admin.api.category-fields.store'), $data)
                ->assertUnprocessable()
                ->assertJsonFragment(['message' => 'Validation failed.', 'success' => false])
                ->assertJsonStructure([
                    'errors' => [
                        'code',
                    ],
                ]);

            unset($data['labels']);

            $this->assertDatabaseMissing($this->getFullTableName(CategoryField::class), $data);
        } catch (\Exception $e) {
            throw new \Exception('Failed with categoryField code: '.$code.'. '.$e->getMessage());
        }
    }
});

it('should give required validation for code and type field when creating category field', function () {
    $data = [
        'code'             => '',
        'type'             => '',
        'validation'       => null,
        'regex_pattern'    => null,
        'is_required'      => 0,
        'is_unique'        => 0,
        'value_per_locale' => 0,
        'enable_wysiwyg'   => 0,
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.category-fields.store'), $data)
        ->assertUnprocessable()
        ->assertJsonFragment(['message' => 'Validation failed.', 'success' => false])
        ->assertJsonStructure([
            'errors' => [
                'code',
                'type',
            ],
        ]);

    $this->assertDatabaseMissing($this->getFullTableName(CategoryField::class), $data);
});

it('should give validation error when invalid type is added for creating category field', function () {
    $data = [
        'code'             => 'testing_category_field',
        'type'             => 'price',
        'validation'       => null,
        'regex_pattern'    => null,
        'is_required'      => 0,
        'is_unique'        => 0,
        'value_per_locale' => 0,
        'enable_wysiwyg'   => 0,
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.category-fields.store'), $data)
        ->assertUnprocessable()
        ->assertJsonFragment(['message' => 'Validation failed.', 'success' => false])
        ->assertJsonStructure([
            'errors' => [
                'type',
            ],
        ]);

    $this->assertDatabaseMissing($this->getFullTableName(CategoryField::class), $data);
});

it('should give validation error when invalid validation type is given', function () {
    $data = [
        'code'             => 'testing_category_field',
        'type'             => 'text',
        'validation'       => 'no_zeros',
        'regex_pattern'    => null,
        'is_required'      => 0,
        'is_unique'        => 0,
        'value_per_locale' => 0,
        'enable_wysiwyg'   => 0,
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.category-fields.store'), $data)
        ->assertUnprocessable()
        ->assertJsonFragment(['message' => 'Validation failed.', 'success' => false])
        ->assertJsonStructure([
            'errors' => [
                'validation',
            ],
        ]);

    $this->assertDatabaseMissing($this->getFullTableName(CategoryField::class), $data);
});

it('should create text category field with these validation types', function () {
    $validationTypes = [
        'number',
        'email',
        'decimal',
        'url',
        'regex',
    ];

    $data = [
        'code'             => 'test_category_field',
        'type'             => 'text',
        'validation'       => null,
        'regex_pattern'    => null,
        'is_required'      => 0,
        'is_unique'        => 0,
        'value_per_locale' => 0,
        'enable_wysiwyg'   => 0,
    ];

    foreach ($validationTypes as $validation) {
        $data['code'] .= $validation;

        $data['validation'] = $validation;

        try {
            $this->withHeaders($this->headers)->json('POST', route('admin.api.category-fields.store'), $data)
                ->assertCreated();

            $this->assertDatabaseHas($this->getFullTableName(CategoryField::class), $data);
        } catch (\Exception $e) {
            throw new \Exception('Failed with validation type code: '.$validation.'. '.$e->getMessage());
        }
    }
});

it('should return 404 error when updating non existent category field', function () {
    $data = [
        'code'             => 'non_existing_field',
        'type'             => 'text',
        'section'          => 'left',
        'validation'       => null,
        'regex_pattern'    => null,
        'is_required'      => 0,
        'is_unique'        => 0,
        'value_per_locale' => 0,
        'enable_wysiwyg'   => 0,
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.category-fields.update', 'non_existing_field'), $data)
        ->assertNotFound()
        ->assertJsonStructure([
            'success',
            'message',
        ]);
});

it('should update a categoryField successfully', function () {
    $categoryField = CategoryField::factory()->create(['type' => 'textarea', 'enable_wysiwyg' => 0]);

    $localeCode = Locale::where('status', 1)->first()->code;

    $data = [
        'code'             => $categoryField->code,
        'type'             => 'textarea',
        'section'          => $categoryField->section,
        'validation'       => $categoryField->validation,
        'regex_pattern'    => $categoryField->regex_pattern,
        'is_required'      => $categoryField->is_required,
        'is_unique'        => $categoryField->is_unique,
        'value_per_locale' => $categoryField->value_per_locale,
        'enable_wysiwyg'   => 1,
        'labels'           => [
            $localeCode => 'Test Label',
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.category-fields.update', $data['code']), $data)
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.catalog.category_fields.update-success')]);

    unset($data['labels']);

    $this->assertDatabaseHas($this->getFullTableName(CategoryField::class), $data);

    $this->assertDatabaseHas($this->getFullTableName(CategoryFieldTranslation::class), [
        'category_field_id' => $categoryField->id,
        'locale'            => $localeCode,
        'name'              => 'Test Label',
    ]);
});

it('should not update code,type,value_per_locale and is_unique fields when updating a category field', function () {
    $categoryField = CategoryField::factory()->create([
        'type'             => 'text',
        'value_per_locale' => 1,
        'is_unique'        => 1,
    ]);

    $data = [
        'code'             => 'new_code',
        'type'             => 'select',
        'section'          => $categoryField->section,
        'validation'       => $categoryField->validation,
        'regex_pattern'    => $categoryField->regex_pattern,
        'is_required'      => $categoryField->is_required,
        'is_unique'        => 0,
        'value_per_locale' => 0,
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.category-fields.update', $categoryField->code), $data)
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.catalog.category_fields.update-success')]);

    $this->assertDatabaseMissing($this->getFullTableName(CategoryField::class), $data);

    $this->assertDatabaseHas($this->getFullTableName(CategoryField::class), [
        'code'             => $categoryField->code,
        'type'             => 'text',
        'value_per_locale' => 1,
        'is_unique'        => 1,
    ]);
});

it('should return list of options for a category field which allows options', function () {
    $categoryField = CategoryField::factory()->create(['code' => 'select_type_test_field', 'type' => 'select']);

    $response = $this->withHeaders($this->headers)->json('GET', route('admin.api.category-fields_options.get', $categoryField->code))
        ->assertOK()
        ->assertJsonStructure([
            '*' => [
                'code',
                'sort_order',
                'labels',
            ],
        ])
        ->json();

    $attributeOptions = $categoryField->options()->orderBy('sort_order');

    $this->assertEquals(count($response), $attributeOptions->count());

    $option = $attributeOptions->first();

    $this->assertEquals($response[0], [
        'code'       => $option->code,
        'sort_order' => $option->sort_order,
        'labels'     => $option->translations()->pluck('label', 'locale')->toArray(),
    ]);
});

it('should return 404 error when non existent category field code is used while creating category field options', function () {
    $data = [
        [
            'code'       => 'option_1',
            'sort_order' => 1,
        ], [
            'code'       => 'option_1',
            'sort_order' => 1,
        ],
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.category-fields-options.store_option', 'non_existent_attribute_'), $data)
        ->assertNotFound();
});

it('should return required validation for category field option code', function () {
    $categoryField = CategoryField::factory()->create(['code' => 'multiselect_type_test_field', 'type' => 'multiselect']);

    $data = [
        [
            'code'       => '',
            'sort_order' => 1,
        ],
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.category-fields-options.store_option', $categoryField->code), $data)
        ->assertUnprocessable()
        ->assertJsonStructure([
            'errors' => [
                '*' => [
                    'code',
                ],
            ],
        ]);
});

it('should return unique code validation for category field options', function () {
    $categoryField = CategoryField::factory()->create(['code' => 'select_type_test_field', 'type' => 'select']);

    $data = [
        [
            'code'       => 'option_1',
            'sort_order' => 1,
        ], [
            'code'       => 'option_1',
            'sort_order' => 1,
        ],
    ];

    $response = $this->withHeaders($this->headers)->json('POST', route('admin.api.category-fields-options.store_option', $categoryField->code), $data)
        ->assertUnprocessable()
        ->assertJsonStructure([
            'errors' => [
                '*' => [
                    'code',
                ],
            ],
        ]);
});

it('should store category field options for a category field successfully', function () {
    $categoryField = CategoryField::factory()->create(['code' => 'checkbox_type_test_field', 'type' => 'checkbox']);

    $firstOption = $categoryField->options()->first();

    $data = [
        [
            'code'       => 'option_1',
            'sort_order' => 1,
        ], [
            'code'       => 'option_2',
            'sort_order' => 2,
        ],
    ];

    $response = $this->withHeaders($this->headers)->json('POST', route('admin.api.category-fields-options.store_option', $categoryField->code), $data)
        ->assertCreated()
        ->assertJsonFragment([
            'success' => true,
            'message' => trans('admin::app.catalog.category-fields-options.create-success'),
        ]);

    $this->assertModelWise([
        CategoryFieldOption::class => [
            [
                'category_field_id' => $categoryField->id,
                'code'              => 'option_1',
                'sort_order'        => 1,
            ], [
                'category_field_id' => $categoryField->id,
                'code'              => 'option_2',
                'sort_order'        => 2,
            ], [
                'category_field_id' => $categoryField->id,
                'code'              => $firstOption->code,
                'sort_order'        => $firstOption->sort_order,
            ],
        ],
    ]);
});

it('should return 404 error when non existent category field code is used while updating category field options', function () {
    $data = [
        [
            'code'       => 'option_1',
            'sort_order' => 1,
        ], [
            'code'       => 'option_1',
            'sort_order' => 1,
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.category-fields-options.update_option', 'non_existing_field_'), $data)
        ->assertNotFound();
});

it('should return required validation for category field option code when updating', function () {
    $categoryField = CategoryField::factory()->create(['code' => 'select_type_test_attribute', 'type' => 'multiselect']);

    $data = [
        [
            'code'       => '',
            'sort_order' => 1,
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.category-fields-options.update_option', $categoryField->code), $data)
        ->assertUnprocessable()
        ->assertJsonStructure([
            'errors' => [
                '*' => [
                    'code',
                ],
            ],
        ]);
});

it('should update category field options for a category field successfully', function () {
    $categoryField = CategoryField::factory()->create(['code' => 'select_type_test_attribute', 'type' => 'multiselect']);

    $localeCode = Locale::where('status', 1)->first()->code;

    $firstOption = $categoryField->options->first();

    $data = [
        [
            'code'       => $firstOption->code,
            'sort_order' => 1,
            'labels'     => [
                $localeCode => 'New Label',
            ],
        ], [
            'code'       => 'option_2',
            'sort_order' => 2,
            'labels'     => [
                $localeCode => 'New Label for option_2',
            ],
        ],
    ];

    $response = $this->withHeaders($this->headers)->json('PUT', route('admin.api.category-fields-options.update_option', $categoryField->code), $data)
        ->assertOk()
        ->assertJsonFragment([
            'success' => true,
            'message' => trans('admin::app.catalog.category-fields-options.update-success'),
        ]);

    $this->assertModelWise([
        CategoryFieldOption::class => [
            [
                'category_field_id' => $categoryField->id,
                'code'              => 'option_2',
                'sort_order'        => 2,
            ], [
                'category_field_id' => $categoryField->id,
                'code'              => $firstOption->code,
                'sort_order'        => 1,
            ],
        ],
    ]);

    $categoryField->refresh();

    $newOption = $categoryField->options()->where('code', 'option_2')->first();

    $this->assertInstanceOf(CategoryFieldOption::class, $newOption);

    $this->assertModelWise([
        CategoryFieldOptionTranslation::class => [
            [
                'category_field_option_id' => $firstOption->id,
                'locale'                   => $localeCode,
                'label'                    => 'New Label',
            ], [
                'category_field_option_id' => $newOption->id,
                'locale'                   => $localeCode,
                'label'                    => 'New Label for option_2',
            ],
        ],
    ]);
});
