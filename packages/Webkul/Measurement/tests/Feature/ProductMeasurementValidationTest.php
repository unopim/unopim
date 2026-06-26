<?php

use Illuminate\Validation\ValidationException;
use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;
use Webkul\Measurement\Observers\ProductObserver;
use Webkul\Product\Models\Product;

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
