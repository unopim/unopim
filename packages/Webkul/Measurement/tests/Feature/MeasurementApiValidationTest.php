<?php

use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Models\MeasurementFamily;

uses(ApiHelperTrait::class);

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

function probeFamily(): MeasurementFamily
{
    return MeasurementFamily::factory()->create([
        'standard_unit' => 'meter',
        'units'         => [
            ['code' => 'meter', 'labels' => ['en_US' => 'Meter'], 'symbol' => 'm', 'convert_from_standard' => [['operator' => 'mul', 'value' => '1']]],
        ],
    ]);
}

it('rejects family store when standard_unit is not one of the units', function () {
    $this->withHeaders($this->headers)
        ->postJson(route('admin.api.measurement.store'), [
            'code'          => 'probe_'.uniqid(),
            'name'          => 'Probe',
            'labels'        => ['en_US' => 'Probe'],
            'standard_unit' => 'kelvin',   // not in units
            'symbol'        => 'K',
            'units'         => [
                ['code' => 'meter', 'labels' => ['en_US' => 'Meter'], 'symbol' => 'm'],
            ],
        ])
        ->assertStatus(422)
        ->assertJson(['success' => false]);
});

it('rejects family update when the resulting standard_unit is not among units', function () {
    $family = probeFamily();

    $this->withHeaders($this->headers)
        ->putJson(route('admin.api.measurement.update', $family->id), [
            'standard_unit' => 'lightyear',  // not in existing units
        ])
        ->assertStatus(422)
        ->assertJson(['success' => false]);
});

it('returns 404 (not 500) for attribute-measurement on a non-existent attribute', function () {
    $family = probeFamily();

    $this->withHeaders($this->headers)
        ->postJson(route('admin.api.attribute-measurement.store', 999999), [
            'family_code' => $family->code,
            'unit_code'   => 'meter',
        ])
        ->assertStatus(404)
        ->assertJson(['success' => false]);
});

it('rejects attribute-measurement on a non-measurement attribute type', function () {
    $attribute = Attribute::factory()->create(['type' => 'text']);
    $family = probeFamily();

    $this->withHeaders($this->headers)
        ->postJson(route('admin.api.attribute-measurement.store', $attribute->id), [
            'family_code' => $family->code,
            'unit_code'   => 'meter',
        ])
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    $this->assertDatabaseMissing('attribute_measurement', ['attribute_id' => $attribute->id]);
});

it('saves attribute-measurement config for a valid measurement attribute', function () {
    $attribute = Attribute::factory()->create(['type' => 'measurement']);
    $family = probeFamily();

    $this->withHeaders($this->headers)
        ->postJson(route('admin.api.attribute-measurement.store', $attribute->id), [
            'family_code' => $family->code,
            'unit_code'   => 'meter',
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    $this->assertDatabaseHas('attribute_measurement', [
        'attribute_id' => $attribute->id,
        'family_code'  => $family->code,
        'unit_code'    => 'meter',
    ]);
});
