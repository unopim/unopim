<?php

use Webkul\Measurement\Models\MeasurementFamily;

uses(
    Webkul\Measurement\Tests\MeasurementTestCase::class
)->group('measurement', 'admin');

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
            'message' => 'Unit code already exists',
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
