<?php

use Illuminate\Testing\Fluent\AssertableJson;
use Webkul\Measurement\Models\MeasurementFamily;

beforeEach(function () {
    $this->loginAsAdmin();
});

it('should return the measurement family index page', function () {

    $this->get(route('admin.measurement.families.index'))
        ->assertOk()
        ->assertSeeText('Measurement Families');
});

it('should return the measurement family datagrid', function () {

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

it('should filter measurement family datagrid by standard unit label with spaces', function () {

    MeasurementFamily::factory()->create([
        'code'          => 'matched-family',
        'standard_unit' => 'ranjan',
        'units'         => [
            [
                'code'   => 'ranjan',
                'labels' => [
                    'en_US' => 'ranjan rajput',
                ],
                'symbol' => 'rr',
            ],
        ],
    ]);

    MeasurementFamily::factory()->create([
        'code'          => 'unmatched-family',
        'standard_unit' => 'other',
        'units'         => [
            [
                'code'   => 'other',
                'labels' => [
                    'en_US' => 'other unit',
                ],
                'symbol' => 'ou',
            ],
        ],
    ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->get(route('admin.measurement.families.index', [
        'filters' => [
            'standard_unit' => ['ranjan rajput'],
        ],
    ]));

    $response->assertOk();

    $records = collect($response->json('records'));

    expect($records)->toHaveCount(1)
        ->and($records->first()['code'])->toBe('matched-family')
        ->and($records->first()['standard_unit'])->toBe('ranjan rajput');
});

it('should show code fallback when measurement family labels are missing', function () {

    MeasurementFamily::factory()->create([
        'code'          => 'fallback-family',
        'labels'        => [],
        'standard_unit' => 'fallback-unit',
        'units'         => [
            [
                'code'   => 'fallback-unit',
                'labels' => [],
                'symbol' => 'fu',
            ],
        ],
    ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->get(route('admin.measurement.families.index', [
        'filters' => [
            'labels' => ['fallback-family'],
        ],
    ]));

    $response->assertOk();

    $records = collect($response->json('records'));

    expect($records)->toHaveCount(1)
        ->and($records->first()['labels'])->toBe('[fallback-family]')
        ->and($records->first()['standard_unit'])->toBe('[fallback-unit]');
});

it('should return validation errors when creating measurement family', function () {

    $this->post(route('admin.measurement.families.store'), [])
        ->assertInvalid('code')
        ->assertInvalid('standard_unit_code');
});

it('should reject invalid measurement family code and labels', function () {

    $this->postJson(route('admin.measurement.families.store'), [
        'code'               => '15222222225155.$%^&**$%#@',
        'standard_unit_code' => '123meter$%',
        'labels'             => [
            'en_US' => 'Length123$%',
        ],
        'unit_labels' => [
            'en_US' => 'Meter$%',
        ],
    ])
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'code',
            'standard_unit_code',
            'labels.en_US',
            'unit_labels.en_US',
        ]);

    $this->assertDatabaseMissing('measurement_families', [
        'code' => '15222222225155.$%^&**$%#@',
    ]);
});

it('should create a measurement family successfully', function () {

    $code = 'length_'.uniqid();

    $data = [
        'code'               => $code,
        'standard_unit_code' => 'meter',
        'symbol'             => 'm',
        'labels'             => [
            'en_US' => 'Length',
            'hi_IN' => 'लंबाई',
        ],
    ];

    $this->postJson(route('admin.measurement.families.store'), $data)
        ->assertOk()
        ->assertJson(fn (AssertableJson $json) => $json->whereType('data.redirect_url', 'string')
        );

    $this->assertDatabaseHas('measurement_families', [
        'code' => $code,
    ]);
});

it('should return edit page of measurement family', function () {

    $family = MeasurementFamily::factory()->create();

    $this->get(route('admin.measurement.families.edit', $family->id))
        ->assertOk()
        ->assertSeeText($family->name);
});

it('should update measurement family labels successfully', function () {

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

    $families = MeasurementFamily::factory()->count(3)->create();

    $ids = $families->pluck('id')->toArray();

    $this->post(
        route('admin.measurement.families.mass_delete'),
        ['indices' => $ids]
    )
        ->assertOk()
        ->assertJsonFragment([
            'success' => true,
        ]);

    foreach ($ids as $id) {
        $this->assertDatabaseMissing('measurement_families', ['id' => $id]);
    }
});
