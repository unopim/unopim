<?php

use Webkul\Attribute\Models\AttributeGroup;

it('quick-creates an attribute group and returns its option payload', function () {
    $this->loginAsAdmin();

    $response = $this->postJson(route('admin.catalog.attribute.groups.quick-store'), [
        'code' => 'marketing_copy',
        'name' => 'Marketing Copy',
    ])->assertOk();

    $group = AttributeGroup::where('code', 'marketing_copy')->firstOrFail();

    $response->assertJsonPath('data.id', $group->id);
    $response->assertJsonPath('data.code', 'marketing_copy');
    $response->assertJsonPath('data.label', 'Marketing Copy');

    expect($group->name)->toBe('Marketing Copy');
});

it('rejects a duplicate group code on quick-create', function () {
    $this->loginAsAdmin();

    AttributeGroup::create(['code' => 'existing_group']);

    $this->postJson(route('admin.catalog.attribute.groups.quick-store'), [
        'code' => 'existing_group',
        'name' => 'Existing Group',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('code');
});

it('rejects an invalid group code on quick-create', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.catalog.attribute.groups.quick-store'), [
        'code' => 'Not A Code!',
        'name' => 'Not A Code!',
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('code');
});
