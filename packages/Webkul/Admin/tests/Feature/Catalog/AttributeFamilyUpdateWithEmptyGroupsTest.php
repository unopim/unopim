<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;

it('should not throw an error when updating an attribute family with all groups removed', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::factory()->create();

    $group = AttributeGroup::factory()->create();
    $attribute = Attribute::factory()->create();

    // First, assign a group with an attribute
    $this->put(route('admin.catalog.families.update', $family->id), [
        'code'             => $family->code,
        'attribute_groups' => [
            $group->id => [
                'attribute_groups_mapping' => null,
                'position'                 => 1,
                'custom_attributes'        => [
                    [
                        'id'       => $attribute->id,
                        'position' => 1,
                    ],
                ],
            ],
        ],
    ])->assertRedirect();

    // Now update again with empty attribute_groups (user unassigned the group)
    $this->put(route('admin.catalog.families.update', $family->id), [
        'code'             => $family->code,
        'attribute_groups' => [],
    ])->assertRedirect(route('admin.catalog.families.edit', $family->id))
        ->assertSessionHas('success', trans('admin::app.catalog.families.update-success'));
});

it('should not throw an error when updating an attribute family without attribute_groups key', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::factory()->create();

    $this->put(route('admin.catalog.families.update', $family->id), [
        'code' => $family->code,
    ])->assertRedirect(route('admin.catalog.families.edit', $family->id))
        ->assertSessionHas('success', trans('admin::app.catalog.families.update-success'));
});
