<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();
});

function recalcAttribute(?string $familyCode = null): array
{
    $suffix = uniqid();

    $family = MeasurementFamily::factory()->create([
        'code'          => $familyCode ?? ('len_'.$suffix),
        'standard_unit' => 'meter',
        'units'         => [
            ['code' => 'meter', 'labels' => ['en_US' => 'Meter'], 'convert_from_standard' => [['operator' => 'mul', 'value' => '1']]],
            ['code' => 'cm', 'labels' => ['en_US' => 'Centimeter'], 'convert_from_standard' => [['operator' => 'mul', 'value' => '100']]],
        ],
    ]);

    $attribute = Attribute::factory()->create([
        'code' => 'depth_'.$suffix,
        'type' => 'measurement',
    ]);

    AttributeMeasurement::create([
        'attribute_id' => $attribute->id,
        'family_code'  => $family->code,
        'unit_code'    => 'meter',
    ]);

    return [$family, $attribute];
}

function productWithStaleMeasurement(Attribute $attribute, string $amount = '500', string $unit = 'cm'): Product
{
    $product = new Product;
    $product->sku = 'recalc-'.uniqid();
    $product->type = 'simple';
    $product->attribute_family_id = AttributeFamily::first()->id;
    $product->values = [
        'common' => [
            'sku'              => $product->sku,
            $attribute->code   => ['value' => $amount, 'unit' => $unit],
        ],
    ];
    $product->save();

    $values = $product->fresh()->values;
    $values['common'][$attribute->code]['base_data'] = '999.999999';
    $values['common'][$attribute->code]['base_unit'] = 'WRONG';

    Product::where('id', $product->id)->update(['values' => json_encode($values)]);

    return $product->fresh();
}

it('rebuilds a stale base value', function () {
    [, $attribute] = recalcAttribute();

    $product = productWithStaleMeasurement($attribute);

    expect($product->values['common'][$attribute->code]['base_data'])->toBe('999.999999');

    $this->artisan('measurement:recalculate')->assertSuccessful();

    $rebuilt = $product->fresh()->values['common'][$attribute->code];

    expect($rebuilt['base_data'])->toBe('5.000000')
        ->and($rebuilt['base_unit'])->toBe('meter')
        ->and($rebuilt['amount'])->toBe('500.0000');
});

it('does not write anything in dry-run mode', function () {
    [, $attribute] = recalcAttribute();

    $product = productWithStaleMeasurement($attribute);

    $this->artisan('measurement:recalculate', ['--dry-run' => true])->assertSuccessful();

    expect($product->fresh()->values['common'][$attribute->code]['base_data'])->toBe('999.999999');
});

it('only touches the requested family when --family is given', function () {
    [$familyA, $attributeA] = recalcAttribute();
    [, $attributeB] = recalcAttribute();

    $productA = productWithStaleMeasurement($attributeA);
    $productB = productWithStaleMeasurement($attributeB);

    $this->artisan('measurement:recalculate', ['--family' => $familyA->code])->assertSuccessful();

    expect($productA->fresh()->values['common'][$attributeA->code]['base_data'])->toBe('5.000000')
        ->and($productB->fresh()->values['common'][$attributeB->code]['base_data'])->toBe('999.999999');
});

it('leaves an already correct value untouched', function () {
    [, $attribute] = recalcAttribute();

    $product = new Product;
    $product->sku = 'recalc-ok-'.uniqid();
    $product->type = 'simple';
    $product->attribute_family_id = AttributeFamily::first()->id;
    $product->values = [
        'common' => [
            'sku'            => $product->sku,
            $attribute->code => ['value' => '2', 'unit' => 'meter'],
        ],
    ];
    $product->save();

    $before = $product->fresh()->values['common'][$attribute->code];

    $this->artisan('measurement:recalculate')->assertSuccessful();

    expect($product->fresh()->values['common'][$attribute->code])->toBe($before);
});

it('backfills the symbol onto values stored before symbols existed', function () {
    [, $attribute] = recalcAttribute();

    $product = productWithStaleMeasurement($attribute);

    $values = $product->values;
    unset($values['common'][$attribute->code]['symbol']);
    Product::where('id', $product->id)->update(['values' => json_encode($values)]);

    expect($product->fresh()->values['common'][$attribute->code])->not->toHaveKey('symbol');

    $this->artisan('measurement:recalculate')->assertSuccessful();

    expect($product->fresh()->values['common'][$attribute->code])->toHaveKey('symbol');
});
