<?php

use Webkul\Attribute\Repositories\AttributeRepository;
use Webkul\DataTransfer\Contracts\JobTrack;
use Webkul\DataTransfer\Contracts\JobTrackBatch;
use Webkul\DataTransfer\Helpers\Exporters\Attribute\Exporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

// ─── Shared Helpers ───────────────────────────────────────────────────────────

/**
 * Build a JobTrack mock with the given file format.
 */
function makeExportTrack(string $fileFormat): JobTrack
{
    $jobInstance = new stdClass;
    $jobInstance->code = 'attribute-export';
    $jobInstance->entity_type = 'attributes';
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
 * Minimal but complete attribute row with two locale translations.
 */
function attributeRow(array $overrides = []): array
{
    return array_merge([
        'code'              => 'color',
        'type'              => 'select',
        'position'          => 1,
        'enable_wysiwyg'    => 0,
        'swatch_type'       => 'color',
        'is_required'       => 0,
        'is_unique'         => 0,
        'validation'        => null,
        'regex_pattern'     => null,
        'value_per_locale'  => 0,
        'value_per_channel' => 0,
        'is_filterable'     => 1,
        'ai_translate'      => 0,
        'translations'      => [
            ['locale' => 'en_US', 'name' => 'Color'],
            ['locale' => 'fr_FR', 'name' => 'Couleur'],
        ],
    ], $overrides);
}

/**
 * Build a batch mock containing the given rows.
 */
function makeBatch(array $rows): JobTrackBatch
{
    $batch = Mockery::mock(JobTrackBatch::class);
    $batch->data = $rows;

    return $batch;
}

/**
 * Read a protected/private property from any object via Reflection.
 * Fixes: "Cannot access protected property $createdItemsCount"
 */
function getProtectedProperty(object $object, string $property): mixed
{
    $ref = new ReflectionProperty($object, $property);
    $ref->setAccessible(true);

    return $ref->getValue($object);
}

// ─── Global beforeEach / afterEach ───────────────────────────────────────────

beforeEach(function () {
    $this->exportBatchRepository = Mockery::mock(JobTrackBatchRepository::class);
    $this->exportFileBuffer = Mockery::mock(FileExportFileBuffer::class);
    $this->attributeRepository = Mockery::mock(AttributeRepository::class);

    $this->exporter = new Exporter(
        $this->exportBatchRepository,
        $this->exportFileBuffer,
        $this->attributeRepository,
    );
});

afterEach(fn () => Mockery::close());

// ─── Shared assertion logic ───────────────────────────────────────────────────
//
// Defined at file scope so $this doesn't exist here.
// Fix: accept $exporter explicitly instead of relying on $this.
//

$sharedPrepareAssertions = function (string $fileFormat, Exporter $exporter): void {
    $exporter->setExport(makeExportTrack($fileFormat));

    $locales = core()->getAllActiveLocales()->pluck('code');
    $batch = makeBatch([attributeRow()]);
    $result = $exporter->prepareAttributes($batch, "dummy/path/attributes.{$fileFormat}");

    // ── Structure ──────────────────────────────────────────────────────────
    expect($result)
        ->toBeArray()
        ->toHaveCount(count($locales));

    // ── en_US row ─────────────────────────────────────────────────────────
    $enRow = collect($result)->firstWhere('locale', 'en_US');

    expect($enRow)->not->toBeNull()
        ->and($enRow['code'])->toBe('color')
        ->and($enRow['type'])->toBe('select')
        ->and($enRow['name'])->toBe('Color')
        ->and($enRow['position'])->toBe(1)
        ->and($enRow['enable_wysiwyg'])->toBe(0)
        ->and($enRow['swatch_type'])->toBe('color')
        ->and($enRow['is_required'])->toBe(0)
        ->and($enRow['is_unique'])->toBe(0)
        ->and($enRow['validation'])->toBeNull()
        ->and($enRow['is_filterable'])->toBe(1)
        ->and($enRow['productCounts'])->toBe(0);

    // ── fr_FR row (when locale is active) ─────────────────────────────────
    if ($locales->contains('fr_FR')) {
        $frRow = collect($result)->firstWhere('locale', 'fr_FR');

        expect($frRow)->not->toBeNull()
            ->and($frRow['name'])->toBe('Couleur');
    }

    // ── Required keys present in every row ────────────────────────────────
    foreach ($result as $row) {
        expect($row)->toHaveKeys([
            'code', 'type', 'locale', 'name', 'position',
            'enable_wysiwyg', 'swatch_type', 'is_required',
            'is_unique', 'validation', 'regex_pattern',
            'value_per_locale', 'value_per_channel',
            'is_filterable', 'ai_translate', 'productCounts',
        ]);
    }
};

// ─── initilize() ─────────────────────────────────────────────────────────────

describe('initilize', function () {
    it('calls initialize on the file buffer for CSV', function () {
        $this->exporter->setExport(makeExportTrack('Csv'));
        $this->exportFileBuffer->shouldReceive('initialize')->once();

        $this->exporter->initilize();
    });

    it('calls initialize on the file buffer for XLS', function () {
        $this->exporter->setExport(makeExportTrack('Xls'));
        $this->exportFileBuffer->shouldReceive('initialize')->once();

        $this->exporter->initilize();
    });

    it('calls initialize on the file buffer for XLSX', function () {
        $this->exporter->setExport(makeExportTrack('Xlsx'));
        $this->exportFileBuffer->shouldReceive('initialize')->once();

        $this->exporter->initilize();
    });
});

// ─── CSV ─────────────────────────────────────────────────────────────────────

describe('prepareAttributes [CSV]', function () use ($sharedPrepareAssertions) {
    it('produces one row per locale with correct field values', function () use ($sharedPrepareAssertions) {
        // Pass $this->exporter explicitly — $this is valid inside it() closures
        ($sharedPrepareAssertions)('Csv', $this->exporter);
    });

    it('defaults missing fields to null for CSV', function () {
        $this->exporter->setExport(makeExportTrack('Csv'));

        $batch = makeBatch([['code' => 'size']]);
        $result = $this->exporter->prepareAttributes($batch, 'dummy/path/attributes.csv');

        expect($result[0]['type'])->toBeNull()
            ->and($result[0]['swatch_type'])->toBeNull()
            ->and($result[0]['is_required'])->toBeNull()
            ->and($result[0]['name'])->toBeNull();
    });

    it('handles missing translations gracefully for CSV', function () {
        $this->exporter->setExport(makeExportTrack('Csv'));

        $batch = makeBatch([attributeRow(['translations' => []])]);
        $result = $this->exporter->prepareAttributes($batch, 'dummy/path/attributes.csv');

        foreach ($result as $row) {
            expect($row['name'])->toBeNull();
        }
    });

    it('handles partial translations for CSV', function () {
        $this->exporter->setExport(makeExportTrack('Csv'));

        $batch = makeBatch([attributeRow([
            'translations' => [
                ['locale' => 'en_US', 'name' => 'Color'],
            ],
        ])]);
        $result = $this->exporter->prepareAttributes($batch, 'dummy/path/attributes.csv');

        $enRow = collect($result)->firstWhere('locale', 'en_US');
        expect($enRow['name'])->toBe('Color');

        $frRow = collect($result)->firstWhere('locale', 'fr_FR');
        if ($frRow) {
            expect($frRow['name'])->toBeNull();
        }
    });

    it('increments createdItemsCount once per attribute not per locale for CSV', function () {
        $this->exporter->setExport(makeExportTrack('Csv'));

        $batch = makeBatch([
            attributeRow(['code' => 'color']),
            attributeRow(['code' => 'size']),
        ]);
        $this->exporter->prepareAttributes($batch, 'dummy/path/attributes.csv');

        // Use reflection because $createdItemsCount is protected
        expect(getProtectedProperty($this->exporter, 'createdItemsCount'))->toBe(2);
    });

    it('handles multiple attributes in one batch for CSV', function () {
        $this->exporter->setExport(makeExportTrack('Csv'));

        $locales = core()->getAllActiveLocales()->pluck('code');
        $batch = makeBatch([
            attributeRow(['code' => 'color']),
            attributeRow(['code' => 'size']),
        ]);
        $result = $this->exporter->prepareAttributes($batch, 'dummy/path/attributes.csv');

        expect(count($result))->toBe(2 * count($locales));
    });
});

// ─── XLS ─────────────────────────────────────────────────────────────────────

describe('prepareAttributes [XLS]', function () use ($sharedPrepareAssertions) {
    it('produces one row per locale with correct field values', function () use ($sharedPrepareAssertions) {
        ($sharedPrepareAssertions)('Xls', $this->exporter);
    });

    it('defaults missing fields to null for XLS', function () {
        $this->exporter->setExport(makeExportTrack('Xls'));

        $batch = makeBatch([['code' => 'size']]);
        $result = $this->exporter->prepareAttributes($batch, 'dummy/path/attributes.xls');

        expect($result[0]['type'])->toBeNull()
            ->and($result[0]['swatch_type'])->toBeNull()
            ->and($result[0]['is_required'])->toBeNull()
            ->and($result[0]['name'])->toBeNull();
    });

    it('handles missing translations gracefully for XLS', function () {
        $this->exporter->setExport(makeExportTrack('Xls'));

        $batch = makeBatch([attributeRow(['translations' => []])]);
        $result = $this->exporter->prepareAttributes($batch, 'dummy/path/attributes.xls');

        foreach ($result as $row) {
            expect($row['name'])->toBeNull();
        }
    });

    it('handles partial translations for XLS', function () {
        $this->exporter->setExport(makeExportTrack('Xls'));

        $batch = makeBatch([attributeRow([
            'translations' => [
                ['locale' => 'en_US', 'name' => 'Color'],
            ],
        ])]);
        $result = $this->exporter->prepareAttributes($batch, 'dummy/path/attributes.xls');

        $enRow = collect($result)->firstWhere('locale', 'en_US');
        expect($enRow['name'])->toBe('Color');

        $frRow = collect($result)->firstWhere('locale', 'fr_FR');
        if ($frRow) {
            expect($frRow['name'])->toBeNull();
        }
    });

    it('increments createdItemsCount once per attribute not per locale for XLS', function () {
        $this->exporter->setExport(makeExportTrack('Xls'));

        $batch = makeBatch([
            attributeRow(['code' => 'color']),
            attributeRow(['code' => 'size']),
        ]);
        $this->exporter->prepareAttributes($batch, 'dummy/path/attributes.xls');

        expect(getProtectedProperty($this->exporter, 'createdItemsCount'))->toBe(2);
    });

    it('handles multiple attributes in one batch for XLS', function () {
        $this->exporter->setExport(makeExportTrack('Xls'));

        $locales = core()->getAllActiveLocales()->pluck('code');
        $batch = makeBatch([
            attributeRow(['code' => 'color']),
            attributeRow(['code' => 'size']),
        ]);
        $result = $this->exporter->prepareAttributes($batch, 'dummy/path/attributes.xls');

        expect(count($result))->toBe(2 * count($locales));
    });
});

// ─── XLSX ────────────────────────────────────────────────────────────────────

describe('prepareAttributes [XLSX]', function () use ($sharedPrepareAssertions) {
    it('produces one row per locale with correct field values', function () use ($sharedPrepareAssertions) {
        ($sharedPrepareAssertions)('Xlsx', $this->exporter);
    });

    it('defaults missing fields to null for XLSX', function () {
        $this->exporter->setExport(makeExportTrack('Xlsx'));

        $batch = makeBatch([['code' => 'size']]);
        $result = $this->exporter->prepareAttributes($batch, 'dummy/path/attributes.xlsx');

        expect($result[0]['type'])->toBeNull()
            ->and($result[0]['swatch_type'])->toBeNull()
            ->and($result[0]['is_required'])->toBeNull()
            ->and($result[0]['name'])->toBeNull();
    });

    it('handles missing translations gracefully for XLSX', function () {
        $this->exporter->setExport(makeExportTrack('Xlsx'));

        $batch = makeBatch([attributeRow(['translations' => []])]);
        $result = $this->exporter->prepareAttributes($batch, 'dummy/path/attributes.xlsx');

        foreach ($result as $row) {
            expect($row['name'])->toBeNull();
        }
    });

    it('handles partial translations for XLSX', function () {
        $this->exporter->setExport(makeExportTrack('Xlsx'));

        $batch = makeBatch([attributeRow([
            'translations' => [
                ['locale' => 'en_US', 'name' => 'Color'],
            ],
        ])]);
        $result = $this->exporter->prepareAttributes($batch, 'dummy/path/attributes.xlsx');

        $enRow = collect($result)->firstWhere('locale', 'en_US');
        expect($enRow['name'])->toBe('Color');

        $frRow = collect($result)->firstWhere('locale', 'fr_FR');
        if ($frRow) {
            expect($frRow['name'])->toBeNull();
        }
    });

    it('increments createdItemsCount once per attribute not per locale for XLSX', function () {
        $this->exporter->setExport(makeExportTrack('Xlsx'));

        $batch = makeBatch([
            attributeRow(['code' => 'color']),
            attributeRow(['code' => 'size']),
        ]);
        $this->exporter->prepareAttributes($batch, 'dummy/path/attributes.xlsx');

        expect(getProtectedProperty($this->exporter, 'createdItemsCount'))->toBe(2);
    });

    it('handles multiple attributes in one batch for XLSX', function () {
        $this->exporter->setExport(makeExportTrack('Xlsx'));

        $locales = core()->getAllActiveLocales()->pluck('code');
        $batch = makeBatch([
            attributeRow(['code' => 'color']),
            attributeRow(['code' => 'size']),
        ]);
        $result = $this->exporter->prepareAttributes($batch, 'dummy/path/attributes.xlsx');

        expect(count($result))->toBe(2 * count($locales));
    });
});

// ─── Output parity across all formats ────────────────────────────────────────

describe('output parity across formats', function () {
    it('produces identical attribute rows regardless of file format', function () {
        $results = [];

        foreach (['Csv', 'Xls', 'Xlsx'] as $format) {
            $exporter = new Exporter(
                Mockery::mock(JobTrackBatchRepository::class),
                Mockery::mock(FileExportFileBuffer::class),
                Mockery::mock(AttributeRepository::class),
            );

            $exporter->setExport(makeExportTrack($format));

            $results[$format] = $exporter->prepareAttributes(
                makeBatch([attributeRow()]),
                "dummy/path/attributes.{$format}",
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
                Mockery::mock(AttributeRepository::class),
            );

            $exporter->setExport(makeExportTrack($format));

            $counts[$format] = count($exporter->prepareAttributes(
                makeBatch([attributeRow()]),
                "dummy/path/attributes.{$format}",
            ));
        }

        expect($counts['Xls'])->toBe($counts['Csv'])
            ->and($counts['Xlsx'])->toBe($counts['Csv']);
    });
});
