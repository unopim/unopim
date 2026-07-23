<?php

use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\Measurement\Models\MeasurementFamily;
use Webkul\Measurement\Repositories\MeasurementFamilyRepository;
use Webkul\Measurement\Validation\MeasurementFamilyValidator;
use Webkul\Measurement\Validation\MeasurementUnitValidator;

uses(ApiHelperTrait::class);

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

function limitFamilyPayload(array $units): array
{
    return [
        'code'          => 'limit_'.uniqid(),
        'name'          => 'Limit probe',
        'labels'        => ['en_US' => 'Limit probe'],
        'standard_unit' => 'meter',
        'symbol'        => 'm',
        'units'         => $units,
    ];
}

function meterUnit(array $conversions): array
{
    return [
        'code'                  => 'meter',
        'labels'                => ['en_US' => 'Meter'],
        'symbol'                => 'm',
        'convert_from_standard' => $conversions,
    ];
}

it('rejects a unit with more than the allowed conversion operations', function () {
    $tooMany = array_fill(0, MeasurementUnitValidator::MAX_CONVERSIONS + 1, ['operator' => 'mul', 'value' => '2']);

    $this->withHeaders($this->headers)
        ->postJson(route('admin.api.measurement.store'), limitFamilyPayload([meterUnit($tooMany)]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['units.0.convert_from_standard']);
});

it('accepts a unit at exactly the conversion limit', function () {
    $atLimit = array_fill(0, MeasurementUnitValidator::MAX_CONVERSIONS, ['operator' => 'mul', 'value' => '2']);

    $this->withHeaders($this->headers)
        ->postJson(route('admin.api.measurement.store'), limitFamilyPayload([meterUnit($atLimit)]))
        ->assertStatus(201);
});

it('rejects a family with more units than allowed', function () {
    $units = [meterUnit([['operator' => 'mul', 'value' => '1']])];

    for ($i = 0; $i < MeasurementFamilyValidator::MAX_UNITS; $i++) {
        $units[] = [
            'code'                  => 'unit_'.$i,
            'labels'                => ['en_US' => 'Unit '.$i],
            'convert_from_standard' => [['operator' => 'mul', 'value' => '2']],
        ];
    }

    $this->withHeaders($this->headers)
        ->postJson(route('admin.api.measurement.store'), limitFamilyPayload($units))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['units']);
});

it('rejects a new family once the family limit is reached', function () {
    $repository = app(MeasurementFamilyRepository::class);

    $existing = $repository->count();

    MeasurementFamily::factory()
        ->count(max(0, MeasurementFamilyValidator::MAX_FAMILIES - $existing))
        ->create();

    $this->withHeaders($this->headers)
        ->postJson(
            route('admin.api.measurement.store'),
            limitFamilyPayload([meterUnit([['operator' => 'mul', 'value' => '1']])])
        )
        ->assertStatus(422)
        ->assertJson(['success' => false]);
});
