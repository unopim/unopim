<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Product\Models\Product;

it('should return the family index datgrid page', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.catalog.families.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.families.index.title'));
});

it('should return the family create page successfully', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.catalog.families.create'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.families.create.title'));
});

it('should return validation error for unique family code', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::factory()->create();

    $this->post(route('admin.catalog.families.store'), ['code' => $family->code])
        ->assertRedirect()
        ->assertInvalid('code');
});

it('should return validation error for invalid code with special characters or spaces', function () {
    $this->loginAsAdmin();

    $this->post(route('admin.catalog.families.store'), ['code' => 'Test -Family'])
        ->assertRedirect()
        ->assertInvalid('code');
});

it('should create the family sucessfully', function () {
    $this->loginAsAdmin();

    $data = [
        'code' => 'attribute_family_1092345629823_s',
    ];

    $groups = AttributeGroup::factory()->count(3)->create();

    $attributes = Attribute::factory()->count(6)->create()->chunk(2)->toArray();

    $attributeGroupId = null;

    $attributeId = null;

    $position = 1;

    foreach ($groups as $group) {
        $groupId = $group['id'];

        $attributeGroupId ??= $groupId;

        $data['attribute_groups'][$groupId] = [
            'position'          => $position,
            'custom_attributes' => [],
        ];

        $pos = 1;

        foreach ($attributes[$position - 1] as $key => $attr) {
            $attributeId ??= $attr['id'];

            $data['attribute_groups'][$groupId]['custom_attributes'][] = [
                'id'       => $attr['id'],
                'position' => $pos,
            ];

            $pos++;
        }

        $position++;
    }

    $this->post(route('admin.catalog.families.store'), $data)
        ->assertRedirect(route('admin.catalog.families.index'))
        ->assertSessionHas('success', trans('admin::app.catalog.families.create-success'));

    $family = AttributeFamily::where('code', $data['code'])->first();

    $this->assertTrue($family instanceof AttributeFamily);

    $familyGroupMappingId = $family->familyGroups()->where('attribute_group_id', $attributeGroupId)
        ->pluck('attribute_family_group_mappings.id')
        ->first();

    $this->assertIsInt($familyGroupMappingId);

    $this->assertDatabaseHas('attribute_group_mappings', [
        'attribute_family_group_id' => $familyGroupMappingId,
        'attribute_id'              => $attributeId,
    ]);
});

it('should return the family edit page', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::factory()->create();

    $this->get(route('admin.catalog.families.edit', $family->id))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.families.edit.title'));
});

it('should update the family with new attributes and group successfully', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::factory()->create();

    $attributeGroupId = AttributeGroup::factory()->create()->id;

    $attributeId = Attribute::factory()->create()->id;

    $familyId = $family->id;

    $data = [
        'code' => $family->code,

        'attribute_groups' => [
            $attributeGroupId => [
                'attribute_groups_mapping' => null,
                'position'                 => 1,
                'custom_attributes'        => [
                    [
                        'id'       => $attributeId,
                        'position' => 1,
                    ],
                ],
            ],
        ],
    ];

    $this->put(route('admin.catalog.families.update', $familyId), $data)
        ->assertRedirect(route('admin.catalog.families.update', $familyId))
        ->assertSessionHas('success', trans('admin::app.catalog.families.update-success'));

    $familyGroupMappingId = $family->familyGroups()->where('attribute_group_id', $attributeGroupId)
        ->pluck('attribute_family_group_mappings.id')
        ->first();

    $this->assertIsInt($familyGroupMappingId);

    $this->assertDatabaseHas('attribute_group_mappings', [
        'attribute_family_group_id' => $familyGroupMappingId,
        'attribute_id'              => $attributeId,
    ]);
});

it('should delete the family successfully', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::factory()->create();

    $this->delete(route('admin.catalog.families.delete', $family->id))
        ->assertOk()
        ->assertJsonFragment(['message' => trans('admin::app.catalog.families.delete-success')]);

    $this->assertDatabaseMissing($this->getFullTableName(AttributeFamily::class), [
        'id'   => $family->id,
        'code' => $family->code,
    ]);
});

it('should not delete the family if the family has any products', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::factory()->create();

    Product::factory()->create(['attribute_family_id' => $family->id]);

    $this->delete(route('admin.catalog.families.delete', $family->id))
        ->assertBadRequest()
        ->assertJsonFragment(['message' => trans('admin::app.catalog.families.attribute-product-error')]);

    $this->assertDatabaseHas($this->getFullTableName(AttributeFamily::class), [
        'id'   => $family->id,
        'code' => $family->code,
    ]);
});
