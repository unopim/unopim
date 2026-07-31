<?php

use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
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
use Webkul\Measurement\Helpers\Exporters\ProductExporter;
use Webkul\Measurement\Helpers\MeasurementHelper;
use Webkul\Measurement\Models\AttributeMeasurement;
use Webkul\Measurement\Models\MeasurementFamily;
use Webkul\Product\Models\Product;

beforeEach(function () {
    $this->loginAsAdmin();

    $cache = new ReflectionProperty(Exporter::class, 'staticInitCache');
    $cache->setAccessible(true);
    $cache->setValue(null, null);
});

function measurementLengthFamily(): MeasurementFamily
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

function measurementLengthAttribute(MeasurementFamily $measurementFamily, bool $perChannel = false, bool $perLocale = false): Attribute
{
    $attribute = Attribute::factory()->create([
        'code'              => 'length_'.uniqid(),
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

function measurementProductFamily(Attribute $measurement): AttributeFamily
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

function measurementExtraChannel(string $localeCode = 'en_US'): Channel
{
    $locale = Locale::firstWhere('code', $localeCode);

    $channel = Channel::factory()->create(['code' => 'cm_channel_'.uniqid()]);

    $channel->locales()->sync([$locale->id]);

    return $channel->fresh();
}

function measurementExportJobTrack(array $filters): JobTrack
{
    $jobInstance = JobInstances::create([
        'code'                => 'measurement_conv_export_'.uniqid(),
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

function measurementExportRows(Product $product, array $filters): array
{
    $jobTrack = measurementExportJobTrack($filters);

    $exporter = app(Exporter::class);
    $exporter->setExport($jobTrack);
    $exporter->initilize();

    $buffer = JSONFileBuffer::initialize($jobTrack);
    $exporter->setExportBuffer($buffer);

    $batch = new JobTrackBatch(['data' => [['id' => $product->id]]]);

    $exporter->prepareProducts($batch, null);

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

function measurementWriteExportFile(array $rows, string $format): string
{
    $buffer = app(FlatItemBuffer::class)->initialize(
        'exports/measurement-conversion-'.uniqid(),
        'products.'.strtolower($format),
        ['type' => $format, 'fieldDelimiter' => ',', 'fieldEnclosure' => '"', 'shouldAddBOM' => false]
    );

    $buffer->addData($rows);
    $buffer->writerClose();

    return $buffer->getFilePath()->getLocalPath();
}

function measurementReadExportFile(string $path, string $format): array
{
    $reader = strtolower($format) === 'csv' ? new CsvReader : new XlsxReader;

    $reader->open($path);

    $rows = [];

    foreach ($reader->getSheetIterator() as $sheet) {
        foreach ($sheet->getRowIterator() as $row) {
            $rows[] = array_map(
                fn ($cell): string => (string) $cell->getValue(),
                $row->getCells()
            );
        }

        break;
    }

    $reader->close();

    return $rows;
}

function measurementCellFor(array $fileRows, string $channelCode, string $columnHeader): ?string
{
    $header = $fileRows[0] ?? [];

    $channelIndex = array_search('channel', $header, true);
    $columnIndex = array_search($columnHeader, $header, true);

    if ($channelIndex === false || $columnIndex === false) {
        return null;
    }

    foreach (array_slice($fileRows, 1) as $row) {
        if (($row[$channelIndex] ?? null) === $channelCode) {
            return $row[$columnIndex] ?? null;
        }
    }

    return null;
}

describe('Issue #1196 - measurement conversion during product export', function () {
    beforeEach(function () {
        $this->measurementFamily = measurementLengthFamily();
        $this->defaultChannel = Channel::first();
        $this->extraChannel = measurementExtraChannel();
    });

    it('has no channel-level measurement unit configuration anywhere in the schema', function () {
        $channelColumns = Schema::getColumnListing((new Channel)->getTable());

        expect(implode(',', $channelColumns))->not->toContain('unit')
            ->and(implode(',', $channelColumns))->not->toContain('measurement');

        $attributeMeasurementColumns = Schema::getColumnListing((new AttributeMeasurement)->getTable());

        expect($attributeMeasurementColumns)->toContain('attribute_id')
            ->and($attributeMeasurementColumns)->toContain('family_code')
            ->and($attributeMeasurementColumns)->toContain('unit_code')
            ->and($attributeMeasurementColumns)->not->toContain('channel_id')
            ->and($attributeMeasurementColumns)->not->toContain('channel_code');
    });

    it('exposes no helper capable of converting a stored amount into an arbitrary target unit', function () {
        $helper = app(MeasurementHelper::class);

        $methods = get_class_methods($helper);

        expect($methods)->toContain('calculateBaseValue')
            ->and($methods)->not->toContain('convertToUnit')
            ->and($methods)->not->toContain('convertTo')
            ->and($methods)->not->toContain('convertForChannel');

        expect((float) $helper->calculateBaseValue('2.5', 'meter', $this->measurementFamily))->toBe(2.5)
            ->and((float) $helper->calculateBaseValue('250', 'centimeter', $this->measurementFamily))->toBe(2.5);
    });

    it('writes the identical stored amount and unit into every channel row for a common-scoped measurement value', function () {
        $measurement = measurementLengthAttribute($this->measurementFamily);
        $family = measurementProductFamily($measurement);

        $sku = 'MEAS-COMMON-'.uniqid();

        $product = Product::create([
            'sku'                 => $sku,
            'type'                => 'simple',
            'status'              => 1,
            'attribute_family_id' => $family->id,
        ]);

        $product->values = [
            'common' => [
                'sku'                => $sku,
                $measurement->code   => [
                    'unit'      => 'meter',
                    'amount'    => '2.5000',
                    'family'    => $this->measurementFamily->code,
                    'base_data' => '2.500000',
                    'base_unit' => 'meter',
                    'symbol'    => 'm',
                ],
            ],
        ];

        $product->save();

        $rows = measurementExportRows($product, [
            'channels' => [$this->defaultChannel->code, $this->extraChannel->code],
            'locales'  => ['en_US'],
        ]);

        expect($rows)->toHaveCount(2);

        $byChannel = collect($rows)->keyBy('channel');

        expect($byChannel)->toHaveKey($this->defaultChannel->code)
            ->and($byChannel)->toHaveKey($this->extraChannel->code);

        $defaultRow = $byChannel[$this->defaultChannel->code];
        $extraRow = $byChannel[$this->extraChannel->code];

        expect($defaultRow[$measurement->code])->toBe('2.5000')
            ->and($defaultRow[$measurement->code.'(unit)'])->toBe('Meter')
            ->and($extraRow[$measurement->code])->toBe('2.5000')
            ->and($extraRow[$measurement->code.'(unit)'])->toBe('Meter');
    });

    it('produces the same unconverted amount in both the CSV and the XLSX file for every channel', function () {
        $measurement = measurementLengthAttribute($this->measurementFamily);
        $family = measurementProductFamily($measurement);

        $sku = 'MEAS-FILE-'.uniqid();

        $product = Product::create([
            'sku'                 => $sku,
            'type'                => 'simple',
            'status'              => 1,
            'attribute_family_id' => $family->id,
        ]);

        $product->values = [
            'common' => [
                'sku'                => $sku,
                $measurement->code   => [
                    'unit'      => 'meter',
                    'amount'    => '2.5000',
                    'family'    => $this->measurementFamily->code,
                    'base_data' => '2.500000',
                    'base_unit' => 'meter',
                    'symbol'    => 'm',
                ],
            ],
        ];

        $product->save();

        $rows = measurementExportRows($product, [
            'channels' => [$this->defaultChannel->code, $this->extraChannel->code],
            'locales'  => ['en_US'],
        ]);

        foreach (['Csv', 'Xlsx'] as $format) {
            $path = measurementWriteExportFile($rows, $format);

            expect(file_exists($path))->toBeTrue();

            $fileRows = measurementReadExportFile($path, $format);

            expect(measurementCellFor($fileRows, $this->defaultChannel->code, $measurement->code))->toBe('2.5000')
                ->and(measurementCellFor($fileRows, $this->defaultChannel->code, $measurement->code.'(unit)'))->toBe('Meter')
                ->and(measurementCellFor($fileRows, $this->extraChannel->code, $measurement->code))->toBe('2.5000')
                ->and(measurementCellFor($fileRows, $this->extraChannel->code, $measurement->code.'(unit)'))->toBe('Meter');

            @unlink($path);
        }
    });

    it('keeps the stored per-channel amount untouched when the second channel holds a centimeter value', function () {
        $measurement = measurementLengthAttribute($this->measurementFamily, perChannel: true);
        $family = measurementProductFamily($measurement);

        $sku = 'MEAS-CHANNEL-'.uniqid();

        $product = Product::create([
            'sku'                 => $sku,
            'type'                => 'simple',
            'status'              => 1,
            'attribute_family_id' => $family->id,
        ]);

        $product->values = [
            'common'           => ['sku' => $sku],
            'channel_specific' => [
                $this->defaultChannel->code => [
                    $measurement->code => [
                        'unit'      => 'meter',
                        'amount'    => '2.5000',
                        'family'    => $this->measurementFamily->code,
                        'base_data' => '2.500000',
                        'base_unit' => 'meter',
                        'symbol'    => 'm',
                    ],
                ],
                $this->extraChannel->code => [
                    $measurement->code => [
                        'unit'      => 'centimeter',
                        'amount'    => '250.0000',
                        'family'    => $this->measurementFamily->code,
                        'base_data' => '2.500000',
                        'base_unit' => 'meter',
                        'symbol'    => 'cm',
                    ],
                ],
            ],
        ];

        $product->save();

        $rows = measurementExportRows($product, [
            'channels' => [$this->defaultChannel->code, $this->extraChannel->code],
            'locales'  => ['en_US'],
        ]);

        $byChannel = collect($rows)->keyBy('channel');

        expect($byChannel[$this->defaultChannel->code][$measurement->code])->toBe('2.5000')
            ->and($byChannel[$this->defaultChannel->code][$measurement->code.'(unit)'])->toBe('Meter')
            ->and($byChannel[$this->extraChannel->code][$measurement->code])->toBe('250.0000')
            ->and($byChannel[$this->extraChannel->code][$measurement->code.'(unit)'])->toBe('Centimeter');
    });

    it('leaves the second channel column empty when a channel-scoped value exists only for the first channel', function () {
        $measurement = measurementLengthAttribute($this->measurementFamily, perChannel: true);
        $family = measurementProductFamily($measurement);

        $sku = 'MEAS-PARTIAL-'.uniqid();

        $product = Product::create([
            'sku'                 => $sku,
            'type'                => 'simple',
            'status'              => 1,
            'attribute_family_id' => $family->id,
        ]);

        $product->values = [
            'common'           => ['sku' => $sku],
            'channel_specific' => [
                $this->defaultChannel->code => [
                    $measurement->code => [
                        'unit'      => 'meter',
                        'amount'    => '2.5000',
                        'family'    => $this->measurementFamily->code,
                        'base_data' => '2.500000',
                        'base_unit' => 'meter',
                        'symbol'    => 'm',
                    ],
                ],
            ],
        ];

        $product->save();

        $rows = measurementExportRows($product, [
            'channels' => [$this->defaultChannel->code, $this->extraChannel->code],
            'locales'  => ['en_US'],
        ]);

        $byChannel = collect($rows)->keyBy('channel');

        expect($byChannel[$this->defaultChannel->code][$measurement->code])->toBe('2.5000')
            ->and($byChannel[$this->extraChannel->code][$measurement->code])->toBeNull()
            ->and($byChannel[$this->extraChannel->code][$measurement->code.'(unit)'])->toBeNull();
    });

    it('translates only the unit label per locale and never rescales the amount', function () {
        $measurement = measurementLengthAttribute($this->measurementFamily);
        $family = measurementProductFamily($measurement);

        $frenchLocale = Locale::firstWhere('code', 'fr_FR');

        if (! $frenchLocale) {
            $this->markTestSkipped('fr_FR locale is not installed.');
        }

        $frenchLocale->update(['status' => 1]);

        $this->extraChannel->locales()->syncWithoutDetaching([$frenchLocale->id]);

        $sku = 'MEAS-LOCALE-'.uniqid();

        $product = Product::create([
            'sku'                 => $sku,
            'type'                => 'simple',
            'status'              => 1,
            'attribute_family_id' => $family->id,
        ]);

        $product->values = [
            'common' => [
                'sku'                => $sku,
                $measurement->code   => [
                    'unit'      => 'meter',
                    'amount'    => '2.5000',
                    'family'    => $this->measurementFamily->code,
                    'base_data' => '2.500000',
                    'base_unit' => 'meter',
                    'symbol'    => 'm',
                ],
            ],
        ];

        $product->save();

        $rows = measurementExportRows($product, [
            'channels' => [$this->extraChannel->code],
            'locales'  => ['en_US', 'fr_FR'],
        ]);

        $byLocale = collect($rows)->keyBy('locale');

        expect($byLocale)->toHaveKey('en_US')
            ->and($byLocale)->toHaveKey('fr_FR')
            ->and($byLocale['en_US'][$measurement->code])->toBe('2.5000')
            ->and($byLocale['en_US'][$measurement->code.'(unit)'])->toBe('Meter')
            ->and($byLocale['fr_FR'][$measurement->code])->toBe('2.5000')
            ->and($byLocale['fr_FR'][$measurement->code.'(unit)'])->toBe('Metre');
    });

    it('resolves the core product exporter to the measurement exporter which reads amount and unit verbatim', function () {
        expect(app(Exporter::class))->toBeInstanceOf(ProductExporter::class);

        $exporter = app(Exporter::class);

        $method = new ReflectionMethod($exporter, 'extractMeasurement');
        $method->setAccessible(true);

        expect($method->invoke($exporter, ['amount' => '2.5000', 'unit' => 'meter']))
            ->toBe(['2.5000', 'meter']);
    });
});
