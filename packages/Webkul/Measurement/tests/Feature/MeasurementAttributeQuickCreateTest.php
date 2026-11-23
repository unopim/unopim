<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;

beforeEach(function (): void {
    $this->loginAsAdmin();
});

function quickCreateLengthFamily(): MeasurementFamily
{
    return MeasurementFamily::factory()->create([
        'code'          => 'length_'.uniqid(),
        'standard_unit' => 'meter',
        'units'         => [
            ['code' => 'meter', 'labels' => ['en_US' => 'Meter'], 'symbol' => 'm', 'convert_from_standard' => [['operator' => 'mul', 'value' => '1']]],
            ['code' => 'centimeter', 'labels' => ['en_US' => 'Centimeter'], 'symbol' => 'cm', 'convert_from_standard' => [['operator' => 'mul', 'value' => '100']]],
        ],
    ]);
}

it('injects the measurement family and unit fields into the attribute create modal', function (): void {
    quickCreateLengthFamily();

    $response = $this->get(route('admin.catalog.attributes.index'));

    $response->assertOk()
        ->assertSee('v-measurement-create', false)
        ->assertSee('measurement_family', false)
        ->assertSee('measurement_unit', false);
});

it('saves the measurement configuration posted with a measurement attribute on create', function (): void {
    $family = quickCreateLengthFamily();

    $code = 'meas_'.uniqid();

    $response = $this->post(route('admin.catalog.attributes.store'), [
        'code'               => $code,
        'type'               => 'measurement',
        'measurement_family' => $family->code,
        'measurement_unit'   => 'meter',
    ]);

    $attribute = Attribute::where('code', $code)->firstOrFail();

    $response->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id));

    $saved = AttributeMeasurement::where('attribute_id', $attribute->id)->first();

    expect($saved)->not->toBeNull()
        ->and($saved->family_code)->toBe($family->code)
        ->and($saved->unit_code)->toBe('meter');

    $response->assertSessionMissing('warning');
});

it('warns and stores no configuration when a measurement attribute is created without family and unit', function (): void {
    $code = 'meas_'.uniqid();

    $response = $this->post(route('admin.catalog.attributes.store'), [
        'code' => $code,
        'type' => 'measurement',
    ]);

    $attribute = Attribute::where('code', $code)->firstOrFail();

    expect(AttributeMeasurement::where('attribute_id', $attribute->id)->exists())->toBeFalse();

    $response->assertSessionHas('info');
});

it('does not persist a measurement configuration whose unit is not part of the family', function (): void {
    $family = quickCreateLengthFamily();

    $code = 'meas_'.uniqid();

    $response = $this->post(route('admin.catalog.attributes.store'), [
        'code'               => $code,
        'type'               => 'measurement',
        'measurement_family' => $family->code,
        'measurement_unit'   => 'kilometer',
    ]);

    expect(Attribute::where('code', $code)->exists())->toBeFalse();

    $response->assertSessionHas('error', trans('measurement::app.messages.attribute.unit_not_in_family'));
});

it('does not persist a measurement configuration whose family does not exist', function (): void {
    $code = 'meas_'.uniqid();

    $response = $this->post(route('admin.catalog.attributes.store'), [
        'code'               => $code,
        'type'               => 'measurement',
        'measurement_family' => 'missing_family_'.uniqid(),
        'measurement_unit'   => 'meter',
    ]);

    expect(Attribute::where('code', $code)->exists())->toBeFalse();

    $response->assertSessionHas('error', trans('measurement::app.messages.attribute.family_not_found'));
});

it('ignores posted measurement fields for a non-measurement attribute', function (): void {
    $family = quickCreateLengthFamily();

    $code = 'text_'.uniqid();

    $this->post(route('admin.catalog.attributes.store'), [
        'code'               => $code,
        'type'               => 'text',
        'measurement_family' => $family->code,
        'measurement_unit'   => 'meter',
    ]);

    $attribute = Attribute::where('code', $code)->firstOrFail();

    expect(AttributeMeasurement::where('attribute_id', $attribute->id)->exists())->toBeFalse();
});
