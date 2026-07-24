<?php

use Webkul\AdminApi\Tests\Traits\ApiHelperTrait;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Core\Models\Channel;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;
use Webkul\Product\Models\Product;

uses(ApiHelperTrait::class);

beforeEach(function () {
    $this->headers = $this->getAuthenticationHeaders();
});

function apiCovMeasurementAttribute(bool $perLocale = false, bool $perChannel = false): array
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

function apiCovAttachToFamily(Attribute $attribute, AttributeFamily $family): void
{
    $family->attributeFamilyGroupMappings->first()?->customAttributes()?->attach($attribute);
}

it('creates a simple product with a common measurement value and stores the normalized structure', function () {
    [$attribute, $familyCode] = apiCovMeasurementAttribute();
    $family = AttributeFamily::first();
    apiCovAttachToFamily($attribute, $family);

    $code = $attribute->code;
    $sku = 'meas_'.uniqid();

    $payload = [
        'sku'    => $sku,
        'parent' => null,
        'family' => $family->code,
        'values' => [
            'common' => [
                'sku' => $sku,
                $code => ['value' => '10', 'unit' => 'meter'],
            ],
        ],
    ];

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.products.store'), $payload)
        ->assertStatus(201)
        ->assertJsonFragment(['success' => true]);

    $stored = Product::where('sku', $sku)->first()->values['common'][$code] ?? null;

    expect($stored)->toBeArray()
        ->toHaveKeys(['unit', 'amount', 'family', 'base_data', 'base_unit', 'symbol'])
        ->and($stored['unit'])->toBe('meter')
        ->and((float) $stored['amount'])->toBe(10.0)
        ->and((float) $stored['base_data'])->toBe(10.0)
        ->and($stored['base_unit'])->toBe('meter')
        ->and($stored['family'])->toBe($familyCode)
        ->and($stored['symbol'])->toBe('m');

    $this->assertDatabaseHas('products', [
        'sku'                             => $sku,
        'values->common->'.$code.'->unit' => 'meter',
    ]);
});

it('reads a stored common measurement value back through the product GET endpoint with unit converted to base', function () {
    [$attribute] = apiCovMeasurementAttribute();
    $family = AttributeFamily::first();
    apiCovAttachToFamily($attribute, $family);

    $code = $attribute->code;
    $sku = 'meas_'.uniqid();

    $this->withHeaders($this->headers)
        ->json('POST', route('admin.api.products.store'), [
            'sku'    => $sku,
            'parent' => null,
            'family' => $family->code,
            'values' => [
                'common' => [
                    'sku' => $sku,
                    $code => ['value' => '250', 'unit' => 'cm'],
                ],
            ],
        ])
        ->assertStatus(201);

    $this->withHeaders($this->headers)
        ->json('GET', route('admin.api.products.get', ['code' => $sku]))
        ->assertOk()
        ->assertJsonPath("values.common.$code.unit", 'cm')
        ->assertJsonPath("values.common.$code.base_unit", 'meter')
        ->assertJsonFragment(['sku' => $sku]);

    $stored = Product::where('sku', $sku)->first()->values['common'][$code];

    expect((float) $stored['amount'])->toBe(250.0)
        ->and((float) $stored['base_data'])->toBe(2.5)
        ->and($stored['symbol'])->toBe('cm');
});

it('sets a common measurement value on an existing product through the product PUT endpoint', function () {
    [$attribute, $familyCode] = apiCovMeasurementAttribute();

    $product = Product::factory()->withInitialValues()->create();
    apiCovAttachToFamily($attribute, $product->attribute_family);

    $code = $attribute->code;

    $payload = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $product->attribute_family->code,
        'values' => [
            'common' => [
                'sku' => $product->sku,
                $code => ['value' => '500', 'unit' => 'cm'],
            ],
        ],
    ];

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.products.update', ['code' => $product->sku]), $payload)
        ->assertOk()
        ->assertJsonFragment(['success' => true]);

    $product->refresh();
    $stored = $product->values['common'][$code] ?? null;

    expect($stored)->toBeArray()
        ->toHaveKeys(['unit', 'amount', 'family', 'base_data', 'base_unit', 'symbol'])
        ->and($stored['unit'])->toBe('cm')
        ->and((float) $stored['amount'])->toBe(500.0)
        ->and((float) $stored['base_data'])->toBe(5.0)
        ->and($stored['base_unit'])->toBe('meter')
        ->and($stored['family'])->toBe($familyCode);

    $this->assertDatabaseHas('products', [
        'id'                              => $product->id,
        'values->common->'.$code.'->unit' => 'cm',
    ]);
});

it('recomputes amount and base_data when a common measurement value is replaced through a second PUT', function () {
    [$attribute] = apiCovMeasurementAttribute();

    $product = Product::factory()->withInitialValues()->create();
    apiCovAttachToFamily($attribute, $product->attribute_family);

    $code = $attribute->code;

    $buildPayload = fn (string $value): array => [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $product->attribute_family->code,
        'values' => [
            'common' => [
                'sku' => $product->sku,
                $code => ['value' => $value, 'unit' => 'meter'],
            ],
        ],
    ];

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.products.update', ['code' => $product->sku]), $buildPayload('10'))
        ->assertOk();

    expect((float) Product::find($product->id)->values['common'][$code]['amount'])->toBe(10.0);

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.products.update', ['code' => $product->sku]), $buildPayload('3'))
        ->assertOk();

    $stored = Product::find($product->id)->values['common'][$code];

    expect((float) $stored['amount'])->toBe(3.0)
        ->and((float) $stored['base_data'])->toBe(3.0)
        ->and($stored)->not->toHaveKey('value');
});

it('sets a common measurement value on an existing product through the PATCH endpoint', function () {
    [$attribute, $familyCode] = apiCovMeasurementAttribute();

    $product = Product::factory()->withInitialValues()->create();
    apiCovAttachToFamily($attribute, $product->attribute_family);

    $code = $attribute->code;

    $this->withHeaders($this->headers)
        ->json('PATCH', route('admin.api.products.patch', ['sku' => $product->sku]), [
            'values' => [
                'common' => [
                    $code => ['value' => '7', 'unit' => 'meter'],
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonFragment(['success' => true]);

    $stored = Product::find($product->id)->values['common'][$code] ?? null;

    expect($stored)->toBeArray()
        ->toHaveKeys(['unit', 'amount', 'family', 'base_data', 'base_unit', 'symbol'])
        ->and($stored['unit'])->toBe('meter')
        ->and((float) $stored['amount'])->toBe(7.0)
        ->and((float) $stored['base_data'])->toBe(7.0)
        ->and($stored['family'])->toBe($familyCode);
});

it('stores distinct channel-scoped measurement values through the product PUT endpoint without leaking across channels', function () {
    [$attribute] = apiCovMeasurementAttribute(perLocale: false, perChannel: true);

    $product = Product::factory()->withInitialValues()->create();
    apiCovAttachToFamily($attribute, $product->attribute_family);

    $code = $attribute->code;
    $defaultChannel = core()->getDefaultChannel()->code;
    $otherChannel = Channel::factory()->create()->code;

    $payload = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $product->attribute_family->code,
        'values' => [
            'common' => [
                'sku' => $product->sku,
            ],
            'channel_specific' => [
                $defaultChannel => [$code => ['value' => '10', 'unit' => 'meter']],
                $otherChannel   => [$code => ['value' => '500', 'unit' => 'cm']],
            ],
        ],
    ];

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.products.update', ['code' => $product->sku]), $payload)
        ->assertOk()
        ->assertJsonFragment(['success' => true]);

    $values = Product::find($product->id)->values;

    expect($values['common'] ?? [])->not->toHaveKey($code);

    $a = $values['channel_specific'][$defaultChannel][$code] ?? null;
    $b = $values['channel_specific'][$otherChannel][$code] ?? null;

    expect($a)->toBeArray()
        ->and($a['unit'])->toBe('meter')
        ->and((float) $a['amount'])->toBe(10.0)
        ->and((float) $a['base_data'])->toBe(10.0);

    expect($b)->toBeArray()
        ->and($b['unit'])->toBe('cm')
        ->and((float) $b['amount'])->toBe(500.0)
        ->and((float) $b['base_data'])->toBe(5.0);

    expect((float) $a['base_data'])->not->toBe((float) $b['base_data']);

    $this->assertDatabaseHas('products', [
        'id'                                                             => $product->id,
        'values->channel_specific->'.$defaultChannel.'->'.$code.'->unit' => 'meter',
        'values->channel_specific->'.$otherChannel.'->'.$code.'->unit'   => 'cm',
    ]);
});

it('stores a locale-scoped measurement value under locale_specific through the product PUT endpoint', function () {
    [$attribute, $familyCode] = apiCovMeasurementAttribute(perLocale: true, perChannel: false);

    $product = Product::factory()->withInitialValues()->create();
    apiCovAttachToFamily($attribute, $product->attribute_family);

    $code = $attribute->code;
    $locale = core()->getDefaultChannel()->locales->first()->code;

    $payload = [
        'sku'    => $product->sku,
        'parent' => null,
        'family' => $product->attribute_family->code,
        'values' => [
            'common' => [
                'sku' => $product->sku,
            ],
            'locale_specific' => [
                $locale => [$code => ['value' => '250', 'unit' => 'cm']],
            ],
        ],
    ];

    $this->withHeaders($this->headers)
        ->json('PUT', route('admin.api.products.update', ['code' => $product->sku]), $payload)
        ->assertOk()
        ->assertJsonFragment(['success' => true]);

    $values = Product::find($product->id)->values;

    expect($values['common'] ?? [])->not->toHaveKey($code)
        ->and($values)->not->toHaveKey('channel_specific');

    $stored = $values['locale_specific'][$locale][$code] ?? null;

    expect($stored)->toBeArray()
        ->toHaveKeys(['unit', 'amount', 'family', 'base_data', 'base_unit', 'symbol'])
        ->and($stored['unit'])->toBe('cm')
        ->and((float) $stored['amount'])->toBe(250.0)
        ->and((float) $stored['base_data'])->toBe(2.5)
        ->and($stored['family'])->toBe($familyCode);

    $this->assertDatabaseHas('products', [
        'id'                                                       => $product->id,
        'values->locale_specific->'.$locale.'->'.$code.'->unit'    => 'cm',
    ]);
});

it('forbids reading measurement families for an api key without the measurement scope (fails closed)', function () {
    MeasurementFamily::factory()->create();

    $headers = $this->getAuthenticationHeaders('custom', ['api.settings.locales']);

    $this->withHeaders($headers)
        ->json('GET', route('admin.api.measurement.index'))
        ->assertForbidden();
});

it('allows reading measurement families for an api key holding the measurement scope', function () {
    MeasurementFamily::factory()->create();

    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.measurements']);

    $this->withHeaders($headers)
        ->json('GET', route('admin.api.measurement.index'))
        ->assertOk()
        ->assertJson(['success' => true]);
});

it('forbids creating a product with a measurement value for an api key without the product create scope', function () {
    [$attribute] = apiCovMeasurementAttribute();
    $family = AttributeFamily::first();
    apiCovAttachToFamily($attribute, $family);

    $headers = $this->getAuthenticationHeaders('custom', ['api.catalog.measurements']);

    $code = $attribute->code;
    $sku = 'meas_'.uniqid();

    $this->withHeaders($headers)
        ->json('POST', route('admin.api.products.store'), [
            'sku'    => $sku,
            'parent' => null,
            'family' => $family->code,
            'values' => [
                'common' => [
                    'sku' => $sku,
                    $code => ['value' => '10', 'unit' => 'meter'],
                ],
            ],
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('products', ['sku' => $sku]);
});
