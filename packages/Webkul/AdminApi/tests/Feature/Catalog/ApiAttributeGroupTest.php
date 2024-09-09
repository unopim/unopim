<?php

use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Core\Models\Locale;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should return the list of all attribute group', function () {
    $attributeGroup = AttributeGroup::first();

    $this->withHeaders($this->headers)->json('GET', route('admin.api.attribute_groups.index'))
        ->assertOK()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'code',
                    'labels',
                ],
            ],
            'current_page',
            'last_page',
            'total',
            'links' => [
                'first',
                'last',
                'next',
                'prev',
            ],
        ])
        ->assertJsonFragment(['code'  => $attributeGroup->code])
        ->assertJsonFragment(['total' => AttributeGroup::count()]);
});

it('should return the Attribute group using the code', function () {
    $attributeGroup = AttributeGroup::first();

    $this->withHeaders($this->headers)->json('GET', route('admin.api.attribute_groups.get', ['code' => $attributeGroup->code]))
        ->assertOK()
        ->assertJsonStructure([
            'code',
            'labels',
        ])
        ->assertJsonFragment(['code' => $attributeGroup->code]);
});

it('should return the message when code does not exists in attribute group', function () {
    $this->withHeaders($this->headers)->json('GET', route('admin.api.attribute_groups.get', ['code' => 'abcxyz']))
        ->assertStatus(404)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => false]);
});

it('should create the attribute group', function () {
    $locale = Locale::where('status', 1)->first();

    $attributeGroup = [
        'code'   => 'other_attribute_group',
        'labels' => [
            $locale->code => 'Attribute group',
        ],
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.attribute_groups.store'), $attributeGroup)
        ->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $this->assertDatabaseHas($this->getFullTableName(AttributeGroup::class), ['code' => 'other_attribute_group']);
});

it('should give warning if code is already used in attribute group', function () {
    $attributeGroup = AttributeGroup::first();

    $locale = Locale::where('status', 1)->first();

    $attributeGroup = [
        'code'   => $attributeGroup->code,
        'labels' => [
            $locale->code => 'Attribute group',
        ],
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.attribute_groups.store'), $attributeGroup)
        ->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => [
                'code',
            ],
        ])
        ->assertJsonFragment(['success' => false]);
});

it('should give warning if code is empty in attribute group', function () {
    $locale = Locale::where('status', 1)->first();

    $attributeGroup = [
        'code'   => '',
        'labels' => [
            $locale->code => 'Attribute group',
        ],
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.attribute_groups.store'), $attributeGroup)
        ->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => [
                'code',
            ],
        ])
        ->assertJsonFragment(['success' => false]);
});

it('should update the attribute group', function () {
    $attributeGroup = AttributeGroup::factory()->create();

    $locales = Locale::where('status', 1)->first();

    $updated = [
        'code'   => $attributeGroup->code,
        'labels' => [
            $locales->code => 'Attribute group',
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.attribute_groups.update', ['code' => $updated['code']]), $updated)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);
});

it('should give warning if code is not valid', function () {
    $attributeGroup = [
        'code' => '<invalid>',
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.attribute_groups.store'), $attributeGroup)
        ->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => [
                'code',
            ],
        ])
        ->assertJsonFragment(['success' => false]);
});

it('should not update the code of attribute group', function () {
    $attributeGroup = AttributeGroup::factory()->create();

    $locales = Locale::where('status', 1)->first();

    $updated = [
        'code'   => 'updatedCode',
        'labels' => [
            $locales->code => 'Attribute group',
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.attribute_groups.update', ['code' => $attributeGroup->code]), $updated)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $this->assertDatabaseHas($this->getFullTableName(AttributeGroup::class), ['code' => $attributeGroup->code]);
});
