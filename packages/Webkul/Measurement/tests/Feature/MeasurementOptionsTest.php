<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;

uses(
    Webkul\Measurement\Tests\MeasurementTestCase::class
)->group('measurement', 'admin');

beforeEach(function () {
    $this->loginAsAdmin();
});

function measurementFamilyWithUnits(array $units)
{
    return MeasurementFamily::factory()->create([
        'code'  => 'length',
        'units' => $units,
    ]);
}

it('should return empty options when no measurement family selected', function () {
    $attribute = Attribute::factory()->create();

    $this->getJson(
        route('admin.measurement.attribute.units', [
            'attribute_id' => $attribute->id,
        ])
    )
        ->assertOk()
        ->assertJson([
            'options'  => [],
            'page'     => 1,
            'lastPage' => 1,
        ]);
});

it('should return unit options based on attribute measurement family', function () {
    $attribute = Attribute::factory()->create();

    $family = measurementFamilyWithUnits([
        [
            'code'   => 'meter',
            'labels' => ['en_US' => 'Meter'],
        ],
        [
            'code'   => 'cm',
            'labels' => ['en_US' => 'Centimeter'],
        ],
    ]);

    AttributeMeasurement::create([
        'attribute_id' => $attribute->id,
        'family_code'  => $family->code,
        'unit_code'    => 'meter',
    ]);

    $this->getJson(
        route('admin.measurement.attribute.units', [
            'attribute_id' => $attribute->id,
        ])
    )
        ->assertOk()
        ->assertJsonStructure([
            'options' => [
                [
                    'id',
                    'label',
                    'code',
                ],
            ],
            'page',
            'lastPage',
        ])
        ->assertJsonFragment([
            'id'    => 'meter',
            'label' => 'Meter',
        ]);
});

it('should put selected unit on top of options', function () {
    $attribute = Attribute::factory()->create();

    $family = measurementFamilyWithUnits([
        ['code' => 'meter', 'labels' => ['en_US' => 'Meter']],
        ['code' => 'cm', 'labels' => ['en_US' => 'Centimeter']],
    ]);

    AttributeMeasurement::create([
        'attribute_id' => $attribute->id,
        'family_code'  => $family->code,
        'unit_code'    => 'cm',
    ]);

    $response = $this->getJson(
        route('admin.measurement.attribute.units', [
            'attribute_id' => $attribute->id,
            'queryParams'  => [
                'identifiers' => [
                    'value' => 'cm',
                ],
            ],
        ])
    )->assertOk();

    expect($response->json('options.0.id'))->toBe('cm');
});

it('should paginate unit options correctly', function () {
    $attribute = Attribute::factory()->create();

    $units = collect(range(1, 60))->map(fn ($i) => [
        'code'   => "unit_$i",
        'labels' => ['en_US' => "Unit $i"],
    ])->toArray();

    $family = measurementFamilyWithUnits($units);

    AttributeMeasurement::create([
        'attribute_id' => $attribute->id,
        'family_code'  => $family->code,
        'unit_code'    => 'unit_1',
    ]);

    $this->getJson(
        route('admin.measurement.attribute.units', [
            'attribute_id' => $attribute->id,
            'page'         => 1,
        ])
    )
        ->assertOk()
        ->assertJson([
            'page'     => 1,
            'lastPage' => 2,
        ]);
});
