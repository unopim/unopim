<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Models\Locale;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\get;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

it('should return the Attribute index page', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.catalog.attributes.index'));

    $response->assertStatus(200)
        ->assertSeeText(trans('admin::app.catalog.attributes.index.title'));
});

it('should create the Attribute', function () {
    $this->loginAsAdmin();

    $attribute = [
        'code' => 'testAttribute',
        'type' => 'text',
    ];

    $response = postJson(route('admin.catalog.attributes.store'), $attribute);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.index'));

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), $attribute);
});

it('should create the gallery type Attribute', function () {
    $this->loginAsAdmin();

    $attribute = [
        'code' => 'testAttribute',
        'type' => 'gallery',
    ];

    $response = postJson(route('admin.catalog.attributes.store'), $attribute);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.index'));

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), $attribute);
});

it('should return the attribute datagrid', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create();

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.attributes.index'));

    $response->assertStatus(200);

    $data = $response->json();

    $this->assertArrayHasKey('records', $data);
    $this->assertArrayHasKey('columns', $data);
    $this->assertNotEmpty($data['records']);

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), [
        'id'   => $data['records'][0]['id'],
        'code' => $data['records'][0]['code'],
    ]);
});

it('should show the create attribute form', function () {
    $this->loginAsAdmin();

    $response = get(route('admin.catalog.attributes.create'));

    $response->assertStatus(200)
        ->assertSeeText(trans('admin::app.catalog.attributes.create.title'));
});

it('should show the edit attribute form', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create();

    $response = get(route('admin.catalog.attributes.edit', ['id' => $attribute->id]));

    $response->assertStatus(200)
        ->assertSeeText(trans('admin::app.catalog.attributes.edit.title'));
});

it('should update the Attribute', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create();

    $updatedData = [
        'code'        => $attribute->code,
        'type'        => $attribute->type,
        'is_required' => 1,
    ];

    $response = putJson(route('admin.catalog.attributes.update', $attribute->id), $updatedData);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), $updatedData);
});

it('should not update the value per channel property in Attribute', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create();

    $updatedData = [
        'code'              => $attribute->code,
        'type'              => $attribute->type,
        'is_required'       => 1,
        'value_per_channel' => 1,
    ];

    $response = putJson(route('admin.catalog.attributes.update', $attribute->id), $updatedData);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), $updatedData);
});

it('should not update the value per locale property in Attribute', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create();

    $updatedData = [
        'code'             => $attribute->code,
        'type'             => $attribute->type,
        'is_required'      => 1,
        'value_per_locale' => 1,
    ];

    $response = putJson(route('admin.catalog.attributes.update', $attribute->id), $updatedData);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));

    $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), $updatedData);
});

it('should not update the type and code of Attribute', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create();

    $updatedData = [
        'code'             => 'updated'.$attribute->code,
        'type'             => $attribute->type == 'text' ? 'textarea' : 'text',
        'is_required'      => 1,
        'value_per_locale' => 1,
    ];

    $response = putJson(route('admin.catalog.attributes.update', $attribute->id), $updatedData);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));

    $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), $updatedData);
});

it('should delete the Attribute', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create();

    $response = deleteJson(route('admin.catalog.attributes.delete', $attribute->id));

    $response->assertStatus(200)
        ->assertJson([
            'message' => trans('admin::app.catalog.attributes.delete-success'),
        ]);

    $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), ['id' => $attribute->id]);
});

it('should not delete the sku Attribute', function () {
    $this->loginAsAdmin();

    $sku = Attribute::where('code', 'sku');

    $response = deleteJson(route('admin.catalog.attributes.delete', $sku->first()->id));

    $response->assertStatus(400)
        ->assertJson([
            'message' => trans('admin::app.catalog.attributes.index.datagrid.delete-failed'),
        ]);

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), ['id' => $sku->first()->id]);
});

it('should mass delete attributes', function () {
    $this->loginAsAdmin();

    $attributes = Attribute::factory()->count(3)->create();

    $attributeIds = $attributes->pluck('id')->toArray();

    $response = postJson(route('admin.catalog.attributes.mass_delete'), ['indices' => $attributeIds]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => trans('admin::app.catalog.attributes.index.datagrid.mass-delete-success'),
        ]);

    foreach ($attributeIds as $id) {
        $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), ['id' => $id]);
    }
});

it('should not delete sku with mass delete attributes', function () {
    $this->loginAsAdmin();

    $attributes = Attribute::factory()->count(3)->create();
    $sku = Attribute::where('code', 'sku')?->first()?->id;

    $attributeIds = $attributes->pluck('id')->toArray();
    $attributeIds[] = $sku;

    $response = postJson(route('admin.catalog.attributes.mass_delete'), ['indices' => $attributeIds]);

    $response->assertStatus(200)
        ->assertJson([
            'message' => trans('admin::app.catalog.attributes.index.datagrid.mass-delete-success'),
        ]);

    foreach ($attributeIds as $id) {
        if ($id === $sku) {
            $this->assertDatabaseHas($this->getFullTableName(Attribute::class), ['id' => $id]);

            continue;
        }

        $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), ['id' => $id]);
    }
});

it('should fetch attribute options', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'select']);
    $response = get(route('admin.catalog.attributes.options', ['id' => $attribute->id]));

    $responseData = $response->json();
    $this->assertIsArray($responseData);

    $this->assertEquals($responseData[0], $attribute->options()->orderBy('sort_order')->first()->toArray());

    $response->assertStatus(200);
});

it('should fetch attribute options for multiselect type attribute', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'multiselect']);
    $response = get(route('admin.catalog.attributes.options', ['id' => $attribute->id]));

    $responseData = $response->json();
    $this->assertIsArray($responseData);

    $this->assertEquals($responseData[0], $attribute->options()->orderBy('sort_order')->first()->toArray());

    $response->assertStatus(200);
});

it('should update attribute options', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'select']);

    $locales = Locale::where('status', 1)->limit(1);
    $locale = $locales->first()->toArray();
    $option = $attribute->options()->first();

    $updatedData = [
        'code'    => $attribute->code,
        'type'    => $attribute->type,
        'options' => [
            $option->id => [
                'isNew'         => false,
                'isDelete'      => false,
                $locale['code'] => ['label' => fake()->word()],
            ],
        ],
    ];

    $response = putJson(route('admin.catalog.attributes.update', $attribute->id), $updatedData);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));
});

it('should delete an attribute option', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'select']);
    $option = $attribute->options()->first();

    $updatedData = [
        'code'    => $attribute->code,
        'type'    => $attribute->type,
        'options' => [
            $option->id => [
                'isNew'    => false,
                'isDelete' => true,
            ],
        ],
    ];

    $response = putJson(route('admin.catalog.attributes.update', $attribute->id), $updatedData);
    $this->assertDatabaseMissing('attribute_options', ['id' => $option->id]);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));
});

it('should enable Wysiwyg in textarea type attribute', function () {
    $this->loginAsAdmin();

    $attribute = Attribute::factory()->create(['type' => 'textarea']);

    $updatedData = [
        'code'           => $attribute->code,
        'type'           => $attribute->type,
        'enable_wysiwyg' => 1,
    ];

    $response = putJson(route('admin.catalog.attributes.update', $attribute->id), $updatedData);

    $response->assertStatus(302)
        ->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), $updatedData);
});
