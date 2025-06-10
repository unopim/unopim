<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Attribute\Models\AttributeOptionTranslation;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('should return the attribute options for an attribute', function () {
    $attribute = Attribute::factory()->create(['type' => 'multiselect']);

    $response = $this->getJson(route('admin.catalog.attributes.options.index', $attribute->id))
        ->assertStatus(200);

    $json = $response->json();

    $this->assertIsArray($json);
    $this->assertArrayHasKey('records', $json);
    $this->assertIsArray($json['records']);

    $optionCount = AttributeOption::where('attribute_id', $attribute->id)->count();

    // Default 3 options are created in AttributeFactory for select, multiselect, and checkbox types
    $this->assertCount(3, $json['records']);
});

it('should not allow duplicate option code for the same attribute', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $attributeId = $attribute->id;
    $option = $attribute->options()->first();

    $payload = [
        'code'    => $option->code,
        'locales' => [
            'en_US' => [
                'label' => 'Duplicate Option Label',
            ],
        ],
    ];

    $this->postJson(route('admin.catalog.attributes.options.store', $attributeId), $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors('code');

    $this->assertDatabaseMissing($this->getFullTableName(AttributeOption::class), ['code' => $option->code, 'id' => '!= '.$option->id, 'attribute_id' => $attributeId]);
});

it('should create the attribute option', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $attributeId = $attribute->id;

    $payload = [
        'code'    => 'test_option',
        'locales' => [
            'en_US' => [
                'label' => 'Test Option Label',
            ],
        ],
    ];

    $response = $this->postJson(route('admin.catalog.attributes.options.store', $attributeId), $payload)
        ->assertStatus(200)
        ->assertJsonFragment(['message' => trans('admin::app.catalog.attributes.edit.option.create-success')]);

    $this->assertDatabaseHas($this->getFullTableName(AttributeOption::class), ['code' => 'test_option', 'attribute_id' => $attributeId]);

    $optionId = AttributeOption::where('code', 'test_option')->where('attribute_id', $attributeId)->first()->id;

    $this->assertDatabaseHas(
        $this->getFullTableName(AttributeOptionTranslation::class),
        ['label' => 'Test Option Label', 'locale' => 'en_US', 'attribute_option_id' => $optionId]
    );
});

it('should update the attribute option', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $option = $attribute->options()->first();

    $payload = [
        'code'    => 'updated_option',
        'locales' => [
            'en_US' => [
                'label' => 'Updated Option Label',
            ],
        ],
    ];

    $response = $this->putJson(route('admin.catalog.attributes.options.update', [$attribute->id, $option->id]), $payload);
    $response->assertStatus(200)
        ->assertJsonFragment(['message' => trans('admin::app.catalog.attributes.edit.option.update-success')]);

    $this->assertDatabaseHas($this->getFullTableName(AttributeOptionTranslation::class), ['attribute_option_id' => $option->id, 'label' => 'Updated Option Label', 'locale' => 'en_US']);
});

it('should delete the attribute option', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $option = $attribute->options()->first();

    $response = $this->deleteJson(route('admin.catalog.attributes.options.delete', [$attribute->id, $option->id]));
    $response->assertStatus(200)
        ->assertJsonFragment(['message' => trans('admin::app.catalog.attributes.edit.option.delete-success')]);

    $this->assertDatabaseMissing($this->getFullTableName(AttributeOption::class), ['id' => $option->id, 'attribute_id' => $attribute->id]);
});

it('should update the sort order of attribute options when sorted up', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);

    $attributeId = $attribute->id;

    $options = $attribute->options()->orderBy('sort_order')->get();

    $optionIds = [$options[2]->id, $options[0]->id, $options[1]->id];

    $fromIndex = $options[2]->id;
    $toIndex = $options[0]->id;

    $direction = 'up';

    $payload = [
        'attributeId' => $attributeId,
        'fromIndex'   => $fromIndex,
        'toIndex'     => $toIndex,
        'optionIds'   => $optionIds,
        'direction'   => $direction,
    ];

    $this->putJson(route('admin.catalog.attributes.options.update_sort', $attributeId), $payload)
        ->assertStatus(200)
        ->assertJsonFragment(['message' => trans('admin::app.catalog.attributes.edit.option.sort-update-success')]);

    $attribute->refresh();

    $sortedOptionIds = $attribute->options()->orderBy('sort_order')->get()->map(fn ($option) => $option->id);

    $this->assertEquals($optionIds, $sortedOptionIds->toArray());
});

it('should update the sort order of attribute options when sorted down', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);

    $attributeId = $attribute->id;

    $options = $attribute->options()->orderBy('sort_order')->get();

    $optionIds = [$options[2]->id, $options[1]->id, $options[0]->id];

    $fromIndex = $options[0]->id;
    $toIndex = $options[2]->id;

    $direction = 'down';

    $payload = [
        'attributeId' => $attributeId,
        'fromIndex'   => $fromIndex,
        'toIndex'     => $toIndex,
        'optionIds'   => $optionIds,
        'direction'   => $direction,
    ];

    $this->putJson(route('admin.catalog.attributes.options.update_sort', $attributeId), $payload)
        ->assertStatus(200)
        ->assertJsonFragment(['message' => trans('admin::app.catalog.attributes.edit.option.sort-update-success')]);

    $attribute->refresh();

    $sortedOptionIds = $attribute->options()->orderBy('sort_order')->get()->map(fn ($option) => $option->id);

    $this->assertEquals($optionIds, $sortedOptionIds->toArray());
});

it('should return the attribute option for edit modal', function () {
    $attribute = Attribute::factory()->create(['type' => 'select']);
    $attributeId = $attribute->id;
    $option = $attribute->options()->first();

    $translation = $option->translations()->create([
        'label' => 'Option Label',
    ]);

    $translation->locale = 'en_US';
    $translation->save();

    $response = $this->getJson(route('admin.catalog.attributes.options.edit', [$attributeId, $option->id]))
        ->assertStatus(200)
        ->assertJsonStructure([
            'option' => [
                'id',
                'code',
                'attribute_id',
                'sort_order',
                'locales',
                'translations',
            ],
        ])
        ->json('option');

    $this->assertEquals($option->id, $response['id']);
    $this->assertEquals($attributeId, $response['attribute_id']);

    $this->assertEquals(
        $option->translations->where('locale', 'en_US')->first()?->label,
        $response['locales']['en_US']
    );
});
