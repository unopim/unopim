<?php

use Illuminate\Support\Facades\DB;
use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;
use Webkul\Measurement\Services\AttributeMeasurementService;

beforeEach(function () {
    $this->loginAsAdmin();

    $this->service = app(AttributeMeasurementService::class);
});

function payloadFamilyWithUnits(string $code, array $units): MeasurementFamily
{
    return MeasurementFamily::factory()->create([
        'code'  => $code,
        'units' => $units,
    ]);
}

function lengthUnits(): array
{
    return [
        ['code' => 'meter', 'labels' => ['en_US' => 'Meter'], 'symbol' => 'm'],
        ['code' => 'kilometer', 'labels' => ['en_US' => 'Kilometer'], 'symbol' => 'km'],
    ];
}

it('lists every family without carrying any of their units', function () {
    payloadFamilyWithUnits('payload_length', lengthUnits());
    payloadFamilyWithUnits('payload_weight', [['code' => 'gram', 'labels' => ['en_US' => 'Gram']]]);

    $attribute = Attribute::factory()->create();

    $payload = $this->service->buildPayload($attribute->id);

    expect(collect($payload['familyOptions'])->pluck('id'))
        ->toContain('payload_length')
        ->toContain('payload_weight');

    foreach ($payload['familyOptions'] as $option) {
        expect($option)->toHaveKeys(['id', 'label'])->not->toHaveKey('units');
    }

    expect($payload['units'])->toBe([]);
});

it('carries the units of the saved family so the current value renders', function () {
    $family = payloadFamilyWithUnits('payload_saved', lengthUnits());

    $attribute = Attribute::factory()->create();

    AttributeMeasurement::create([
        'attribute_id' => $attribute->id,
        'family_code'  => $family->code,
        'unit_code'    => 'meter',
    ]);

    $payload = $this->service->buildPayload($attribute->id);

    expect($payload['oldFamily'])->toBe('payload_saved')
        ->and($payload['oldUnit'])->toBe('meter')
        ->and(collect($payload['units'])->pluck('id')->all())->toBe(['meter', 'kilometer'])
        ->and(collect($payload['units'])->pluck('label')->all())->toBe(['Meter', 'Kilometer']);
});

/**
 * The payload used to embed every family's units, so its cost grew with the
 * whole measurement catalogue rather than staying flat.
 */
it('does not read more unit rows as the catalogue grows', function () {
    payloadFamilyWithUnits('payload_one', lengthUnits());

    $attribute = Attribute::factory()->create();

    $count = function () use ($attribute): int {
        $queries = 0;

        DB::listen(function ($query) use (&$queries): void {
            if (str_contains($query->sql, 'measurement_unit')) {
                $queries++;
            }
        });

        $this->service->buildPayload($attribute->id);

        return $queries;
    };

    $before = $count();

    foreach (range(1, 5) as $index) {
        payloadFamilyWithUnits('payload_extra_'.$index, lengthUnits());
    }

    expect($count())->toBe($before);
});

it('serves the units of a requested family', function () {
    payloadFamilyWithUnits('payload_endpoint', lengthUnits());

    $this->getJson(route('admin.measurement.family.units', ['family' => 'payload_endpoint']))
        ->assertOk()
        ->assertJson([
            'units' => [
                ['id' => 'meter', 'label' => 'Meter'],
                ['id' => 'kilometer', 'label' => 'Kilometer'],
            ],
        ]);
});

it('rejects a family that does not exist', function () {
    $this->getJson(route('admin.measurement.family.units', ['family' => 'no_such_family']))
        ->assertStatus(422)
        ->assertJsonValidationErrors('family');
});
