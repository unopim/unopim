<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;

use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

beforeEach(function () {
    $this->loginAsAdmin();

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
            ['code' => 'meter', 'labels' => ['en_US' => 'Meter']],
            ['code' => 'cm', 'labels' => ['en_US' => 'Centimeter']],
        ],
    ]);
});

it('resolves measurement backed types from the attribute types config', function () {
    $helper = app(MeasurementHelper::class);

    expect($helper->isMeasurementBacked('measurement'))->toBeTrue()
        ->and($helper->isMeasurementBacked('conditional_measurement'))->toBeTrue()
        ->and($helper->isMeasurementBacked('plain_custom'))->toBeFalse()
        ->and($helper->isMeasurementBacked('text'))->toBeFalse()
        ->and($helper->isMeasurementBacked('unknown_type'))->toBeFalse()
        ->and($helper->isMeasurementBacked(null))->toBeFalse();
});

it('saves the family and unit of a registered measurement backed type on update', function (string $type) {
    $attribute = Attribute::factory()->create(['type' => $type]);

    putJson(route('admin.catalog.attributes.update', $attribute->id), [
        'code'               => $attribute->code,
        'type'               => $attribute->type,
        'measurement_family' => $this->family->code,
        'measurement_unit'   => 'cm',
    ])
        ->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id))
        ->assertSessionHas('success');

    $this->assertDatabaseHas($this->getFullTableName(AttributeMeasurement::class), [
        'attribute_id' => $attribute->id,
        'family_code'  => $this->family->code,
        'unit_code'    => 'cm',
    ]);
})->with(['measurement', 'conditional_measurement']);

it('rejects an update of a registered measurement backed type without a family and unit', function (string $type) {
    $attribute = Attribute::factory()->create(['type' => $type]);

    putJson(route('admin.catalog.attributes.update', $attribute->id), [
        'code' => $attribute->code,
        'type' => $attribute->type,
    ])
        ->assertStatus(302)
        ->assertSessionHas('error', trans('measurement::app.messages.attribute.family_unit_required'));

    $this->assertDatabaseMissing($this->getFullTableName(AttributeMeasurement::class), [
        'attribute_id' => $attribute->id,
    ]);
})->with(['measurement', 'conditional_measurement']);

it('rejects a unit outside the family for a registered measurement backed type', function () {
    $attribute = Attribute::factory()->create(['type' => 'conditional_measurement']);

    putJson(route('admin.catalog.attributes.update', $attribute->id), [
        'code'               => $attribute->code,
        'type'               => $attribute->type,
        'measurement_family' => $this->family->code,
        'measurement_unit'   => 'kilogram',
    ])
        ->assertSessionHas('error', trans('measurement::app.messages.attribute.unit_not_in_family'));

    $this->assertDatabaseMissing($this->getFullTableName(AttributeMeasurement::class), [
        'attribute_id' => $attribute->id,
    ]);
});

it('ignores the measurement fields for a type that is not measurement backed', function () {
    $attribute = Attribute::factory()->create(['type' => 'plain_custom']);

    putJson(route('admin.catalog.attributes.update', $attribute->id), [
        'code'               => $attribute->code,
        'type'               => $attribute->type,
        'measurement_family' => $this->family->code,
        'measurement_unit'   => 'cm',
    ])
        ->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id))
        ->assertSessionHas('success')
        ->assertSessionMissing('error');

    $this->assertDatabaseMissing($this->getFullTableName(AttributeMeasurement::class), [
        'attribute_id' => $attribute->id,
    ]);
});

it('shows the family and unit panel on the edit page only for measurement backed types', function () {
    $backed = Attribute::factory()->create(['type' => 'conditional_measurement']);
    $plain = Attribute::factory()->create(['type' => 'plain_custom']);

    $this->get(route('admin.catalog.attributes.edit', $backed->id))
        ->assertOk()
        ->assertSee('<v-measurement', false);

    $this->get(route('admin.catalog.attributes.edit', $plain->id))
        ->assertOk()
        ->assertDontSee('<v-measurement', false);
});

it('warns to set the family and unit after creating a measurement backed attribute', function (string $type) {
    $response = postJson(route('admin.catalog.attributes.store'), [
        'code' => 'created_'.$type,
        'type' => $type,
    ]);

    $attribute = Attribute::where('code', 'created_'.$type)->firstOrFail();

    $response->assertRedirect(route('admin.catalog.attributes.edit', $attribute->id))
        ->assertSessionHas('success')
        ->assertSessionHas('warning', trans('measurement::app.messages.attribute.family_unit_required'));
})->with(['measurement', 'conditional_measurement']);

it('does not warn after creating an attribute that is not measurement backed', function () {
    postJson(route('admin.catalog.attributes.store'), [
        'code' => 'created_plain_custom',
        'type' => 'plain_custom',
    ])
        ->assertSessionHas('success')
        ->assertSessionMissing('warning');
});
