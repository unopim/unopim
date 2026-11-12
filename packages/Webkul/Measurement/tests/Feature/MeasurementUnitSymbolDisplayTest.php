<?php

use Illuminate\Support\Facades\Cache;
use Webkul\Admin\Traits\AttributeColumnTrait;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Core\Models\CoreConfig;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter as CoreExporter;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer as CoreImporter;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;
use Webkul\Measurement\Services\Normalizers\MeasurementNormalizer;
use Webkul\Product\Enums\ProductType;
use Webkul\Product\Normalizer\ProductAttributeValuesNormalizer;

beforeEach(function () {
    $this->loginAsAdmin();

    Cache::flush();

    foreach ([CoreImporter::class, CoreExporter::class] as $class) {
        $cache = new ReflectionProperty($class, 'staticInitCache');
        $cache->setValue(null, null);
    }
});

function enableUnitSymbolDisplay(): void
{
    CoreConfig::updateOrCreate(
        ['code' => 'system.measurement.display_unit_symbol'],
        ['value' => '1']
    );

    Cache::flush();
}

function temperatureAttribute(): Attribute
{
    $suffix = uniqid();

    $family = MeasurementFamily::factory()->create([
        'standard_unit' => 'celsius',
        'units'         => [
            ['code' => 'celsius', 'labels' => ['en_US' => 'Degree Celsius'], 'symbol' => '°C', 'convert_from_standard' => [['operator' => 'mul', 'value' => '1']]],
            ['code' => 'fahrenheit', 'labels' => ['en_US' => 'Degree Fahrenheit'], 'symbol' => '°F', 'convert_from_standard' => [['operator' => 'mul', 'value' => '1.8'], ['operator' => 'add', 'value' => '32']]],
            ['code' => 'kelvin', 'labels' => ['en_US' => 'Kelvin'], 'symbol' => '', 'convert_from_standard' => [['operator' => 'add', 'value' => '273.15']]],
        ],
    ]);

    $attribute = Attribute::factory()->create([
        'code' => 'temperature_'.$suffix,
        'type' => 'measurement',
    ]);

    AttributeMeasurement::create([
        'attribute_id' => $attribute->id,
        'family_code'  => $family->code,
        'unit_code'    => 'celsius',
    ]);

    return $attribute;
}

function exportTemperatureUnitColumn(Attribute $attribute, string $unit): string
{
    $exporter = app(CoreExporter::class);
    $reflection = new ReflectionClass($exporter);
    $collection = collect([$attribute]);

    $reflection->getProperty('attributes')->setValue($exporter, $collection);
    $reflection->getProperty('attributeMeta')->setValue(
        $exporter,
        $reflection->getMethod('buildAttributeMeta')->invoke($exporter, $collection)
    );
    $reflection->getProperty('currencies')->setValue($exporter, ['USD']);

    $columns = $reflection->getMethod('setAttributesValues')->invoke(
        $exporter,
        [$attribute->code => ['amount' => '100', 'unit' => $unit]],
        null,
        'en_US'
    );

    return $columns[$attribute->code.'(unit)'];
}

it('registers the display unit symbol setting switched off by default', function () {
    $fields = collect(config('system_settings'))->firstWhere('key', 'system.measurement')['fields'];

    $field = collect($fields)->firstWhere('name', 'display_unit_symbol');

    expect($field['type'])->toBe('boolean')
        ->and($field['default_value'])->toBe('0')
        ->and(app(MeasurementHelper::class)->displaysUnitSymbol())->toBeFalse();
});

it('shows the unit name while the setting is off', function () {
    $attribute = temperatureAttribute();

    expect(app(MeasurementHelper::class)->getUnitDisplayLabel('celsius', $attribute, 'en_US'))->toBe('Degree Celsius');
});

it('shows the unit symbol while the setting is on', function () {
    enableUnitSymbolDisplay();

    $attribute = temperatureAttribute();

    expect(app(MeasurementHelper::class)->getUnitDisplayLabel('celsius', $attribute, 'en_US'))->toBe('°C');
});

it('falls back to the unit name when the setting is on but the unit has no symbol', function () {
    enableUnitSymbolDisplay();

    $attribute = temperatureAttribute();

    expect(app(MeasurementHelper::class)->getUnitDisplayLabel('kelvin', $attribute, 'en_US'))->toBe('Kelvin');
});

it('labels the product unit dropdown with the unit name while the setting is off', function () {
    $attribute = temperatureAttribute();

    $this->getJson(route('admin.measurement.attribute.units', ['attribute_id' => $attribute->id]))
        ->assertOk()
        ->assertJsonFragment(['id' => 'celsius', 'label' => 'Degree Celsius']);
});

it('labels the product unit dropdown with symbols while the setting is on', function () {
    enableUnitSymbolDisplay();

    $attribute = temperatureAttribute();

    $this->getJson(route('admin.measurement.attribute.units', ['attribute_id' => $attribute->id]))
        ->assertOk()
        ->assertJsonFragment(['id' => 'celsius', 'label' => '°C'])
        ->assertJsonFragment(['id' => 'fahrenheit', 'label' => '°F'])
        ->assertJsonFragment(['id' => 'kelvin', 'label' => 'Kelvin']);
});

it('renders the datagrid value with the unit name while the setting is off', function () {
    $attribute = temperatureAttribute();

    $displayed = app(MeasurementNormalizer::class)->getData(
        ['amount' => '100', 'unit' => 'celsius'],
        $attribute,
        ['format' => 'datagrid', 'locale' => 'en_US']
    );

    expect($displayed)->toBe('100 Degree Celsius');
});

it('renders the datagrid value with the unit symbol while the setting is on', function () {
    enableUnitSymbolDisplay();

    $attribute = temperatureAttribute();

    $displayed = app(MeasurementNormalizer::class)->getData(
        ['amount' => '100', 'unit' => 'celsius'],
        $attribute,
        ['format' => 'datagrid', 'locale' => 'en_US']
    );

    expect($displayed)->toBe('100 °C');
});

it('labels the product datagrid unit filter with symbols while the setting is on', function () {
    enableUnitSymbolDisplay();

    $attribute = temperatureAttribute();

    $grid = new class
    {
        use AttributeColumnTrait;

        public function unitOptions(Attribute $attribute): array
        {
            return $this->getMeasurementUnitOptions($attribute);
        }
    };

    expect($grid->unitOptions($attribute))->toContain(
        ['label' => '°C', 'value' => 'celsius'],
        ['label' => 'Kelvin', 'value' => 'kelvin'],
    );
});

it('exports the unit column with the unit name while the setting is off', function () {
    $attribute = temperatureAttribute();

    expect(exportTemperatureUnitColumn($attribute, 'celsius'))->toBe('Degree Celsius');
});

it('exports the unit column with the unit symbol while the setting is on', function () {
    enableUnitSymbolDisplay();

    $attribute = temperatureAttribute();

    expect(exportTemperatureUnitColumn($attribute, 'fahrenheit'))->toBe('°F')
        ->and(exportTemperatureUnitColumn($attribute, 'kelvin'))->toBe('Kelvin');
});

it('exports the datagrid quick export unit column with the unit symbol while the setting is on', function () {
    enableUnitSymbolDisplay();

    $attribute = temperatureAttribute();

    $values = app(ProductAttributeValuesNormalizer::class)->normalizeAttributes(
        [$attribute->code => ['amount' => '100', 'unit' => 'celsius']],
        ['forExport' => 'true', 'locale' => 'en_US']
    );

    expect($values[$attribute->code.'(unit)'])->toBe('°C');
});

it('re-imports a symbol export to the original unit code', function () {
    enableUnitSymbolDisplay();

    $attribute = temperatureAttribute();

    $family = AttributeFamily::factory()->create();
    AttributeFamily::factory()->linkAttributeGroupToFamily($family);
    $family->refresh();
    AttributeFamily::factory()->linkAttributesToFamily($family, Attribute::whereIn('code', ['sku', 'status'])->get());
    AttributeFamily::factory()->linkAttributesToFamily($family, collect([$attribute]));

    $importer = app(CoreImporter::class)->setImport(JobTrack::factory()->create());

    foreach (['celsius' => '°C', 'fahrenheit' => '°F', 'kelvin' => 'Kelvin'] as $code => $expectedColumn) {
        $unitColumn = exportTemperatureUnitColumn($attribute, $code);

        expect($unitColumn)->toBe($expectedColumn);

        $rowData = [
            'sku'                     => 'temperature-'.uniqid(),
            'type'                    => ProductType::Simple->value,
            'attribute_family'        => $family->code,
            $attribute->code          => '100',
            $attribute->code.'(unit)' => $unitColumn,
        ];

        $attributeValues = [];

        $importer->prepareAttributeValues($rowData, $attributeValues);

        expect($attributeValues['common'][$attribute->code]['unit'] ?? null)->toBe($code);
    }
});

it('resolves a symbol that differs only in case to its exact unit', function (string $value, string $code) {
    $family = MeasurementFamily::factory()->create([
        'standard_unit' => 'WATT',
        'units'         => [
            ['code' => 'MILLIWATT', 'labels' => ['en_US' => 'Milliwatt'], 'symbol' => 'mW', 'convert_from_standard' => [['operator' => 'mul', 'value' => '1000']]],
            ['code' => 'WATT', 'labels' => ['en_US' => 'Watt'], 'symbol' => 'W', 'convert_from_standard' => [['operator' => 'mul', 'value' => '1']]],
            ['code' => 'MEGAWATT', 'labels' => ['en_US' => 'Megawatt'], 'symbol' => 'MW', 'convert_from_standard' => [['operator' => 'div', 'value' => '1000000']]],
            ['code' => 'BIT', 'labels' => ['en_US' => 'Bit'], 'symbol' => 'b', 'convert_from_standard' => [['operator' => 'mul', 'value' => '8']]],
            ['code' => 'BYTE', 'labels' => ['en_US' => 'Byte'], 'symbol' => 'B', 'convert_from_standard' => [['operator' => 'mul', 'value' => '1']]],
        ],
    ]);

    $attribute = Attribute::factory()->create(['code' => 'power_'.uniqid(), 'type' => 'measurement']);

    AttributeMeasurement::create([
        'attribute_id' => $attribute->id,
        'family_code'  => $family->code,
        'unit_code'    => 'WATT',
    ]);

    expect(app(MeasurementHelper::class)->resolveUnitCode($value, $attribute))->toBe($code);
})->with([
    'megawatt symbol'       => ['MW', 'MEGAWATT'],
    'milliwatt symbol'      => ['mW', 'MILLIWATT'],
    'byte symbol'           => ['B', 'BYTE'],
    'bit symbol'            => ['b', 'BIT'],
    'label in another case' => ['megawatt', 'MEGAWATT'],
    'code in another case'  => ['byte', 'BYTE'],
]);
