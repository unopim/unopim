<?php

use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;

uses(ApiHelperTrait::class);

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

function lockableFamily(): MeasurementFamily
{
    return MeasurementFamily::factory()->create([
        'standard_unit' => 'meter',
        'units'         => [
            ['code' => 'meter', 'labels' => ['en_US' => 'Meter'], 'symbol' => 'm', 'convert_from_standard' => [['operator' => 'mul', 'value' => '1']]],
            ['code' => 'cm', 'labels' => ['en_US' => 'Centimeter'], 'symbol' => 'cm', 'convert_from_standard' => [['operator' => 'mul', 'value' => '100']]],
        ],
    ]);
}

function attachAttributeTo(MeasurementFamily $family): void
{
    $attribute = Attribute::factory()->create([
        'code' => 'lock_'.uniqid(),
        'type' => 'measurement',
    ]);

    AttributeMeasurement::create([
        'attribute_id' => $attribute->id,
        'family_code'  => $family->code,
        'unit_code'    => 'meter',
    ]);
}

it('blocks changing the standard unit once the family is used by an attribute', function () {
    $family = lockableFamily();
    attachAttributeTo($family);

    $this->withHeaders($this->headers)
        ->putJson(route('admin.api.measurement.update', $family->id), [
            'standard_unit' => 'cm',
        ])
        ->assertStatus(422)
        ->assertJson(['success' => false]);

    expect($family->fresh()->standard_unit)->toBe('meter');
});

it('blocks changing conversion operations once the family is used by an attribute', function () {
    $family = lockableFamily();
    attachAttributeTo($family);

    $this->withHeaders($this->headers)
        ->putJson(route('admin.api.measurement.update', $family->id), [
            'units' => [
                ['code' => 'meter', 'labels' => ['en_US' => 'Meter'], 'convert_from_standard' => [['operator' => 'mul', 'value' => '1']]],
                ['code' => 'cm', 'labels' => ['en_US' => 'Centimeter'], 'convert_from_standard' => [['operator' => 'mul', 'value' => '999']]],
            ],
        ])
        ->assertStatus(422);

    $cm = collect($family->fresh()->units)->firstWhere('code', 'cm');

    expect($cm['convert_from_standard'][0]['value'])->toBe('100');
});

it('still allows label changes on a family that is in use', function () {
    $family = lockableFamily();
    attachAttributeTo($family);

    $this->withHeaders($this->headers)
        ->putJson(route('admin.api.measurement.update', $family->id), [
            'labels' => ['en_US' => 'Renamed Length'],
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($family->fresh()->labels['en_US'])->toBe('Renamed Length');
});

it('still allows changing the standard unit when the family is not in use', function () {
    $family = lockableFamily();

    $this->withHeaders($this->headers)
        ->putJson(route('admin.api.measurement.update', $family->id), [
            'standard_unit' => 'cm',
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    expect($family->fresh()->standard_unit)->toBe('cm');
});
