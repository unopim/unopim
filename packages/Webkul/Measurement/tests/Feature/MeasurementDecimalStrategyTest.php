<?php

use Illuminate\Support\Facades\Cache;
use Webkul\Attribute\Models\Attribute;
use Webkul\Core\Models\CoreConfig;
use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;
use Webkul\Measurement\Services\Normalizers\MeasurementNormalizer;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

beforeEach(function () {
    $this->loginAsAdmin();

    Cache::flush();
});

function measurementPrecisionSetting(string $field, string $value): void
{
    CoreConfig::updateOrCreate(
        ['code' => "system.measurement.$field"],
        ['value' => $value]
    );

    Cache::flush();
}

function measurementRoundingPreset(): void
{
    measurementPrecisionSetting('strategy', 'round');
    measurementPrecisionSetting('amount', '2');
    measurementPrecisionSetting('base', '3');
}

function measurementPrecisionFixture(): array
{
    $suffix = uniqid();

    $family = MeasurementFamily::factory()->create([
        'code'          => 'length_'.$suffix,
        'standard_unit' => 'meter',
        'symbol'        => 'm',
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

    return [$attribute, $family];
}

it('reproduces the QA numbers through applyPrecision', function () {
    measurementRoundingPreset();

    $helper = app(MeasurementHelper::class);

    $amount = $helper->applyPrecision('346.57689', 'amount');
    $base = $helper->applyPrecision('346.57689', 'base');

    dump(['strategy' => 'round', 'amount_decimals' => 2, 'base_decimals' => 3, 'input' => '346.57689', 'amount_out' => $amount, 'base_out' => $base]);

    expect($amount)->toBe('346.58')
        ->and($base)->toBe('346.577');
});

it('stores one user entry as two differently rounded numbers on the product', function () {
    measurementRoundingPreset();

    [$attribute] = measurementPrecisionFixture();

    $product = Product::factory()->withInitialValues()->create();

    app(ProductRepository::class)->update([
        'values' => [
            'common' => [
                $attribute->code => ['value' => '346.57689', 'unit' => 'meter'],
            ],
        ],
    ], $product->id);

    $stored = Product::find($product->id)->values['common'][$attribute->code];

    dump(['stored_meter' => $stored]);

    expect($stored['amount'])->toBe('346.58')
        ->and($stored['base_data'])->toBe('346.577');
});

it('shows base_data is the converted value and not the entered value', function () {
    measurementRoundingPreset();

    [$attribute] = measurementPrecisionFixture();

    $product = Product::factory()->withInitialValues()->create();

    app(ProductRepository::class)->update([
        'values' => [
            'common' => [
                $attribute->code => ['value' => '346.57689', 'unit' => 'cm'],
            ],
        ],
    ], $product->id);

    $stored = Product::find($product->id)->values['common'][$attribute->code];

    dump(['stored_cm' => $stored]);

    expect($stored['amount'])->toBe('346.58')
        ->and($stored['base_data'])->toBe('3.466')
        ->and($stored['base_unit'])->toBe('meter');
});

it('rounds only at persistence and not on the raw calculation', function () {
    measurementRoundingPreset();

    [$attribute, $family] = measurementPrecisionFixture();

    $helper = app(MeasurementHelper::class);

    $raw = $helper->calculateBaseValue('346.57689', 'cm', $family);

    dump(['raw_base_value_before_precision' => $raw]);

    expect($raw)->toBe(3.4657689);

    $structure = $helper->getMeasurementValueStructure('346.57689', 'cm', $attribute);

    expect($structure['base_data'])->toBe('3.466');
});

it('applies the trim strategy to both amount and base', function () {
    measurementPrecisionSetting('strategy', 'trim');
    measurementPrecisionSetting('amount', '2');
    measurementPrecisionSetting('base', '3');

    $helper = app(MeasurementHelper::class);

    $amount = $helper->applyPrecision('346.57689', 'amount');
    $base = $helper->applyPrecision('346.57689', 'base');

    dump(['strategy' => 'trim', 'amount_out' => $amount, 'base_out' => $base]);

    expect($amount)->toBe('346.57')
        ->and($base)->toBe('346.576');
});

it('documents the help text currently shipped for the three settings', function () {
    $strings = [
        'strategy'      => trans('measurement::app.config.catalog.measurement.precision.strategy'),
        'strategy-info' => trans('measurement::app.config.catalog.measurement.precision.strategy-info'),
        'amount'        => trans('measurement::app.config.catalog.measurement.precision.amount'),
        'amount-info'   => trans('measurement::app.config.catalog.measurement.precision.amount-info'),
        'base'          => trans('measurement::app.config.catalog.measurement.precision.base'),
        'base-info'     => trans('measurement::app.config.catalog.measurement.precision.base-info'),
    ];

    dump($strings);

    expect($strings['strategy'])->toBe('Decimal strategy')
        ->and($strings['amount'])->toBe('Amount decimals')
        ->and($strings['base'])->toBe('Base value decimals');
});

it('shows fewer decimals than it stores because display formatting is hardcoded to four', function () {
    measurementPrecisionSetting('strategy', 'round');
    measurementPrecisionSetting('amount', '6');
    measurementPrecisionSetting('base', '6');

    [$attribute] = measurementPrecisionFixture();

    $product = Product::factory()->withInitialValues()->create();

    app(ProductRepository::class)->update([
        'values' => [
            'common' => [
                $attribute->code => ['value' => '1.2345678', 'unit' => 'meter'],
            ],
        ],
    ], $product->id);

    $stored = Product::find($product->id)->values['common'][$attribute->code];

    $displayed = app(MeasurementNormalizer::class)->getData(
        ['amount' => $stored['amount'], 'unit' => $stored['unit']],
        $attribute,
        ['format' => 'datagrid']
    );

    dump(['stored_amount' => $stored['amount'], 'datagrid_display' => $displayed]);

    expect($stored['amount'])->toBe('1.234568')
        ->and($displayed)->toBe('1.2346 Meter');
});

it('renders the info tooltip for every precision field including the strategy field', function () {
    measurementRoundingPreset();

    $html = $this->get(route('admin.settings.system.edit', ['key' => 'system.measurement']))
        ->assertOk()
        ->getContent();

    $strategyInfo = trans('measurement::app.config.catalog.measurement.precision.strategy-info');
    $amountInfo = trans('measurement::app.config.catalog.measurement.precision.amount-info');
    $baseInfo = trans('measurement::app.config.catalog.measurement.precision.base-info');

    expect(str_contains($html, $amountInfo))->toBeTrue()
        ->and(str_contains($html, $baseInfo))->toBeTrue()
        ->and(str_contains($html, $strategyInfo))->toBeTrue();
});
