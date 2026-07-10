<?php

use Webkul\Product\Models\AssociationType;
use Webkul\Product\Models\AssociationTypeField;
use Webkul\Product\Repositories\AssociationTypeRepository;

function createAssociationType(array $overrides = []): AssociationType
{
    return app(AssociationTypeRepository::class)->create(array_merge([
        'code'            => 'test_association_'.uniqid(),
        'status'          => 1,
        'position'        => 1,
        'is_user_defined' => 1,
        'en_US'           => ['name' => 'Test Association'],
    ], $overrides));
}

it('should return the association type datagrid', function () {
    $this->loginAsAdmin();

    $associationType = createAssociationType();

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.association_types.index'));

    $response->assertStatus(200);

    $data = $response->json();

    $this->assertArrayHasKey('records', $data);
    $this->assertArrayHasKey('columns', $data);

    $this->assertDatabaseHas($this->getFullTableName(AssociationType::class), [
        'id'   => $associationType->id,
        'code' => $associationType->code,
    ]);
});

it('should show validation errors when creating an association type without required fields', function () {
    $this->loginAsAdmin();

    $this->post(route('admin.catalog.association_types.store'))
        ->assertRedirect()
        ->assertInvalid('code')
        ->assertInvalid('en_US.name');
});

it('should show a validation error when creating an association type with a not-allowed code', function () {
    $this->loginAsAdmin();

    $data = [
        'code'   => 'type',
        'status' => 1,
        'en_US'  => ['name' => 'Type'],
    ];

    $this->post(route('admin.catalog.association_types.store'), $data)
        ->assertRedirect()
        ->assertInvalid('code');

    $this->assertDatabaseMissing($this->getFullTableName(AssociationType::class), ['code' => 'type']);
});

it('should create an association type with fields successfully', function () {
    $this->loginAsAdmin();

    $data = [
        'code'     => 'spare_parts_'.uniqid(),
        'status'   => 1,
        'position' => 1,
        'en_US'    => ['name' => 'Spare Parts'],
        'fields'   => [
            [
                'code'    => 'note',
                'type'    => 'text',
                'status'  => 1,
                'section' => 'left',
                'en_US'   => ['name' => 'Note'],
            ],
        ],
    ];

    $response = $this->post(route('admin.catalog.association_types.store'), $data);

    $response->assertRedirect(route('admin.catalog.association_types.index'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas($this->getFullTableName(AssociationType::class), [
        'code'            => $data['code'],
        'is_user_defined' => 1,
    ]);

    $associationType = AssociationType::where('code', $data['code'])->firstOrFail();

    $this->assertDatabaseHas($this->getFullTableName(AssociationTypeField::class), [
        'association_type_id' => $associationType->id,
        'code'                => 'note',
        'type'                => 'text',
    ]);
});

it('should reject creating an association type when a fields entry omits code or type', function () {
    $this->loginAsAdmin();

    $data = [
        'code'     => 'bypass_test_'.uniqid(),
        'status'   => 1,
        'position' => 1,
        'en_US'    => ['name' => 'Bypass Test'],
        'fields'   => [
            [
                'status'  => 1,
                'section' => 'left',
                'en_US'   => ['name' => 'No Code Field'],
            ],
        ],
    ];

    $this->post(route('admin.catalog.association_types.store'), $data)
        ->assertRedirect()
        ->assertInvalid(['fields.0.code', 'fields.0.type']);

    $this->assertDatabaseMissing($this->getFullTableName(AssociationType::class), ['code' => $data['code']]);

    $this->assertDatabaseMissing($this->getFullTableName(AssociationTypeField::class), ['code' => '']);
});

it('converts the create redirect into a json redirect_url for an ajax-form submit', function () {
    $this->loginAsAdmin();

    $data = [
        'code'   => 'ajax_type_'.uniqid(),
        'status' => 1,
        'en_US'  => ['name' => 'Ajax Type'],
    ];

    $this->withHeader('X-Ajax-Form', 'true')
        ->post(route('admin.catalog.association_types.store'), $data)
        ->assertOk()
        ->assertJson([
            'redirect_url' => route('admin.catalog.association_types.index'),
        ]);

    $this->assertDatabaseHas($this->getFullTableName(AssociationType::class), ['code' => $data['code']]);
});

it('should update the association type successfully', function () {
    $this->loginAsAdmin();

    $associationType = createAssociationType();

    $updatedData = [
        'status'   => 0,
        'position' => 5,
        'en_US'    => ['name' => 'Updated Name'],
    ];

    $this->put(route('admin.catalog.association_types.update', $associationType->id), $updatedData)
        ->assertRedirect(route('admin.catalog.association_types.edit', $associationType->id));

    $this->assertDatabaseHas($this->getFullTableName(AssociationType::class), [
        'id'       => $associationType->id,
        'status'   => 0,
        'position' => 5,
    ]);
});

it('should not change the code or is_user_defined of a default association type on update', function () {
    $this->loginAsAdmin();

    // Reuse the type seeded by the Task 5 default-types migration instead of
    // inserting a duplicate row (code is unique).
    $default = AssociationType::where('code', 'related_products')->firstOrFail();

    $this->put(route('admin.catalog.association_types.update', $default->id), [
        'code'            => 'hijacked_code',
        'is_user_defined' => 1,
        'status'          => 1,
        'en_US'           => ['name' => 'Related Products Updated'],
    ])->assertRedirect(route('admin.catalog.association_types.edit', $default->id));

    $this->assertDatabaseHas($this->getFullTableName(AssociationType::class), [
        'id'              => $default->id,
        'code'            => 'related_products',
        'is_user_defined' => 0,
    ]);
});

it('should delete a user-defined association type successfully', function () {
    $this->loginAsAdmin();

    $associationType = createAssociationType(['is_user_defined' => 1]);

    $this->delete(route('admin.catalog.association_types.delete', $associationType->id))
        ->assertOk();

    $this->assertDatabaseMissing($this->getFullTableName(AssociationType::class), ['id' => $associationType->id]);
});

it('should not delete a default association type and should return an error', function () {
    $this->loginAsAdmin();

    $default = createAssociationType(['code' => 'up_sells_test', 'is_user_defined' => 0]);

    $this->delete(route('admin.catalog.association_types.delete', $default->id))
        ->assertBadRequest();

    $this->assertDatabaseHas($this->getFullTableName(AssociationType::class), ['id' => $default->id]);
});

it('should mass delete user-defined association types successfully', function () {
    $this->loginAsAdmin();

    $ids = collect(range(1, 3))->map(fn () => createAssociationType(['is_user_defined' => 1])->id)->toArray();

    $this->post(route('admin.catalog.association_types.mass_delete'), ['indices' => $ids])
        ->assertOk();

    foreach ($ids as $id) {
        $this->assertDatabaseMissing($this->getFullTableName(AssociationType::class), ['id' => $id]);
    }
});

it('should not mass delete default association types', function () {
    $this->loginAsAdmin();

    $default = createAssociationType(['code' => 'cross_sells_test', 'is_user_defined' => 0]);

    $this->post(route('admin.catalog.association_types.mass_delete'), ['indices' => [$default->id]])
        ->assertBadRequest();

    $this->assertDatabaseHas($this->getFullTableName(AssociationType::class), ['id' => $default->id]);
});

it('should delete only the user-defined association type in a mixed massDestroy batch and keep the default type', function () {
    $this->loginAsAdmin();

    $default = createAssociationType(['code' => 'mixed_default_test', 'is_user_defined' => 0]);
    $userDefined = createAssociationType(['is_user_defined' => 1]);

    $this->post(route('admin.catalog.association_types.mass_delete'), [
        'indices' => [$default->id, $userDefined->id],
    ])->assertOk();

    $this->assertDatabaseHas($this->getFullTableName(AssociationType::class), ['id' => $default->id]);
    $this->assertDatabaseMissing($this->getFullTableName(AssociationType::class), ['id' => $userDefined->id]);
});

it('should mass update the status of association types', function () {
    $this->loginAsAdmin();

    $ids = collect(range(1, 2))->map(fn () => createAssociationType(['status' => 1])->id)->toArray();

    $this->post(route('admin.catalog.association_types.mass_update'), ['indices' => $ids, 'value' => 0])
        ->assertOk();

    foreach ($ids as $id) {
        $this->assertDatabaseHas($this->getFullTableName(AssociationType::class), ['id' => $id, 'status' => 0]);
    }
});

it('should render the create page with the reusable field-builder component', function () {
    $this->loginAsAdmin();

    $response = $this->get(route('admin.catalog.association_types.create'));

    $response->assertStatus(200);

    // The field-builder component renders the "Type" field-select label
    // (borrowed from the category field form) for every field row.
    $response->assertSee(trans('admin::app.catalog.category_fields.create.type'), false);

    // Every association-field type option (sourced from
    // `config('association_field_types')`) must be present in the type dropdown.
    $response->assertSee(trans('admin::app.catalog.attributes.create.text'), false);
    $response->assertSee(trans('admin::app.catalog.attributes.create.select'), false);

    $response->assertSee(trans('admin::app.catalog.association_types.fields.add-field-btn'), false);
});

it('should render the edit page with the quantity field prefilled', function () {
    $this->loginAsAdmin();

    $data = [
        'code'     => 'edit_prefill_'.uniqid(),
        'status'   => 1,
        'position' => 1,
        'en_US'    => ['name' => 'Edit Prefill'],
        'fields'   => [
            [
                'code'    => 'quantity',
                'type'    => 'text',
                'status'  => 1,
                'section' => 'left',
                'en_US'   => ['name' => 'Quantity'],
            ],
        ],
    ];

    $this->post(route('admin.catalog.association_types.store'), $data)
        ->assertRedirect(route('admin.catalog.association_types.index'));

    $associationType = AssociationType::where('code', $data['code'])->firstOrFail();

    $response = $this->get(route('admin.catalog.association_types.edit', $associationType->id));

    $response->assertStatus(200);

    // The `quantity` field's code is embedded server-side in the field-builder's
    // initial Vue data payload, so it must be present in the raw HTML response.
    $response->assertSee('quantity', false);
    $response->assertSee($associationType->code, false);
});
