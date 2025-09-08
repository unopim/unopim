<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Attribute\Models\AttributeOptionTranslation;
use Webkul\Attribute\Models\AttributeTranslation;
use Webkul\Core\Models\Locale;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should return the list of all attributes', function () {
    $attribute = Attribute::first();

    $attributeData = [
        'code'              => $attribute->code,
        'type'              => $attribute->type,
        'swatch_type'       => $attribute->swatch_type,
        'validation'        => $attribute->validation,
        'regex_pattern'     => $attribute->regex_pattern,
        'position'          => $attribute->position,
        'is_required'       => $attribute->is_required,
        'is_unique'         => $attribute->is_unique,
        'value_per_locale'  => $attribute->value_per_locale,
        'value_per_channel' => $attribute->value_per_channel,
        'enable_wysiwyg'    => $attribute->enable_wysiwyg,
        'labels'            => $attribute->translations->pluck('name', 'locale')->filter()->toArray(),
    ];

    $response = $this->withHeaders($this->headers)->json('GET', route('admin.api.attributes.index'))
        ->assertOK()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'code',
                    'type',
                    'swatch_type',
                    'validation',
                    'regex_pattern',
                    'position',
                    'is_required',
                    'is_unique',
                    'value_per_locale',
                    'value_per_channel',
                    'enable_wysiwyg',
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
        ->assertJsonFragment(['total' => Attribute::count()])
        ->json('data');

    $this->assertTrue(
        collect($response)->contains($attributeData),
    );
});

it('should return the attribute by code', function () {
    $attribute = Attribute::first();

    $attributeData = [
        'code'              => $attribute->code,
        'type'              => $attribute->type,
        'validation'        => $attribute->validation,
        'regex_pattern'     => $attribute->regex_pattern,
        'is_required'       => $attribute->is_required,
        'is_unique'         => $attribute->is_unique,
        'value_per_locale'  => $attribute->value_per_locale,
        'value_per_channel' => $attribute->value_per_channel,
        'enable_wysiwyg'    => $attribute->enable_wysiwyg,
        'labels'            => $attribute->translations->pluck('name', 'locale')->filter()->toArray(),
    ];

    $this->withHeaders($this->headers)->json('GET', route('admin.api.attributes.get', $attributeData['code']))
        ->assertOk()
        ->assertJsonStructure([
            'code',
            'type',
            'validation',
            'regex_pattern',
            'is_required',
            'is_unique',
            'value_per_locale',
            'value_per_channel',
            'enable_wysiwyg',
            'labels',
        ])
        ->assertJson($attributeData);
});

it('should return 404 error when fetching attribute by code', function () {
    $this->withHeaders($this->headers)->json('GET', route('admin.api.attributes.get', 'non_existing_attribute_'))
        ->assertNotFound()
        ->assertJsonStructure([
            'success',
            'message',
        ]);
});

it('should create a new attribute successfully', function () {
    $data = [
        'code'              => 'testing_attribute_090_9',
        'type'              => 'text',
        'validation'        => null,
        'regex_pattern'     => null,
        'is_required'       => 0,
        'is_unique'         => 0,
        'value_per_locale'  => 0,
        'value_per_channel' => 1,
        'enable_wysiwyg'    => 0,
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.attributes.store'), $data)
        ->assertCreated()
        ->assertJsonFragment(['success' => true, 'message' => trans('admin::app.catalog.attributes.create-success')]);

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), $data);
});

it('should create attributes with certain attribute types', function () {
    $attributeTypes = [
        'text',
        'textarea',
        'price',
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
            'code'              => 'testing_attribute_090_9'.$type,
            'type'              => $type,
            'validation'        => null,
            'regex_pattern'     => null,
            'is_required'       => 0,
            'is_unique'         => 0,
            'value_per_locale'  => 0,
            'value_per_channel' => 0,
            'enable_wysiwyg'    => 0,
        ];

        try {
            $this->withHeaders($this->headers)->json('POST', route('admin.api.attributes.store'), $data)
                ->assertCreated()
                ->assertJsonFragment(['success' => true, 'message' => trans('admin::app.catalog.attributes.create-success')]);

            $this->assertDatabaseHas($this->getFullTableName(Attribute::class), $data);
        } catch (\Exception $e) {
            throw new \Exception('Failed with attribute code: '.$code.'. '.$e->getMessage());
        }
    }
});

it('should create a unique attribute successfully', function () {
    $data = [
        'code'              => 'testing_attribute_090_9',
        'type'              => 'text',
        'validation'        => null,
        'regex_pattern'     => null,
        'is_required'       => 0,
        'is_unique'         => 1,
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
        'enable_wysiwyg'    => 0,
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.attributes.store'), $data)
        ->assertCreated()
        ->assertJsonFragment(['success' => true, 'message' => trans('admin::app.catalog.attributes.create-success')]);

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), $data);
});

it('should return unique validation when creating with duplicate code', function () {
    Attribute::factory()->create(['code' => 'testing_attribute_00_0', 'type' => 'textarea']);

    $data = [
        'code'              => 'testing_attribute_00_0',
        'type'              => 'text',
        'validation'        => null,
        'regex_pattern'     => null,
        'is_required'       => 0,
        'is_unique'         => 0,
        'value_per_locale'  => 0,
        'value_per_channel' => 1,
        'enable_wysiwyg'    => 0,
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.attributes.store'), $data)
        ->assertUnprocessable()
        ->assertJsonFragment(['message' => 'Validation failed.', 'success' => false])
        ->assertJsonStructure([
            'errors' => [
                'code',
            ],
        ]);

    $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), $data);
});

it('should not create attributes with certain codes', function () {
    $restrictedFields = [
        'channel',
        'locale',
        'sku',
        'type',
        'parent',
        'attribute_family',
        'configurable_attributes',
        'categories',
        'up_sells',
        'cross_sells',
        'related_products',
        'status',
    ];

    $data = [
        'type'              => 'text',
        'validation'        => null,
        'regex_pattern'     => null,
        'is_required'       => 0,
        'is_unique'         => 0,
        'value_per_locale'  => 0,
        'value_per_channel' => 1,
        'enable_wysiwyg'    => 0,
        'labels'            => [],
    ];

    foreach ($restrictedFields as $code) {
        $data['code'] = $code;

        try {
            $this->withHeaders($this->headers)->json('POST', route('admin.api.attributes.store'), $data)
                ->assertUnprocessable()
                ->assertJsonFragment(['message' => 'Validation failed.', 'success' => false])
                ->assertJsonStructure([
                    'errors' => [
                        'code',
                    ],
                ]);

            unset($data['labels']);

            $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), $data);
        } catch (\Exception $e) {
            throw new \Exception('Failed with attribute code: '.$code.'. '.$e->getMessage());
        }
    }
});

it('should give required validation for code and type field when creating attribute', function () {
    $data = [
        'code'              => '',
        'type'              => '',
        'validation'        => null,
        'regex_pattern'     => null,
        'is_required'       => 0,
        'is_unique'         => 0,
        'value_per_locale'  => 0,
        'value_per_channel' => 1,
        'enable_wysiwyg'    => 0,
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.attributes.store'), $data)
        ->assertUnprocessable()
        ->assertJsonFragment(['message' => 'Validation failed.', 'success' => false])
        ->assertJsonStructure([
            'errors' => [
                'code',
                'type',
            ],
        ]);

    $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), $data);
});

it('should give validation error when invalid type is added for creating attribute', function () {
    $data = [
        'code'              => 'testing_attribute',
        'type'              => 'rich_price_field',
        'validation'        => null,
        'regex_pattern'     => null,
        'is_required'       => 0,
        'is_unique'         => 0,
        'value_per_locale'  => 0,
        'value_per_channel' => 1,
        'enable_wysiwyg'    => 0,
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.attributes.store'), $data)
        ->assertUnprocessable()
        ->assertJsonFragment(['message' => 'Validation failed.', 'success' => false])
        ->assertJsonStructure([
            'errors' => [
                'type',
            ],
        ]);

    $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), $data);
});

it('should give validation error when invalid validation type is given', function () {
    $data = [
        'code'              => 'testing_attribute',
        'type'              => 'text',
        'validation'        => 'no_zeros',
        'regex_pattern'     => null,
        'is_required'       => 0,
        'is_unique'         => 0,
        'value_per_locale'  => 0,
        'value_per_channel' => 1,
        'enable_wysiwyg'    => 0,
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.attributes.store'), $data)
        ->assertUnprocessable()
        ->assertJsonFragment(['message' => 'Validation failed.', 'success' => false])
        ->assertJsonStructure([
            'errors' => [
                'validation',
            ],
        ]);

    $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), $data);
});

it('should create text attribute with these validation types', function () {
    $validationTypes = [
        'number',
        'email',
        'decimal',
        'url',
        'regex',
    ];

    $data = [
        'code'              => 'testing_attribute',
        'type'              => 'text',
        'validation'        => null,
        'regex_pattern'     => null,
        'is_required'       => 0,
        'is_unique'         => 0,
        'value_per_locale'  => 0,
        'value_per_channel' => 1,
        'enable_wysiwyg'    => 0,
    ];

    foreach ($validationTypes as $validation) {
        $data['code'] .= $validation;

        $data['validation'] = $validation;

        try {
            $this->withHeaders($this->headers)->json('POST', route('admin.api.attributes.store'), $data)
                ->assertCreated();

            $this->assertDatabaseHas($this->getFullTableName(Attribute::class), $data);
        } catch (\Exception $e) {
            throw new \Exception('Failed with validation type code: '.$validation.'. '.$e->getMessage());
        }
    }
});

it('should return 404 error when updating non existent attribute', function () {
    $data = [
        'code'              => 'non_existing_attribute_',
        'type'              => 'text',
        'validation'        => null,
        'regex_pattern'     => null,
        'is_required'       => 0,
        'is_unique'         => 0,
        'value_per_locale'  => 0,
        'value_per_channel' => 1,
        'enable_wysiwyg'    => 0,
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.attributes.update', 'non_existing_attribute_'), $data)
        ->assertNotFound()
        ->assertJsonStructure([
            'success',
            'message',
        ]);
});

it('should update an attribute successfully', function () {
    $attribute = Attribute::factory()->create(['type' => 'textarea', 'enable_wysiwyg' => 0]);

    $localeCode = Locale::where('status', 1)->first()->code;

    $data = [
        'code'              => $attribute->code,
        'type'              => 'textarea',
        'validation'        => $attribute->validation,
        'regex_pattern'     => $attribute->regex_pattern,
        'is_required'       => $attribute->is_required,
        'is_unique'         => $attribute->is_unique,
        'value_per_locale'  => $attribute->value_per_locale,
        'value_per_channel' => $attribute->value_per_channel,
        'enable_wysiwyg'    => 1,
        'labels'            => [
            $localeCode => 'Test Label',
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.attributes.update', $data['code']), $data)
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.catalog.attributes.update-success')]);

    unset($data['labels']);

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), $data);

    $this->assertDatabaseHas($this->getFullTableName(AttributeTranslation::class), [
        'attribute_id' => $attribute->id,
        'locale'       => $localeCode,
        'name'         => 'Test Label',
    ]);
});

it('should not update code,type,value_per_locale,value_per_channel and is_unique fields when updating an attribute', function () {
    $attribute = Attribute::factory()->create([
        'type'              => 'textarea',
        'value_per_locale'  => 1,
        'value_per_channel' => 1,
        'is_unique'         => 1,
    ]);

    $data = [
        'code'              => 'new_code_for_attribute',
        'type'              => 'price',
        'validation'        => $attribute->validation,
        'regex_pattern'     => $attribute->regex_pattern,
        'is_required'       => $attribute->is_required,
        'is_unique'         => 0,
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.attributes.update', $attribute->code), $data)
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.catalog.attributes.update-success')]);

    $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), $data);

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), [
        'code'              => $attribute->code,
        'type'              => 'textarea',
        'value_per_locale'  => 1,
        'value_per_channel' => 1,
        'is_unique'         => 1,
    ]);
});

it('should return list of attribute options for an attribute with option type', function () {
    $attribute = Attribute::factory()->create(['code' => 'select_type_test_attribute', 'type' => 'select']);

    $response = $this->withHeaders($this->headers)->json('GET', route('admin.api.attribute_options.get', $attribute->code))
        ->assertOK()
        ->assertJsonStructure([
            '*' => [
                'code',
                'sort_order',
                'labels',
            ],
        ])
        ->json();

    $attributeOptions = $attribute->options()->orderBy('sort_order');

    $this->assertEquals(count($response), $attributeOptions->count());

    $option = $attributeOptions->first();

    $this->assertEquals($response[0], [
        'code'       => $option->code,
        'sort_order' => $option->sort_order,
        'labels'     => $option->translations()->pluck('label', 'locale')->toArray(),
    ]);
});

it('should return 404 error when non existent attribute code is used while creating attribute options', function () {
    $data = [
        [
            'code'       => 'option_1',
            'sort_order' => 1,
        ], [
            'code'       => 'option_1',
            'sort_order' => 1,
        ],
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.attribute_options.store_option', 'non_existent_attribute_'), $data)
        ->assertNotFound();
});

it('should return required validation for attribute option code', function () {
    $attribute = Attribute::factory()->create(['code' => 'select_type_test_attribute', 'type' => 'multiselect']);

    $data = [
        [
            'code'       => '',
            'sort_order' => 1,
        ],
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.attribute_options.store_option', $attribute->code), $data)
        ->assertUnprocessable()
        ->assertJsonStructure([
            'errors' => [
                '*' => [
                    'code',
                ],
            ],
        ]);
});

it('should return unique code validation for attribute options', function () {
    $attribute = Attribute::factory()->create(['code' => 'select_type_test_attribute', 'type' => 'select']);

    $data = [
        [
            'code'       => 'option_1',
            'sort_order' => 1,
        ], [
            'code'       => 'option_1',
            'sort_order' => 1,
        ],
    ];

    $response = $this->withHeaders($this->headers)->json('POST', route('admin.api.attribute_options.store_option', $attribute->code), $data)
        ->assertUnprocessable()
        ->assertJsonStructure([
            'errors' => [
                '*' => [
                    'code',
                ],
            ],
        ]);
});

it('should store attribute options for an attribute successfully', function () {
    $attribute = Attribute::factory()->create(['code' => 'select_type_test_attribute', 'type' => 'checkbox']);

    $firstOption = $attribute->options()->first();

    $data = [
        [
            'code'       => 'option_1',
            'sort_order' => 1,
        ], [
            'code'       => 'option_2',
            'sort_order' => 2,
        ],
    ];

    $response = $this->withHeaders($this->headers)->json('POST', route('admin.api.attribute_options.store_option', $attribute->code), $data)
        ->assertCreated()
        ->assertJsonFragment([
            'success' => true,
            'message' => trans('admin::app.catalog.attribute-options.create-success'),
        ]);

    $this->assertModelWise([
        AttributeOption::class => [
            [
                'attribute_id' => $attribute->id,
                'code'         => 'option_1',
                'sort_order'   => 1,
            ], [
                'attribute_id' => $attribute->id,
                'code'         => 'option_2',
                'sort_order'   => 2,
            ], [
                'attribute_id' => $attribute->id,
                'code'         => $firstOption->code,
                'sort_order'   => $firstOption->sort_order,
            ],
        ],
    ]);
});

it('should return 404 error when non existent attribute code is used while updating attribute options', function () {
    $data = [
        [
            'code'       => 'option_1',
            'sort_order' => 1,
        ], [
            'code'       => 'option_1',
            'sort_order' => 1,
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.attribute_options.update_option', 'non_existent_attribute_'), $data)
        ->assertNotFound();
});

it('should return required validation for attribute option code when updating', function () {
    $attribute = Attribute::factory()->create(['code' => 'select_type_test_attribute', 'type' => 'multiselect']);

    $data = [
        [
            'code'       => '',
            'sort_order' => 1,
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.attribute_options.update_option', $attribute->code), $data)
        ->assertUnprocessable()
        ->assertJsonStructure([
            'errors' => [
                '*' => [
                    'code',
                ],
            ],
        ]);
});

it('should update attribute options for an attribute successfully', function () {
    $attribute = Attribute::factory()->create(['code' => 'select_type_test_attribute', 'type' => 'multiselect']);

    $localeCode = Locale::where('status', 1)->first()->code;

    $firstOption = $attribute->options->first();

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

    $response = $this->withHeaders($this->headers)->json('PUT', route('admin.api.attribute_options.update_option', $attribute->code), $data)
        ->assertOk()
        ->assertJsonFragment([
            'success' => true,
            'message' => trans('admin::app.catalog.attribute-options.update-success'),
        ]);

    $this->assertModelWise([
        AttributeOption::class => [
            [
                'attribute_id' => $attribute->id,
                'code'         => 'option_2',
                'sort_order'   => 2,
            ], [
                'attribute_id' => $attribute->id,
                'code'         => $firstOption->code,
                'sort_order'   => 1,
            ],
        ],
    ]);

    $attribute->refresh();

    $newOption = $attribute->options()->where('code', 'option_2')->first();

    $this->assertInstanceOf(AttributeOption::class, $newOption);

    $this->assertModelWise([
        AttributeOptionTranslation::class => [
            [
                'attribute_option_id' => $firstOption->id,
                'locale'              => $localeCode,
                'label'               => 'New Label',
            ], [
                'attribute_option_id' => $newOption->id,
                'locale'              => $localeCode,
                'label'               => 'New Label for option_2',
            ],
        ],
    ]);
});
