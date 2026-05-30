<?php

use Webkul\Attribute\Repositories\AttributeFamilyRepository;
use Webkul\DataTransfer\Contracts\JobTrack;
use Webkul\DataTransfer\Contracts\JobTrackBatch;
use Webkul\DataTransfer\Helpers\Exporters\AttributeFamily\Exporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

// ─── Shared Helpers ───────────────────────────────────────────────────────────

/**
 * Build a JobTrack mock with the given file format.
 */
function makeFamilyExportTrack(string $fileFormat): JobTrack
{
    $jobInstance = new stdClass;
    $jobInstance->code = 'attribute-family-export';
    $jobInstance->entity_type = 'attribute-families';
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
 * Minimal but complete attribute family row with two locale translations.
 */
function familyRow(array $overrides = []): array
{
    return array_merge([
        'code'         => 'default',
        'translations' => [
            ['locale' => 'en_US', 'name' => 'Default'],
            ['locale' => 'fr_FR', 'name' => 'Défaut'],
        ],
    ], $overrides);
}

/**
 * Build a batch mock containing the given rows.
 */
function makeFamilyBatch(array $rows): JobTrackBatch
{
    $batch = Mockery::mock(JobTrackBatch::class);
    $batch->data = $rows;

    return $batch;
}

/**
 * Read a protected/private property from any object via Reflection.
 */
function getFamilyProtectedProperty(object $object, string $property): mixed
{
    $ref = new ReflectionProperty($object, $property);
    $ref->setAccessible(true);

    return $ref->getValue($object);
}

// ─── Global beforeEach / afterEach ───────────────────────────────────────────

beforeEach(function () {
    $this->exportBatchRepository = Mockery::mock(JobTrackBatchRepository::class);
    $this->exportFileBuffer = Mockery::mock(FileExportFileBuffer::class);
    $this->attributeFamilyRepository = Mockery::mock(AttributeFamilyRepository::class);

    $this->exporter = new Exporter(
        $this->exportBatchRepository,
        $this->exportFileBuffer,
        $this->attributeFamilyRepository,
    );
});

afterEach(fn () => Mockery::close());

// ─── Shared assertion logic ───────────────────────────────────────────────────
//
// Accepts $exporter explicitly to avoid "Using $this when not in object context"
// which happens when closures are defined at file scope.
//

$sharedFamilyAssertions = function (string $fileFormat, Exporter $exporter): void {
    $exporter->setExport(makeFamilyExportTrack($fileFormat));

    $locales = core()->getAllActiveLocales()->pluck('code');
    $batch = makeFamilyBatch([familyRow()]);
    $result = $exporter->prepareAttributeFamilies($batch, "dummy/path/attribute-families.{$fileFormat}");

    // ── Structure ──────────────────────────────────────────────────────────
    expect($result)
        ->toBeArray()
        ->toHaveCount(count($locales));

    // ── en_US row ─────────────────────────────────────────────────────────
    $enRow = collect($result)->firstWhere('locale', 'en_US');

    expect($enRow)->not->toBeNull()
        ->and($enRow['code'])->toBe('default')
        ->and($enRow['name'])->toBe('Default')
        ->and($enRow['locale'])->toBe('en_US');

    // ── fr_FR row (when locale is active) ─────────────────────────────────
    if ($locales->contains('fr_FR')) {
        $frRow = collect($result)->firstWhere('locale', 'fr_FR');

        expect($frRow)->not->toBeNull()
            ->and($frRow['name'])->toBe('Défaut');
    }

    // ── Required keys present in every row ────────────────────────────────
    foreach ($result as $row) {
        expect($row)->toHaveKeys(['code', 'locale', 'name']);
    }
};

// ─── initilize() ─────────────────────────────────────────────────────────────

describe('initilize', function () {
    it('calls initialize on the file buffer for CSV', function () {
        $this->exporter->setExport(makeFamilyExportTrack('Csv'));
        $this->exportFileBuffer->shouldReceive('initialize')->once();

        $this->exporter->initilize();
    });

    it('calls initialize on the file buffer for XLS', function () {
        $this->exporter->setExport(makeFamilyExportTrack('Xls'));
        $this->exportFileBuffer->shouldReceive('initialize')->once();

        $this->exporter->initilize();
    });

    it('calls initialize on the file buffer for XLSX', function () {
        $this->exporter->setExport(makeFamilyExportTrack('Xlsx'));
        $this->exportFileBuffer->shouldReceive('initialize')->once();

        $this->exporter->initilize();
    });
});

// ─── CSV ─────────────────────────────────────────────────────────────────────

describe('prepareAttributeFamilies [CSV]', function () use ($sharedFamilyAssertions) {
    it('produces one row per locale with correct field values', function () use ($sharedFamilyAssertions) {
        ($sharedFamilyAssertions)('Csv', $this->exporter);
    });

    it('defaults missing fields to null for CSV', function () {
        $this->exporter->setExport(makeFamilyExportTrack('Csv'));

        $batch = makeFamilyBatch([['code' => 'electronics']]);
        $result = $this->exporter->prepareAttributeFamilies($batch, 'dummy/path/attribute-families.csv');

        expect($result[0]['name'])->toBeNull();
    });

    it('handles missing translations gracefully for CSV', function () {
        $this->exporter->setExport(makeFamilyExportTrack('Csv'));

        $batch = makeFamilyBatch([familyRow(['translations' => []])]);
        $result = $this->exporter->prepareAttributeFamilies($batch, 'dummy/path/attribute-families.csv');

        foreach ($result as $row) {
            expect($row['name'])->toBeNull();
        }
    });

    it('handles partial translations for CSV', function () {
        $this->exporter->setExport(makeFamilyExportTrack('Csv'));

        $batch = makeFamilyBatch([familyRow([
            'translations' => [
                ['locale' => 'en_US', 'name' => 'Default'],
                // fr_FR intentionally missing
            ],
        ])]);
        $result = $this->exporter->prepareAttributeFamilies($batch, 'dummy/path/attribute-families.csv');

        $enRow = collect($result)->firstWhere('locale', 'en_US');
        expect($enRow['name'])->toBe('Default');

        $frRow = collect($result)->firstWhere('locale', 'fr_FR');
        if ($frRow) {
            expect($frRow['name'])->toBeNull();
        }
    });

    it('increments createdItemsCount once per family not per locale for CSV', function () {
        $this->exporter->setExport(makeFamilyExportTrack('Csv'));

        $batch = makeFamilyBatch([
            familyRow(['code' => 'default']),
            familyRow(['code' => 'electronics']),
        ]);
        $this->exporter->prepareAttributeFamilies($batch, 'dummy/path/attribute-families.csv');

        expect(getFamilyProtectedProperty($this->exporter, 'createdItemsCount'))->toBe(2);
    });

    it('handles multiple families in one batch for CSV', function () {
        $this->exporter->setExport(makeFamilyExportTrack('Csv'));

        $locales = core()->getAllActiveLocales()->pluck('code');
        $batch = makeFamilyBatch([
            familyRow(['code' => 'default']),
            familyRow(['code' => 'electronics']),
        ]);
        $result = $this->exporter->prepareAttributeFamilies($batch, 'dummy/path/attribute-families.csv');

        expect(count($result))->toBe(2 * count($locales));
    });
});

// ─── XLS ─────────────────────────────────────────────────────────────────────

describe('prepareAttributeFamilies [XLS]', function () use ($sharedFamilyAssertions) {
    it('produces one row per locale with correct field values', function () use ($sharedFamilyAssertions) {
        ($sharedFamilyAssertions)('Xls', $this->exporter);
    });

    it('defaults missing fields to null for XLS', function () {
        $this->exporter->setExport(makeFamilyExportTrack('Xls'));

        $batch = makeFamilyBatch([['code' => 'electronics']]);
        $result = $this->exporter->prepareAttributeFamilies($batch, 'dummy/path/attribute-families.xls');

        expect($result[0]['name'])->toBeNull();
    });

    it('handles missing translations gracefully for XLS', function () {
        $this->exporter->setExport(makeFamilyExportTrack('Xls'));

        $batch = makeFamilyBatch([familyRow(['translations' => []])]);
        $result = $this->exporter->prepareAttributeFamilies($batch, 'dummy/path/attribute-families.xls');

        foreach ($result as $row) {
            expect($row['name'])->toBeNull();
        }
    });

    it('handles partial translations for XLS', function () {
        $this->exporter->setExport(makeFamilyExportTrack('Xls'));

        $batch = makeFamilyBatch([familyRow([
            'translations' => [
                ['locale' => 'en_US', 'name' => 'Default'],
            ],
        ])]);
        $result = $this->exporter->prepareAttributeFamilies($batch, 'dummy/path/attribute-families.xls');

        $enRow = collect($result)->firstWhere('locale', 'en_US');
        expect($enRow['name'])->toBe('Default');

        $frRow = collect($result)->firstWhere('locale', 'fr_FR');
        if ($frRow) {
            expect($frRow['name'])->toBeNull();
        }
    });

    it('increments createdItemsCount once per family not per locale for XLS', function () {
        $this->exporter->setExport(makeFamilyExportTrack('Xls'));

        $batch = makeFamilyBatch([
            familyRow(['code' => 'default']),
            familyRow(['code' => 'electronics']),
        ]);
        $this->exporter->prepareAttributeFamilies($batch, 'dummy/path/attribute-families.xls');

        expect(getFamilyProtectedProperty($this->exporter, 'createdItemsCount'))->toBe(2);
    });

    it('handles multiple families in one batch for XLS', function () {
        $this->exporter->setExport(makeFamilyExportTrack('Xls'));

        $locales = core()->getAllActiveLocales()->pluck('code');
        $batch = makeFamilyBatch([
            familyRow(['code' => 'default']),
            familyRow(['code' => 'electronics']),
        ]);
        $result = $this->exporter->prepareAttributeFamilies($batch, 'dummy/path/attribute-families.xls');

        expect(count($result))->toBe(2 * count($locales));
    });
});

// ─── XLSX ────────────────────────────────────────────────────────────────────

describe('prepareAttributeFamilies [XLSX]', function () use ($sharedFamilyAssertions) {
    it('produces one row per locale with correct field values', function () use ($sharedFamilyAssertions) {
        ($sharedFamilyAssertions)('Xlsx', $this->exporter);
    });

    it('defaults missing fields to null for XLSX', function () {
        $this->exporter->setExport(makeFamilyExportTrack('Xlsx'));

        $batch = makeFamilyBatch([['code' => 'electronics']]);
        $result = $this->exporter->prepareAttributeFamilies($batch, 'dummy/path/attribute-families.xlsx');

        expect($result[0]['name'])->toBeNull();
    });

    it('handles missing translations gracefully for XLSX', function () {
        $this->exporter->setExport(makeFamilyExportTrack('Xlsx'));

        $batch = makeFamilyBatch([familyRow(['translations' => []])]);
        $result = $this->exporter->prepareAttributeFamilies($batch, 'dummy/path/attribute-families.xlsx');

        foreach ($result as $row) {
            expect($row['name'])->toBeNull();
        }
    });

    it('handles partial translations for XLSX', function () {
        $this->exporter->setExport(makeFamilyExportTrack('Xlsx'));

        $batch = makeFamilyBatch([familyRow([
            'translations' => [
                ['locale' => 'en_US', 'name' => 'Default'],
            ],
        ])]);
        $result = $this->exporter->prepareAttributeFamilies($batch, 'dummy/path/attribute-families.xlsx');

        $enRow = collect($result)->firstWhere('locale', 'en_US');
        expect($enRow['name'])->toBe('Default');

        $frRow = collect($result)->firstWhere('locale', 'fr_FR');
        if ($frRow) {
            expect($frRow['name'])->toBeNull();
        }
    });

    it('increments createdItemsCount once per family not per locale for XLSX', function () {
        $this->exporter->setExport(makeFamilyExportTrack('Xlsx'));

        $batch = makeFamilyBatch([
            familyRow(['code' => 'default']),
            familyRow(['code' => 'electronics']),
        ]);
        $this->exporter->prepareAttributeFamilies($batch, 'dummy/path/attribute-families.xlsx');

        expect(getFamilyProtectedProperty($this->exporter, 'createdItemsCount'))->toBe(2);
    });

    it('handles multiple families in one batch for XLSX', function () {
        $this->exporter->setExport(makeFamilyExportTrack('Xlsx'));

        $locales = core()->getAllActiveLocales()->pluck('code');
        $batch = makeFamilyBatch([
            familyRow(['code' => 'default']),
            familyRow(['code' => 'electronics']),
        ]);
        $result = $this->exporter->prepareAttributeFamilies($batch, 'dummy/path/attribute-families.xlsx');

        expect(count($result))->toBe(2 * count($locales));
    });
});

// ─── Output parity across all formats ────────────────────────────────────────

describe('output parity across formats', function () {
    it('produces identical family rows regardless of file format', function () {
        $results = [];

        foreach (['Csv', 'Xls', 'Xlsx'] as $format) {
            $exporter = new Exporter(
                Mockery::mock(JobTrackBatchRepository::class),
                Mockery::mock(FileExportFileBuffer::class),
                Mockery::mock(AttributeFamilyRepository::class),
            );

            $exporter->setExport(makeFamilyExportTrack($format));

            $results[$format] = $exporter->prepareAttributeFamilies(
                makeFamilyBatch([familyRow()]),
                "dummy/path/attribute-families.{$format}",
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
                Mockery::mock(AttributeFamilyRepository::class),
            );

            $exporter->setExport(makeFamilyExportTrack($format));

            $counts[$format] = count($exporter->prepareAttributeFamilies(
                makeFamilyBatch([familyRow()]),
                "dummy/path/attribute-families.{$format}",
            ));
        }

        expect($counts['Xls'])->toBe($counts['Csv'])
            ->and($counts['Xlsx'])->toBe($counts['Csv']);
    });
});
