<?php

use Webkul\Category\Repositories\CategoryFieldRepository;
use Webkul\DataTransfer\Contracts\JobTrack;
use Webkul\DataTransfer\Contracts\JobTrackBatch;
use Webkul\DataTransfer\Helpers\Exporters\CategoryField\Exporter;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer as FileExportFileBuffer;
use Webkul\DataTransfer\Repositories\JobTrackBatchRepository;

/**
 * Build a JobTrack mock with the given file format.
 */
function makeCategoryFieldExportTrack(string $fileFormat): JobTrack
{
    $jobInstance = new stdClass;
    $jobInstance->code = 'category-field-export';
    $jobInstance->entity_type = 'category-fields';
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
 * Minimal but complete category field row with two locale translations.
 */
function categoryFieldRow(array $overrides = []): array
{
    return array_merge([
        'code'             => 'description',
        'type'             => 'textarea',
        'enable_wysiwyg'   => 1,
        'section'          => 'left',
        'position'         => 2,
        'status'           => 1,
        'is_required'      => 0,
        'is_unique'        => 0,
        'validation'       => null,
        'regex_pattern'    => null,
        'value_per_locale' => 1,
        'translations'     => [
            ['locale' => 'en_US', 'name' => 'Description'],
            ['locale' => 'fr_FR', 'name' => 'Description FR'],
        ],
    ], $overrides);
}

/**
 * Build a batch mock containing the given rows.
 */
function makeCategoryFieldBatch(array $rows): JobTrackBatch
{
    $batch = Mockery::mock(JobTrackBatch::class);
    $batch->data = $rows;

    return $batch;
}

/**
 * Read a protected/private property from any object via Reflection.
 */
function getCategoryFieldProtectedProperty(object $object, string $property): mixed
{
    $ref = new ReflectionProperty($object, $property);
    $ref->setAccessible(true);

    return $ref->getValue($object);
}

beforeEach(function () {
    $this->exportBatchRepository = Mockery::mock(JobTrackBatchRepository::class);
    $this->exportFileBuffer = Mockery::mock(FileExportFileBuffer::class);
    $this->categoryFieldRepository = Mockery::mock(CategoryFieldRepository::class);

    $this->exporter = new Exporter(
        $this->exportBatchRepository,
        $this->exportFileBuffer,
        $this->categoryFieldRepository,
    );
});

afterEach(fn () => Mockery::close());

$sharedCategoryFieldAssertions = function (string $fileFormat, Exporter $exporter): void {
    $exporter->setExport(makeCategoryFieldExportTrack($fileFormat));

    $locales = core()->getAllActiveLocales()->pluck('code');
    $batch = makeCategoryFieldBatch([categoryFieldRow()]);
    $result = $exporter->prepareCategoryFields($batch, "dummy/path/category-fields.{$fileFormat}");

    expect($result)
        ->toBeArray()
        ->toHaveCount(count($locales));

    $enRow = collect($result)->firstWhere('locale', 'en_US');

    expect($enRow)->not->toBeNull()
        ->and($enRow['code'])->toBe('description')
        ->and($enRow['type'])->toBe('textarea')
        ->and($enRow['name'])->toBe('Description')
        ->and($enRow['enable_wysiwyg'])->toBe(1)
        ->and($enRow['section'])->toBe('left')
        ->and($enRow['position'])->toBe(2)
        ->and($enRow['status'])->toBe(1)
        ->and($enRow['is_required'])->toBe(0)
        ->and($enRow['is_unique'])->toBe(0)
        ->and($enRow['validation'])->toBeNull()
        ->and($enRow['value_per_locale'])->toBe(1);

    if ($locales->contains('fr_FR')) {
        $frRow = collect($result)->firstWhere('locale', 'fr_FR');

        expect($frRow)->not->toBeNull()
            ->and($frRow['name'])->toBe('Description FR');
    }

    foreach ($result as $row) {
        expect($row)->toHaveKeys([
            'code', 'type', 'locale', 'name', 'enable_wysiwyg',
            'section', 'position', 'status',
            'is_required', 'is_unique', 'validation',
            'regex_pattern', 'value_per_locale',
        ]);
    }
};

describe('initilize', function () {
    it('calls initialize on the file buffer for CSV', function () {
        $this->exporter->setExport(makeCategoryFieldExportTrack('Csv'));
        $this->exportFileBuffer->shouldReceive('initialize')->once();

        $this->exporter->initilize();
    });

    it('calls initialize on the file buffer for XLS', function () {
        $this->exporter->setExport(makeCategoryFieldExportTrack('Xls'));
        $this->exportFileBuffer->shouldReceive('initialize')->once();

        $this->exporter->initilize();
    });

    it('calls initialize on the file buffer for XLSX', function () {
        $this->exporter->setExport(makeCategoryFieldExportTrack('Xlsx'));
        $this->exportFileBuffer->shouldReceive('initialize')->once();

        $this->exporter->initilize();
    });
});

describe('prepareCategoryFields [CSV]', function () use ($sharedCategoryFieldAssertions) {
    it('produces one row per locale with correct field values', function () use ($sharedCategoryFieldAssertions) {
        ($sharedCategoryFieldAssertions)('Csv', $this->exporter);
    });

    it('defaults missing fields to null for CSV', function () {
        $this->exporter->setExport(makeCategoryFieldExportTrack('Csv'));

        $batch = makeCategoryFieldBatch([['code' => 'name']]);
        $result = $this->exporter->prepareCategoryFields($batch, 'dummy/path/category-fields.csv');

        expect($result[0]['type'])->toBeNull()
            ->and($result[0]['name'])->toBeNull()
            ->and($result[0]['section'])->toBeNull()
            ->and($result[0]['status'])->toBeNull();
    });

    it('handles missing translations gracefully for CSV', function () {
        $this->exporter->setExport(makeCategoryFieldExportTrack('Csv'));

        $batch = makeCategoryFieldBatch([categoryFieldRow(['translations' => []])]);
        $result = $this->exporter->prepareCategoryFields($batch, 'dummy/path/category-fields.csv');

        foreach ($result as $row) {
            expect($row['name'])->toBeNull();
        }
    });

    it('handles partial translations for CSV', function () {
        $this->exporter->setExport(makeCategoryFieldExportTrack('Csv'));

        $batch = makeCategoryFieldBatch([categoryFieldRow([
            'translations' => [
                ['locale' => 'en_US', 'name' => 'Description'],
            ],
        ])]);
        $result = $this->exporter->prepareCategoryFields($batch, 'dummy/path/category-fields.csv');

        $enRow = collect($result)->firstWhere('locale', 'en_US');
        expect($enRow['name'])->toBe('Description');

        $frRow = collect($result)->firstWhere('locale', 'fr_FR');
        if ($frRow) {
            expect($frRow['name'])->toBeNull();
        }
    });

    it('increments createdItemsCount once per field not per locale for CSV', function () {
        $this->exporter->setExport(makeCategoryFieldExportTrack('Csv'));

        $batch = makeCategoryFieldBatch([
            categoryFieldRow(['code' => 'description']),
            categoryFieldRow(['code' => 'name']),
        ]);
        $this->exporter->prepareCategoryFields($batch, 'dummy/path/category-fields.csv');

        expect(getCategoryFieldProtectedProperty($this->exporter, 'createdItemsCount'))->toBe(2);
    });

    it('handles multiple fields in one batch for CSV', function () {
        $this->exporter->setExport(makeCategoryFieldExportTrack('Csv'));

        $locales = core()->getAllActiveLocales()->pluck('code');
        $batch = makeCategoryFieldBatch([
            categoryFieldRow(['code' => 'description']),
            categoryFieldRow(['code' => 'name']),
        ]);
        $result = $this->exporter->prepareCategoryFields($batch, 'dummy/path/category-fields.csv');

        expect(count($result))->toBe(2 * count($locales));
    });
});

describe('prepareCategoryFields [XLS]', function () use ($sharedCategoryFieldAssertions) {
    it('produces one row per locale with correct field values', function () use ($sharedCategoryFieldAssertions) {
        ($sharedCategoryFieldAssertions)('Xls', $this->exporter);
    });

    it('defaults missing fields to null for XLS', function () {
        $this->exporter->setExport(makeCategoryFieldExportTrack('Xls'));

        $batch = makeCategoryFieldBatch([['code' => 'name']]);
        $result = $this->exporter->prepareCategoryFields($batch, 'dummy/path/category-fields.xls');

        expect($result[0]['type'])->toBeNull()
            ->and($result[0]['name'])->toBeNull()
            ->and($result[0]['section'])->toBeNull()
            ->and($result[0]['status'])->toBeNull();
    });

    it('handles missing translations gracefully for XLS', function () {
        $this->exporter->setExport(makeCategoryFieldExportTrack('Xls'));

        $batch = makeCategoryFieldBatch([categoryFieldRow(['translations' => []])]);
        $result = $this->exporter->prepareCategoryFields($batch, 'dummy/path/category-fields.xls');

        foreach ($result as $row) {
            expect($row['name'])->toBeNull();
        }
    });

    it('handles partial translations for XLS', function () {
        $this->exporter->setExport(makeCategoryFieldExportTrack('Xls'));

        $batch = makeCategoryFieldBatch([categoryFieldRow([
            'translations' => [
                ['locale' => 'en_US', 'name' => 'Description'],
            ],
        ])]);
        $result = $this->exporter->prepareCategoryFields($batch, 'dummy/path/category-fields.xls');

        $enRow = collect($result)->firstWhere('locale', 'en_US');
        expect($enRow['name'])->toBe('Description');

        $frRow = collect($result)->firstWhere('locale', 'fr_FR');
        if ($frRow) {
            expect($frRow['name'])->toBeNull();
        }
    });

    it('increments createdItemsCount once per field not per locale for XLS', function () {
        $this->exporter->setExport(makeCategoryFieldExportTrack('Xls'));

        $batch = makeCategoryFieldBatch([
            categoryFieldRow(['code' => 'description']),
            categoryFieldRow(['code' => 'name']),
        ]);
        $this->exporter->prepareCategoryFields($batch, 'dummy/path/category-fields.xls');

        expect(getCategoryFieldProtectedProperty($this->exporter, 'createdItemsCount'))->toBe(2);
    });

    it('handles multiple fields in one batch for XLS', function () {
        $this->exporter->setExport(makeCategoryFieldExportTrack('Xls'));

        $locales = core()->getAllActiveLocales()->pluck('code');
        $batch = makeCategoryFieldBatch([
            categoryFieldRow(['code' => 'description']),
            categoryFieldRow(['code' => 'name']),
        ]);
        $result = $this->exporter->prepareCategoryFields($batch, 'dummy/path/category-fields.xls');

        expect(count($result))->toBe(2 * count($locales));
    });
});

describe('prepareCategoryFields [XLSX]', function () use ($sharedCategoryFieldAssertions) {
    it('produces one row per locale with correct field values', function () use ($sharedCategoryFieldAssertions) {
        ($sharedCategoryFieldAssertions)('Xlsx', $this->exporter);
    });

    it('defaults missing fields to null for XLSX', function () {
        $this->exporter->setExport(makeCategoryFieldExportTrack('Xlsx'));

        $batch = makeCategoryFieldBatch([['code' => 'name']]);
        $result = $this->exporter->prepareCategoryFields($batch, 'dummy/path/category-fields.xlsx');

        expect($result[0]['type'])->toBeNull()
            ->and($result[0]['name'])->toBeNull()
            ->and($result[0]['section'])->toBeNull()
            ->and($result[0]['status'])->toBeNull();
    });

    it('handles missing translations gracefully for XLSX', function () {
        $this->exporter->setExport(makeCategoryFieldExportTrack('Xlsx'));

        $batch = makeCategoryFieldBatch([categoryFieldRow(['translations' => []])]);
        $result = $this->exporter->prepareCategoryFields($batch, 'dummy/path/category-fields.xlsx');

        foreach ($result as $row) {
            expect($row['name'])->toBeNull();
        }
    });

    it('handles partial translations for XLSX', function () {
        $this->exporter->setExport(makeCategoryFieldExportTrack('Xlsx'));

        $batch = makeCategoryFieldBatch([categoryFieldRow([
            'translations' => [
                ['locale' => 'en_US', 'name' => 'Description'],
            ],
        ])]);
        $result = $this->exporter->prepareCategoryFields($batch, 'dummy/path/category-fields.xlsx');

        $enRow = collect($result)->firstWhere('locale', 'en_US');
        expect($enRow['name'])->toBe('Description');

        $frRow = collect($result)->firstWhere('locale', 'fr_FR');
        if ($frRow) {
            expect($frRow['name'])->toBeNull();
        }
    });

    it('increments createdItemsCount once per field not per locale for XLSX', function () {
        $this->exporter->setExport(makeCategoryFieldExportTrack('Xlsx'));

        $batch = makeCategoryFieldBatch([
            categoryFieldRow(['code' => 'description']),
            categoryFieldRow(['code' => 'name']),
        ]);
        $this->exporter->prepareCategoryFields($batch, 'dummy/path/category-fields.xlsx');

        expect(getCategoryFieldProtectedProperty($this->exporter, 'createdItemsCount'))->toBe(2);
    });

    it('handles multiple fields in one batch for XLSX', function () {
        $this->exporter->setExport(makeCategoryFieldExportTrack('Xlsx'));

        $locales = core()->getAllActiveLocales()->pluck('code');
        $batch = makeCategoryFieldBatch([
            categoryFieldRow(['code' => 'description']),
            categoryFieldRow(['code' => 'name']),
        ]);
        $result = $this->exporter->prepareCategoryFields($batch, 'dummy/path/category-fields.xlsx');

        expect(count($result))->toBe(2 * count($locales));
    });
});

describe('output parity across formats', function () {
    it('produces identical category field rows regardless of file format', function () {
        $results = [];

        foreach (['Csv', 'Xls', 'Xlsx'] as $format) {
            $exporter = new Exporter(
                Mockery::mock(JobTrackBatchRepository::class),
                Mockery::mock(FileExportFileBuffer::class),
                Mockery::mock(CategoryFieldRepository::class),
            );

            $exporter->setExport(makeCategoryFieldExportTrack($format));

            $results[$format] = $exporter->prepareCategoryFields(
                makeCategoryFieldBatch([categoryFieldRow()]),
                "dummy/path/category-fields.{$format}",
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
                Mockery::mock(CategoryFieldRepository::class),
            );

            $exporter->setExport(makeCategoryFieldExportTrack($format));

            $counts[$format] = count($exporter->prepareCategoryFields(
                makeCategoryFieldBatch([categoryFieldRow()]),
                "dummy/path/category-fields.{$format}",
            ));
        }

        expect($counts['Xls'])->toBe($counts['Csv'])
            ->and($counts['Xlsx'])->toBe($counts['Csv']);
    });
});
