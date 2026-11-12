<?php

use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;

uses(ApiHelperTrait::class);

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();

    config()->set('attribute_types.conditional_measurement', [
        'key'                => 'conditional_measurement',
        'name'               => 'measurement::app.attribute.measurement',
        'measurement_backed' => true,
    ]);

    config()->set('attribute_types.plain_custom', [
        'key'  => 'plain_custom',
        'name' => 'measurement::app.attribute.measurement',
    ]);

    $this->family = MeasurementFamily::factory()->create([
        'units' => [
            ['code' => 'meter', 'labels' => ['en_US' => 'Meter'], 'symbol' => 'm'],
        ],
    ]);
});

it('stores the measurement config of a registered measurement backed type through the API', function () {
    $attribute = Attribute::factory()->create(['type' => 'conditional_measurement']);

    $this->withHeaders($this->headers)
        ->postJson(route('admin.api.attribute-measurement.store', $attribute->code), [
            'family_code' => $this->family->code,
            'unit_code'   => 'meter',
        ])
        ->assertSuccessful();

    $this->assertDatabaseHas($this->getFullTableName(AttributeMeasurement::class), [
        'attribute_id' => $attribute->id,
        'family_code'  => $this->family->code,
        'unit_code'    => 'meter',
    ]);
});

it('rejects the measurement config of a type that is not measurement backed through the API', function () {
    $attribute = Attribute::factory()->create(['type' => 'plain_custom']);

    $this->withHeaders($this->headers)
        ->postJson(route('admin.api.attribute-measurement.store', $attribute->code), [
            'family_code' => $this->family->code,
            'unit_code'   => 'meter',
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', trans('measurement::app.messages.attribute.not_measurement_type'));

    $this->assertDatabaseMissing($this->getFullTableName(AttributeMeasurement::class), [
        'attribute_id' => $attribute->id,
    ]);
});
