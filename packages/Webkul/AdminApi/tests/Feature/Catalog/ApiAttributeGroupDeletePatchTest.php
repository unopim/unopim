<?php

use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('deletes an unmapped attribute group', function () {
    $group = AttributeGroup::factory()->create();

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.attribute_groups.delete', $group->code))
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseMissing($this->getFullTableName(AttributeGroup::class), ['id' => $group->id]);
});

it('refuses to delete a group mapped to a family', function () {
    $group = AttributeGroup::factory()->create();
    $family = AttributeFamily::factory()->create();
    AttributeFamily::factory()->linkAttributeGroupToFamily($family, $group);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.attribute_groups.delete', $group->code))
        ->assertStatus(422);

    $this->assertDatabaseHas($this->getFullTableName(AttributeGroup::class), ['id' => $group->id]);
});

it('returns 404 deleting an unknown group', function () {
    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.attribute_groups.delete', 'does_not_exist'))
        ->assertNotFound();
});

it('patches an attribute group', function () {
    $group = AttributeGroup::factory()->create();

    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.attribute_groups.patch', $group->code), [
            'labels' => ['en_US' => 'Patched Group'],
        ])
        ->assertOk()
        ->assertJson(['success' => true]);
});

it('returns 404 patching an unknown group', function () {
    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.attribute_groups.patch', 'does_not_exist'), [
            'labels' => ['en_US' => 'Patched Group'],
        ])
        ->assertNotFound();
});

it('forbids group delete without the delete permission', function () {
    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.attribute_groups']);
    $group = AttributeGroup::factory()->create();

    $this->withHeaders($headers)
        ->json('DELETE', route('admin.api.attribute_groups.delete', $group->code))
        ->assertForbidden();
});

it('rejects unauthenticated group delete', function () {
    $group = AttributeGroup::factory()->create();

    $this->json('DELETE', route('admin.api.attribute_groups.delete', $group->code), [], [
        'Accept' => 'application/json',
    ])->assertUnauthorized();
});
