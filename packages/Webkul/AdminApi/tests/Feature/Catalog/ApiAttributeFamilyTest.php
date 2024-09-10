<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Attribute\Models\AttributeGroup;
use Webkul\Core\Models\Locale;

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

it('should return the list of all attribute family', function () {
    $attributeFamily = AttributeFamily::first();

    $this->withHeaders($this->headers)->json('GET', route('admin.api.families.index'))
        ->assertOK()
        ->assertJsonStructure([
            'data' => [
                '*' => [
                    'code',
                    'labels',
                    'attribute_groups' => [
                        '*' => [
                            'code',
                            'position',
                            'custom_attributes',
                        ],
                    ],
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
        ->assertJsonFragment(['code'  => $attributeFamily->code])
        ->assertJsonFragment(['total' => AttributeFamily::count()]);
});

it('should return the attribute family using the code', function () {
    $attributeFamily = AttributeFamily::first();

    $this->withHeaders($this->headers)->json('GET', route('admin.api.families.get', ['code' => $attributeFamily->code]))
        ->assertOK()
        ->assertJsonStructure([
            'code',
            'labels',
            'attribute_groups' => [
                '*' => [
                    'code',
                    'position',
                    'custom_attributes',
                ],
            ],
        ])
        ->assertJson(['code' => $attributeFamily->code]);
});

it('should return the message when code does not exists', function () {
    $this->withHeaders($this->headers)->json('GET', route('admin.api.families.get', ['code' => 'abcxyz']))
        ->assertStatus(404)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => false]);
});

it('should create the attribute family', function () {

    $locales = Locale::where('status', 1)->limit(2);

    $attributefamily = [
        'code'   => 'attrFamily',
        'labels' => [
            $locales->first()->code => 'Attribute Family',
        ],
        'attribute_groups' => [],
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.families.store'), $attributefamily)
        ->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $this->assertDatabaseHas($this->getFullTableName(AttributeFamily::class), ['code' => $attributefamily['code']]);
});

it('should give warning if code is not unique for attribute family', function () {

    $locales = Locale::where('status', 1)->limit(2);
    $attributeFamily = AttributeFamily::factory()->create();

    $attributefamily = [
        'code'   => $attributeFamily->code,
        'labels' => [
            $locales->first()->code => 'Attribute Family',
        ],
        'attribute_groups' => [],
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.families.store'), $attributefamily)
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

it('should create attribute family with complete attribute group data', function () {

    $locales = Locale::where('status', 1)->limit(2);
    $attributeGroups = AttributeGroup::limit(2)->get();
    $attributes = Attribute::limit(6)->get();

    $data = [];
    $pos = 0;

    $attributesChunked = $attributes->chunk(3);
    foreach ($attributeGroups as $index => $grp) {

        if (isset($attributesChunked[$index])) {
            $customAttributes = [];
            $currentAttributes = $attributesChunked[$index];
            $attrPos = 0;
            foreach ($currentAttributes as $attribute) {
                $customAttributes[] = [
                    'code'     => $attribute->code,
                    'position' => ++$attrPos,
                ];
            }

            $data[] = [
                'code'              => $grp->code,
                'position'          => ++$pos,
                'custom_attributes' => $customAttributes,
            ];
        }
    }

    $attributefamily = [
        'code'   => 'attrFamily',
        'labels' => [
            $locales->first()->code => 'Attribute Family',
        ],
        'attribute_groups' => $data,
    ];

    $this->withHeaders($this->headers)->json('POST', route('admin.api.families.store'), $attributefamily)
        ->assertStatus(201)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);

    $this->assertDatabaseHas($this->getFullTableName(AttributeFamily::class), ['code' => $attributefamily['code']]);
});

// it('should give validation message for custom attribute key during attribute family create', function () {

//     $locales = Locale::where('status', 1)->limit(2);
//     $attributeGroups = AttributeGroup::limit(2)->get();

//     $data = [];
//     $pos = 0;

//     foreach ($attributeGroups as $index => $grp) {
//             $data[] = [
//                 'code' => $grp->code,
//                 'position' => ++$pos,
//             ];
//     }

//     $attributefamily = [
//         'code'   => 'attrFamily',
//         'labels' => [
//             $locales->first()->code => 'Attribute Family',
//         ],
//         'attribute_groups' => $data
//     ];

//     $this->withHeaders($this->headers)->json('POST', route('admin.api.families.store'), $attributefamily)
//         ->assertStatus(422)
//         ->assertJsonStructure([
//             'success',
//             'message',
//         ])
//         ->assertJsonFragment(['success' => false]);
// });

// Attribute group is not updated with blank attribute group, shows 500 internal server error
it('should update the attribute family', function () {

    $attributeFamily = AttributeFamily::factory()->create();
    $locales = Locale::where('status', 1)->limit(2);
    $attributeGroups = AttributeGroup::limit(1)->get();

    $updatedfamily = [
        'code'   => $attributeFamily->code,
        'labels' => [
            $locales->first()->code => 'Attribute Family',
        ],
        'attribute_groups' => [
            [
                'code'              => $attributeGroups->first()->code,
                'position'          => 1,
                'custom_attributes' => [],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.families.update', ['code' => $updatedfamily['code']]), $updatedfamily)
        ->assertStatus(200)
        ->assertJsonStructure([
            'success',
            'message',
        ])
        ->assertJsonFragment(['success' => true]);
});

it('should give locale validation message during update attribute family', function () {

    $attributeFamily = AttributeFamily::factory()->create();
    $attributeGroups = AttributeGroup::limit(1)->get();

    $updatedfamily = [
        'code'   => $attributeFamily->code,
        'labels' => [
            'be_BY' => 'attribute family',
        ],
        'attribute_groups' => [
            [
                'code'              => $attributeGroups->first()->code,
                'position'          => 1,
                'custom_attributes' => [],
            ],
        ],
    ];

    $this->withHeaders($this->headers)->json('PUT', route('admin.api.families.update', ['code' => $updatedfamily['code']]), $updatedfamily)
        ->assertStatus(422)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => [
                'locale',
            ],
        ])
        ->assertJsonFragment(['success' => false]);

    $this->assertDatabaseHas($this->getFullTableName(AttributeFamily::class), ['code' => $updatedfamily['code']]);
});
