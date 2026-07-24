<?php

use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeOption;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;
use Webkul\Measurement\Observers\ProductObserver;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

beforeEach(function () {
    $this->loginAsAdmin();
});

function matrixMeasurementAttribute(bool $isRequired = false, string $label = 'mwidth'): Attribute
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
        'code'        => $label.'_'.$suffix,
        'type'        => 'measurement',
        'is_required' => $isRequired ? 1 : 0,
    ]);

    AttributeMeasurement::create([
        'attribute_id' => $attribute->id,
        'family_code'  => $family->code,
        'unit_code'    => 'meter',
    ]);

    return $attribute;
}

function matrixReloadCommon(int $productId): array
{
    return Product::find($productId)->values['common'] ?? [];
}

function matrixRunObserver(array $common): Product
{
    $product = new Product;

    $product->values = ['common' => $common];

    app(ProductObserver::class)->saving($product);

    return $product;
}

it('flags only the empty required measurement when several are submitted together', function () {
    $required = matrixMeasurementAttribute(isRequired: true, label: 'reqmeas');
    $filled = matrixMeasurementAttribute(isRequired: true, label: 'filledmeas');

    $product = new Product;

    $product->values = [
        'common' => [
            $required->code => ['value' => '', 'unit' => 'meter'],
            $filled->code   => ['value' => '5', 'unit' => 'meter'],
        ],
    ];

    $exception = null;

    try {
        app(ProductObserver::class)->saving($product);
    } catch (ValidationException $e) {
        $exception = $e;
    }

    expect($exception)->not->toBeNull();

    expect(array_keys($exception->errors()))
        ->toContain($required->code)
        ->not->toContain($filled->code);
});

it('accepts an optional empty measurement submitted next to a filled required one', function () {
    $required = matrixMeasurementAttribute(isRequired: true, label: 'reqmeas');
    $optional = matrixMeasurementAttribute(isRequired: false, label: 'optmeas');

    $product = matrixRunObserver([
        $required->code => ['value' => '10', 'unit' => 'meter'],
        $optional->code => ['value' => '', 'unit' => 'meter'],
    ]);

    $common = $product->values['common'];

    expect($common)->not->toHaveKey($optional->code)
        ->and($common[$required->code])->toBeArray()
        ->and((float) $common[$required->code]['amount'])->toBe(10.0)
        ->and($common[$required->code]['base_unit'])->toBe('meter');
});

it('persists two independent measurement values on one product through the repository', function () {
    $first = matrixMeasurementAttribute(label: 'firstmeas');
    $second = matrixMeasurementAttribute(label: 'secondmeas');

    $product = Product::factory()->withInitialValues()->create();

    app(ProductRepository::class)->update([
        'values' => [
            'common' => [
                $first->code  => ['value' => '10', 'unit' => 'meter'],
                $second->code => ['value' => '250', 'unit' => 'cm'],
            ],
        ],
    ], $product->id);

    $common = matrixReloadCommon($product->id);

    expect($common[$first->code])->toBeArray()
        ->toHaveKeys(['unit', 'amount', 'family', 'base_data', 'base_unit', 'symbol'])
        ->and($common[$first->code]['unit'])->toBe('meter')
        ->and((float) $common[$first->code]['base_data'])->toBe(10.0)
        ->and($common[$second->code]['unit'])->toBe('cm')
        ->and((float) $common[$second->code]['amount'])->toBe(250.0)
        ->and((float) $common[$second->code]['base_data'])->toBe(2.5);

    $this->assertDatabaseHas('products', [
        'id'                                       => $product->id,
        'values->common->'.$first->code.'->unit'   => 'meter',
        'values->common->'.$second->code.'->unit'  => 'cm',
    ]);
});

it('saves a measurement attribute alongside text, number, select and price attributes', function () {
    $measurement = matrixMeasurementAttribute(label: 'combomeas');

    $suffix = uniqid();

    $text = Attribute::factory()->create(['code' => 'note_'.$suffix, 'type' => 'text']);
    $number = Attribute::factory()->create(['code' => 'qty_'.$suffix, 'type' => 'text', 'validation' => 'numeric']);
    $select = Attribute::factory()->create(['code' => 'pick_'.$suffix, 'type' => 'select']);
    $price = Attribute::factory()->create(['code' => 'cost_'.$suffix, 'type' => 'price']);

    $optionCode = AttributeOption::where('attribute_id', $select->id)->value('code');

    $product = Product::factory()->withInitialValues()->create();

    app(ProductRepository::class)->update([
        'values' => [
            'common' => [
                $measurement->code => ['value' => '4', 'unit' => 'cm'],
                $text->code        => 'a combined note',
                $number->code      => '7',
                $select->code      => $optionCode,
                $price->code       => ['USD' => '12.50'],
            ],
        ],
    ], $product->id);

    $common = matrixReloadCommon($product->id);

    expect($common[$measurement->code])->toBeArray()
        ->toHaveKeys(['unit', 'amount', 'family', 'base_data', 'base_unit', 'symbol'])
        ->and($common[$measurement->code]['unit'])->toBe('cm')
        ->and((float) $common[$measurement->code]['amount'])->toBe(4.0)
        ->and((float) $common[$measurement->code]['base_data'])->toBe(0.04)
        ->and($common[$text->code] ?? null)->toBe('a combined note')
        ->and($common[$number->code] ?? null)->toBe('7')
        ->and($common[$select->code] ?? null)->toBe($optionCode)
        ->and($common[$price->code]['USD'] ?? null)->toBe('12.50');

    $this->assertDatabaseHas('products', [
        'id'                                            => $product->id,
        'values->common->'.$measurement->code.'->unit'  => 'cm',
    ]);
});

it('rejects an empty required measurement on the repository save stack and stores nothing', function () {
    $attribute = matrixMeasurementAttribute(isRequired: true, label: 'reqmeas');

    $product = Product::factory()->withInitialValues()->create();

    expect(fn () => app(ProductRepository::class)->update([
        'values' => [
            'common' => [
                $attribute->code => ['value' => '', 'unit' => 'meter'],
            ],
        ],
    ], $product->id))->toThrow(ValidationException::class);

    expect(matrixReloadCommon($product->id))->not->toHaveKey($attribute->code);

    $this->assertDatabaseMissing('products', [
        'id'                                          => $product->id,
        'values->common->'.$attribute->code.'->unit'  => 'meter',
    ]);
});

it('rejects a measurement value whose unit is not in the attribute family', function () {
    $attribute = matrixMeasurementAttribute(label: 'badunitmeas');

    expect(fn () => matrixRunObserver([
        $attribute->code => ['value' => '3', 'unit' => 'furlong'],
    ]))->toThrow(ValidationException::class);
});

it('rejects a non-numeric measurement amount', function () {
    $attribute = matrixMeasurementAttribute(isRequired: true, label: 'reqmeas');

    expect(fn () => matrixRunObserver([
        $attribute->code => ['value' => 'abc', 'unit' => 'meter'],
    ]))->toThrow(ValidationException::class);
});

it('recomputes a normalized measurement when a fresh value is re-submitted on top of it', function () {
    $attribute = matrixMeasurementAttribute(label: 'patchmeas');

    $product = matrixRunObserver([
        $attribute->code => [
            'unit'      => 'meter',
            'amount'    => '5',
            'base_data' => '5',
            'base_unit' => 'meter',
            'symbol'    => 'm',
            'value'     => '20',
        ],
    ]);

    $stored = $product->values['common'][$attribute->code];

    expect((float) $stored['amount'])->toBe(20.0)
        ->and($stored)->not->toHaveKey('value');
});

it('recomputes base_data from the amount when a normalized value is re-saved without a fresh value', function () {
    $attribute = matrixMeasurementAttribute(label: 'amountonly');

    $product = matrixRunObserver([
        $attribute->code => [
            'unit'      => 'cm',
            'amount'    => '250',
            'base_data' => '999',
            'base_unit' => 'meter',
            'symbol'    => 'cm',
        ],
    ]);

    $stored = $product->values['common'][$attribute->code];

    expect((float) $stored['amount'])->toBe(250.0)
        ->and((float) $stored['base_data'])->toBe(2.5)
        ->and($stored)->not->toHaveKey('value');
});
