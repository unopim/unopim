<?php

use Webkul\Measurement\Models\MeasurementFamily;

beforeEach(function () {
    $this->loginAsAdmin();
});

function familyWithUnits(array $units = [])
{
    return MeasurementFamily::factory()->create([
        'units' => $units,
    ]);
}

it('should return units index page', function () {
    $family = familyWithUnits();

    $this->get(
        route('admin.measurement.families.units', $family->id)
    )
        ->assertOk();
});

it('should show code fallback when unit label is missing', function () {
    $family = familyWithUnits([
        ['code' => 'fallback-unit', 'labels' => []],
    ]);

    $response = $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->get(
        route('admin.measurement.families.units', $family->id)
    );

    $response->assertOk();

    $records = collect($response->json('records'));

    expect($records)->toHaveCount(1)
        ->and($records->first()['label'])->toBe('[fallback-unit]');
});

it('should create a unit successfully', function () {
    $family = familyWithUnits();

    $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->post(
        route('admin.measurement.families.units.store', $family->id),
        [
            'code'   => 'meter',
            'symbol' => 'm',
            'labels' => ['en_US' => 'Meter'],
        ]
    )
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['redirect_url'],
        ]);

    $family->refresh();

    expect($family->units)->toHaveCount(1);
});

it('should return validation error when unit code missing', function () {
    $family = familyWithUnits();

    $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept'           => 'application/json',
    ])->post(
        route('admin.measurement.families.units.store', $family->id),
        [
            'labels' => ['en_US' => 'Meter'],
        ]
    )
        ->assertStatus(422)
        ->assertJsonValidationErrors(['code']);
});

it('should reject invalid unit code and labels', function () {
    $family = familyWithUnits();

    $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept'           => 'application/json',
    ])->post(
        route('admin.measurement.families.units.store', $family->id),
        [
            'code'   => '15222222225155.$%^&**$%#@',
            'labels' => [
                'en_US' => 'Meter123$%',
            ],
        ]
    )
        ->assertStatus(422)
        ->assertJsonValidationErrors([
            'code',
            'labels.en_US',
        ]);

    $family->refresh();

    expect($family->units)->toBeEmpty();
});

it('should not allow duplicate unit code', function () {
    $family = familyWithUnits([
        ['code' => 'meter', 'labels' => ['en_US' => 'Meter']],
    ]);

    $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->post(
        route('admin.measurement.families.units.store', $family->id),
        [
            'code'   => 'meter',
            'labels' => ['en_US' => 'Meter'],
        ]
    )
        ->assertStatus(422)
        ->assertJsonFragment([
            'message' => 'Unit code already exists.',
        ]);
});

it('should return edit unit page', function () {
    $family = familyWithUnits([
        ['code' => 'meter', 'labels' => ['en_US' => 'Meter']],
    ]);

    $this->get(
        route(
            'admin.measurement.families.units.edit',
            ['familyId' => $family->id, 'code' => 'meter']
        )
    )
        ->assertOk();
});

it('should update unit successfully', function () {
    $family = familyWithUnits([
        ['code' => 'meter', 'symbol' => 'm', 'labels' => ['en_US' => 'Meter']],
    ]);

    $this->put(
        route(
            'admin.measurement.families.units.update',
            ['familyId' => $family->id, 'code' => 'meter']
        ),
        [
            'symbol' => 'mtr',
            'labels' => ['hi_IN' => 'मीटर'],
        ]
    )
        ->assertRedirect();

    $family->refresh();

    expect($family->units[0]['symbol'])->toBe('mtr');
    expect($family->units[0]['labels'])->toHaveKey('hi_IN');
});

it('should create a unit with conversion data successfully', function () {
    $family = familyWithUnits();

    $this->withHeaders([
        'X-Requested-With' => 'XMLHttpRequest',
    ])->post(
        route('admin.measurement.families.units.store', $family->id),
        [
            'code'                  => 'meter',
            'symbol'                => 'm',
            'labels'                => ['en_US' => 'Meter'],
            'convert_from_standard' => ['mul', 'add'],
            'convert_value'         => ['1', '10'],
        ]
    )
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['redirect_url'],
        ]);

    $family->refresh();

    expect($family->units)->toHaveCount(1);
    expect($family->units[0]['convert_from_standard'])->toEqual([
        ['operator' => 'mul', 'value' => '1'],
        ['operator' => 'add', 'value' => '10'],
    ]);
});

it('should update unit conversion successfully', function () {
    $family = familyWithUnits([
        [
            'code'                  => 'meter',
            'symbol'                => 'm',
            'labels'                => ['en_US' => 'Meter'],
            'convert_from_standard' => ['mul'],
            'convert_value'         => ['1'],
        ],
    ]);

    $this->put(
        route(
            'admin.measurement.families.units.update',
            ['familyId' => $family->id, 'code' => 'meter']
        ),
        [
            'symbol'                => 'mtr',
            'labels'                => ['hi_IN' => 'मीटर'],
            'convert_from_standard' => ['div', 'add'],
            'convert_value'         => ['2', '5'],
        ]
    )
        ->assertRedirect();

    $family->refresh();

    expect($family->units[0]['symbol'])->toBe('mtr');
    expect($family->units[0]['convert_from_standard'])->toEqual([
        ['operator' => 'div', 'value' => '2'],
        ['operator' => 'add', 'value' => '5'],
    ]);
});

it('should delete unit successfully', function () {
    $family = familyWithUnits([
        ['code' => 'meter', 'labels' => ['en_US' => 'Meter']],
    ]);

    $this->delete(
        route(
            'admin.measurement.families.units.delete',
            ['familyId' => $family->id, 'code' => 'meter']
        )
    )
        ->assertOk()
        ->assertJson(['status' => true]);

    $family->refresh();

    expect($family->units)->toBeEmpty();
});

it('should update only the labels without requiring the symbol', function () {
    $family = familyWithUnits([
        ['code' => 'meter', 'labels' => ['en_US' => 'Meter'], 'symbol' => 'm'],
    ]);

    $this->put(
        route(
            'admin.measurement.families.units.update',
            ['familyId' => $family->id, 'code' => 'meter']
        ),
        ['labels' => ['en_US' => 'Metre']]
    )
        ->assertRedirect();

    $family->refresh();

    expect($family->units[0]['labels']['en_US'])->toBe('Metre')
        ->and($family->units[0]['symbol'])->toBe('m');
});
