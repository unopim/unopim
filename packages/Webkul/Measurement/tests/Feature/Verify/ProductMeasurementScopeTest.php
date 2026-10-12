<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;
use Webkul\Product\Models\Product;
use Webkul\Product\Repositories\ProductRepository;

beforeEach(function () {
    $this->loginAsAdmin();
});

function scopedMeasurementAttribute(bool $perLocale, bool $perChannel): array
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
        'code'              => 'width_'.$suffix,
        'type'              => 'measurement',
        'value_per_locale'  => $perLocale,
        'value_per_channel' => $perChannel,
    ]);

    AttributeMeasurement::create([
        'attribute_id' => $attribute->id,
        'family_code'  => $family->code,
        'unit_code'    => 'meter',
    ]);

    return [$attribute, $family->code];
}

it('stores distinct locale-scoped measurement values without leaking across locales', function () {
    [$attribute, $familyCode] = scopedMeasurementAttribute(perLocale: true, perChannel: false);
    $code = $attribute->code;

    $product = Product::factory()->withInitialValues()->create();

    $product->values = array_merge($product->values, [
        'locale_specific' => [
            'en_US' => [$code => ['value' => '10', 'unit' => 'meter']],
            'fr_FR' => [$code => ['value' => '250', 'unit' => 'cm']],
        ],
    ]);
    $product->save();

    $values = Product::find($product->id)->values;

    expect($values['common'] ?? [])->not->toHaveKey($code)
        ->and($values)->not->toHaveKey('channel_specific')
        ->and($values)->not->toHaveKey('channel_locale_specific');

    $en = $values['locale_specific']['en_US'][$code] ?? null;
    $fr = $values['locale_specific']['fr_FR'][$code] ?? null;

    expect($en)->toBeArray()
        ->toHaveKeys(['unit', 'amount', 'family', 'base_data', 'base_unit', 'symbol'])
        ->and($en['unit'])->toBe('meter')
        ->and((float) $en['amount'])->toBe(10.0)
        ->and((float) $en['base_data'])->toBe(10.0)
        ->and($en['base_unit'])->toBe('meter')
        ->and($en['family'])->toBe($familyCode)
        ->and($en['symbol'])->toBe('m');

    expect($fr)->toBeArray()
        ->and($fr['unit'])->toBe('cm')
        ->and((float) $fr['amount'])->toBe(250.0)
        ->and((float) $fr['base_data'])->toBe(2.5)
        ->and($fr['base_unit'])->toBe('meter')
        ->and($fr['symbol'])->toBe('cm');

    expect((float) $en['base_data'])->not->toBe((float) $fr['base_data']);

    expect((float) $attribute->getValueFromProductValues($values, 'anychannel', 'en_US')['amount'])->toBe(10.0)
        ->and((float) $attribute->getValueFromProductValues($values, 'anychannel', 'fr_FR')['amount'])->toBe(250.0);

    $this->assertDatabaseHas('products', [
        'id'                                              => $product->id,
        'values->locale_specific->en_US->'.$code.'->unit' => 'meter',
        'values->locale_specific->fr_FR->'.$code.'->unit' => 'cm',
    ]);
});

it('stores distinct channel-scoped measurement values without leaking across channels', function () {
    [$attribute] = scopedMeasurementAttribute(perLocale: false, perChannel: true);
    $code = $attribute->code;

    $product = Product::factory()->withInitialValues()->create();

    $product->values = array_merge($product->values, [
        'channel_specific' => [
            'chan_a' => [$code => ['value' => '10', 'unit' => 'meter']],
            'chan_b' => [$code => ['value' => '500', 'unit' => 'cm']],
        ],
    ]);
    $product->save();

    $values = Product::find($product->id)->values;

    expect($values['common'] ?? [])->not->toHaveKey($code)
        ->and($values)->not->toHaveKey('locale_specific')
        ->and($values)->not->toHaveKey('channel_locale_specific');

    $a = $values['channel_specific']['chan_a'][$code] ?? null;
    $b = $values['channel_specific']['chan_b'][$code] ?? null;

    expect($a)->toBeArray()
        ->toHaveKeys(['unit', 'amount', 'family', 'base_data', 'base_unit', 'symbol'])
        ->and($a['unit'])->toBe('meter')
        ->and((float) $a['amount'])->toBe(10.0)
        ->and((float) $a['base_data'])->toBe(10.0)
        ->and($a['base_unit'])->toBe('meter');

    expect($b)->toBeArray()
        ->and($b['unit'])->toBe('cm')
        ->and((float) $b['amount'])->toBe(500.0)
        ->and((float) $b['base_data'])->toBe(5.0)
        ->and($b['symbol'])->toBe('cm');

    expect((float) $a['base_data'])->not->toBe((float) $b['base_data']);

    expect((float) $attribute->getValueFromProductValues($values, 'chan_a', 'anylocale')['base_data'])->toBe(10.0)
        ->and((float) $attribute->getValueFromProductValues($values, 'chan_b', 'anylocale')['base_data'])->toBe(5.0);

    $this->assertDatabaseHas('products', [
        'id'                                                => $product->id,
        'values->channel_specific->chan_a->'.$code.'->unit' => 'meter',
        'values->channel_specific->chan_b->'.$code.'->unit' => 'cm',
    ]);
});

it('stores channel-and-locale scoped measurement values under channel_locale_specific with per-cell base_data', function () {
    [$attribute] = scopedMeasurementAttribute(perLocale: true, perChannel: true);
    $code = $attribute->code;

    $product = Product::factory()->withInitialValues()->create();

    $product->values = array_merge($product->values, [
        'channel_locale_specific' => [
            'chan_a' => [
                'en_US' => [$code => ['value' => '10', 'unit' => 'meter']],
                'fr_FR' => [$code => ['value' => '250', 'unit' => 'cm']],
            ],
        ],
    ]);
    $product->save();

    $values = Product::find($product->id)->values;

    expect($values['common'] ?? [])->not->toHaveKey($code)
        ->and($values)->not->toHaveKey('locale_specific')
        ->and($values)->not->toHaveKey('channel_specific');

    $en = $values['channel_locale_specific']['chan_a']['en_US'][$code] ?? null;
    $fr = $values['channel_locale_specific']['chan_a']['fr_FR'][$code] ?? null;

    expect($en)->toBeArray()
        ->toHaveKeys(['unit', 'amount', 'family', 'base_data', 'base_unit', 'symbol'])
        ->and($en['unit'])->toBe('meter')
        ->and((float) $en['amount'])->toBe(10.0)
        ->and((float) $en['base_data'])->toBe(10.0);

    expect($fr)->toBeArray()
        ->and($fr['unit'])->toBe('cm')
        ->and((float) $fr['amount'])->toBe(250.0)
        ->and((float) $fr['base_data'])->toBe(2.5);

    expect((float) $en['base_data'])->not->toBe((float) $fr['base_data']);

    expect((float) $attribute->getValueFromProductValues($values, 'chan_a', 'en_US')['base_data'])->toBe(10.0)
        ->and((float) $attribute->getValueFromProductValues($values, 'chan_a', 'fr_FR')['base_data'])->toBe(2.5);

    $this->assertDatabaseHas('products', [
        'id'                                                              => $product->id,
        'values->channel_locale_specific->chan_a->en_US->'.$code.'->unit' => 'meter',
        'values->channel_locale_specific->chan_a->fr_FR->'.$code.'->unit' => 'cm',
    ]);
});

it('preserves an existing locale-scoped measurement value when another locale is updated through the repository', function () {
    [$attribute] = scopedMeasurementAttribute(perLocale: true, perChannel: false);
    $code = $attribute->code;

    $product = Product::factory()->withInitialValues()->create();

    request()->merge(['locale' => 'en_US']);
    app(ProductRepository::class)->update([
        'values' => [
            'locale_specific' => [
                'en_US' => [$code => ['value' => '10', 'unit' => 'meter']],
            ],
        ],
    ], $product->id);

    request()->merge(['locale' => 'fr_FR']);
    app(ProductRepository::class)->update([
        'values' => [
            'locale_specific' => [
                'fr_FR' => [$code => ['value' => '250', 'unit' => 'cm']],
            ],
        ],
    ], $product->id);

    $values = Product::find($product->id)->values;

    $en = $values['locale_specific']['en_US'][$code] ?? null;
    $fr = $values['locale_specific']['fr_FR'][$code] ?? null;

    expect($en)->toBeArray()
        ->and($en['unit'])->toBe('meter')
        ->and((float) $en['amount'])->toBe(10.0)
        ->and((float) $en['base_data'])->toBe(10.0);

    expect($fr)->toBeArray()
        ->and($fr['unit'])->toBe('cm')
        ->and((float) $fr['amount'])->toBe(250.0)
        ->and((float) $fr['base_data'])->toBe(2.5);

    $this->assertDatabaseHas('products', [
        'id'                                              => $product->id,
        'values->locale_specific->en_US->'.$code.'->unit' => 'meter',
        'values->locale_specific->fr_FR->'.$code.'->unit' => 'cm',
    ]);
});

it('preserves an existing channel-scoped measurement value when another channel is updated through the repository', function () {
    [$attribute] = scopedMeasurementAttribute(perLocale: false, perChannel: true);
    $code = $attribute->code;

    $product = Product::factory()->withInitialValues()->create();

    request()->merge(['channel' => 'chan_a']);
    app(ProductRepository::class)->update([
        'values' => [
            'channel_specific' => [
                'chan_a' => [$code => ['value' => '10', 'unit' => 'meter']],
            ],
        ],
    ], $product->id);

    request()->merge(['channel' => 'chan_b']);
    app(ProductRepository::class)->update([
        'values' => [
            'channel_specific' => [
                'chan_b' => [$code => ['value' => '500', 'unit' => 'cm']],
            ],
        ],
    ], $product->id);

    $values = Product::find($product->id)->values;

    $a = $values['channel_specific']['chan_a'][$code] ?? null;
    $b = $values['channel_specific']['chan_b'][$code] ?? null;

    expect($a)->toBeArray()
        ->and($a['unit'])->toBe('meter')
        ->and((float) $a['amount'])->toBe(10.0)
        ->and((float) $a['base_data'])->toBe(10.0);

    expect($b)->toBeArray()
        ->and($b['unit'])->toBe('cm')
        ->and((float) $b['amount'])->toBe(500.0)
        ->and((float) $b['base_data'])->toBe(5.0);

    $this->assertDatabaseHas('products', [
        'id'                                                => $product->id,
        'values->channel_specific->chan_a->'.$code.'->unit' => 'meter',
        'values->channel_specific->chan_b->'.$code.'->unit' => 'cm',
    ]);
});
