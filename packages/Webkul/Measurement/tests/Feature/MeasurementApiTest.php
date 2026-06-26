<?php

use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Models\MeasurementFamily;

uses(ApiHelperTrait::class);

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

/**
 * Build a valid create-family payload.
 */
function familyPayload(array $overrides = []): array
{
    return array_merge([
        'code'          => 'len_'.uniqid(),
        'name'          => 'Length',
        'labels'        => ['en_US' => 'Length'],
        'standard_unit' => 'meter',
        'symbol'        => 'M',
        'units'         => [
            ['code' => 'meter', 'labels' => ['en_US' => 'Meter'], 'symbol' => 'm'],
        ],
    ], $overrides);
}

/* ===================== FAMILY ===================== */

it('creates a measurement family (POST /measurement)', function () {
    $payload = familyPayload();

    $this->withHeaders($this->headers)
        ->postJson(route('admin.api.measurement.store'), $payload)
        ->assertStatus(201)
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('measurement_families', ['code' => $payload['code']]);
});

it('rejects a duplicate family code (unique fix)', function () {
    $payload = familyPayload();

    $this->withHeaders($this->headers)
        ->postJson(route('admin.api.measurement.store'), $payload)
        ->assertStatus(201);

    // same code again must fail validation
    $this->withHeaders($this->headers)
        ->postJson(route('admin.api.measurement.store'), $payload)
        ->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

it('lists families (GET /measurement)', function () {
    $family = MeasurementFamily::factory()->create();

    $this->withHeaders($this->headers)
        ->getJson(route('admin.api.measurement.index'))
        ->assertOk()
        ->assertJson(['success' => true]);
});

it('shows a single family (GET /measurement/{id})', function () {
    $family = MeasurementFamily::factory()->create();

    $this->withHeaders($this->headers)
        ->getJson(route('admin.api.measurement.show', $family->id))
        ->assertOk()
        ->assertJsonPath('data.code', $family->code);
});

it('returns 404 for a missing family on show', function () {
    $this->withHeaders($this->headers)
        ->getJson(route('admin.api.measurement.show', 999999))
        ->assertStatus(404)
        ->assertJson(['success' => false]);
});

it('updates a family (PUT /measurement/{id})', function () {
    $family = MeasurementFamily::factory()->create();

    $this->withHeaders($this->headers)
        ->putJson(route('admin.api.measurement.update', $family->id), ['name' => 'Renamed'])
        ->assertOk()
        ->assertJson(['success' => true]);
});

it('returns 404 when updating a missing family', function () {
    $this->withHeaders($this->headers)
        ->putJson(route('admin.api.measurement.update', 999999), ['name' => 'x'])
        ->assertStatus(404);
});

it('deletes a family and 404s on a missing one', function () {
    $family = MeasurementFamily::factory()->create();

    $this->withHeaders($this->headers)
        ->deleteJson(route('admin.api.measurement.delete', $family->id))
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->withHeaders($this->headers)
        ->deleteJson(route('admin.api.measurement.delete', 999999))
        ->assertStatus(404);
});

/* ===================== UNITS ===================== */

it('adds, shows, updates and deletes a unit', function () {
    $family = MeasurementFamily::factory()->create([
        'standard_unit' => 'meter',
        'units'         => [
            ['code' => 'meter', 'labels' => ['en_US' => 'Meter'], 'symbol' => 'm', 'convert_from_standard' => [['operator' => 'mul', 'value' => '1']]],
        ],
    ]);

    // add unit
    $this->withHeaders($this->headers)
        ->postJson(route('admin.api.measurement-units.store', $family->id), [
            'code'                  => 'km',
            'labels'                => ['en_US' => 'Kilometer'],
            'symbol'                => 'km',
            'convert_from_standard' => ['mul'],
            'convert_value'         => ['1000'],
        ])
        ->assertStatus(201);

    // duplicate unit code
    $this->withHeaders($this->headers)
        ->postJson(route('admin.api.measurement-units.store', $family->id), [
            'code'   => 'km',
            'labels' => ['en_US' => 'Kilometer'],
        ])
        ->assertStatus(422);

    // show unit
    $this->withHeaders($this->headers)
        ->getJson(route('admin.api.measurement-units.show', [$family->id, 'km']))
        ->assertOk()
        ->assertJsonPath('data.code', 'km');

    // show missing unit
    $this->withHeaders($this->headers)
        ->getJson(route('admin.api.measurement-units.show', [$family->id, 'nope']))
        ->assertStatus(404);

    // update unit
    $this->withHeaders($this->headers)
        ->putJson(route('admin.api.measurement-units.update', [$family->id, 'km']), [
            'symbol' => 'KM',
            'labels' => ['en_US' => 'Kilometre'],
        ])
        ->assertOk();

    // cannot delete the standard unit
    $this->withHeaders($this->headers)
        ->deleteJson(route('admin.api.measurement-units.delete', [$family->id, 'meter']))
        ->assertStatus(422);

    // delete a normal unit
    $this->withHeaders($this->headers)
        ->deleteJson(route('admin.api.measurement-units.delete', [$family->id, 'km']))
        ->assertOk();

    // delete a missing unit
    $this->withHeaders($this->headers)
        ->deleteJson(route('admin.api.measurement-units.delete', [$family->id, 'ghost']))
        ->assertStatus(404);
});

/* ============== ATTRIBUTE MEASUREMENT ============== */

it('returns units by family code', function () {
    $family = MeasurementFamily::factory()->create([
        'standard_unit' => 'meter',
        'units'         => [
            ['code' => 'meter', 'labels' => ['en_US' => 'Meter'], 'symbol' => 'm', 'convert_from_standard' => [['operator' => 'mul', 'value' => '1']]],
        ],
    ]);

    $this->withHeaders($this->headers)
        ->getJson(route('admin.api.attribute-measurement.getUnitsByFamily', $family->code))
        ->assertOk()
        ->assertJson(['success' => true]);
});

it('rejects attribute config for a missing family (404) and a foreign unit (422)', function () {
    $family = MeasurementFamily::factory()->create([
        'standard_unit' => 'meter',
        'units'         => [
            ['code' => 'meter', 'labels' => ['en_US' => 'Meter'], 'symbol' => 'm', 'convert_from_standard' => [['operator' => 'mul', 'value' => '1']]],
        ],
    ]);

    $attribute = Attribute::factory()->create(['type' => 'measurement']);

    // family not found
    $this->withHeaders($this->headers)
        ->postJson(route('admin.api.attribute-measurement.store', $attribute->id), [
            'family_code' => 'does_not_exist',
            'unit_code'   => 'meter',
        ])
        ->assertStatus(404);

    // unit not in family
    $this->withHeaders($this->headers)
        ->postJson(route('admin.api.attribute-measurement.store', $attribute->id), [
            'family_code' => $family->code,
            'unit_code'   => 'lightyear',
        ])
        ->assertStatus(422);
});

it('keeps the legacy misspelled route working', function () {
    $family = MeasurementFamily::factory()->create();

    $this->withHeaders($this->headers)
        ->getJson(route('admin.api.attribute-measurment.getUnitsByFamily', $family->code))
        ->assertOk();
});
