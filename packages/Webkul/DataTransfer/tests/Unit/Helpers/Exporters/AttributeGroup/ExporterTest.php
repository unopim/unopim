<?php

use Webkul\Attribute\Repositories\AttributeGroupRepository;
use Webkul\DataTransfer\Contracts\JobTrack;
use Webkul\DataTransfer\Contracts\JobTrackBatch;
use Webkul\DataTransfer\Helpers\Exporters\AttributeGroup\Exporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

// ─── Shared Helpers ───────────────────────────────────────────────────────────

/**
 * Build a JobTrack mock with the given file format.
 */
function makeGroupExportTrack(string $fileFormat): JobTrack
{
    $jobInstance = new stdClass;
    $jobInstance->code = 'attribute-group-export';
    $jobInstance->entity_type = 'attribute-groups';
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
 * Minimal but complete attribute group row with two locale translations.
 */
function groupRow(array $overrides = []): array
{
    return array_merge([
        'code'         => 'general',
        'column'       => 1,
        'position'     => 1,
        'translations' => [
            ['locale' => 'en_US', 'name' => 'General'],
            ['locale' => 'fr_FR', 'name' => 'Général'],
        ],
    ], $overrides);
}

/**
 * Build a batch mock containing the given rows.
 */
function makeGroupBatch(array $rows): JobTrackBatch
{
    $batch = Mockery::mock(JobTrackBatch::class);
    $batch->data = $rows;

    return $batch;
}

/**
 * Read a protected/private property from any object via Reflection.
 */
function getGroupProtectedProperty(object $object, string $property): mixed
{
    $ref = new ReflectionProperty($object, $property);
    $ref->setAccessible(true);

    return $ref->getValue($object);
}

// ─── Global beforeEach / afterEach ───────────────────────────────────────────

beforeEach(function () {
    $this->exportBatchRepository = Mockery::mock(JobTrackBatchRepository::class);
    $this->exportFileBuffer = Mockery::mock(FileExportFileBuffer::class);
    $this->attributeGroupRepository = Mockery::mock(AttributeGroupRepository::class);

    $this->exporter = new Exporter(
        $this->exportBatchRepository,
        $this->exportFileBuffer,
        $this->attributeGroupRepository,
    );
});

afterEach(fn () => Mockery::close());

// ─── Shared assertion logic ───────────────────────────────────────────────────
//
// Accepts $exporter explicitly to avoid "Using $this when not in object context"
// which happens when closures are defined at file scope.
//

$sharedGroupAssertions = function (string $fileFormat, Exporter $exporter): void {
    $exporter->setExport(makeGroupExportTrack($fileFormat));

    $locales = core()->getAllActiveLocales()->pluck('code');
    $batch = makeGroupBatch([groupRow()]);
    $result = $exporter->prepareAttributeGroups($batch, "dummy/path/attribute-groups.{$fileFormat}");

    // ── Structure ──────────────────────────────────────────────────────────
    expect($result)
        ->toBeArray()
        ->toHaveCount(count($locales));

    // ── en_US row ─────────────────────────────────────────────────────────
    $enRow = collect($result)->firstWhere('locale', 'en_US');

    expect($enRow)->not->toBeNull()
        ->and($enRow['code'])->toBe('general')
        ->and($enRow['name'])->toBe('General')
        ->and($enRow['column'])->toBe(1)
        ->and($enRow['position'])->toBe(1)
        ->and($enRow['locale'])->toBe('en_US');

    // ── fr_FR row (when locale is active) ─────────────────────────────────
    if ($locales->contains('fr_FR')) {
        $frRow = collect($result)->firstWhere('locale', 'fr_FR');

        expect($frRow)->not->toBeNull()
            ->and($frRow['name'])->toBe('Général');
    }

    // ── Required keys present in every row ────────────────────────────────
    foreach ($result as $row) {
        expect($row)->toHaveKeys(['code', 'locale', 'name', 'column', 'position']);
    }
};

// ─── initilize() ─────────────────────────────────────────────────────────────

describe('initilize', function () {
    it('calls initialize on the file buffer for CSV', function () {
        $this->exporter->setExport(makeGroupExportTrack('Csv'));
        $this->exportFileBuffer->shouldReceive('initialize')->once();

        $this->exporter->initilize();
    });

    it('calls initialize on the file buffer for XLS', function () {
        $this->exporter->setExport(makeGroupExportTrack('Xls'));
        $this->exportFileBuffer->shouldReceive('initialize')->once();

        $this->exporter->initilize();
    });

    it('calls initialize on the file buffer for XLSX', function () {
        $this->exporter->setExport(makeGroupExportTrack('Xlsx'));
        $this->exportFileBuffer->shouldReceive('initialize')->once();

        $this->exporter->initilize();
    });
});

// ─── CSV ─────────────────────────────────────────────────────────────────────

describe('prepareAttributeGroups [CSV]', function () use ($sharedGroupAssertions) {
    it('produces one row per locale with correct field values', function () use ($sharedGroupAssertions) {
        ($sharedGroupAssertions)('Csv', $this->exporter);
    });

    it('defaults missing fields to null for CSV', function () {
        $this->exporter->setExport(makeGroupExportTrack('Csv'));

        $batch = makeGroupBatch([['code' => 'technical']]);
        $result = $this->exporter->prepareAttributeGroups($batch, 'dummy/path/attribute-groups.csv');

        expect($result[0]['name'])->toBeNull()
            ->and($result[0]['column'])->toBeNull()
            ->and($result[0]['position'])->toBeNull();
    });

    it('handles missing translations gracefully for CSV', function () {
        $this->exporter->setExport(makeGroupExportTrack('Csv'));

        $batch = makeGroupBatch([groupRow(['translations' => []])]);
        $result = $this->exporter->prepareAttributeGroups($batch, 'dummy/path/attribute-groups.csv');

        foreach ($result as $row) {
            expect($row['name'])->toBeNull();
        }
    });

    it('handles partial translations for CSV', function () {
        $this->exporter->setExport(makeGroupExportTrack('Csv'));

        $batch = makeGroupBatch([groupRow([
            'translations' => [
                ['locale' => 'en_US', 'name' => 'General'],
                // fr_FR intentionally missing
            ],
        ])]);
        $result = $this->exporter->prepareAttributeGroups($batch, 'dummy/path/attribute-groups.csv');

        $enRow = collect($result)->firstWhere('locale', 'en_US');
        expect($enRow['name'])->toBe('General');

        $frRow = collect($result)->firstWhere('locale', 'fr_FR');
        if ($frRow) {
            expect($frRow['name'])->toBeNull();
        }
    });

    it('increments createdItemsCount once per group not per locale for CSV', function () {
        $this->exporter->setExport(makeGroupExportTrack('Csv'));

        $batch = makeGroupBatch([
            groupRow(['code' => 'general']),
            groupRow(['code' => 'technical']),
        ]);
        $this->exporter->prepareAttributeGroups($batch, 'dummy/path/attribute-groups.csv');

        expect(getGroupProtectedProperty($this->exporter, 'createdItemsCount'))->toBe(2);
    });

    it('handles multiple groups in one batch for CSV', function () {
        $this->exporter->setExport(makeGroupExportTrack('Csv'));

        $locales = core()->getAllActiveLocales()->pluck('code');
        $batch = makeGroupBatch([
            groupRow(['code' => 'general']),
            groupRow(['code' => 'technical']),
        ]);
        $result = $this->exporter->prepareAttributeGroups($batch, 'dummy/path/attribute-groups.csv');

        expect(count($result))->toBe(2 * count($locales));
    });
});

// ─── XLS ─────────────────────────────────────────────────────────────────────

describe('prepareAttributeGroups [XLS]', function () use ($sharedGroupAssertions) {
    it('produces one row per locale with correct field values', function () use ($sharedGroupAssertions) {
        ($sharedGroupAssertions)('Xls', $this->exporter);
    });

    it('defaults missing fields to null for XLS', function () {
        $this->exporter->setExport(makeGroupExportTrack('Xls'));

        $batch = makeGroupBatch([['code' => 'technical']]);
        $result = $this->exporter->prepareAttributeGroups($batch, 'dummy/path/attribute-groups.xls');

        expect($result[0]['name'])->toBeNull()
            ->and($result[0]['column'])->toBeNull()
            ->and($result[0]['position'])->toBeNull();
    });

    it('handles missing translations gracefully for XLS', function () {
        $this->exporter->setExport(makeGroupExportTrack('Xls'));

        $batch = makeGroupBatch([groupRow(['translations' => []])]);
        $result = $this->exporter->prepareAttributeGroups($batch, 'dummy/path/attribute-groups.xls');

        foreach ($result as $row) {
            expect($row['name'])->toBeNull();
        }
    });

    it('handles partial translations for XLS', function () {
        $this->exporter->setExport(makeGroupExportTrack('Xls'));

        $batch = makeGroupBatch([groupRow([
            'translations' => [
                ['locale' => 'en_US', 'name' => 'General'],
            ],
        ])]);
        $result = $this->exporter->prepareAttributeGroups($batch, 'dummy/path/attribute-groups.xls');

        $enRow = collect($result)->firstWhere('locale', 'en_US');
        expect($enRow['name'])->toBe('General');

        $frRow = collect($result)->firstWhere('locale', 'fr_FR');
        if ($frRow) {
            expect($frRow['name'])->toBeNull();
        }
    });

    it('increments createdItemsCount once per group not per locale for XLS', function () {
        $this->exporter->setExport(makeGroupExportTrack('Xls'));

        $batch = makeGroupBatch([
            groupRow(['code' => 'general']),
            groupRow(['code' => 'technical']),
        ]);
        $this->exporter->prepareAttributeGroups($batch, 'dummy/path/attribute-groups.xls');

        expect(getGroupProtectedProperty($this->exporter, 'createdItemsCount'))->toBe(2);
    });

    it('handles multiple groups in one batch for XLS', function () {
        $this->exporter->setExport(makeGroupExportTrack('Xls'));

        $locales = core()->getAllActiveLocales()->pluck('code');
        $batch = makeGroupBatch([
            groupRow(['code' => 'general']),
            groupRow(['code' => 'technical']),
        ]);
        $result = $this->exporter->prepareAttributeGroups($batch, 'dummy/path/attribute-groups.xls');

        expect(count($result))->toBe(2 * count($locales));
    });
});

// ─── XLSX ────────────────────────────────────────────────────────────────────

describe('prepareAttributeGroups [XLSX]', function () use ($sharedGroupAssertions) {
    it('produces one row per locale with correct field values', function () use ($sharedGroupAssertions) {
        ($sharedGroupAssertions)('Xlsx', $this->exporter);
    });

    it('defaults missing fields to null for XLSX', function () {
        $this->exporter->setExport(makeGroupExportTrack('Xlsx'));

        $batch = makeGroupBatch([['code' => 'technical']]);
        $result = $this->exporter->prepareAttributeGroups($batch, 'dummy/path/attribute-groups.xlsx');

        expect($result[0]['name'])->toBeNull()
            ->and($result[0]['column'])->toBeNull()
            ->and($result[0]['position'])->toBeNull();
    });

    it('handles missing translations gracefully for XLSX', function () {
        $this->exporter->setExport(makeGroupExportTrack('Xlsx'));

        $batch = makeGroupBatch([groupRow(['translations' => []])]);
        $result = $this->exporter->prepareAttributeGroups($batch, 'dummy/path/attribute-groups.xlsx');

        foreach ($result as $row) {
            expect($row['name'])->toBeNull();
        }
    });

    it('handles partial translations for XLSX', function () {
        $this->exporter->setExport(makeGroupExportTrack('Xlsx'));

        $batch = makeGroupBatch([groupRow([
            'translations' => [
                ['locale' => 'en_US', 'name' => 'General'],
            ],
        ])]);
        $result = $this->exporter->prepareAttributeGroups($batch, 'dummy/path/attribute-groups.xlsx');

        $enRow = collect($result)->firstWhere('locale', 'en_US');
        expect($enRow['name'])->toBe('General');

        $frRow = collect($result)->firstWhere('locale', 'fr_FR');
        if ($frRow) {
            expect($frRow['name'])->toBeNull();
        }
    });

    it('increments createdItemsCount once per group not per locale for XLSX', function () {
        $this->exporter->setExport(makeGroupExportTrack('Xlsx'));

        $batch = makeGroupBatch([
            groupRow(['code' => 'general']),
            groupRow(['code' => 'technical']),
        ]);
        $this->exporter->prepareAttributeGroups($batch, 'dummy/path/attribute-groups.xlsx');

        expect(getGroupProtectedProperty($this->exporter, 'createdItemsCount'))->toBe(2);
    });

    it('handles multiple groups in one batch for XLSX', function () {
        $this->exporter->setExport(makeGroupExportTrack('Xlsx'));

        $locales = core()->getAllActiveLocales()->pluck('code');
        $batch = makeGroupBatch([
            groupRow(['code' => 'general']),
            groupRow(['code' => 'technical']),
        ]);
        $result = $this->exporter->prepareAttributeGroups($batch, 'dummy/path/attribute-groups.xlsx');

        expect(count($result))->toBe(2 * count($locales));
    });
});

// ─── Output parity across all formats ────────────────────────────────────────

describe('output parity across formats', function () {
    it('produces identical group rows regardless of file format', function () {
        $results = [];

        foreach (['Csv', 'Xls', 'Xlsx'] as $format) {
            $exporter = new Exporter(
                Mockery::mock(JobTrackBatchRepository::class),
                Mockery::mock(FileExportFileBuffer::class),
                Mockery::mock(AttributeGroupRepository::class),
            );

            $exporter->setExport(makeGroupExportTrack($format));

            $results[$format] = $exporter->prepareAttributeGroups(
                makeGroupBatch([groupRow()]),
                "dummy/path/attribute-groups.{$format}",
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
                Mockery::mock(AttributeGroupRepository::class),
            );

            $exporter->setExport(makeGroupExportTrack($format));

            $counts[$format] = count($exporter->prepareAttributeGroups(
                makeGroupBatch([groupRow()]),
                "dummy/path/attribute-groups.{$format}",
            ));
        }

        expect($counts['Xls'])->toBe($counts['Csv'])
            ->and($counts['Xlsx'])->toBe($counts['Csv']);
    });
});
