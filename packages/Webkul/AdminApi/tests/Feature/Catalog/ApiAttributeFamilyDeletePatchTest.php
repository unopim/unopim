<?php

use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('deletes an attribute family without products', function () {
    $family = AttributeFamily::factory()->create();

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.families.delete', $family->code))
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing($this->getFullTableName(AttributeFamily::class), ['id' => $family->id]);
});

it('refuses to delete a family that has products', function () {
    $family = AttributeFamily::factory()->create();
    Product::factory()->create(['attribute_family_id' => $family->id]);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.families.delete', $family->code))
        ->assertStatus(422);

    $this->assertDatabaseHas($this->getFullTableName(AttributeFamily::class), ['id' => $family->id]);
});

it('returns 404 deleting an unknown family', function () {
    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.families.delete', 'does_not_exist'))
        ->assertNotFound();
});

it('patches an attribute family', function () {
    $family = AttributeFamily::factory()->create();

    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.families.patch', $family->code), [])
        ->assertOk()
        ->assertJson(['success' => true]);
});

it('returns 404 patching an unknown family', function () {
    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.families.patch', 'does_not_exist'), [])
        ->assertNotFound();
});

it('forbids family delete without the delete permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.families']);
    $family = AttributeFamily::factory()->create();

    $this->withHeaders($headers)
        ->json('DELETE', route('admin.api.families.delete', $family->code))
        ->assertForbidden();
});

it('rejects unauthenticated family delete', function () {
    $family = AttributeFamily::factory()->create();

    $this->json('DELETE', route('admin.api.families.delete', $family->code), [], [
        'Accept' => 'application/json',
    ])->assertUnauthorized();
});
