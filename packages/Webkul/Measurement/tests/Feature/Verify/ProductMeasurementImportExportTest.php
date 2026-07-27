<?php

use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter as CoreExporter;
use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer as CoreImporter;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();

    foreach ([CoreImporter::class, CoreExporter::class] as $class) {
        $cache = new ReflectionProperty($class, 'staticInitCache');
        $cache->setAccessible(true);
        $cache->setValue(null, null);
    }
});

function verifyImpExpSetup(?array $units = null): array
{
    $suffix = uniqid();

    $measurementFamily = MeasurementFamily::factory()->create([
        'standard_unit' => 'meter',
        'symbol'        => 'm',
        'units'         => $units ?? [
            [
                'code'                  => 'meter',
                'symbol'                => 'm',
                'labels'                => ['en_US' => 'Meter', 'hi_IN' => 'मीटर'],
                'convert_from_standard' => [['value' => '1', 'operator' => 'mul']],
            ],
            [
                'code'                  => 'cm',
                'symbol'                => 'cm',
                'labels'                => ['en_US' => 'Centimeter', 'hi_IN' => 'सेंटीमीटर'],
                'convert_from_standard' => [['value' => '100', 'operator' => 'mul']],
            ],
        ],
    ]);

    $measurement = Attribute::factory()->create([
        'code' => 'depth_'.$suffix,
        'type' => 'measurement',
    ]);

    AttributeMeasurement::create([
        'attribute_id' => $measurement->id,
        'family_code'  => $measurementFamily->code,
        'unit_code'    => 'meter',
    ]);

    $family = AttributeFamily::factory()->create();
    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    $family->refresh();
    AttributeFamily::factory()->linkAttributesToFamily($family, Attribute::whereIn('code', ['sku', 'status'])->get());
    AttributeFamily::factory()->linkAttributesToFamily($family, collect([$measurement]));

    return [$family->fresh(), $measurement, $measurementFamily];
}

function verifyImpExpImport(AttributeFamily $family, array $extraRow): array
{
    $importer = app(CoreImporter::class)->setImport(JobTrack::factory()->create());

    $rowData = array_merge([
        'sku'              => 'measurement-'.uniqid(),
        'type'             => 'simple',
        'attribute_family' => $family->code,
    ], $extraRow);

    $attributeValues = [];

    $importer->prepareAttributeValues($rowData, $attributeValues);

    return $attributeValues;
}

function verifyImpExpExportColumns(array $attributes, array $values, ?string $locale = null): array
{
    $exporter = app(CoreExporter::class);
    $reflection = new ReflectionClass($exporter);
    $collection = collect($attributes);

    $attributesProp = $reflection->getParentClass()->getProperty('attributes');
    $attributesProp->setAccessible(true);
    $attributesProp->setValue($exporter, $collection);

    $buildMeta = $reflection->getParentClass()->getMethod('buildAttributeMeta');
    $buildMeta->setAccessible(true);

    $metaProp = $reflection->getParentClass()->getProperty('attributeMeta');
    $metaProp->setAccessible(true);
    $metaProp->setValue($exporter, $buildMeta->invoke($exporter, $collection));

    $currencies = $reflection->getParentClass()->getProperty('currencies');
    $currencies->setAccessible(true);
    $currencies->setValue($exporter, ['USD']);

    $method = $reflection->getMethod('setAttributesValues');
    $method->setAccessible(true);

    return $method->invoke($exporter, $values, null, $locale);
}

it('imports a measurement column into the full { unit, amount, base_data } structure normalized to the family standard unit', function () {
    [$family, $measurement, $measurementFamily] = verifyImpExpSetup();

    $attributeValues = verifyImpExpImport($family, [
        $measurement->code          => '250',
        $measurement->code.'(unit)' => 'Centimeter',
    ]);

    $stored = $attributeValues['common'][$measurement->code] ?? null;

    expect($stored)->toBeArray()
        ->and($stored['unit'] ?? null)->toBe('cm')
        ->and((float) ($stored['amount'] ?? 0))->toBe(250.0)
        ->and((float) ($stored['base_data'] ?? 0))->toBe(2.5)
        ->and($stored['base_unit'] ?? null)->toBe('meter')
        ->and($stored['symbol'] ?? null)->toBe('cm')
        ->and($stored['family'] ?? null)->toBe($measurementFamily->code);
});

it('persists an imported measurement structure on a product and reads it back after reload', function () {
    [$family, $measurement] = verifyImpExpSetup();

    $attributeValues = verifyImpExpImport($family, [
        $measurement->code          => '250',
        $measurement->code.'(unit)' => 'Centimeter',
    ]);

    $stored = $attributeValues['common'][$measurement->code];

    $product = Product::factory()->withInitialValues()->create();

    $values = $product->values ?? [];
    $values['common'][$measurement->code] = $stored;
    $product->values = $values;
    $product->save();

    $reloaded = Product::find($product->id);
    $persisted = $reloaded->values['common'][$measurement->code] ?? null;

    expect($persisted)->toBeArray()
        ->and($persisted['unit'] ?? null)->toBe('cm')
        ->and((float) ($persisted['amount'] ?? 0))->toBe(250.0)
        ->and((float) ($persisted['base_data'] ?? 0))->toBe(2.5)
        ->and($persisted['base_unit'] ?? null)->toBe('meter');
});

it('exports the amount column plus a (unit) companion column carrying the localized unit label', function () {
    [, $measurement] = verifyImpExpSetup();

    $columns = verifyImpExpExportColumns(
        [$measurement],
        [$measurement->code => ['amount' => '250', 'unit' => 'cm']],
        'hi_IN'
    );

    expect($columns)->toHaveKey($measurement->code)
        ->and($columns)->toHaveKey($measurement->code.'(unit)')
        ->and($columns[$measurement->code])->toBe('250')
        ->and($columns[$measurement->code.'(unit)'])->toBe('सेंटीमीटर');
});

it('round-trips a measurement value through export then re-import without drift', function () {
    [$family, $measurement] = verifyImpExpSetup();

    $imported = verifyImpExpImport($family, [
        $measurement->code          => '250',
        $measurement->code.'(unit)' => 'Centimeter',
    ]);

    $stored = $imported['common'][$measurement->code];

    $columns = verifyImpExpExportColumns(
        [$measurement],
        [$measurement->code => $stored]
    );

    expect((float) $columns[$measurement->code])->toBe(250.0)
        ->and($columns[$measurement->code.'(unit)'])->toBe('Centimeter');

    $reimported = verifyImpExpImport($family, [
        $measurement->code          => $columns[$measurement->code],
        $measurement->code.'(unit)' => $columns[$measurement->code.'(unit)'],
    ]);

    $roundTripped = $reimported['common'][$measurement->code] ?? null;

    expect($roundTripped)->toBeArray()
        ->and($roundTripped['unit'] ?? null)->toBe('cm')
        ->and((float) ($roundTripped['amount'] ?? 0))->toBe(250.0)
        ->and((float) ($roundTripped['base_data'] ?? 0))->toBe(2.5);
});

it('escapes a formula-operator amount on export and the import unescapes it back', function () {
    [$family, $measurement] = verifyImpExpSetup();

    $imported = verifyImpExpImport($family, [
        $measurement->code          => '-5',
        $measurement->code.'(unit)' => 'Meter',
    ]);

    $stored = $imported['common'][$measurement->code];

    $columns = verifyImpExpExportColumns(
        [$measurement],
        [$measurement->code => $stored]
    );

    $exportedAmount = $columns[$measurement->code];

    expect($exportedAmount)->toStartWith("'")
        ->and((float) EscapeFormulaOperators::unescapeValue($exportedAmount))->toBe(-5.0);

    $reimported = verifyImpExpImport($family, [
        $measurement->code          => $exportedAmount,
        $measurement->code.'(unit)' => 'Meter',
    ]);

    $roundTripped = $reimported['common'][$measurement->code] ?? null;

    expect($roundTripped)->toBeArray()
        ->and((float) ($roundTripped['amount'] ?? 0))->toBe(-5.0);
});

it('unwraps a channel/locale scoped measurement value when exporting', function () {
    [, $measurement] = verifyImpExpSetup();

    $columns = verifyImpExpExportColumns(
        [$measurement],
        [
            $measurement->code => [
                '<all_channels>' => [
                    '<all_locales>' => ['amount' => '42', 'unit' => 'meter'],
                ],
            ],
        ]
    );

    expect($columns[$measurement->code])->toBe('42')
        ->and($columns[$measurement->code.'(unit)'])->toBe('Meter');
});

it('escapes a unit label that begins with a formula operator on export', function () {
    [, $measurement] = verifyImpExpSetup([
        [
            'code'                  => 'meter',
            'symbol'                => 'm',
            'labels'                => ['en_US' => '=Meter'],
            'convert_from_standard' => [['value' => '1', 'operator' => 'mul']],
        ],
    ]);

    $columns = verifyImpExpExportColumns(
        [$measurement],
        [$measurement->code => ['amount' => '3', 'unit' => 'meter']]
    );

    expect($columns[$measurement->code.'(unit)'])->toBe("'=Meter'");
});
