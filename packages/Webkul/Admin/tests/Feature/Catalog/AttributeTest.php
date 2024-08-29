<?php

use Webkul\Attribute\Models\Attribute;

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
