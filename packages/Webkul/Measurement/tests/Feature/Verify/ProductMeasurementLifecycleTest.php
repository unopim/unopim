<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

beforeEach(function () {
    $this->loginAsAdmin();
});

function lifecycleMeasurementSetup(bool $isRequired = false): array
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
        'code'        => 'width_'.$suffix,
        'type'        => 'measurement',
        'is_required' => $isRequired ? 1 : 0,
    ]);

    AttributeMeasurement::create([
        'attribute_id' => $attribute->id,
        'family_code'  => $family->code,
        'unit_code'    => 'meter',
    ]);

    return [$attribute, $family->code];
}

function lifecycleSubmitMeasurement(int $productId, string $code, string|int|float $value, string $unit): Product
{
    return app(ProductRepository::class)->update([
        'values' => [
            'common' => [
                $code => ['value' => (string) $value, 'unit' => $unit],
            ],
        ],
    ], $productId);
}

function lifecycleReloadCommon(int $productId): array
{
    return Product::find($productId)->values['common'] ?? [];
}

it('persists the full measurement value structure when a value is saved on a product', function () {
    [$attribute, $familyCode] = lifecycleMeasurementSetup();

    $product = Product::factory()->withInitialValues()->create();

    lifecycleSubmitMeasurement($product->id, $attribute->code, 10, 'meter');

    $stored = lifecycleReloadCommon($product->id)[$attribute->code] ?? null;

    expect($stored)->toBeArray()
        ->toHaveKeys(['unit', 'amount', 'family', 'base_data', 'base_unit', 'symbol'])
        ->and($stored['unit'])->toBe('meter')
        ->and((float) $stored['amount'])->toBe(10.0)
        ->and($stored['family'])->toBe($familyCode)
        ->and((float) $stored['base_data'])->toBe(10.0)
        ->and($stored['base_unit'])->toBe('meter')
        ->and($stored['symbol'])->toBe('m');

    $this->assertDatabaseHas('products', [
        'id'                                          => $product->id,
        'values->common->'.$attribute->code.'->unit'  => 'meter',
    ]);
});

it('recomputes base_data when the measurement amount and unit are updated', function () {
    [$attribute] = lifecycleMeasurementSetup();

    $product = Product::factory()->withInitialValues()->create();

    lifecycleSubmitMeasurement($product->id, $attribute->code, 10, 'meter');

    $before = lifecycleReloadCommon($product->id)[$attribute->code];

    expect($before['unit'])->toBe('meter')
        ->and((float) $before['base_data'])->toBe(10.0);

    lifecycleSubmitMeasurement($product->id, $attribute->code, 250, 'cm');

    $after = lifecycleReloadCommon($product->id)[$attribute->code];

    expect($after['unit'])->toBe('cm')
        ->and((float) $after['amount'])->toBe(250.0)
        ->and((float) $after['base_data'])->toBe(2.5)
        ->and($after['base_unit'])->toBe('meter')
        ->and($after['symbol'])->toBe('cm');
});

it('loads the saved measurement value back and keeps it intact across an unrelated product save', function () {
    [$attribute, $familyCode] = lifecycleMeasurementSetup();

    $product = Product::factory()->withInitialValues()->create();

    lifecycleSubmitMeasurement($product->id, $attribute->code, 10, 'meter');

    app(ProductRepository::class)->updateStatus(false, $product->id);

    $reloaded = lifecycleReloadCommon($product->id)[$attribute->code] ?? null;

    expect($reloaded)->toBeArray()
        ->toHaveKeys(['unit', 'amount', 'family', 'base_data', 'base_unit', 'symbol'])
        ->and($reloaded['unit'])->toBe('meter')
        ->and((float) $reloaded['amount'])->toBe(10.0)
        ->and((float) $reloaded['base_data'])->toBe(10.0)
        ->and($reloaded['family'])->toBe($familyCode);
});

it('saves a product that has no measurement value', function () {
    $text = Attribute::factory()->create([
        'code' => 'note_'.uniqid(),
        'type' => 'text',
    ]);

    $product = Product::factory()->withInitialValues()->create();

    app(ProductRepository::class)->update([
        'values' => [
            'common' => [
                $text->code => 'plain text',
            ],
        ],
    ], $product->id);

    $common = lifecycleReloadCommon($product->id);

    expect($common[$text->code] ?? null)->toBe('plain text');

    $this->assertDatabaseHas('products', [
        'id'  => $product->id,
        'sku' => $product->sku,
    ]);
});

it('adds a measurement value to an existing product that did not have one', function () {
    [$attribute] = lifecycleMeasurementSetup();

    $text = Attribute::factory()->create([
        'code' => 'note_'.uniqid(),
        'type' => 'text',
    ]);

    $product = Product::factory()->withInitialValues()->create();

    app(ProductRepository::class)->update([
        'values' => [
            'common' => [
                $text->code => 'hello',
            ],
        ],
    ], $product->id);

    expect(lifecycleReloadCommon($product->id))->not->toHaveKey($attribute->code);

    lifecycleSubmitMeasurement($product->id, $attribute->code, 5, 'meter');

    $common = lifecycleReloadCommon($product->id);

    expect($common[$text->code] ?? null)->toBe('hello')
        ->and($common[$attribute->code] ?? null)->toBeArray()
        ->and($common[$attribute->code]['unit'])->toBe('meter')
        ->and((float) $common[$attribute->code]['amount'])->toBe(5.0);
});

it('removes a measurement value when an empty amount is submitted', function () {
    [$attribute] = lifecycleMeasurementSetup();

    $product = Product::factory()->withInitialValues()->create();

    lifecycleSubmitMeasurement($product->id, $attribute->code, 10, 'meter');

    expect(lifecycleReloadCommon($product->id))->toHaveKey($attribute->code);

    lifecycleSubmitMeasurement($product->id, $attribute->code, '', 'meter');

    $common = lifecycleReloadCommon($product->id);

    expect($common)->not->toHaveKey($attribute->code)
        ->and($common['sku'] ?? null)->toBe($product->sku);
});
