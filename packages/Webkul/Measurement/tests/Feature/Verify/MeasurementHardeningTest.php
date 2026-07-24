<?php

use Illuminate\Http\Exceptions\HttpResponseException;
use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Listeners\ValidateAttributeMeasurementBeforeUpdate;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;

uses(ApiHelperTrait::class);

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

function hardeningFamily(): MeasurementFamily
{
    return MeasurementFamily::factory()->create([
        'code'          => 'len_'.uniqid(),
        'standard_unit' => 'meter',
        'symbol'        => 'm',
        'units'         => [
            ['code' => 'meter', 'labels' => ['en_US' => 'Meter'], 'symbol' => 'm', 'convert_from_standard' => [['operator' => 'mul', 'value' => '1']]],
            ['code' => 'cm', 'labels' => ['en_US' => 'Centimeter'], 'symbol' => 'cm', 'convert_from_standard' => [['operator' => 'mul', 'value' => '100']]],
        ],
    ]);
}

it('M2: rejects assigning a unit that is not in the family to a measurement attribute', function () {
    $family = hardeningFamily();

    $attribute = Attribute::factory()->create(['code' => 'w_'.uniqid(), 'type' => 'measurement']);

    request()->merge([
        'measurement_family' => $family->code,
        'measurement_unit'   => 'furlong',
    ]);

    expect(fn () => app(ValidateAttributeMeasurementBeforeUpdate::class)->handle($attribute->id))
        ->toThrow(HttpResponseException::class);
});

it('M2: accepts a unit that belongs to the family', function () {
    $family = hardeningFamily();

    $attribute = Attribute::factory()->create(['code' => 'w_'.uniqid(), 'type' => 'measurement']);

    request()->merge([
        'measurement_family' => $family->code,
        'measurement_unit'   => 'cm',
    ]);

    expect(fn () => app(ValidateAttributeMeasurementBeforeUpdate::class)->handle($attribute->id))
        ->not->toThrow(HttpResponseException::class);
});

it('H1: blocks deleting a unit referenced by an attribute through the API', function () {
    $family = hardeningFamily();

    $attribute = Attribute::factory()->create(['code' => 'w_'.uniqid(), 'type' => 'measurement']);

    AttributeMeasurement::create([
        'attribute_id' => $attribute->id,
        'family_code'  => $family->code,
        'unit_code'    => 'cm',
    ]);

    $this->withHeaders($this->headers)
        ->json('DELETE', route('admin.api.measurement-units.delete', ['familyCode' => $family->id, 'code' => 'cm']))
        ->assertStatus(422);

    expect(collect($family->fresh()->units)->contains('code', 'cm'))->toBeTrue();
});

it('H1: does not change a unit conversion through the API when the family is in use', function () {
    $family = hardeningFamily();

    $attribute = Attribute::factory()->create(['code' => 'w_'.uniqid(), 'type' => 'measurement']);

    AttributeMeasurement::create([
        'attribute_id' => $attribute->id,
        'family_code'  => $family->code,
        'unit_code'    => 'meter',
    ]);

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.measurement-units.update', ['familyCode' => $family->id, 'code' => 'cm']), [
            'labels'                => ['en_US' => 'Centimeter'],
            'symbol'                => 'cm',
            'convert_from_standard' => ['mul'],
            'convert_value'         => ['50'],
        ])
        ->assertOk();

    $cm = collect($family->fresh()->units)->firstWhere('code', 'cm');

    expect($cm['convert_from_standard'][0]['value'])->toBe('100');
});
