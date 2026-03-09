<?php

use Illuminate\Testing\Fluent\AssertableJson;
use Webkul\Measurement\Models\MeasurementFamily;

uses(
    Webkul\Measurement\Tests\MeasurementTestCase::class
)->group('measurement', 'admin');

it('should return the measurement family index page', function () {
    $this->loginAsAdmin();

    $this->get(route('admin.measurement.families.index'))
        ->assertOk()
        ->assertSeeText('Measurement Families');
});

it('should return the measurement family datagrid', function () {
    $this->loginAsAdmin();

    MeasurementFamily::factory()->create();

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->get(route('admin.measurement.families.index'));

    $response->assertOk();

    $data = $response->json();

    $this->assertArrayHasKey('records', $data);
    $this->assertArrayHasKey('columns', $data);
    $this->assertNotEmpty($data['records']);
});

it('should return validation errors when creating measurement family', function () {
    $this->loginAsAdmin();

    $this->post(route('admin.measurement.families.store'), [])
        ->assertInvalid('code')
        ->assertInvalid('standard_unit_code')
        ->assertInvalid('labels');
});

it('should create a measurement family successfully', function () {
    $this->loginAsAdmin();

    $data = [
        'code'               => 'length',
        'standard_unit_code' => 'meter',
        'symbol'             => 'm',
        'labels'             => [
            'en_US' => 'Length',
            'hi_IN' => 'लंबाई',
        ],
    ];

    $this->post(route('admin.measurement.families.store'), $data)
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json->whereType('data.redirect_url', 'string')
        );

    $this->assertDatabaseHas('measurement_families', [
        'code' => 'length',
    ]);
});

it('should return edit page of measurement family', function () {
    $this->loginAsAdmin();

    $family = MeasurementFamily::factory()->create();

    $this->get(route('admin.measurement.families.edit', $family->id))
        ->assertOk()
        ->assertSeeText($family->name);
});

it('should update measurement family labels successfully', function () {
    $this->loginAsAdmin();

    $family = MeasurementFamily::factory()->create([
        'labels' => [
            'en_US' => 'Length',
        ],
    ]);

    $this->put(
        route('admin.measurement.families.update', $family->id),
        [
            'labels' => [
                'hi_IN' => 'लंबाई',
            ],
        ]
    )
        ->assertSessionHas('success');

    $family->refresh();

    expect($family->labels)->toHaveKeys(['en_US', 'hi_IN']);
});

it('should delete measurement family successfully', function () {
    $this->loginAsAdmin();

    $family = MeasurementFamily::factory()->create();

    $this->delete(route('admin.measurement.families.delete', $family->id))
        ->assertOk()
        ->assertJsonFragment([
            'success' => true,
        ]);

    $this->assertDatabaseMissing('measurement_families', [
        'id' => $family->id,
    ]);
});

it('should mass delete measurement families successfully', function () {
    $this->loginAsAdmin();

    $families = MeasurementFamily::factory()->count(3)->create();

    $ids = $families->pluck('id')->toArray();

    $this->post(
        route('admin.measurement.families.mass_delete'),
        ['indices' => $ids]
    )
        ->assertRedirect()
        ->assertSessionHas('success');

    foreach ($ids as $id) {
        $this->assertDatabaseMissing('measurement_families', ['id' => $id]);
    }
});
