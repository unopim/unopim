<?php

use Webkul\Attribute\Models\Attribute;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('deletes a user-defined attribute', function () {
    $attribute = Attribute::factory()->create();

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.attributes.delete', $attribute->code))
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing($this->getFullTableName(Attribute::class), ['id' => $attribute->id]);
});

it('refuses to delete a non-deletable attribute', function () {
    $sku = Attribute::where('code', 'sku')->firstOrFail();

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.attributes.delete', 'sku'))
        ->assertStatus(422);

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), ['id' => $sku->id]);
});

it('returns 404 deleting an unknown attribute', function () {
    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.attributes.delete', 'does_not_exist'))
        ->assertNotFound();
});

it('patches an attribute', function () {
    $attribute = Attribute::factory()->create(['is_required' => false]);

    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.attributes.patch', $attribute->code), ['is_required' => true])
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas($this->getFullTableName(Attribute::class), [
        'id'          => $attribute->id,
        'is_required' => 1,
    ]);
});

it('returns 404 patching an unknown attribute', function () {
    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.attributes.patch', 'does_not_exist'), ['is_required' => true])
        ->assertNotFound();
});

it('forbids delete without the delete permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.attributes']);
    $attribute = Attribute::factory()->create();

    $this->withHeaders($headers)
        ->json('DELETE', route('admin.api.attributes.delete', $attribute->code))
        ->assertForbidden();
});

it('rejects unauthenticated delete', function () {
    $attribute = Attribute::factory()->create();

    $this->json('DELETE', route('admin.api.attributes.delete', $attribute->code), [], [
        'Accept' => 'application/json',
    ])->assertUnauthorized();
});
