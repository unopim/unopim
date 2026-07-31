<?php

use OpenSpout\Reader\CSV\Reader as CsvReader;
use Webkul\Attribute\Models\Attribute;
use Webkul\Attribute\Models\AttributeFamily;
use Webkul\Core\Models\Channel;
use Webkul\Core\Models\Locale;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer;
use Webkul\DataTransfer\Jobs\Export\File\JSONFileBuffer;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;
use Webkul\DataTransfer\Models\JobTrackBatch;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;
use Webkul\Measurement\Tests\Support\ChannelScopeMeasurementSpyExporter;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();

    $cache = new ReflectionProperty(Exporter::class, 'staticInitCache');
    $cache->setAccessible(true);
    $cache->setValue(null, null);

    $this->measurementFamily = channelScopeLengthFamily();
    $this->channelA = Channel::first();
    $this->channelB = channelScopeExtraChannel();
});

function channelScopeLengthFamily(): MeasurementFamily
{
    return MeasurementFamily::factory()->create([
        'standard_unit' => 'meter',
        'symbol'        => 'm',
        'labels'        => ['en_US' => 'Length'],
        'units'         => [
            [
                'code'                  => 'meter',
                'symbol'                => 'm',
                'labels'                => ['en_US' => 'Meter', 'fr_FR' => 'Metre'],
                'convert_from_standard' => [['value' => '1', 'operator' => 'mul']],
            ],
            [
                'code'                  => 'centimeter',
                'symbol'                => 'cm',
                'labels'                => ['en_US' => 'Centimeter', 'fr_FR' => 'Centimetre'],
                'convert_from_standard' => [['value' => '100', 'operator' => 'mul']],
            ],
        ],
    ]);
}

function channelScopeLengthAttribute(MeasurementFamily $measurementFamily, bool $perChannel = false, bool $perLocale = false): Attribute
{
    $attribute = Attribute::factory()->create([
        'code'              => 'clen_'.uniqid(),
        'type'              => 'measurement',
        'value_per_channel' => $perChannel,
        'value_per_locale'  => $perLocale,
    ]);

    AttributeMeasurement::create([
        'attribute_id' => $attribute->id,
        'family_code'  => $measurementFamily->code,
        'unit_code'    => 'meter',
    ]);

    return $attribute;
}

function channelScopeProductFamily(Attribute $measurement): AttributeFamily
{
    $family = AttributeFamily::factory()->create();

    AttributeFamily::factory()->linkAttributeGroupToFamily($family);

    $family->refresh();

    AttributeFamily::factory()->linkAttributesToFamily(
        $family,
        Attribute::whereIn('code', ['sku', 'status'])->get()
    );

    AttributeFamily::factory()->linkAttributesToFamily($family, collect([$measurement]));

    return $family->fresh();
}

function channelScopeExtraChannel(string $localeCode = 'en_US'): Channel
{
    $locale = Locale::firstWhere('code', $localeCode);

    $channel = Channel::factory()->create(['code' => 'cs_channel_'.uniqid()]);

    $channel->locales()->sync([$locale->id]);

    return $channel->fresh();
}

function channelScopeMeasurementValue(string $unit, string $amount, string $baseData, string $symbol, MeasurementFamily $family): array
{
    return [
        'unit'      => $unit,
        'amount'    => $amount,
        'family'    => $family->code,
        'base_data' => $baseData,
        'base_unit' => 'meter',
        'symbol'    => $symbol,
    ];
}

function channelScopeProduct(AttributeFamily $family, string $prefix, array $values): Product
{
    $sku = $prefix.'-'.uniqid();

    $product = Product::create([
        'sku'                 => $sku,
        'type'                => 'simple',
        'status'              => 1,
        'attribute_family_id' => $family->id,
    ]);

    $values['common'] = array_merge(['sku' => $sku], $values['common'] ?? []);

    $product->values = $values;

    $product->save();

    return $product->fresh();
}

function channelScopeJobTrack(array $filters): JobTrack
{
    $jobInstance = JobInstances::create([
        'code'                => 'channel_scope_export_'.uniqid(),
        'entity_type'         => 'products',
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'filters'             => array_merge(['file_format' => 'Csv'], $filters),
    ]);

    return JobTrack::create([
        'state'               => Export::STATE_PROCESSING,
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'job_instances_id'    => $jobInstance->id,
        'meta'                => $jobInstance->toArray(),
    ]);
}

function channelScopeExportRows(Product $product, array $filters, ?Exporter $exporter = null): array
{
    $jobTrack = channelScopeJobTrack($filters);

    $exporter ??= app(Exporter::class);
    $exporter->setExport($jobTrack);
    $exporter->initilize();

    $buffer = JSONFileBuffer::initialize($jobTrack);
    $exporter->setExportBuffer($buffer);

    $exporter->prepareProducts(new JobTrackBatch(['data' => [['id' => $product->id]]]), null);

    $rows = [];

    $buffer->rewind();

    while ($buffer->valid()) {
        $written = $buffer->current();

        if (! empty($written[0])) {
            $rows[] = $written[0];
        }

        $buffer->next();
    }

    return $rows;
}

function channelScopeWriteCsv(array $rows): string
{
    $buffer = app(FlatItemBuffer::class)->initialize(
        'exports/channel-scoped-measurement-'.uniqid(),
        'products.csv',
        ['type' => 'Csv', 'fieldDelimiter' => ',', 'fieldEnclosure' => '"', 'shouldAddBOM' => false]
    );

    $buffer->addData($rows);
    $buffer->writerClose();

    return $buffer->getFilePath()->getLocalPath();
}

function channelScopeReadCsv(string $path): array
{
    $reader = new CsvReader;

    $reader->open($path);

    $rows = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = array_map(fn ($cell): string => (string) $cell->getValue(), $row->getCells());
        }

        break;
    }

    $reader->close();

    return $rows;
}

function channelScopeCell(array $fileRows, string $channelCode, string $localeCode, string $columnHeader): ?string
{
    $header = $fileRows[0] ?? [];

    $channelIndex = array_search('channel', $header, true);
    $localeIndex = array_search('locale', $header, true);
    $columnIndex = array_search($columnHeader, $header, true);

    if ($channelIndex === false || $localeIndex === false || $columnIndex === false) {
        return null;
    }

    foreach (array_slice($fileRows, 1) as $row) {
        if (($row[$channelIndex] ?? null) === $channelCode && ($row[$localeIndex] ?? null) === $localeCode) {
            return $row[$columnIndex] ?? null;
        }
    }

    return null;
}

function channelScopeExportedCells(Product $product, Attribute $measurement, array $channels, array $locales = ['en_US']): array
{
    $rows = channelScopeExportRows($product, [
        'channels' => $channels,
        'locales'  => $locales,
    ]);

    $path = channelScopeWriteCsv($rows);

    $fileRows = channelScopeReadCsv($path);

    $cells = [];

    foreach ($channels as $channel) {
        foreach ($locales as $locale) {
            $cells[$channel][$locale] = [
                'amount' => channelScopeCell($fileRows, $channel, $locale, $measurement->code),
                'unit'   => channelScopeCell($fileRows, $channel, $locale, $measurement->code.'(unit)'),
            ];
        }
    }

    @unlink($path);

    return $cells;
}

describe('Issue #1196 - channel scoped measurement values during product export', function () {
    it('A1: writes each channel its own stored amount and unit for a channel scoped attribute', function () {
        $measurement = channelScopeLengthAttribute($this->measurementFamily, perChannel: true);
        $family = channelScopeProductFamily($measurement);

        $product = channelScopeProduct($family, 'MEAS-A1', [
            'channel_specific' => [
                $this->channelA->code => [
                    $measurement->code => channelScopeMeasurementValue('meter', '2.5000', '2.500000', 'm', $this->measurementFamily),
                ],
                $this->channelB->code => [
                    $measurement->code => channelScopeMeasurementValue('centimeter', '250.0000', '2.500000', 'cm', $this->measurementFamily),
                ],
            ],
        ]);

        $cells = channelScopeExportedCells($product, $measurement, [$this->channelA->code, $this->channelB->code]);

        expect($cells[$this->channelA->code]['en_US']['amount'])->toBe('2.5000')
            ->and($cells[$this->channelA->code]['en_US']['unit'])->toBe('Meter')
            ->and($cells[$this->channelB->code]['en_US']['amount'])->toBe('250.0000')
            ->and($cells[$this->channelB->code]['en_US']['unit'])->toBe('Centimeter');
    });

    it('A2: writes empty amount and unit cells for a channel holding no value', function () {
        $measurement = channelScopeLengthAttribute($this->measurementFamily, perChannel: true);
        $family = channelScopeProductFamily($measurement);

        $product = channelScopeProduct($family, 'MEAS-A2', [
            'channel_specific' => [
                $this->channelA->code => [
                    $measurement->code => channelScopeMeasurementValue('meter', '2.5000', '2.500000', 'm', $this->measurementFamily),
                ],
            ],
        ]);

        $cells = channelScopeExportedCells($product, $measurement, [$this->channelA->code, $this->channelB->code]);

        expect($cells[$this->channelA->code]['en_US']['amount'])->toBe('2.5000')
            ->and($cells[$this->channelA->code]['en_US']['unit'])->toBe('Meter')
            ->and($cells[$this->channelB->code]['en_US']['amount'])->toBe('')
            ->and($cells[$this->channelB->code]['en_US']['unit'])->toBe('');
    });

    it('B1: writes each channel its own stored amount and unit for a channel and locale scoped attribute', function () {
        $measurement = channelScopeLengthAttribute($this->measurementFamily, perChannel: true, perLocale: true);
        $family = channelScopeProductFamily($measurement);

        $product = channelScopeProduct($family, 'MEAS-B1', [
            'channel_locale_specific' => [
                $this->channelA->code => [
                    'en_US' => [
                        $measurement->code => channelScopeMeasurementValue('meter', '2.5000', '2.500000', 'm', $this->measurementFamily),
                    ],
                ],
                $this->channelB->code => [
                    'en_US' => [
                        $measurement->code => channelScopeMeasurementValue('centimeter', '250.0000', '2.500000', 'cm', $this->measurementFamily),
                    ],
                ],
            ],
        ]);

        $cells = channelScopeExportedCells($product, $measurement, [$this->channelA->code, $this->channelB->code]);

        expect($cells[$this->channelA->code]['en_US']['amount'])->toBe('2.5000')
            ->and($cells[$this->channelA->code]['en_US']['unit'])->toBe('Meter')
            ->and($cells[$this->channelB->code]['en_US']['amount'])->toBe('250.0000')
            ->and($cells[$this->channelB->code]['en_US']['unit'])->toBe('Centimeter');
    });

    it('B2: writes empty cells for a channel and locale pair holding no value', function () {
        $measurement = channelScopeLengthAttribute($this->measurementFamily, perChannel: true, perLocale: true);
        $family = channelScopeProductFamily($measurement);

        $product = channelScopeProduct($family, 'MEAS-B2', [
            'channel_locale_specific' => [
                $this->channelA->code => [
                    'en_US' => [
                        $measurement->code => channelScopeMeasurementValue('meter', '2.5000', '2.500000', 'm', $this->measurementFamily),
                    ],
                ],
            ],
        ]);

        $cells = channelScopeExportedCells($product, $measurement, [$this->channelA->code, $this->channelB->code]);

        expect($cells[$this->channelA->code]['en_US']['amount'])->toBe('2.5000')
            ->and($cells[$this->channelA->code]['en_US']['unit'])->toBe('Meter')
            ->and($cells[$this->channelB->code]['en_US']['amount'])->toBe('')
            ->and($cells[$this->channelB->code]['en_US']['unit'])->toBe('');
    });

    it('C: repeats the single common value in every channel row for a common scoped attribute', function () {
        $measurement = channelScopeLengthAttribute($this->measurementFamily);
        $family = channelScopeProductFamily($measurement);

        $product = channelScopeProduct($family, 'MEAS-C', [
            'common' => [
                $measurement->code => channelScopeMeasurementValue('centimeter', '250.0000', '2.500000', 'cm', $this->measurementFamily),
            ],
        ]);

        $cells = channelScopeExportedCells($product, $measurement, [$this->channelA->code, $this->channelB->code]);

        expect($cells[$this->channelA->code]['en_US']['amount'])->toBe('250.0000')
            ->and($cells[$this->channelA->code]['en_US']['unit'])->toBe('Centimeter')
            ->and($cells[$this->channelB->code]['en_US']['amount'])->toBe('250.0000')
            ->and($cells[$this->channelB->code]['en_US']['unit'])->toBe('Centimeter');
    });

    it('D: exports the stored amount in the stored unit and never the base data or the attribute standard unit', function () {
        $measurement = channelScopeLengthAttribute($this->measurementFamily, perChannel: true);
        $family = channelScopeProductFamily($measurement);

        $product = channelScopeProduct($family, 'MEAS-D', [
            'channel_specific' => [
                $this->channelA->code => [
                    $measurement->code => channelScopeMeasurementValue('meter', '2.5000', '2.500000', 'm', $this->measurementFamily),
                ],
                $this->channelB->code => [
                    $measurement->code => channelScopeMeasurementValue('centimeter', '250.0000', '2.500000', 'cm', $this->measurementFamily),
                ],
            ],
        ]);

        $stored = $product->fresh()->values;

        expect($stored['channel_specific'][$this->channelB->code][$measurement->code]['amount'])->toBe('250.0000')
            ->and($stored['channel_specific'][$this->channelB->code][$measurement->code]['base_data'])->toBe('2.500000');

        $cells = channelScopeExportedCells($product, $measurement, [$this->channelA->code, $this->channelB->code]);

        expect($cells[$this->channelB->code]['en_US']['amount'])
            ->toBe($stored['channel_specific'][$this->channelB->code][$measurement->code]['amount'])
            ->and($cells[$this->channelB->code]['en_US']['amount'])->not->toBe('2.500000')
            ->and($cells[$this->channelB->code]['en_US']['amount'])->not->toBe('2.5000')
            ->and($cells[$this->channelB->code]['en_US']['unit'])->not->toBe('Meter');
    });

    it('shape: hands extractMeasurement an already scope resolved flat array with no all_channels wrapper', function () {
        $measurement = channelScopeLengthAttribute($this->measurementFamily, perChannel: true);
        $family = channelScopeProductFamily($measurement);

        $product = channelScopeProduct($family, 'MEAS-SHAPE', [
            'channel_specific' => [
                $this->channelA->code => [
                    $measurement->code => channelScopeMeasurementValue('meter', '2.5000', '2.500000', 'm', $this->measurementFamily),
                ],
                $this->channelB->code => [
                    $measurement->code => channelScopeMeasurementValue('centimeter', '250.0000', '2.500000', 'cm', $this->measurementFamily),
                ],
            ],
        ]);

        $spy = app()->make(ChannelScopeMeasurementSpyExporter::class);

        channelScopeExportRows($product, [
            'channels' => [$this->channelA->code, $this->channelB->code],
            'locales'  => ['en_US'],
        ], $spy);

        expect($spy->extractMeasurementInputs)->toHaveCount(2);

        foreach ($spy->extractMeasurementInputs as $input) {
            expect($input)->toBeArray()
                ->and($input)->not->toHaveKey('<all_channels>')
                ->and($input)->not->toHaveKey($this->channelA->code)
                ->and($input)->not->toHaveKey($this->channelB->code)
                ->and($input)->toHaveKeys(['unit', 'amount', 'family', 'base_data', 'base_unit', 'symbol']);
        }

        $byUnit = collect($spy->extractMeasurementInputs)->keyBy('unit');

        expect($byUnit->keys()->sort()->values()->all())->toBe(['centimeter', 'meter'])
            ->and($byUnit['meter']['amount'])->toBe('2.5000')
            ->and($byUnit['centimeter']['amount'])->toBe('250.0000');
    });

    it('shape: extractMeasurement returns nulls for a channel keyed array it is never actually given', function () {
        $exporter = app(Exporter::class);

        $method = new ReflectionMethod($exporter, 'extractMeasurement');
        $method->setAccessible(true);

        $channelKeyed = [
            $this->channelA->code => ['amount' => '2.5000', 'unit' => 'meter'],
        ];

        expect($method->invoke($exporter, $channelKeyed))->toBe([null, null])
            ->and($method->invoke($exporter, ['amount' => '2.5000', 'unit' => 'meter']))->toBe(['2.5000', 'meter'])
            ->and($method->invoke($exporter, ['<all_channels>' => ['<all_locales>' => ['amount' => '7', 'unit' => 'centimeter']]]))->toBe(['7', 'centimeter']);
    });

    it('exposes no per channel measurement unit configuration that an export could convert towards', function () {
        $channelColumns = Schema::getColumnListing((new Channel)->getTable());

        expect(implode(',', $channelColumns))->not->toContain('unit')
            ->and(implode(',', $channelColumns))->not->toContain('measurement');

        $attributeMeasurementColumns = Schema::getColumnListing((new AttributeMeasurement)->getTable());

        expect($attributeMeasurementColumns)->not->toContain('channel_id')
            ->and($attributeMeasurementColumns)->not->toContain('channel_code')
            ->and($attributeMeasurementColumns)->not->toContain('locale');
    });
});
