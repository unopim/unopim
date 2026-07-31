<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();
});

function measurementVariantFamily(): array
{
    $suffix = uniqid();

    $measurementFamily = MeasurementFamily::factory()->create([
        'standard_unit' => 'meter',
        'units'         => [
            ['code' => 'meter', 'labels' => ['en_US' => 'Meter'], 'symbol' => 'm', 'convert_from_standard' => [['operator' => 'mul', 'value' => '1']]],
            ['code' => 'cm', 'labels' => ['en_US' => 'Centimeter'], 'symbol' => 'cm', 'convert_from_standard' => [['operator' => 'mul', 'value' => '100']]],
        ],
    ]);

    $measurement = Attribute::factory()->create([
        'code' => 'length_'.$suffix,
        'type' => 'measurement',
    ]);

    AttributeMeasurement::create([
        'attribute_id' => $measurement->id,
        'family_code'  => $measurementFamily->code,
        'unit_code'    => 'meter',
    ]);

    $axis = Attribute::factory()->create([
        'code' => 'shade_'.$suffix,
        'type' => 'select',
    ]);

    $family = AttributeFamily::factory()->create(['code' => 'fam_'.$suffix]);
    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    $family->refresh();
    AttributeFamily::factory()->linkAttributesToFamily($family, Attribute::whereIn('code', ['sku', 'status'])->get());
    AttributeFamily::factory()->linkAttributesToFamily($family, collect([$axis, $measurement]));

    return [$family->fresh(), $axis, $measurement, $measurementFamily];
}

function configurableParentWithMeasurement(AttributeFamily $family, Attribute $axis, Attribute $measurement, string $sku, string $rawValue, string $unit): Product
{
    $parent = Product::factory()->configurable()->create([
        'sku'                 => $sku,
        'attribute_family_id' => $family->id,
    ]);

    $parent->super_attributes()->attach($axis->id);

    $parent->values = [
        'common' => [
            'sku'              => $parent->sku,
            $measurement->code => ['value' => $rawValue, 'unit' => $unit],
        ],
    ];
    $parent->save();

    return $parent;
}

function simpleVariant(Product $parent, AttributeFamily $family, string $sku, array $common): Product
{
    $variant = Product::factory()->create([
        'sku'                 => $sku,
        'type'                => 'simple',
        'parent_id'           => $parent->id,
        'attribute_family_id' => $family->id,
    ]);

    $variant->values = ['common' => array_merge(['sku' => $variant->sku], $common)];
    $variant->save();

    return $variant;
}

it('does not offer a measurement attribute as a variant axis (super attribute)', function () {
    [$family, $axis, $measurement] = measurementVariantFamily();

    $configurableCodes = $family->getConfigurableAttributes()->pluck('code')->all();

    expect($configurableCodes)->toContain($axis->code)
        ->and($configurableCodes)->not->toContain($measurement->code);

    expect(AttributeFamily::ALLOWED_VARIANT_OPTION_TYPES)
        ->toContain('select')
        ->and(AttributeFamily::ALLOWED_VARIANT_OPTION_TYPES)->not->toContain('measurement');
});

it('rejects a measurement attribute as a variant-structure axis through the controller (422)', function () {
    [$family, , $measurement] = measurementVariantFamily();

    $this->put(route('admin.catalog.families.variant-structures.save', $family->id), [
        'structures' => [
            [
                'code'   => 'mstruct'.uniqid(),
                'name'   => 'Measurement Axis',
                'levels' => 1,
                'axes'   => [
                    'level_1' => [$measurement->code],
                ],
            ],
        ],
    ])->assertStatus(422);
});

it('accepts a select attribute as a variant-structure axis on the same family', function () {
    [$family, $axis] = measurementVariantFamily();

    $this->put(route('admin.catalog.families.variant-structures.save', $family->id), [
        'structures' => [
            [
                'code'   => 'sstruct'.uniqid(),
                'name'   => 'Select Axis',
                'levels' => 1,
                'axes'   => [
                    'level_1' => [$axis->code],
                ],
            ],
        ],
    ])->assertOk();
});

it('normalizes and persists a measurement value owned by a variant child, and reloads it', function () {
    [$family, $axis, $measurement, $measurementFamily] = measurementVariantFamily();

    $parent = configurableParentWithMeasurement($family, $axis, $measurement, 'CFG-'.uniqid(), '5', 'meter');

    $optionCode = $axis->options()->first()->code;

    $variant = simpleVariant($parent, $family, $parent->sku.'-A', [
        $axis->code        => $optionCode,
        $measurement->code => ['value' => '150', 'unit' => 'cm'],
    ]);

    $stored = Product::find($variant->id)->values['common'][$measurement->code];

    expect($stored)->toBeArray()
        ->and($stored)->toHaveKeys(['unit', 'amount', 'family', 'base_data', 'base_unit', 'symbol'])
        ->and($stored['unit'])->toBe('cm')
        ->and($stored['symbol'])->toBe('cm')
        ->and($stored['family'])->toBe($measurementFamily->code)
        ->and($stored['base_unit'])->toBe('meter')
        ->and((float) $stored['amount'])->toBe(150.0)
        ->and((float) $stored['base_data'])->toBe(1.5);
});

it('keeps parent and variant measurement values independent while inheriting when unset', function () {
    [$family, $axis, $measurement] = measurementVariantFamily();

    $parent = configurableParentWithMeasurement($family, $axis, $measurement, 'CFG-'.uniqid(), '5', 'meter');

    $options = $axis->options()->pluck('code')->all();
    $optionA = $options[0];
    $optionB = $options[1] ?? $options[0];

    $variantA = simpleVariant($parent, $family, $parent->sku.'-A', [
        $axis->code        => $optionA,
        $measurement->code => ['value' => '150', 'unit' => 'cm'],
    ]);

    $variantB = simpleVariant($parent, $family, $parent->sku.'-B', [
        $axis->code => $optionB,
    ]);

    expect(Product::find($variantB->id)->values['common'])->not->toHaveKey($measurement->code);

    $resolvedB = Product::find($variantB->id)->resolvedValues();
    expect((float) $resolvedB['common'][$measurement->code]['amount'])->toBe(5.0)
        ->and((float) $resolvedB['common'][$measurement->code]['base_data'])->toBe(5.0);

    $resolvedA = Product::find($variantA->id)->resolvedValues();
    expect((float) $resolvedA['common'][$measurement->code]['amount'])->toBe(150.0)
        ->and((float) $resolvedA['common'][$measurement->code]['base_data'])->toBe(1.5)
        ->and($resolvedA['common'][$measurement->code]['unit'])->toBe('cm');

    $parentStored = $parent->fresh()->values['common'][$measurement->code];
    expect((float) $parentStored['amount'])->toBe(5.0)
        ->and($parentStored['unit'])->toBe('meter');
});

it('updates a variant measurement value and reloads it without disturbing the parent', function () {
    [$family, $axis, $measurement] = measurementVariantFamily();

    $parent = configurableParentWithMeasurement($family, $axis, $measurement, 'CFG-'.uniqid(), '5', 'meter');

    $optionCode = $axis->options()->first()->code;

    $variant = simpleVariant($parent, $family, $parent->sku.'-A', [
        $axis->code        => $optionCode,
        $measurement->code => ['value' => '150', 'unit' => 'cm'],
    ]);

    $variant = Product::find($variant->id);
    $variant->values = [
        'common' => [
            'sku'              => $variant->sku,
            $axis->code        => $optionCode,
            $measurement->code => ['value' => '3', 'unit' => 'meter'],
        ],
    ];
    $variant->save();

    $reloaded = Product::find($variant->id)->values['common'][$measurement->code];

    expect($reloaded['unit'])->toBe('meter')
        ->and((float) $reloaded['amount'])->toBe(3.0)
        ->and((float) $reloaded['base_data'])->toBe(3.0);

    $parentStored = $parent->fresh()->values['common'][$measurement->code];
    expect((float) $parentStored['amount'])->toBe(5.0)
        ->and($parentStored['unit'])->toBe('meter');
});

it('renders the configurable product edit page carrying a measurement attribute without error', function () {
    [$family, $axis, $measurement] = measurementVariantFamily();

    $parent = configurableParentWithMeasurement($family, $axis, $measurement, 'CFG-'.uniqid(), '10', 'meter');

    $optionCode = $axis->options()->first()->code;

    simpleVariant($parent, $family, $parent->sku.'-A', [
        $axis->code        => $optionCode,
        $measurement->code => ['value' => '150', 'unit' => 'cm'],
    ]);

    $this->get(route('admin.catalog.products.edit', $parent->id))
        ->assertOk();
});
