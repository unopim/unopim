<?php

use Webkul\Attribute\Repositories\AttributeOptionRepository;
use Webkul\DataTransfer\Contracts\JobTrack;
use Webkul\DataTransfer\Contracts\JobTrackBatch;
use Webkul\DataTransfer\Helpers\Exporters\AttributeOption\Exporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

/**
 * Build a JobTrack mock with the given file format.
 */
function makeOptionExportTrack(string $fileFormat): JobTrack
{
    $jobInstance = new stdClass;
    $jobInstance->code = 'attribute-option-export';
    $jobInstance->entity_type = 'attribute-options';
    $jobInstance->filters = ['file_format' => $fileFormat];

    $exportTrack = Mockery::mock(JobTrack::class);
    $exportTrack->id = 1;
    $exportTrack->jobInstance = $jobInstance;
    $exportTrack->shouldReceive('getAttribute')
        ->with('jobInstance')
        ->andReturn($jobInstance);

    return $exportTrack;
}

/**
 * Minimal but complete attribute option row with two locale translations.
 */
function optionRow(array $overrides = []): array
{
    return array_merge([
        'attribute'    => ['code' => 'color'],
        'code'         => 'red',
        'sort_order'   => 1,
        'swatch_value' => null,
        'translations' => [
            ['locale' => 'en_US', 'label' => 'Red'],
            ['locale' => 'fr_FR', 'label' => 'Rouge'],
        ],
    ], $overrides);
}

/**
 * Build a batch mock containing the given rows.
 */
function makeOptionBatch(array $rows): JobTrackBatch
{
    $batch = Mockery::mock(JobTrackBatch::class);
    $batch->data = $rows;

    return $batch;
}

/**
 * Read a protected/private property from any object via Reflection.
 */
function getOptionProtectedProperty(object $object, string $property): mixed
{
    $ref = new ReflectionProperty($object, $property);
    $ref->setAccessible(true);

    return $ref->getValue($object);
}

beforeEach(function () {
    $this->exportBatchRepository = Mockery::mock(JobTrackBatchRepository::class);
    $this->exportFileBuffer = Mockery::mock(FileExportFileBuffer::class);
    $this->attributeOptionRepository = Mockery::mock(AttributeOptionRepository::class);

    $this->exporter = new Exporter(
        $this->exportBatchRepository,
        $this->exportFileBuffer,
        $this->attributeOptionRepository,
    );
});

afterEach(fn () => Mockery::close());

$sharedOptionAssertions = function (string $fileFormat, Exporter $exporter): void {
    $exporter->setExport(makeOptionExportTrack($fileFormat));

    $locales = core()->getAllActiveLocales()->pluck('code');
    $batch = makeOptionBatch([optionRow()]);
    $result = $exporter->prepareAttributeOptions($batch, "dummy/path/attribute-options.{$fileFormat}");

    expect($result)
        ->toBeArray()
        ->toHaveCount(count($locales));

    $enRow = collect($result)->firstWhere('locale', 'en_US');

    expect($enRow)->not->toBeNull()
        ->and($enRow['attribute_code'])->toBe('color')
        ->and($enRow['code'])->toBe('red')
        ->and($enRow['label'])->toBe('Red')
        ->and($enRow['sort_order'])->toBe(1)
        ->and($enRow['swatch_value'])->toBeNull()
        ->and($enRow['locale'])->toBe('en_US');

    if ($locales->contains('fr_FR')) {
        $frRow = collect($result)->firstWhere('locale', 'fr_FR');

        expect($frRow)->not->toBeNull()
            ->and($frRow['label'])->toBe('Rouge');
    }

    foreach ($result as $row) {
        expect($row)->toHaveKeys(['attribute_code', 'code', 'locale', 'label', 'sort_order', 'swatch_value']);
    }
};

describe('initilize', function () {
    it('calls initialize on the file buffer for CSV', function () {
        $this->exporter->setExport(makeOptionExportTrack('Csv'));
        $this->exportFileBuffer->shouldReceive('initialize')->once();

        $this->exporter->initilize();
    });

    it('calls initialize on the file buffer for XLS', function () {
        $this->exporter->setExport(makeOptionExportTrack('Xls'));
        $this->exportFileBuffer->shouldReceive('initialize')->once();

        $this->exporter->initilize();
    });

    it('calls initialize on the file buffer for XLSX', function () {
        $this->exporter->setExport(makeOptionExportTrack('Xlsx'));
        $this->exportFileBuffer->shouldReceive('initialize')->once();

        $this->exporter->initilize();
    });
});

describe('prepareAttributeOptions [CSV]', function () use ($sharedOptionAssertions) {
    it('produces one row per locale with correct field values', function () use ($sharedOptionAssertions) {
        ($sharedOptionAssertions)('Csv', $this->exporter);
    });

    it('defaults missing fields to null for CSV', function () {
        $this->exporter->setExport(makeOptionExportTrack('Csv'));

        $batch = makeOptionBatch([['code' => 'blue']]);
        $result = $this->exporter->prepareAttributeOptions($batch, 'dummy/path/attribute-options.csv');

        expect($result[0]['attribute_code'])->toBeNull()
            ->and($result[0]['label'])->toBeNull()
            ->and($result[0]['sort_order'])->toBeNull()
            ->and($result[0]['swatch_value'])->toBeNull();
    });

    it('handles missing translations gracefully for CSV', function () {
        $this->exporter->setExport(makeOptionExportTrack('Csv'));

        $batch = makeOptionBatch([optionRow(['translations' => []])]);
        $result = $this->exporter->prepareAttributeOptions($batch, 'dummy/path/attribute-options.csv');

        foreach ($result as $row) {
            expect($row['label'])->toBeNull();
        }
    });

    it('handles partial translations for CSV', function () {
        $this->exporter->setExport(makeOptionExportTrack('Csv'));

        $batch = makeOptionBatch([optionRow([
            'translations' => [
                ['locale' => 'en_US', 'label' => 'Red'],
            ],
        ])]);
        $result = $this->exporter->prepareAttributeOptions($batch, 'dummy/path/attribute-options.csv');

        $enRow = collect($result)->firstWhere('locale', 'en_US');
        expect($enRow['label'])->toBe('Red');

        $frRow = collect($result)->firstWhere('locale', 'fr_FR');
        if ($frRow) {
            expect($frRow['label'])->toBeNull();
        }
    });

    it('increments createdItemsCount once per option not per locale for CSV', function () {
        $this->exporter->setExport(makeOptionExportTrack('Csv'));

        $batch = makeOptionBatch([
            optionRow(['code' => 'red']),
            optionRow(['code' => 'blue']),
        ]);
        $this->exporter->prepareAttributeOptions($batch, 'dummy/path/attribute-options.csv');

        expect(getOptionProtectedProperty($this->exporter, 'createdItemsCount'))->toBe(2);
    });

    it('handles multiple options in one batch for CSV', function () {
        $this->exporter->setExport(makeOptionExportTrack('Csv'));

        $locales = core()->getAllActiveLocales()->pluck('code');
        $batch = makeOptionBatch([
            optionRow(['code' => 'red']),
            optionRow(['code' => 'blue']),
        ]);
        $result = $this->exporter->prepareAttributeOptions($batch, 'dummy/path/attribute-options.csv');

        expect(count($result))->toBe(2 * count($locales));
    });
});

describe('prepareAttributeOptions [XLS]', function () use ($sharedOptionAssertions) {
    it('produces one row per locale with correct field values', function () use ($sharedOptionAssertions) {
        ($sharedOptionAssertions)('Xls', $this->exporter);
    });

    it('defaults missing fields to null for XLS', function () {
        $this->exporter->setExport(makeOptionExportTrack('Xls'));

        $batch = makeOptionBatch([['code' => 'blue']]);
        $result = $this->exporter->prepareAttributeOptions($batch, 'dummy/path/attribute-options.xls');

        expect($result[0]['attribute_code'])->toBeNull()
            ->and($result[0]['label'])->toBeNull()
            ->and($result[0]['sort_order'])->toBeNull()
            ->and($result[0]['swatch_value'])->toBeNull();
    });

    it('handles missing translations gracefully for XLS', function () {
        $this->exporter->setExport(makeOptionExportTrack('Xls'));

        $batch = makeOptionBatch([optionRow(['translations' => []])]);
        $result = $this->exporter->prepareAttributeOptions($batch, 'dummy/path/attribute-options.xls');

        foreach ($result as $row) {
            expect($row['label'])->toBeNull();
        }
    });

    it('handles partial translations for XLS', function () {
        $this->exporter->setExport(makeOptionExportTrack('Xls'));

        $batch = makeOptionBatch([optionRow([
            'translations' => [
                ['locale' => 'en_US', 'label' => 'Red'],
            ],
        ])]);
        $result = $this->exporter->prepareAttributeOptions($batch, 'dummy/path/attribute-options.xls');

        $enRow = collect($result)->firstWhere('locale', 'en_US');
        expect($enRow['label'])->toBe('Red');

        $frRow = collect($result)->firstWhere('locale', 'fr_FR');
        if ($frRow) {
            expect($frRow['label'])->toBeNull();
        }
    });

    it('increments createdItemsCount once per option not per locale for XLS', function () {
        $this->exporter->setExport(makeOptionExportTrack('Xls'));

        $batch = makeOptionBatch([
            optionRow(['code' => 'red']),
            optionRow(['code' => 'blue']),
        ]);
        $this->exporter->prepareAttributeOptions($batch, 'dummy/path/attribute-options.xls');

        expect(getOptionProtectedProperty($this->exporter, 'createdItemsCount'))->toBe(2);
    });

    it('handles multiple options in one batch for XLS', function () {
        $this->exporter->setExport(makeOptionExportTrack('Xls'));

        $locales = core()->getAllActiveLocales()->pluck('code');
        $batch = makeOptionBatch([
            optionRow(['code' => 'red']),
            optionRow(['code' => 'blue']),
        ]);
        $result = $this->exporter->prepareAttributeOptions($batch, 'dummy/path/attribute-options.xls');

        expect(count($result))->toBe(2 * count($locales));
    });
});

describe('prepareAttributeOptions [XLSX]', function () use ($sharedOptionAssertions) {
    it('produces one row per locale with correct field values', function () use ($sharedOptionAssertions) {
        ($sharedOptionAssertions)('Xlsx', $this->exporter);
    });

    it('defaults missing fields to null for XLSX', function () {
        $this->exporter->setExport(makeOptionExportTrack('Xlsx'));

        $batch = makeOptionBatch([['code' => 'blue']]);
        $result = $this->exporter->prepareAttributeOptions($batch, 'dummy/path/attribute-options.xlsx');

        expect($result[0]['attribute_code'])->toBeNull()
            ->and($result[0]['label'])->toBeNull()
            ->and($result[0]['sort_order'])->toBeNull()
            ->and($result[0]['swatch_value'])->toBeNull();
    });

    it('handles missing translations gracefully for XLSX', function () {
        $this->exporter->setExport(makeOptionExportTrack('Xlsx'));

        $batch = makeOptionBatch([optionRow(['translations' => []])]);
        $result = $this->exporter->prepareAttributeOptions($batch, 'dummy/path/attribute-options.xlsx');

        foreach ($result as $row) {
            expect($row['label'])->toBeNull();
        }
    });

    it('handles partial translations for XLSX', function () {
        $this->exporter->setExport(makeOptionExportTrack('Xlsx'));

        $batch = makeOptionBatch([optionRow([
            'translations' => [
                ['locale' => 'en_US', 'label' => 'Red'],
            ],
        ])]);
        $result = $this->exporter->prepareAttributeOptions($batch, 'dummy/path/attribute-options.xlsx');

        $enRow = collect($result)->firstWhere('locale', 'en_US');
        expect($enRow['label'])->toBe('Red');

        $frRow = collect($result)->firstWhere('locale', 'fr_FR');
        if ($frRow) {
            expect($frRow['label'])->toBeNull();
        }
    });

    it('increments createdItemsCount once per option not per locale for XLSX', function () {
        $this->exporter->setExport(makeOptionExportTrack('Xlsx'));

        $batch = makeOptionBatch([
            optionRow(['code' => 'red']),
            optionRow(['code' => 'blue']),
        ]);
        $this->exporter->prepareAttributeOptions($batch, 'dummy/path/attribute-options.xlsx');

        expect(getOptionProtectedProperty($this->exporter, 'createdItemsCount'))->toBe(2);
    });

    it('handles multiple options in one batch for XLSX', function () {
        $this->exporter->setExport(makeOptionExportTrack('Xlsx'));

        $locales = core()->getAllActiveLocales()->pluck('code');
        $batch = makeOptionBatch([
            optionRow(['code' => 'red']),
            optionRow(['code' => 'blue']),
        ]);
        $result = $this->exporter->prepareAttributeOptions($batch, 'dummy/path/attribute-options.xlsx');

        expect(count($result))->toBe(2 * count($locales));
    });
});

describe('output parity across formats', function () {
    it('produces identical option rows regardless of file format', function () {
        $results = [];

        foreach (['Csv', 'Xls', 'Xlsx'] as $format) {
            $exporter = new Exporter(
                Mockery::mock(JobTrackBatchRepository::class),
                Mockery::mock(FileExportFileBuffer::class),
                Mockery::mock(AttributeOptionRepository::class),
            );

            $exporter->setExport(makeOptionExportTrack($format));

            $results[$format] = $exporter->prepareAttributeOptions(
                makeOptionBatch([optionRow()]),
                "dummy/path/attribute-options.{$format}",
            );
        }

        expect($results['Xls'])->toEqual($results['Csv'])
            ->and($results['Xlsx'])->toEqual($results['Csv']);
    });

    it('produces the same row count for all formats', function () {
        $counts = [];

        foreach (['Csv', 'Xls', 'Xlsx'] as $format) {
            $exporter = new Exporter(
                Mockery::mock(JobTrackBatchRepository::class),
                Mockery::mock(FileExportFileBuffer::class),
                Mockery::mock(AttributeOptionRepository::class),
            );

            $exporter->setExport(makeOptionExportTrack($format));

            $counts[$format] = count($exporter->prepareAttributeOptions(
                makeOptionBatch([optionRow()]),
                "dummy/path/attribute-options.{$format}",
            ));
        }

        expect($counts['Xls'])->toBe($counts['Csv'])
            ->and($counts['Xlsx'])->toBe($counts['Csv']);
    });
});
