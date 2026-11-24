<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Webkul\DataTransfer\Helpers\Importers\Product\Importer;
use Webkul\DataTransfer\Helpers\Sources\CSV;
use Webkul\DataTransfer\Models\JobTrack;

beforeEach(function (): void {
    if (! function_exists('pcntl_fork') || ! function_exists('posix_kill')) {
        $this->markTestSkipped('Requires the pcntl and posix extensions.');
    }

    $this->fileName = 'parallel-validate-rows-'.uniqid().'.csv';
    $this->badSkus = ['sku-3', 'sku-15', 'sku-27', 'sku-38'];
});

afterEach(function (): void {
    Storage::disk('private')->delete($this->fileName);
});

/**
 * Write a two column product CSV, adding a line with an extra column after each listed row.
 */
function writeParallelValidationCsv(string $fileName, int $rowCount, array $malformedAfter = []): void
{
    $lines = ['sku,name'];

    for ($row = 1; $row <= $rowCount; $row++) {
        $lines[] = "sku-{$row},Name {$row}";

        if (in_array($row, $malformedAfter, true)) {
            $lines[] = "broken-{$row},Name,extra";
        }
    }

    Storage::disk('private')->put($fileName, implode("\n", $lines)."\n");
}

/**
 * Build an importer whose row check rejects the given skus and makes a worker fail on one sku.
 */
function parallelValidationImporter(array $badSkus, ?string $faultySku = null, string $fault = 'none'): Importer
{
    return new class($badSkus, $faultySku, $fault) extends Importer
    {
        public array $parentValidatedSkus = [];

        public array $invalidSkus = [];

        protected int $parentPid;

        public function __construct(
            public array $badSkus,
            public ?string $faultySku,
            public string $fault,
        ) {
            $this->parentPid = getmypid();
            $this->import = (new JobTrack)->forceFill(['id' => 0]);
        }

        public function validateRow(array $rowData, int $rowNumber): bool
        {
            $inWorker = getmypid() !== $this->parentPid;

            if ($inWorker && $rowData['sku'] === $this->faultySku) {
                match ($this->fault) {
                    'kill'  => posix_kill(getmypid(), SIGKILL),
                    'throw' => throw new RuntimeException('Worker failed while validating.'),
                };
            }

            $this->validatedRows[$rowNumber] = true;

            $valid = ! in_array($rowData['sku'], $this->badSkus, true);

            if (! $inWorker) {
                $this->parentValidatedSkus[] = $rowData['sku'];

                if (! $valid) {
                    $this->invalidSkus[] = $rowData['sku'];
                }
            }

            return $valid;
        }

        public function processedRows(): int
        {
            return $this->processedRowsCount;
        }

        public function validatedRowCount(): int
        {
            return count($this->validatedRows);
        }
    };
}

/**
 * Open the CSV as a source whose forked readers stop at the given row, as a reader that loses its place would.
 */
function shortReadingSource(string $fileName, int $stopAtRow): CSV
{
    return new class($fileName, $stopAtRow) extends CSV
    {
        protected int $parentPid;

        public function __construct(string $fileName, protected int $stopAtRow)
        {
            parent::__construct($fileName, ',');

            $this->parentPid = getmypid();
        }

        public function next(): void
        {
            parent::next();

            if (getmypid() !== $this->parentPid && $this->currentRowNumber >= $this->stopAtRow) {
                $this->currentRowNumber = -1;
            }
        }
    };
}

/**
 * Run the parallel validation pass, reconnect the DB the forked workers shared, and report whether a worker escaped instead of exiting.
 */
function runParallelValidation(Importer $importer, CSV $source, int $totalRows, int $workers = 4): bool
{
    $parentPid = getmypid();
    $escapeMarker = sys_get_temp_dir().'/parallel-validation-escape-'.uniqid();

    try {
        (new ReflectionMethod($importer, 'parallelValidateRows'))->invoke($importer, $source, $totalRows, $workers);
    } finally {
        if (getmypid() !== $parentPid) {
            touch($escapeMarker);
            posix_kill(getmypid(), SIGKILL);
        }

        DB::reconnect();
    }

    $escaped = file_exists($escapeMarker);

    @unlink($escapeMarker);

    return $escaped;
}

it('re-validates only the rows healthy workers flagged as invalid', function (): void {
    Log::spy();
    writeParallelValidationCsv($this->fileName, 40);

    $importer = parallelValidationImporter($this->badSkus);

    expect(runParallelValidation($importer, new CSV($this->fileName, ','), 40))->toBeFalse()
        ->and($importer->invalidSkus)->toBe($this->badSkus)
        ->and($importer->parentValidatedSkus)->toBe($this->badSkus)
        ->and($importer->processedRows())->toBe(40)
        ->and($importer->validatedRowCount())->toBe(40);

    Log::shouldNotHaveReceived('warning');
});

it('validates the whole slice of a worker that was killed', function (): void {
    Log::spy();
    writeParallelValidationCsv($this->fileName, 40);

    $importer = parallelValidationImporter($this->badSkus, 'sku-12', 'kill');

    expect(runParallelValidation($importer, new CSV($this->fileName, ','), 40))->toBeFalse()
        ->and($importer->invalidSkus)->toBe($this->badSkus)
        ->and($importer->parentValidatedSkus)->toBe([
            'sku-3', 'sku-11', 'sku-12', 'sku-13', 'sku-14', 'sku-15', 'sku-16',
            'sku-17', 'sku-18', 'sku-19', 'sku-20', 'sku-27', 'sku-38',
        ])
        ->and($importer->processedRows())->toBe(40)
        ->and($importer->validatedRowCount())->toBe(40);

    Log::shouldHaveReceived('warning')->once();
});

it('validates the whole slice of a worker that threw', function (): void {
    Log::spy();
    writeParallelValidationCsv($this->fileName, 40);

    $importer = parallelValidationImporter($this->badSkus, 'sku-12', 'throw');

    expect(runParallelValidation($importer, new CSV($this->fileName, ','), 40))->toBeFalse()
        ->and($importer->invalidSkus)->toBe($this->badSkus)
        ->and($importer->processedRows())->toBe(40);

    Log::shouldHaveReceived('warning')->once();
});

it('ends a failing worker even when its error cannot be logged', function (): void {
    Log::shouldReceive('error')->andThrow(new UnexpectedValueException('The stream or file could not be opened.'));
    Log::shouldReceive('warning');
    writeParallelValidationCsv($this->fileName, 40);

    $importer = parallelValidationImporter($this->badSkus, 'sku-12', 'throw');

    expect(runParallelValidation($importer, new CSV($this->fileName, ','), 40))->toBeFalse()
        ->and($importer->invalidSkus)->toBe($this->badSkus);
});

it('validates the slices of workers that read fewer rows than assigned', function (): void {
    Log::spy();
    writeParallelValidationCsv($this->fileName, 40);

    $importer = parallelValidationImporter($this->badSkus);

    expect(runParallelValidation($importer, shortReadingSource($this->fileName, 13), 40))->toBeFalse()
        ->and($importer->invalidSkus)->toBe($this->badSkus)
        ->and($importer->processedRows())->toBe(40)
        ->and($importer->validatedRowCount())->toBe(40);

    Log::shouldHaveReceived('warning')->times(3);
});

it('validates rows pushed past the counted total by lines with the wrong column count', function (): void {
    Log::spy();
    writeParallelValidationCsv($this->fileName, 38, [5, 6]);

    $importer = parallelValidationImporter(['sku-2', 'sku-37', 'sku-38']);

    expect(runParallelValidation($importer, new CSV($this->fileName, ','), 38))->toBeFalse()
        ->and($importer->invalidSkus)->toBe(['sku-2', 'sku-37', 'sku-38'])
        ->and($importer->validatedRowCount())->toBe(38);
});
