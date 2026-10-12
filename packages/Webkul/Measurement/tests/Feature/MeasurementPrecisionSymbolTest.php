<?php

use Illuminate\Support\Facades\Cache;
use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Models\CoreConfig;
use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;

beforeEach(function () {
    $this->loginAsAdmin();

    Cache::flush();
});

function setMeasurementConfig(string $field, string $value): void
{
    CoreConfig::updateOrCreate(
        ['code' => "system.measurement.$field"],
        ['value' => $value]
    );

    Cache::flush();
}

function symbolAttribute(): Attribute
{
    $suffix = uniqid();

    $family = MeasurementFamily::factory()->create([
        'standard_unit' => 'meter',
        'units'         => [
            ['code' => 'meter', 'labels' => ['en_US' => 'Meter'], 'symbol' => 'm', 'convert_from_standard' => [['operator' => 'mul', 'value' => '1']]],
            ['code' => 'cm', 'labels' => ['en_US' => 'Centimeter'], 'symbol' => 'cm', 'convert_from_standard' => [['operator' => 'mul', 'value' => '100']]],
        ],
    ]);

    $attribute = Attribute::factory()->create([
        'code' => 'width_'.$suffix,
        'type' => 'measurement',
    ]);

    AttributeMeasurement::create([
        'attribute_id' => $attribute->id,
        'family_code'  => $family->code,
        'unit_code'    => 'meter',
    ]);

    return $attribute;
}

it('stores the unit symbol alongside the amount and unit', function () {
    $attribute = symbolAttribute();

    $structure = app(MeasurementHelper::class)
        ->getMeasurementValueStructure('3', 'cm', $attribute);

    expect($structure)->toHaveKeys(['unit', 'amount', 'family', 'base_data', 'base_unit', 'symbol'])
        ->and($structure['symbol'])->toBe('cm')
        ->and($structure['unit'])->toBe('cm');
});

it('returns a null symbol for a unit that has none configured', function () {
    $suffix = uniqid();

    $family = MeasurementFamily::factory()->create([
        'standard_unit' => 'unitless',
        'units'         => [
            ['code' => 'unitless', 'labels' => ['en_US' => 'Unitless'], 'convert_from_standard' => [['operator' => 'mul', 'value' => '1']]],
        ],
    ]);

    $attribute = Attribute::factory()->create(['code' => 'plain_'.$suffix, 'type' => 'measurement']);

    AttributeMeasurement::create([
        'attribute_id' => $attribute->id,
        'family_code'  => $family->code,
        'unit_code'    => 'unitless',
    ]);

    $structure = app(MeasurementHelper::class)
        ->getMeasurementValueStructure('1', 'unitless', $attribute);

    expect($structure['symbol'])->toBeNull();
});

it('defaults to rounding at 4 and 6 decimals', function () {
    $fields = collect(config('system_settings'))->firstWhere('key', 'system.measurement')['fields'];

    expect(collect($fields)->firstWhere('name', 'strategy')['default_value'])->toBe('round')
        ->and(collect($fields)->firstWhere('name', 'amount')['default_value'])->toBe('4')
        ->and(collect($fields)->firstWhere('name', 'base')['default_value'])->toBe('6');

    $helper = app(MeasurementHelper::class);

    expect($helper->applyPrecision('1.234567890', 'amount'))->toBe('1.2346')
        ->and($helper->applyPrecision('1.2345678901', 'base'))->toBe('1.234568');
});

it('truncates instead of rounding under the trim strategy', function () {
    setMeasurementConfig('strategy', 'trim');

    $helper = app(MeasurementHelper::class);

    expect($helper->applyPrecision('1.234567890', 'amount'))->toBe('1.2345')
        ->and($helper->applyPrecision('1.2345678901', 'base'))->toBe('1.234567');
});

it('trims toward zero for negative values', function () {
    setMeasurementConfig('strategy', 'trim');

    expect(app(MeasurementHelper::class)->applyPrecision('-1.98765', 'amount'))->toBe('-1.9876');
});

it('honours a configured decimal count', function () {
    setMeasurementConfig('amount', '2');

    expect(app(MeasurementHelper::class)->applyPrecision('3.14159', 'amount'))->toBe('3.14');
});

it('clamps an out-of-range precision to a safe maximum of ten decimals', function () {
    setMeasurementConfig('amount', '100');

    $result = app(MeasurementHelper::class)->applyPrecision('1.2345678901234567', 'amount');

    expect(strlen(substr($result, (int) strpos($result, '.') + 1)))->toBe(10);
});
