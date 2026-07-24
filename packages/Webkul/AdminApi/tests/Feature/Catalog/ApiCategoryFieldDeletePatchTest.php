<?php

use Webkul\Category\Models\CategoryField;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('deletes a deletable category field', function () {
    $field = CategoryField::factory()->create(['type' => 'text']);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.category-fields.delete', $field->code))
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing($this->getFullTableName(CategoryField::class), ['id' => $field->id]);
});

it('refuses to delete the non-deletable name field', function () {
    $field = CategoryField::firstOrCreate(
        ['code' => 'name'],
        CategoryField::factory()->make(['code' => 'name', 'type' => 'text'])->getAttributes()
    );

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.category-fields.delete', 'name'))
        ->assertStatus(422);

    $this->assertDatabaseHas($this->getFullTableName(CategoryField::class), ['id' => $field->id]);
});

it('returns 404 deleting an unknown category field', function () {
    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.category-fields.delete', 'does_not_exist'))
        ->assertNotFound();
});

it('patches a category field', function () {
    $field = CategoryField::factory()->create(['type' => 'text', 'is_required' => 0]);

    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.category-fields.patch', $field->code), ['is_required' => 1])
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas($this->getFullTableName(CategoryField::class), [
        'id'          => $field->id,
        'is_required' => 1,
    ]);
});

it('returns 404 patching an unknown category field', function () {
    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.category-fields.patch', 'does_not_exist'), ['is_required' => 1])
        ->assertNotFound();
});

it('forbids category field delete without the delete permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.category_fields']);
    $field = CategoryField::factory()->create(['type' => 'text']);

    $this->withHeaders($headers)
        ->json('DELETE', route('admin.api.category-fields.delete', $field->code))
        ->assertForbidden();
});

it('rejects unauthenticated category field delete', function () {
    $field = CategoryField::factory()->create(['type' => 'text']);

    $this->json('DELETE', route('admin.api.category-fields.delete', $field->code), [], [
        'Accept' => 'application/json',
    ])->assertUnauthorized();
});
