<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Http\Controllers\Api\AttributeMeasurementApiController;
use Webkul\Measurement\Models\MeasurementFamily;

uses(ApiHelperTrait::class);

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders('all');
});

function endpointFamilyPayload(array $overrides = []): array
{
    return array_merge([
        'code'          => 'len_'.uniqid(),
        'name'          => 'Length',
        'labels'        => ['en_US' => 'Length'],
        'standard_unit' => 'meter',
        'symbol'        => 'm',
        'units'         => [
            [
                'code'                  => 'meter',
                'labels'                => ['en_US' => 'Meter'],
                'symbol'                => 'm',
                'convert_from_standard' => [['operator' => 'mul', 'value' => '1']],
            ],
            [
                'code'                  => 'km',
                'labels'                => ['en_US' => 'Kilometer'],
                'symbol'                => 'km',
                'convert_from_standard' => [['operator' => 'mul', 'value' => '1000']],
            ],
        ],
    ], $overrides);
}

function endpointFamily(array $overrides = []): MeasurementFamily
{
    return MeasurementFamily::factory()->create(array_merge([
        'code'          => 'fam_'.uniqid(),
        'standard_unit' => 'meter',
        'symbol'        => 'm',
        'labels'        => ['en_US' => 'Length'],
        'units'         => [
            [
                'code'                  => 'meter',
                'labels'                => ['en_US' => 'Meter'],
                'symbol'                => 'm',
                'convert_from_standard' => [['operator' => 'mul', 'value' => '1']],
            ],
            [
                'code'                  => 'km',
                'labels'                => ['en_US' => 'Kilometer'],
                'symbol'                => 'km',
                'convert_from_standard' => [['operator' => 'mul', 'value' => '1000']],
            ],
        ],
    ], $overrides));
}

describe('measurement family endpoints', function () {
    it('completes the family create, read, update, read back and delete round trip', function () {
        $payload = endpointFamilyPayload();

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.measurement.store'), $payload)
            ->assertStatus(201)
            ->assertJson(['success' => true]);

        $family = MeasurementFamily::where('code', $payload['code'])->firstOrFail();

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement.index'))
            ->assertOk()
            ->assertJsonStructure(['success', 'count', 'data' => ['*' => ['id', 'code', 'name', 'standard_unit', 'symbol', 'labels', 'units']]]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement.show', $family->code))
            ->assertOk()
            ->assertJsonPath('data.code', $payload['code'])
            ->assertJsonPath('data.standard_unit', 'meter')
            ->assertJsonPath('data.labels.en_US', 'Length')
            ->assertJsonPath('data.units.1.convert_from_standard.0.value', '1000');

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.measurement.update', $family->code), [
                'name'   => 'Renamed',
                'labels' => ['en_US' => 'Renamed Length'],
                'symbol' => 'ML',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement.show', $family->code))
            ->assertOk()
            ->assertJsonPath('data.name', 'Renamed')
            ->assertJsonPath('data.labels.en_US', 'Renamed Length')
            ->assertJsonPath('data.symbol', 'ML');

        $this->withHeaders($this->headers)
            ->deleteJson(route('admin.api.measurement.delete', $family->code))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement.show', $family->code))
            ->assertStatus(404)
            ->assertJson(['success' => false]);

        $this->assertDatabaseMissing('measurement_families', ['id' => $family->id]);
    });

    it('never returns the identifier of the family it just created', function () {
        $payload = endpointFamilyPayload();

        $response = $this->withHeaders($this->headers)
            ->postJson(route('admin.api.measurement.store'), $payload);

        $response->assertStatus(201);

        expect(array_keys($response->json()))->toBe(['success', 'message'])
            ->and($response->json('data'))->toBeNull()
            ->and($response->json('id'))->toBeNull();
    });

    it('resolves the family path parameter by code and not by primary key', function () {
        $family = endpointFamily();

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement.show', $family->code))
            ->assertOk()
            ->assertJsonPath('data.code', $family->code);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement.show', $family->id))
            ->assertStatus(404)
            ->assertJson(['success' => false]);

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.measurement.update', $family->id), ['name' => 'Nope'])
            ->assertStatus(404);

        $this->withHeaders($this->headers)
            ->deleteJson(route('admin.api.measurement.delete', $family->id))
            ->assertStatus(404);

        $this->assertDatabaseHas('measurement_families', ['id' => $family->id]);
    });

    it('404s a malformed identifier that only coerces to a primary key', function () {
        $family = endpointFamily();

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement.show', $family->id.'abc'))
            ->assertStatus(404)
            ->assertJson(['success' => false]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement.show', $family->code.'abc'))
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    });

    it('rejects malformed family payloads with a validation envelope', function () {
        $family = endpointFamily();

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.measurement.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'name', 'labels', 'standard_unit', 'units'])
            ->assertJsonMissingPath('success');

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.measurement.store'), endpointFamilyPayload(['code' => $family->code]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.measurement.store'), endpointFamilyPayload(['code' => 'bad code!']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.measurement.store'), endpointFamilyPayload(['standard_unit' => 'nope']))
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    });

    it('404s every family route for an unknown code', function () {
        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement.show', 999999))
            ->assertStatus(404);

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.measurement.update', 999999), ['name' => 'x'])
            ->assertStatus(404);

        $this->withHeaders($this->headers)
            ->deleteJson(route('admin.api.measurement.delete', 999999))
            ->assertStatus(404);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement.show', 'no_such_family'))
            ->assertStatus(404);
    });

    it('deletes every omitted unit when a partial update carries a units array', function () {
        $family = endpointFamily();

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.measurement.update', $family->code), [
                'units' => [
                    [
                        'code'                  => 'meter',
                        'labels'                => ['en_US' => 'Meter'],
                        'symbol'                => 'm',
                        'convert_from_standard' => [['operator' => 'mul', 'value' => '1']],
                    ],
                ],
            ])
            ->assertOk();

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement.show', $family->code))
            ->assertOk()
            ->assertJsonCount(1, 'data.units')
            ->assertJsonPath('data.units.0.code', 'meter');
    });

    it('freezes the standard unit but keeps labels editable once an attribute uses the family', function () {
        $family = endpointFamily();

        $attribute = Attribute::factory()->create(['type' => 'measurement']);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.attribute-measurement.store', $attribute->code), [
                'family_code' => $family->code,
                'unit_code'   => 'meter',
            ])
            ->assertOk();

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.measurement.update', $family->code), ['standard_unit' => 'km'])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.measurement.update', $family->code), ['labels' => ['en_US' => 'Still Editable']])
            ->assertOk();
    });

    it('deletes a family that an attribute is still configured against and orphans the config', function () {
        $family = endpointFamily();

        $attribute = Attribute::factory()->create(['type' => 'measurement']);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.attribute-measurement.store', $attribute->code), [
                'family_code' => $family->code,
                'unit_code'   => 'meter',
            ])
            ->assertOk();

        $this->withHeaders($this->headers)
            ->deleteJson(route('admin.api.measurement.delete', $family->code))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.attribute-measurement.show', $attribute->code))
            ->assertOk()
            ->assertJsonPath('data.family_code', $family->code);

        $this->assertDatabaseMissing('measurement_families', ['id' => $family->id]);
    });
});

describe('measurement unit endpoints', function () {
    it('completes the unit create, read, update, read back and delete round trip', function () {
        $family = endpointFamily();

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.index', $family->code))
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonStructure(['success', 'count', 'data' => ['*' => ['code', 'labels', 'symbol', 'convert_from_standard']]]);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.measurement-units.store', $family->code), [
                'code'                  => 'cm',
                'labels'                => ['en_US' => 'Centimeter'],
                'symbol'                => 'cm',
                'convert_from_standard' => ['mul'],
                'convert_value'         => ['0.01'],
            ])
            ->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.show', [$family->code, 'cm']))
            ->assertOk()
            ->assertJsonPath('data.code', 'cm')
            ->assertJsonPath('data.symbol', 'cm')
            ->assertJsonPath('data.convert_from_standard.0.operator', 'mul')
            ->assertJsonPath('data.convert_from_standard.0.value', '0.01');

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.measurement-units.update', [$family->code, 'cm']), [
                'symbol'                => 'CM',
                'labels'                => ['en_US' => 'Centimetre'],
                'convert_from_standard' => ['div'],
                'convert_value'         => ['100'],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.show', [$family->code, 'cm']))
            ->assertOk()
            ->assertJsonPath('data.symbol', 'CM')
            ->assertJsonPath('data.labels.en_US', 'Centimetre')
            ->assertJsonPath('data.convert_from_standard.0.operator', 'div')
            ->assertJsonPath('data.convert_from_standard.0.value', '100');

        $this->withHeaders($this->headers)
            ->deleteJson(route('admin.api.measurement-units.delete', [$family->code, 'cm']))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.show', [$family->code, 'cm']))
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    });

    it('resolves the unit family path parameter by code and not by primary key', function () {
        $family = endpointFamily();

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.index', $family->code))
            ->assertOk();

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.show', [$family->code, 'km']))
            ->assertOk();

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.index', $family->id))
            ->assertStatus(404);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.show', [$family->id, 'km']))
            ->assertStatus(404);
    });

    it('404s every unit route for an unknown family', function () {
        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.index', 999999))
            ->assertStatus(404);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.show', [999999, 'km']))
            ->assertStatus(404);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.measurement-units.store', 999999), ['code' => 'km', 'labels' => ['en_US' => 'Kilometer']])
            ->assertStatus(404);

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.measurement-units.update', [999999, 'km']), ['symbol' => 'KM'])
            ->assertStatus(404);

        $this->withHeaders($this->headers)
            ->deleteJson(route('admin.api.measurement-units.delete', [999999, 'km']))
            ->assertStatus(404);
    });

    it('404s the unit routes for an unknown unit code', function () {
        $family = endpointFamily();

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.show', [$family->code, 'ghost']))
            ->assertStatus(404)
            ->assertJson(['success' => false]);

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.measurement-units.update', [$family->code, 'ghost']), ['symbol' => 'g'])
            ->assertStatus(404);

        $this->withHeaders($this->headers)
            ->deleteJson(route('admin.api.measurement-units.delete', [$family->code, 'ghost']))
            ->assertStatus(404);
    });

    it('rejects malformed unit payloads and duplicate unit codes', function () {
        $family = endpointFamily();

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.measurement-units.store', $family->code), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['code', 'labels']);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.measurement-units.store', $family->code), [
                'code'   => 'km',
                'labels' => ['en_US' => 'Kilometer'],
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.measurement-units.update', [$family->code, 'km']), [
                'convert_from_standard' => ['pow'],
                'convert_value'         => ['2'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['convert_from_standard.0']);
    });

    it('refuses to delete the standard unit', function () {
        $family = endpointFamily();

        $this->withHeaders($this->headers)
            ->deleteJson(route('admin.api.measurement-units.delete', [$family->code, 'meter']))
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.show', [$family->code, 'meter']))
            ->assertOk();
    });

    it('refuses to delete a unit that an attribute is configured against', function () {
        $family = endpointFamily();

        $attribute = Attribute::factory()->create(['type' => 'measurement']);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.attribute-measurement.store', $attribute->code), [
                'family_code' => $family->code,
                'unit_code'   => 'km',
            ])
            ->assertOk();

        $this->withHeaders($this->headers)
            ->deleteJson(route('admin.api.measurement-units.delete', [$family->code, 'km']))
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    });

    it('wipes the symbol and the conversion when a partial unit update omits them', function () {
        $family = endpointFamily();

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.measurement-units.update', [$family->code, 'km']), [
                'labels' => ['en_US' => 'Kilometre'],
            ])
            ->assertOk();

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.show', [$family->code, 'km']))
            ->assertOk()
            ->assertJsonPath('data.labels.en_US', 'Kilometre')
            ->assertJsonPath('data.symbol', null)
            ->assertJsonPath('data.convert_from_standard.0.operator', 'mul')
            ->assertJsonPath('data.convert_from_standard.0.value', null);
    });

    it('creates a unit with a null conversion value when the conversion is omitted', function () {
        $family = endpointFamily();

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.measurement-units.store', $family->code), [
                'code'   => 'mm',
                'labels' => ['en_US' => 'Millimeter'],
                'symbol' => 'mm',
            ])
            ->assertStatus(201);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.show', [$family->code, 'mm']))
            ->assertOk()
            ->assertJsonPath('data.convert_from_standard.0.operator', 'mul')
            ->assertJsonPath('data.convert_from_standard.0.value', null);
    });

    it('rejects the conversion shape that the read endpoints return', function () {
        $family = endpointFamily();

        $readBack = $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.show', [$family->code, 'km']))
            ->assertOk()
            ->json('data');

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.measurement-units.store', $family->code), [
                'code'                  => 'cm',
                'labels'                => ['en_US' => 'Centimeter'],
                'symbol'                => 'cm',
                'convert_from_standard' => $readBack['convert_from_standard'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['convert_from_standard.0']);
    });

    it('ignores conversion changes sent for the standard unit', function () {
        $family = endpointFamily();

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.measurement-units.update', [$family->code, 'meter']), [
                'symbol'                => 'M',
                'convert_from_standard' => ['mul'],
                'convert_value'         => ['99'],
            ])
            ->assertOk();

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.show', [$family->code, 'meter']))
            ->assertOk()
            ->assertJsonPath('data.symbol', 'M')
            ->assertJsonPath('data.convert_from_standard.0.value', '1');
    });

    it('still lets units be added and removed while the family is locked by an attribute', function () {
        $family = endpointFamily();

        $attribute = Attribute::factory()->create(['type' => 'measurement']);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.attribute-measurement.store', $attribute->code), [
                'family_code' => $family->code,
                'unit_code'   => 'meter',
            ])
            ->assertOk();

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.measurement.update', $family->code), [
                'units' => [
                    [
                        'code'                  => 'meter',
                        'labels'                => ['en_US' => 'Meter'],
                        'symbol'                => 'm',
                        'convert_from_standard' => [['operator' => 'mul', 'value' => '1']],
                    ],
                ],
            ])
            ->assertStatus(422);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.measurement-units.store', $family->code), [
                'code'                  => 'cm',
                'labels'                => ['en_US' => 'Centimeter'],
                'convert_from_standard' => ['mul'],
                'convert_value'         => ['0.01'],
            ])
            ->assertStatus(201);

        $this->withHeaders($this->headers)
            ->deleteJson(route('admin.api.measurement-units.delete', [$family->code, 'km']))
            ->assertOk();
    });
});

describe('attribute measurement endpoints', function () {
    it('completes the attribute measurement config round trip', function () {
        $family = endpointFamily();

        $attribute = Attribute::factory()->create(['type' => 'measurement']);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.attribute-measurement.getUnitsByFamily', $family->code))
            ->assertOk()
            ->assertJsonPath('count', 2)
            ->assertJsonStructure(['success', 'count', 'data' => ['*' => ['code', 'labels', 'symbol', 'convert_from_standard']]]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.attribute-measurement.show', $attribute->code))
            ->assertStatus(404)
            ->assertJson(['success' => false]);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.attribute-measurement.store', $attribute->code), [
                'family_code' => $family->code,
                'unit_code'   => 'meter',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.attribute-measurement.show', $attribute->code))
            ->assertOk()
            ->assertJsonPath('data.attribute_id', $attribute->id)
            ->assertJsonPath('data.family_code', $family->code)
            ->assertJsonPath('data.unit_code', 'meter');

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.attribute-measurement.update', $attribute->code), [
                'family_code' => $family->code,
                'unit_code'   => 'km',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.attribute-measurement.show', $attribute->code))
            ->assertOk()
            ->assertJsonPath('data.unit_code', 'km');

        $this->assertDatabaseHas('attribute_measurement', [
            'attribute_id' => $attribute->id,
            'family_code'  => $family->code,
            'unit_code'    => 'km',
        ]);
    });

    it('returns 200 instead of 201 when it creates a brand new config', function () {
        $family = endpointFamily();

        $attribute = Attribute::factory()->create(['type' => 'measurement']);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.attribute-measurement.store', $attribute->code), [
                'family_code' => $family->code,
                'unit_code'   => 'meter',
            ])
            ->assertStatus(200);
    });

    it('lets the store route silently overwrite an existing config', function () {
        $family = endpointFamily();

        $attribute = Attribute::factory()->create(['type' => 'measurement']);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.attribute-measurement.store', $attribute->code), [
                'family_code' => $family->code,
                'unit_code'   => 'meter',
            ])
            ->assertOk();

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.attribute-measurement.store', $attribute->code), [
                'family_code' => $family->code,
                'unit_code'   => 'km',
            ])
            ->assertOk();

        expect($this->withHeaders($this->headers)
            ->getJson(route('admin.api.attribute-measurement.show', $attribute->code))
            ->json('data.unit_code'))->toBe('km');
    });

    it('returns 200 with an empty list for an unknown family code', function () {
        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.attribute-measurement.getUnitsByFamily', 'no_such_family'))
            ->assertOk()
            ->assertJson(['success' => true, 'count' => 0, 'data' => []]);
    });

    it('returns 200 with an empty list when the family primary key is sent instead of the code', function () {
        $family = endpointFamily();

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.attribute-measurement.getUnitsByFamily', $family->id))
            ->assertOk()
            ->assertJson(['success' => true, 'count' => 0, 'data' => []]);
    });

    it('resolves the attribute path parameter by code and not by primary key', function () {
        $family = endpointFamily();

        $attribute = Attribute::factory()->create(['type' => 'measurement']);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.attribute-measurement.store', $attribute->code), [
                'family_code' => $family->code,
                'unit_code'   => 'meter',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.attribute-measurement.show', $attribute->code))
            ->assertOk();

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.attribute-measurement.update', $attribute->code), [
                'family_code' => $family->code,
                'unit_code'   => 'km',
            ])
            ->assertOk();

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.attribute-measurement.store', $attribute->id), [
                'family_code' => $family->code,
                'unit_code'   => 'meter',
            ])
            ->assertStatus(404)
            ->assertJson(['success' => false]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.attribute-measurement.show', $attribute->id))
            ->assertStatus(404);

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.attribute-measurement.update', $attribute->id), [
                'family_code' => $family->code,
                'unit_code'   => 'meter',
            ])
            ->assertStatus(404);
    });

    it('rejects every attribute measurement error path', function () {
        $family = endpointFamily();

        $attribute = Attribute::factory()->create(['type' => 'measurement']);
        $textAttribute = Attribute::factory()->create(['type' => 'text']);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.attribute-measurement.store', $attribute->code), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['family_code', 'unit_code']);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.attribute-measurement.store', 999999), [
                'family_code' => $family->code,
                'unit_code'   => 'meter',
            ])
            ->assertStatus(404);

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.attribute-measurement.update', 999999), [
                'family_code' => $family->code,
                'unit_code'   => 'meter',
            ])
            ->assertStatus(404);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.attribute-measurement.show', 999999))
            ->assertStatus(404);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.attribute-measurement.store', $textAttribute->code), [
                'family_code' => $family->code,
                'unit_code'   => 'meter',
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.attribute-measurement.store', $attribute->code), [
                'family_code' => 'does_not_exist',
                'unit_code'   => 'meter',
            ])
            ->assertStatus(404);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.attribute-measurement.store', $attribute->code), [
                'family_code' => $family->code,
                'unit_code'   => 'lightyear',
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    });
});

describe('measurement api code based path resolution', function () {
    it('resolves GET /measurement/{code} by code and 404s the primary key', function () {
        $family = endpointFamily();

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement.show', $family->code))
            ->assertOk()
            ->assertJsonPath('data.id', $family->id)
            ->assertJsonPath('data.code', $family->code);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement.show', $family->id))
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    });

    it('resolves PUT /measurement/{code} by code and 404s the primary key', function () {
        $family = endpointFamily();

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.measurement.update', $family->code), ['name' => 'Renamed By Code'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.measurement.update', $family->id), ['name' => 'Renamed By Id'])
            ->assertStatus(404)
            ->assertJson(['success' => false]);

        expect(MeasurementFamily::find($family->id)->name)->toBe('Renamed By Code');
    });

    it('resolves DELETE /measurement/{code} by code and 404s the primary key', function () {
        $keptFamily = endpointFamily();
        $doomedFamily = endpointFamily();

        $this->withHeaders($this->headers)
            ->deleteJson(route('admin.api.measurement.delete', $keptFamily->id))
            ->assertStatus(404)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('measurement_families', ['id' => $keptFamily->id]);

        $this->withHeaders($this->headers)
            ->deleteJson(route('admin.api.measurement.delete', $doomedFamily->code))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('measurement_families', ['id' => $doomedFamily->id]);
    });

    it('resolves GET /units/{familyCode} by code and 404s the primary key', function () {
        $family = endpointFamily();

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.index', $family->code))
            ->assertOk()
            ->assertJsonPath('count', 2);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.index', $family->id))
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    });

    it('resolves POST /units/{familyCode} by code and 404s the primary key', function () {
        $family = endpointFamily();

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.measurement-units.store', $family->id), [
                'code'                  => 'byid',
                'labels'                => ['en_US' => 'By Id'],
                'convert_from_standard' => ['mul'],
                'convert_value'         => ['2'],
            ])
            ->assertStatus(404)
            ->assertJson(['success' => false]);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.measurement-units.store', $family->code), [
                'code'                  => 'bycode',
                'labels'                => ['en_US' => 'By Code'],
                'convert_from_standard' => ['mul'],
                'convert_value'         => ['2'],
            ])
            ->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.index', $family->code))
            ->assertOk()
            ->assertJsonPath('count', 3);
    });

    it('resolves GET /units/{familyCode}/{code} by code and 404s the primary key', function () {
        $family = endpointFamily();

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.show', [$family->code, 'km']))
            ->assertOk()
            ->assertJsonPath('data.code', 'km');

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.show', [$family->id, 'km']))
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    });

    it('resolves PUT /units/{familyCode}/{code} by code and 404s the primary key', function () {
        $family = endpointFamily();

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.measurement-units.update', [$family->id, 'km']), ['symbol' => 'BYID'])
            ->assertStatus(404)
            ->assertJson(['success' => false]);

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.measurement-units.update', [$family->code, 'km']), ['symbol' => 'BYCODE'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.show', [$family->code, 'km']))
            ->assertOk()
            ->assertJsonPath('data.symbol', 'BYCODE');
    });

    it('resolves DELETE /units/{familyCode}/{code} by code and 404s the primary key', function () {
        $family = endpointFamily();

        $this->withHeaders($this->headers)
            ->deleteJson(route('admin.api.measurement-units.delete', [$family->id, 'km']))
            ->assertStatus(404)
            ->assertJson(['success' => false]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.show', [$family->code, 'km']))
            ->assertOk();

        $this->withHeaders($this->headers)
            ->deleteJson(route('admin.api.measurement-units.delete', [$family->code, 'km']))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.show', [$family->code, 'km']))
            ->assertStatus(404);
    });

    it('resolves GET /attribute-measurement/{familyCode} by code but answers the primary key with an empty list', function () {
        $family = endpointFamily();

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.attribute-measurement.getUnitsByFamily', $family->code))
            ->assertOk()
            ->assertJsonPath('count', 2);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.attribute-measurement.getUnitsByFamily', $family->id))
            ->assertOk()
            ->assertJson(['success' => true, 'count' => 0, 'data' => []]);
    });

    it('resolves GET /attribute-measurement/config/{attributeCode} by code and 404s the primary key', function () {
        $family = endpointFamily();

        $attribute = Attribute::factory()->create(['type' => 'measurement']);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.attribute-measurement.store', $attribute->code), [
                'family_code' => $family->code,
                'unit_code'   => 'meter',
            ])
            ->assertOk();

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.attribute-measurement.show', $attribute->code))
            ->assertOk()
            ->assertJsonPath('data.attribute_id', $attribute->id);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.attribute-measurement.show', $attribute->id))
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    });

    it('resolves POST /attribute-measurement/{attributeCode} by code and 404s the primary key', function () {
        $family = endpointFamily();

        $attribute = Attribute::factory()->create(['type' => 'measurement']);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.attribute-measurement.store', $attribute->id), [
                'family_code' => $family->code,
                'unit_code'   => 'meter',
            ])
            ->assertStatus(404)
            ->assertJson(['success' => false]);

        $this->assertDatabaseMissing('attribute_measurement', ['attribute_id' => $attribute->id]);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.attribute-measurement.store', $attribute->code), [
                'family_code' => $family->code,
                'unit_code'   => 'meter',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('attribute_measurement', [
            'attribute_id' => $attribute->id,
            'unit_code'    => 'meter',
        ]);
    });

    it('resolves PUT /attribute-measurement/{attributeCode} by code and 404s the primary key', function () {
        $family = endpointFamily();

        $attribute = Attribute::factory()->create(['type' => 'measurement']);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.attribute-measurement.store', $attribute->code), [
                'family_code' => $family->code,
                'unit_code'   => 'meter',
            ])
            ->assertOk();

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.attribute-measurement.update', $attribute->id), [
                'family_code' => $family->code,
                'unit_code'   => 'km',
            ])
            ->assertStatus(404)
            ->assertJson(['success' => false]);

        $this->withHeaders($this->headers)
            ->putJson(route('admin.api.attribute-measurement.update', $attribute->code), [
                'family_code' => $family->code,
                'unit_code'   => 'km',
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('attribute_measurement', [
            'attribute_id' => $attribute->id,
            'unit_code'    => 'km',
        ]);
    });

    it('serves the collection routes that carry no path parameter', function () {
        $payload = endpointFamilyPayload();

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.measurement.store'), $payload)
            ->assertStatus(201)
            ->assertJson(['success' => true]);

        $created = MeasurementFamily::where('code', $payload['code'])->firstOrFail();

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement.index'))
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonFragment(['code' => $payload['code']]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement.show', $payload['code']))
            ->assertOk();

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement.show', $created->id))
            ->assertStatus(404);
    });
});

describe('numeric measurement family codes', function () {
    it('resolves a numeric code to the family that owns it and never to the row with that primary key', function () {
        $rowFamily = endpointFamily();

        $codeFamily = endpointFamily(['code' => (string) $rowFamily->id]);

        expect($codeFamily->code)->toBe((string) $rowFamily->id)
            ->and($codeFamily->id)->not->toBe($rowFamily->id);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement.show', $rowFamily->id))
            ->assertOk()
            ->assertJsonPath('data.id', $codeFamily->id)
            ->assertJsonPath('data.code', (string) $rowFamily->id);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement.show', $rowFamily->code))
            ->assertOk()
            ->assertJsonPath('data.id', $rowFamily->id);
    });

    it('routes the unit endpoints of a numeric family code to the family that owns the code', function () {
        $rowFamily = endpointFamily();

        $codeFamily = endpointFamily([
            'code'          => (string) $rowFamily->id,
            'standard_unit' => 'meter',
            'units'         => [
                [
                    'code'                  => 'meter',
                    'labels'                => ['en_US' => 'Meter'],
                    'symbol'                => 'm',
                    'convert_from_standard' => [['operator' => 'mul', 'value' => '1']],
                ],
            ],
        ]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.index', $rowFamily->id))
            ->assertOk()
            ->assertJsonPath('count', 1)
            ->assertJsonPath('data.0.code', 'meter');

        expect($codeFamily->id)->not->toBe($rowFamily->id);
    });

    it('accepts a fully numeric family code through the store endpoint', function () {
        $code = (string) random_int(100000, 999999);

        $this->withHeaders($this->headers)
            ->postJson(route('admin.api.measurement.store'), endpointFamilyPayload(['code' => $code]))
            ->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement.show', $code))
            ->assertOk()
            ->assertJsonPath('data.code', $code);
    });

    it('does not coerce a numeric prefixed identifier onto a numeric family code', function () {
        $code = (string) random_int(100000, 999999);

        endpointFamily(['code' => $code]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement.show', $code.'abc'))
            ->assertStatus(404)
            ->assertJson(['success' => false]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.index', $code.'abc'))
            ->assertStatus(404);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement-units.show', [$code.'abc', 'km']))
            ->assertStatus(404);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.measurement.show', $code))
            ->assertOk();
    });

    it('does not coerce a numeric prefixed attribute identifier onto an attribute', function () {
        $attribute = Attribute::factory()->create(['type' => 'measurement']);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.attribute-measurement.show', $attribute->id.'abc'))
            ->assertStatus(404)
            ->assertJson(['success' => false]);

        $this->withHeaders($this->headers)
            ->getJson(route('admin.api.attribute-measurement.show', $attribute->code.'abc'))
            ->assertStatus(404)
            ->assertJson(['success' => false]);
    });
});

function restApiPrefix(): string
{
    $products = Route::getRoutes()->getByName('admin.api.products.index')?->uri() ?? '';

    return Str::beforeLast($products, '/products');
}

function measurementPath(string $name, mixed $parameters = []): string
{
    return parse_url(route($name, $parameters), PHP_URL_PATH);
}

describe('attribute measurement route resolution', function () {
    it('routes the literal config path to the attribute config action', function () {
        $path = measurementPath('admin.api.attribute-measurement.show', 'measurement');

        $matched = Route::getRoutes()->match(Request::create($path, 'GET'));

        expect($path)->toEndWith('/attribute-measurement/config/measurement')
            ->and($matched->getName())->toBe('admin.api.attribute-measurement.show')
            ->and($matched->getActionName())->toBe(AttributeMeasurementApiController::class.'@show')
            ->and($matched->parameters())->toBe(['attributeCode' => 'measurement']);
    });

    it('routes a single segment config path to the family units action', function () {
        $path = measurementPath('admin.api.attribute-measurement.getUnitsByFamily', 'config');

        $matched = Route::getRoutes()->match(Request::create($path, 'GET'));

        expect($path)->toEndWith('/attribute-measurement/config')
            ->and($matched->getName())->toBe('admin.api.attribute-measurement.getUnitsByFamily')
            ->and($matched->parameters())->toBe(['familyCode' => 'config']);
    });

    it('answers the literal config path with the attribute config contract', function () {
        $this->withHeaders($this->headers)
            ->getJson(measurementPath('admin.api.attribute-measurement.show', 'measurement'))
            ->assertStatus(404)
            ->assertJson(['success' => false])
            ->assertJsonMissingPath('count');

        $this->withHeaders($this->headers)
            ->getJson(measurementPath('admin.api.attribute-measurement.getUnitsByFamily', 'config'))
            ->assertOk()
            ->assertJson(['success' => true, 'count' => 0, 'data' => []]);
    });

    it('serves every measurement route under the shared rest api prefix', function (string $name, string $uri) {
        expect(restApiPrefix())->not->toBe('')
            ->and(Route::getRoutes()->getByName($name)?->uri())->toBe(restApiPrefix().'/'.$uri);
    })->with([
        ['admin.api.measurement.index', 'measurement'],
        ['admin.api.measurement.show', 'measurement/{code}'],
        ['admin.api.measurement.store', 'measurement'],
        ['admin.api.measurement.update', 'measurement/{code}'],
        ['admin.api.measurement.delete', 'measurement/{code}'],
        ['admin.api.measurement-units.index', 'units/{familyCode}'],
        ['admin.api.measurement-units.show', 'units/{familyCode}/{code}'],
        ['admin.api.measurement-units.store', 'units/{familyCode}'],
        ['admin.api.measurement-units.update', 'units/{familyCode}/{code}'],
        ['admin.api.measurement-units.delete', 'units/{familyCode}/{code}'],
        ['admin.api.attribute-measurement.getUnitsByFamily', 'attribute-measurement/{familyCode}'],
        ['admin.api.attribute-measurement.show', 'attribute-measurement/config/{attributeCode}'],
        ['admin.api.attribute-measurement.store', 'attribute-measurement/{attributeCode}'],
        ['admin.api.attribute-measurement.update', 'attribute-measurement/{attributeCode}'],
    ]);

    it('has no route under the misspelt attribute-measurment prefix', function () {
        $misspelt = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route): bool => str_contains($route->uri(), 'attribute-measurment'));

        expect($misspelt)->toBeEmpty();
    });

    it('has no config-by-code route', function () {
        $legacy = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route): bool => str_contains($route->uri(), 'config-by-code'));

        expect($legacy)->toBeEmpty();
    });
});
