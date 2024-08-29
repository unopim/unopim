<?php

use Illuminate\Support\Arr;
use Webkul\Category\Contracts\CategoryFieldOption;
use Webkul\Category\Models\CategoryField;
use Webkul\Category\Rules\NotSupportedFields;

it('should return the Category Field index page', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.catalog.category_fields.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.category_fields.index.title'));
});

it('should show the create category field form', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.catalog.category_fields.create'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.category_fields.create.title'));
});

it('should show validations for code,type,status,position and section fields on creating category field', function () {
    $this->loginAsAdmin();

    $this->post(route('admin.catalog.category_fields.store'))
        ->assertRedirect()
        ->assertInvalid('code')
        ->assertInvalid('type')
        ->assertInvalid('status')
        ->assertInvalid('position')
        ->assertInvalid('section');
});

it('should show validation error on creation of category field with not allowed codes', function () {
    $this->loginAsAdmin();

    $code = Arr::random(NotSupportedFields::FILED_CODES);

    $data = [
        'code'     => $code,
        'type'     => 'text',
        'status'   => 1,
        'position' => 1,
        'section'  => 'left',
    ];

    $this->post(route('admin.catalog.category_fields.store'), $data)
        ->assertRedirect()
        ->assertInvalid('code');

    $this->assertDatabaseMissing($this->getFullTableName(CategoryField::class), $data);
});

it('should create category field successfully', function () {
    $this->loginAsAdmin();

    $data = [
        'code'     => 'Test_category_Field_0_0',
        'type'     => 'text',
        'status'   => 1,
        'position' => 1,
        'section'  => 'left',
    ];

    $this->post(route('admin.catalog.category_fields.store'), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.category_fields.create-success'))
        ->assertRedirect(route('admin.catalog.category_fields.index'));

    $this->assertDatabaseHas($this->getFullTableName(CategoryField::class), $data);
});

it('should show the category field edit form', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create();

    $this->get(route('admin.catalog.category_fields.edit', ['id' => $categoryField->id]))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.category_fields.edit.title'));
});

it('should update the category field successfully', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create();

    $categoryFieldId = $categoryField->id;

    $updatedData = [
        'code'        => $categoryField->code,
        'type'        => $categoryField->type,
        'status'      => 1,
        'position'    => $categoryField->position + 1,
        'section'     => 'left',
        'is_required' => 0,
    ];

    $this->put(route('admin.catalog.category_fields.update', $categoryFieldId), $updatedData)
        ->assertRedirect(route('admin.catalog.category_fields.edit', $categoryFieldId))
        ->assertSessionHas('success', trans('admin::app.catalog.category_fields.update-success'));

    $this->assertDatabaseHas($this->getFullTableName(CategoryField::class), [
        'id' => $categoryFieldId,
        ...$updatedData,
    ]);
});

it('should not update the value per locale,type,is_unique and code property in Category Field', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create([
        'code'             => 'test_Category_Field_00_0',
        'value_per_locale' => 0,
        'type'             => 'text',
        'is_required'      => 0,
        'is_unique'        => 0,
    ]);

    $categoryFieldId = $categoryField->id;

    $updatedData = [
        'code'             => 'updated_'.$categoryField->code,
        'type'             => 'textarea',
        'value_per_locale' => 1,
        'status'           => 1,
        'position'         => 2,
        'section'          => 'left',
        'is_unique'        => 1,
        'is_required'      => 1,
    ];

    $this->put(route('admin.catalog.category_fields.update', $categoryFieldId), $updatedData)
        ->assertSessionHas('success', trans('admin::app.catalog.category_fields.update-success'))
        ->assertRedirect(route('admin.catalog.category_fields.edit', $categoryFieldId));

    $this->assertDatabaseMissing($this->getFullTableName(CategoryField::class), $updatedData);

    /** It will skip those attributes but update the ones which are not disabled like is_required and labels */
    $this->assertDatabaseHas($this->getFullTableName(CategoryField::class), [
        'code'             => 'test_Category_Field_00_0',
        'value_per_locale' => 0,
        'type'             => 'text',
        'is_unique'        => 0,
        'is_required'      => 1,
    ]);
});

it('should delete the Category Field successfully', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create();

    $this->delete(route('admin.catalog.category_fields.delete', $categoryField->id))
        ->assertOk()
        ->assertJsonFragment([
            'message' => trans('admin::app.catalog.category_fields.delete-success'),
        ]);

    $this->assertDatabaseMissing($this->getFullTableName(CategoryField::class), ['id' => $categoryField->id]);
});

it('should not delete the "name" category field and display flash error', function () {
    $this->loginAsAdmin();

    $categoryFieldId = CategoryField::where('code', 'name')->first()->id;

    $this->delete(route('admin.catalog.category_fields.delete', $categoryFieldId))
        ->assertBadRequest()
        ->assertJsonFragment([
            'message' => trans('admin::app.catalog.category_fields.index.datagrid.delete-failed'),
        ]);

    $this->assertDatabaseHas($this->getFullTableName(CategoryField::class), ['id' => $categoryFieldId, 'code' => 'name']);
});

it('should mass delete category fields successfully', function () {
    $this->loginAsAdmin();

    $categoryFieldIds = CategoryField::factory()->count(3)->create()->pluck('id')->toArray();

    $this->post(route('admin.catalog.category_fields.mass_delete'), ['indices' => $categoryFieldIds])
        ->assertOk()
        ->assertJsonFragment([
            'message' => trans('admin::app.catalog.category_fields.index.datagrid.mass-delete-success'),
        ]);

    foreach ($categoryFieldIds as $id) {
        $this->assertDatabaseMissing($this->getFullTableName(CategoryField::class), ['id' => $id]);
    }
});

it('should not delete the "name" category field through mass delete ', function () {
    $this->loginAsAdmin();

    $categoryFieldIds = CategoryField::factory()->count(3)->create()->pluck('id')->toArray();

    $nameFieldId = CategoryField::where('code', 'name')->first()->id;

    $this->post(route('admin.catalog.category_fields.mass_delete'), ['indices' => [...$categoryFieldIds, $nameFieldId]])
        ->assertOk()
        ->assertJsonFragment([
            'message' => trans('admin::app.catalog.category_fields.index.datagrid.mass-delete-success'),
        ]);

    foreach ($categoryFieldIds as $id) {
        $this->assertDatabaseMissing($this->getFullTableName(CategoryField::class), ['id' => $id]);
    }

    $this->assertDatabaseHas($this->getFullTableName(CategoryField::class), ['id' => $nameFieldId, 'code' => 'name']);
});

it('should return error mesage when only "name" category field is selected through mass delete ', function () {
    $this->loginAsAdmin();

    $nameFieldId = CategoryField::where('code', 'name')->first()->id;

    $this->post(route('admin.catalog.category_fields.mass_delete'), ['indices' => [$nameFieldId]])
        ->assertBadRequest()
        ->assertJsonFragment([
            'message' => trans('admin::app.catalog.category_fields.index.datagrid.mass-delete-failed'),
        ]);

    $this->assertDatabaseHas($this->getFullTableName(CategoryField::class), ['id' => $nameFieldId, 'code' => 'name']);
});

it('should update the status of category fields to enabled through mass update', function () {
    $this->loginAsAdmin();

    $categoryFieldIds = CategoryField::factory()->count(3)->create(['status' => 0])->pluck('id')->toArray();

    $this->post(route('admin.catalog.category_fields.mass_update'), ['indices' => $categoryFieldIds, 'value' => 1])
        ->assertOk()
        ->assertJsonFragment([
            'message' => trans('admin::app.catalog.category_fields.index.datagrid.mass-update-success'),
        ]);

    foreach ($categoryFieldIds as $id) {
        $this->assertDatabaseHas($this->getFullTableName(CategoryField::class), ['id' => $id, 'status' => 1]);
    }
});

it('should update the status of category fields to disabled through mass update', function () {
    $this->loginAsAdmin();

    $categoryFieldIds = CategoryField::factory()->count(3)->create(['status' => 1])->pluck('id')->toArray();

    $this->post(route('admin.catalog.category_fields.mass_update'), ['indices' => $categoryFieldIds, 'value' => 0])
        ->assertOk()
        ->assertJsonFragment([
            'message' => trans('admin::app.catalog.category_fields.index.datagrid.mass-update-success'),
        ]);

    foreach ($categoryFieldIds as $id) {
        $this->assertDatabaseHas($this->getFullTableName(CategoryField::class), ['id' => $id, 'status' => 0]);
    }
});

it('should create a select,multiselect,checkbox type category field with options', function () {
    $this->loginAsAdmin();

    $data = [
        'code'     => 'Select_Test_field_0_0',
        'type'     => 'select',
        'status'   => 1,
        'position' => 1,
        'section'  => 'left',
        'options'  => [
            [
                'code'       => 'option_1',
                'sort_order' => 1,
            ], [
                'code'       => 'option_2',
                'sort_order' => 2,
            ],
        ],
    ];

    $this->post(route('admin.catalog.category_fields.store'), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.category_fields.create-success'))
        ->assertRedirect(route('admin.catalog.category_fields.index'));

    $categoryField = CategoryField::where('code', 'Select_Test_field_0_0')->first();

    $this->assertTrue($categoryField instanceof CategoryField);

    $categoryField = $categoryField->id;

    $this->assertModelWise([
        CategoryFieldOption::class => [
            [
                'code'              => 'option_1',
                'sort_order'        => 1,
                'category_field_id' => $categoryField,
            ], [
                'code'              => 'option_2',
                'sort_order'        => 2,
                'category_field_id' => $categoryField,
            ],
        ],
    ]);
});

it('should fetch the category field options by sort order as json successfully', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create(['type' => 'multiselect']);

    $response = $this->get(route('admin.catalog.category_fields.options', $categoryField->id))
        ->assertOk();

    $responseData = $response->json();

    $fieldOptions = $categoryField->options()->orderBy('sort_order')->first()->toArray();

    $this->assertIsArray($responseData);

    $this->assertEquals($responseData[0], $fieldOptions);
});

it('should update the category field options', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create(['type' => 'checkbox']);

    $categoryFieldId = $categoryField->id;

    $data = [
        'code'     => $categoryField->code,
        'type'     => $categoryField->type,
        'status'   => 1,
        'position' => 1,
        'section'  => 'left',
        'options'  => [],
    ];

    $option = $categoryField->options->first();

    $data['options'][$option->id] = [
        'code'              => $option->code,
        'sort_order'        => 3,
        'isNew'             => 'false',
        'isDelete'          => 'false',
        'category_field_id' => $categoryFieldId,
    ];

    $this->put(route('admin.catalog.category_fields.update', $categoryFieldId), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.category_fields.update-success'))
        ->assertRedirect(route('admin.catalog.category_fields.edit', $categoryFieldId));

    $this->assertDatabaseHas($this->getFullTableName(CategoryFieldOption::class), [
        'code'              => $option->code,
        'sort_order'        => 3,
        'category_field_id' => $categoryFieldId,
    ]);
});

it('should create a new option when updating the category field', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create(['type' => 'checkbox']);

    $categoryFieldId = $categoryField->id;

    $data = [
        'code'     => $categoryField->code,
        'type'     => $categoryField->type,
        'status'   => 1,
        'position' => 1,
        'section'  => 'left',
        'options'  => [],
    ];

    $data['options'][] = [
        'code'       => 'testing_code_1',
        'sort_order' => 1,
        'isNew'      => 'true',
    ];

    $this->put(route('admin.catalog.category_fields.update', $categoryFieldId), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.category_fields.update-success'))
        ->assertRedirect(route('admin.catalog.category_fields.edit', $categoryFieldId));

    $this->assertDatabaseHas($this->getFullTableName(CategoryFieldOption::class), [
        'code'              => 'testing_code_1',
        'sort_order'        => 1,
        'category_field_id' => $categoryFieldId,
    ]);
});

it('should remove a category field option successfully', function () {
    $this->loginAsAdmin();

    $categoryField = CategoryField::factory()->create(['type' => 'multiselect']);

    $categoryFieldId = $categoryField->id;

    $data = [
        'code'     => $categoryField->code,
        'type'     => $categoryField->type,
        'status'   => 1,
        'position' => 1,
        'section'  => 'left',
        'options'  => [],
    ];

    $option = $categoryField->options->first()->toArray();

    $data['options'][$option['id']] = [
        'code'       => $option['code'],
        'sort_order' => $option['sort_order'],
        'isNew'      => 'false',
        'isDelete'   => 'true',
    ];

    $this->put(route('admin.catalog.category_fields.update', $categoryFieldId), $data)
        ->assertSessionHas('success', trans('admin::app.catalog.category_fields.update-success'))
        ->assertRedirect(route('admin.catalog.category_fields.edit', $categoryFieldId));

    $this->assertDatabaseMissing($this->getFullTableName(CategoryFieldOption::class), [
        'code'              => $option['code'],
        'sort_order'        => $option['sort_order'],
        'category_field_id' => $categoryFieldId,
    ]);
});
