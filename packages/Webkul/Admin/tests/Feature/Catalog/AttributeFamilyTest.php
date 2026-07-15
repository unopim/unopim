<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\Product\Models\Product;

it('should return the family index datgrid page', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.catalog.families.index'))
        ->assertOk()
        ->assertSeeText(trans('admin::app.catalog.families.index.title'));
});

it('should return the attribute family datagrid', function () {
    $this->loginAsAdmin();

    $attributeFamily = AttributeFamily::factory()->create();

    $response = $this->withHeaders([
        'Accept'           => 'application/json',
        'Content-Type'     => 'application/json',
        'X-Requested-With' => 'XMLHttpRequest',
    ])->json('GET', route('admin.catalog.families.index'));

    $response->assertStatus(200);

    $data = $response->json();

    $this->assertArrayHasKey('records', $data);
    $this->assertArrayHasKey('columns', $data);
    $this->assertNotEmpty($data['records']);

    $this->assertDatabaseHas($this->getFullTableName(AttributeFamily::class), [
        'id'   => $data['records'][0]['id'],
        'code' => $data['records'][0]['code'],
    ]);
});

it('should return validation error for unique family code', function () {
    $this->loginAsAdmin();

    $family = AttributeFamily::factory()->create();
    $locale = core()->getRequestedLocaleCode();

    $this->postJson(route('admin.catalog.families.store'), [
        'code'  => $family->code,
        $locale => [
            'name' => 'Duplicate Family',
        ],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('code');
});

it('should return validation error for invalid code with special characters or spaces', function () {
    $this->loginAsAdmin();
    $locale = core()->getRequestedLocaleCode();

    $this->postJson(route('admin.catalog.families.store'), [
        'code'  => 'Test -Family',
        $locale => [
            'name' => 'Test Family',
        ],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('code');
});

it('should return validation error for an unknown based_on family', function () {
    $this->loginAsAdmin();
    $locale = core()->getRequestedLocaleCode();

    $this->postJson(route('admin.catalog.families.store'), [
        'code'     => 'family_bad_source',
        $locale    => [
            'name' => 'Family Bad Source',
        ],
        'based_on' => 99999999,
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors('based_on');
});

it('should create a family without a name', function () {
    $this->loginAsAdmin();

    $this->postJson(route('admin.catalog.families.store'), [
        'code' => 'family_without_name',
    ])->assertOk();

    $family = AttributeFamily::where('code', 'family_without_name')->firstOrFail();

    expect($family->name)->toBeNull();
});

it('should create a family with a general group holding sku and redirect to its edit page', function () {
    $this->loginAsAdmin();
    $locale = core()->getRequestedLocaleCode();

    $response = $this->postJson(route('admin.catalog.families.store'), [
        'code'  => 'family_from_modal',
        $locale => [
            'name' => 'Family From Modal',
        ],
    ])->assertOk();

    $family = AttributeFamily::where('code', 'family_from_modal')->firstOrFail();

    $response->assertJsonPath('data.redirect_url', route('admin.catalog.families.edit', $family->id));

    $mapping = $family->attributeFamilyGroupMappings()->firstOrFail();

    expect($family->translate($locale)->name)->toBe('Family From Modal');
    expect($mapping->attributeGroups->first()->code)->toBe('general');
    expect($mapping->customAttributes->pluck('code')->all())->toBe(['sku']);
});

it('should clone the structure of the based_on family', function () {
    $this->loginAsAdmin();
    $locale = core()->getRequestedLocaleCode();

    $source = app(AttributeFamilyRepository::class)
        ->createScaffolded('family_clone_source');

    $this->postJson(route('admin.catalog.families.store'), [
        'code'     => 'family_clone_target',
        $locale    => [
            'name' => 'Family Clone Target',
        ],
        'based_on' => $source->id,
    ])->assertOk();

    $clone = AttributeFamily::where('code', 'family_clone_target')->firstOrFail();

    $mapping = $clone->attributeFamilyGroupMappings()->firstOrFail();

    expect($mapping->attributeGroups->first()->code)->toBe('general');
    expect($mapping->customAttributes->pluck('code')->all())->toBe(['sku']);
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
