<?php

use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;
use Webkul\Measurement\Observers\ProductObserver;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;
use Webkul\Product\Validator\CommonValuesValidator;

beforeEach(function () {
    $this->loginAsAdmin();
});

function requiredMeasurementAttribute(bool $isRequired = true): Attribute
{
    $suffix = uniqid();

    $family = MeasurementFamily::factory()->create([
        'code'  => 'length_'.$suffix,
        'units' => [
            ['code' => 'meter', 'labels' => ['en_US' => 'Meter']],
            ['code' => 'cm', 'labels' => ['en_US' => 'Centimeter']],
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

    return $attribute;
}

it('throws a validation error when a required measurement value is empty', function () {
    $attribute = requiredMeasurementAttribute();

    $product = new Product;

    $product->values = [
        'common' => [
            $attribute->code => ['value' => '', 'unit' => 'meter'],
        ],
    ];

    app(ProductObserver::class)->saving($product);
})->throws(ValidationException::class);

it('does not throw when a required measurement value is provided', function () {
    $attribute = requiredMeasurementAttribute();

    $product = new Product;

    $product->values = [
        'common' => [
            $attribute->code => ['value' => '10', 'unit' => 'meter'],
        ],
    ];

    app(ProductObserver::class)->saving($product);

    expect($product->values['common'][$attribute->code]['amount'] ?? null)->not->toBeNull();
});

it('does not throw when an optional measurement value is empty', function () {
    $attribute = requiredMeasurementAttribute(isRequired: false);

    $product = new Product;

    $product->values = [
        'common' => [
            $attribute->code => ['value' => '', 'unit' => 'meter'],
        ],
    ];

    app(ProductObserver::class)->saving($product);

    expect($product->values['common'])->not->toHaveKey($attribute->code);
});

it('stores a zero amount for a required measurement saved through the product repository', function () {
    $attribute = requiredMeasurementAttribute();

    $product = Product::factory()->withInitialValues()->create();

    app(ProductRepository::class)->update([
        'values' => ['common' => [$attribute->code => ['value' => '0', 'unit' => 'meter']]],
    ], $product->id);

    expect((float) Product::find($product->id)->values['common'][$attribute->code]['amount'])->toBe(0.0);
});

it('removes a blank measurement saved through the product repository', function () {
    $attribute = requiredMeasurementAttribute(isRequired: false);

    $product = Product::factory()->withInitialValues()->create();

    app(ProductRepository::class)->update([
        'values' => ['common' => [$attribute->code => ['value' => '', 'unit' => 'meter']]],
    ], $product->id);

    expect(Product::find($product->id)->values['common'])->not->toHaveKey($attribute->code);
});

function decimalMeasurementAttribute(bool $isRequired = false): Attribute
{
    $attribute = requiredMeasurementAttribute($isRequired);

    $attribute->validation = 'decimal';
    $attribute->save();

    return $attribute;
}

function validateCommonMeasurement(Attribute $attribute, array $payload): void
{
    app(CommonValuesValidator::class)->validate([
        'common' => [
            $attribute->code => $payload,
        ],
    ]);
}

it('accepts an empty optional measurement validated as decimal', function () {
    $attribute = decimalMeasurementAttribute();

    validateCommonMeasurement($attribute, ['value' => '', 'unit' => 'meter']);
})->throwsNoExceptions();

it('accepts a filled measurement validated as decimal', function () {
    $attribute = decimalMeasurementAttribute();

    validateCommonMeasurement($attribute, ['value' => '10.5', 'unit' => 'meter']);
})->throwsNoExceptions();

it('accepts a stored measurement payload validated as decimal', function () {
    $attribute = decimalMeasurementAttribute();

    validateCommonMeasurement($attribute, ['amount' => '10.5', 'unit' => 'meter']);
})->throwsNoExceptions();

it('rejects a non numeric measurement validated as decimal', function () {
    $attribute = decimalMeasurementAttribute();

    validateCommonMeasurement($attribute, ['value' => 'abc', 'unit' => 'meter']);
})->throws(ValidationException::class);

it('rejects an empty required measurement validated as decimal', function () {
    $attribute = decimalMeasurementAttribute(isRequired: true);

    validateCommonMeasurement($attribute, ['value' => '', 'unit' => 'meter']);
})->throws(ValidationException::class);

it('reports the attribute label instead of the payload key', function () {
    $attribute = decimalMeasurementAttribute();

    try {
        validateCommonMeasurement($attribute, ['value' => 'abc', 'unit' => 'meter']);
    } catch (ValidationException $e) {
        expect($e->validator->errors()->first())->not->toContain('.value');

        return;
    }

    $this->fail('Expected a validation error for a non numeric measurement.');
});
