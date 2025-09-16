<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeColumn;
use Webkul\Attribute\Models\AttributeColumnOption;
use Webkul\Attribute\Models\AttributeColumnOptionTranslation;
use Webkul\Attribute\Models\AttributeColumnTranslation;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->attribute = Attribute::factory()->create(['type' => 'table']);
});

it('should create the attribute column', function () {
    $payload = [
        'code'       => fake()->regexify('[A-Za-z0-9]{8}'),
        'type'       => 'select',
        'validation' => 'required',
    ];

    $response = $this->postJson(
        route('admin.catalog.attributes.columns.add', $this->attribute->id),
        $payload
    );

    $response->assertStatus(200);

    $this->assertDatabaseHas('attribute_columns', [
        'attribute_id' => $this->attribute->id,
        'code'         => $payload['code'],
        'type'         => 'select',
    ]);
});

it('should return an attribute column with options', function () {
    $column = AttributeColumn::factory()->select()->create([
        'attribute_id' => $this->attribute->id,
    ]);

    $option = AttributeColumnOption::factory()->create([
        'attribute_column_id' => $column->id,
        'code'                => fake()->unique()->slug(),
    ]);

    $response = $this->getJson(route('admin.catalog.attributes.column.get', $column->id));
    $response->assertOk()
        ->assertJsonFragment(['code' => $option->code]);
});

it('should return an attribute column with options for multiselect', function () {
    $column = AttributeColumn::factory()->multiselect()->create([
        'attribute_id' => $this->attribute->id,
    ]);

    $option = AttributeColumnOption::factory()->create([
        'attribute_column_id' => $column->id,
        'code'                => fake()->unique()->slug(),
    ]);

    $response = $this->getJson(route('admin.catalog.attributes.column.get', $column->id));
    $response->assertOk()
        ->assertJsonFragment(['code' => $option->code]);
});

it('should update an attribute column', function () {
    $column = AttributeColumn::factory()->text()->create([
        'attribute_id' => $this->attribute->id,
        'code'         => fake()->regexify('[A-Za-z0-9]{8}'),
        'validation'   => 'required',
    ]);

    $newLabel = fake()->words(3, true);

    $response = $this->putJson(
        route('admin.catalog.attributes.columns.update', $column->id),
        [
            'code'       => $column->code,
            'type'       => 'text',
            'en_US'      => [
                'label' => $newLabel,
            ],

        ]
    );

    $response->assertOk();

    $this->assertDatabaseHas($this->getFullTableName(AttributeColumnTranslation::class), ['attribute_column_id' => $column->id, 'label' => $newLabel, 'locale' => 'en_US']);
});

it('should delete an attribute column', function () {
    $column = AttributeColumn::factory()->create([
        'attribute_id' => $this->attribute->id,
    ]);

    $response = $this->deleteJson(route('admin.catalog.attributes.columns.delete', $column->id));

    $response->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.catalog.attributes.edit.column.delete-success')]);

    $this->assertDatabaseMissing('attribute_columns', ['id' => $column->id]);
});

it('should create an option for an attribute column', function () {
    $column = AttributeColumn::factory()->select()->create(['attribute_id' => $this->attribute->id]);

    $payload = ['code' => fake()->regexify('[A-Za-z0-9]{8}')];

    $response = $this->postJson(route('admin.catalog.attributes.columns.options.add', $column->id), $payload);

    $response->assertStatus(201);

    $this->assertDatabaseHas('attribute_column_options', [
        'attribute_column_id' => $column->id,
        'code'                => $payload['code'],
    ]);
});

it('should create an option for an attribute column for multiselect', function () {
    $column = AttributeColumn::factory()->multiselect()->create(['attribute_id' => $this->attribute->id]);

    $payload = ['code' => fake()->regexify('[A-Za-z0-9]{8}')];

    $response = $this->postJson(route('admin.catalog.attributes.columns.options.add', $column->id), $payload);

    $response->assertStatus(201);

    $this->assertDatabaseHas('attribute_column_options', [
        'attribute_column_id' => $column->id,
        'code'                => $payload['code'],
    ]);
});

it('should update an attribute column option', function () {
    $column = AttributeColumn::factory()->create(['attribute_id' => $this->attribute->id]);

    $option = AttributeColumnOption::factory()->create([
        'attribute_column_id' => $column->id,
        'code'                => fake()->regexify('[A-Za-z0-9]{8}'),
    ]);

    $label = fake()->words(2, true);

    $this->putJson(route('admin.catalog.attributes.column.option.update', $option->id), [
        'en_US'      => [
            'label' => $label,
        ],

    ])->assertOk();

    $this->assertDatabaseHas($this->getFullTableName(AttributeColumnOptionTranslation::class), ['option_id' => $option->id, 'label' => $label, 'locale' => 'en_US']);

});

it('should not allow duplicate column code for the same attribute', function () {
    $duplicateCode = fake()->regexify('[A-Za-z0-9]{8}');

    $column = AttributeColumn::factory()->create([
        'attribute_id' => $this->attribute->id,
        'code'         => $duplicateCode,
    ]);

    $payload = [
        'code'       => $duplicateCode,
        'type'       => 'select',
        'validation' => 'required',
    ];

    $response = $this->postJson(
        route('admin.catalog.attributes.columns.add', $this->attribute->id),
        $payload
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

it('should not allow duplicate option code for the same column', function () {
    $column = AttributeColumn::factory()->select()->create([
        'attribute_id' => $this->attribute->id,
    ]);

    $duplicateOptionCode = fake()->regexify('[A-Za-z0-9]{8}');

    AttributeColumnOption::factory()->create([
        'attribute_column_id' => $column->id,
        'code'                => $duplicateOptionCode,
    ]);

    $payload = ['code' => $duplicateOptionCode];

    $response = $this->postJson(
        route('admin.catalog.attributes.columns.options.add', $column->id),
        $payload
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

it('should not allow option creation for non-select type columns', function () {
    $column = AttributeColumn::factory()->text()->create([
        'attribute_id' => $this->attribute->id,
    ]);

    $payload = ['code' => fake()->regexify('[A-Za-z0-9]{8}')];

    $response = $this->postJson(
        route('admin.catalog.attributes.columns.options.add', $column->id),
        $payload
    );

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

it('should delete an attribute column option', function () {
    $column = AttributeColumn::factory()->create(['attribute_id' => $this->attribute->id]);

    $option = AttributeColumnOption::factory()->create([
        'attribute_column_id' => $column->id,
    ]);

    $this->deleteJson(route('admin.catalog.attributes.columns.options.delete', $option->id))
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.catalog.attributes.edit.option.delete-success')]);

    $this->assertDatabaseMissing('attribute_column_options', ['id' => $option->id]);
});

it('should fetch column options with search query', function () {
    $column = AttributeColumn::factory()->create([
        'attribute_id' => $this->attribute->id,
    ]);

    $label = fake()->words(2, true);
    $option = AttributeColumnOption::factory()->create([
        'attribute_column_id' => $column->id,
        'code'                => fake()->regexify('[A-Za-z0-9]{8}'),
    ]);

    $this->putJson(route('admin.catalog.attributes.column.option.update', $option->id), [
        'en_US' => [
            'label' => $label,
        ],
    ])->assertOk();

    $queryWord = explode(' ', $label)[0];

    $response = $this->getJson(route('admin.catalog.attributes.columns.option.get', [
        'id'    => $column->id,
        'query' => $queryWord,
    ]));

    $response->assertOk()->assertJsonFragment(['label' => $label]);
});

it('should handle invalid values for table columns', function () {
    $column = AttributeColumn::factory()->create([
        'attribute_id' => $this->attribute->id,
        'type'         => 'text',
        'validation'   => 'required',
    ]);

    $selectColumn = AttributeColumn::factory()->select()->create([
        'attribute_id' => $this->attribute->id,
    ]);

    $invalidCode = fake()->regexify('[A-Za-z0-9]{4} [A-Za-z0-9]{4}');
    $response = $this->postJson(route('admin.catalog.attributes.columns.options.add', $selectColumn->id), [
        'code' => $invalidCode,
    ]);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);

    $textColumn = AttributeColumn::factory()->text()->create([
        'attribute_id' => $this->attribute->id,
    ]);

    $optionCode = fake()->regexify('[A-Za-z0-9]{8}');
    $response = $this->postJson(route('admin.catalog.attributes.columns.options.add', $textColumn->id), [
        'code' => $optionCode,
    ]);
    $response->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});
