<?php

use Illuminate\Support\Facades\File;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Exception as SpreadsheetWriterException;
use Webkul\DataTransfer\Helpers\Export;
use Webkul\DataTransfer\Helpers\Exporters\Product\Exporter;
use Webkul\DataTransfer\Jobs\Export\Completed;
use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer;
use Webkul\DataTransfer\Jobs\Export\File\XlsFileConverter;
use Webkul\DataTransfer\Models\JobInstances;
use Webkul\DataTransfer\Models\JobTrack;

/**
 * Create a product export job track for the given file format.
 */
function xlsExportJobTrack(string $fileFormat): JobTrack
{
    $jobInstance = JobInstances::create([
        'code'                => 'xls_format_export_'.uniqid(),
        'entity_type'         => 'products',
        'type'                => 'export',
        'action'              => 'export',
        'validation_strategy' => 'stop-on-errors',
        'filters'             => ['file_format' => $fileFormat],
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

/**
 * Create a product exporter for an export job in the given file format.
 */
function xlsProductExporter(string $fileFormat): Exporter
{
    $exporter = app(Exporter::class);
    $exporter->setExport(xlsExportJobTrack($fileFormat));
    $exporter->getFilters();

    return $exporter;
}

/**
 * Initialize the product exporter's file buffer for the given file format.
 */
function xlsExportFileBuffer(string $fileFormat): FlatItemBuffer
{
    return xlsProductExporter($fileFormat)->initializeFileBuffer();
}

/**
 * Write a one-row XLSX workbook at a fresh temporary path ending in .xls and return the path.
 */
function xlsxWorkbookNamedXls(array $values): string
{
    $temporaryFile = tempnam(sys_get_temp_dir(), 'xls-convert-');
    unlink($temporaryFile);

    $path = $temporaryFile.'.xls';

    $writer = new XlsxWriter;
    $writer->openToFile($path);
    $writer->addRow(Row::fromValues($values));
    $writer->close();

    return $path;
}

afterEach(function (): void {
    if (isset($this->exportPath)) {
        @unlink($this->exportPath);
    }
});

it('writes a genuine Excel 97-2003 file when the export job asks for the Xls format', function (): void {
    $buffer = xlsExportFileBuffer('Xls');
    $buffer->addData([
        ['sku' => '00123', 'name' => '=1+1', 'price' => 19.5],
        ['sku' => 'XLS-2', 'name' => 'Shirt', 'price' => 7],
    ]);
    $buffer->writerClose();

    $this->exportPath = $buffer->getFilePath()->getLocalPath();

    $rows = IOFactory::createReader('Xls')->load($this->exportPath)->getActiveSheet()->toArray(null, false, false);

    expect($this->exportPath)->toEndWith('.xls')
        ->and(IOFactory::identify($this->exportPath))->toBe('Xls')
        ->and($rows)->toBe([
            ['sku', 'name', 'price'],
            ['00123', "'=1+1", 19.5],
            ['XLS-2', 'Shirt', 7],
        ]);
});

it('keeps writing an xlsx file when the export job asks for the Xlsx format', function (): void {
    $buffer = xlsExportFileBuffer('Xlsx');
    $buffer->addData([['sku' => 'XLSX-1']]);
    $buffer->writerClose();

    $this->exportPath = $buffer->getFilePath()->getLocalPath();

    expect($this->exportPath)->toEndWith('.xlsx')
        ->and(IOFactory::identify($this->exportPath))->toBe('Xlsx');
});

it('stops an Xls export that has more columns than an XLS sheet can hold', function (): void {
    $buffer = xlsExportFileBuffer('Xls');

    $this->exportPath = $buffer->getFilePath()->getLocalPath();

    $row = array_fill_keys(
        array_map(fn (int $index): string => 'column_'.$index, range(1, XlsFileConverter::MAX_COLUMNS + 1)),
        'value',
    );

    expect(fn (): mixed => $buffer->addData([$row]))->toThrow(
        RuntimeException::class,
        trans('data_transfer::app.exporters.xls-too-many-columns', ['limit' => XlsFileConverter::MAX_COLUMNS]),
    );
});

it('stops an Xls export that has more rows than an XLS sheet can hold', function (): void {
    $buffer = xlsExportFileBuffer('Xls');

    $this->exportPath = $buffer->getFilePath()->getLocalPath();

    $rows = (function (): Generator {
        for ($index = 1; $index <= XlsFileConverter::MAX_ROWS; $index++) {
            yield ['sku' => 'XLS-'.$index];
        }
    })();

    expect(fn (): mixed => $buffer->addData($rows))->toThrow(
        RuntimeException::class,
        trans('data_transfer::app.exporters.xls-too-many-rows', ['limit' => number_format(XlsFileConverter::MAX_ROWS)]),
    );

    expect($buffer->count())->toBe(XlsFileConverter::MAX_ROWS - 1);
});

it('removes the partial xls file when the conversion fails', function (): void {
    $this->exportPath = xlsxWorkbookNamedXls(array_fill(0, XlsFileConverter::MAX_COLUMNS + 1, 'value'));

    expect(fn (): mixed => (new XlsFileConverter)->convert($this->exportPath))
        ->toThrow(SpreadsheetWriterException::class);

    expect(file_exists($this->exportPath.'.converting'))->toBeFalse();
});

it('fails loudly when the converted file cannot replace the export file', function (): void {
    $this->exportPath = xlsxWorkbookNamedXls(['sku', 'name']);

    File::partialMock()->shouldReceive('move')->once()->andReturnFalse();

    expect(fn (): mixed => (new XlsFileConverter)->convert($this->exportPath))->toThrow(
        RuntimeException::class,
        trans('data_transfer::app.exporters.xls-conversion-failed'),
    );

    expect(file_exists($this->exportPath.'.converting'))->toBeFalse();
});

it('rejects an Xls export over the row limit before any batch is dispatched', function (): void {
    $guard = new ReflectionMethod(Exporter::class, 'guardAgainstOversizedExport');

    expect(fn (): mixed => $guard->invoke(xlsProductExporter('Xls'), XlsFileConverter::MAX_ROWS, 2))->toThrow(
        RuntimeException::class,
        trans('data_transfer::app.exporters.xls-too-many-rows', ['limit' => number_format(XlsFileConverter::MAX_ROWS)]),
    );

    $guard->invoke(xlsProductExporter('Csv'), XlsFileConverter::MAX_ROWS, 2);
});

it('rejects an Xls export with more cells than can be converted', function (): void {
    $memoryLimit = ini_get('memory_limit');
    ini_set('memory_limit', '-1');

    try {
        expect(fn (): mixed => (new XlsFileConverter)->assertFits(10_000, 100))->toThrow(
            RuntimeException::class,
            trans('data_transfer::app.exporters.xls-too-large', [
                'rows'    => number_format(10_000),
                'columns' => number_format(100),
                'limit'   => number_format(XlsFileConverter::MAX_CELLS),
            ]),
        );

        (new XlsFileConverter)->assertFits(5_000, 100);
    } finally {
        ini_set('memory_limit', $memoryLimit);
    }
});

it('lowers the cell limit to the memory left under memory_limit', function (): void {
    $memoryLimit = ini_get('memory_limit');
    ini_set('memory_limit', (string) (memory_get_usage(true) + 64 * 1024 * 1024));

    try {
        expect((new XlsFileConverter)->cellLimit())
            ->toBeGreaterThan(0)
            ->toBeLessThanOrEqual(intdiv(64 * 1024 * 1024, XlsFileConverter::BYTES_PER_CELL));
    } finally {
        ini_set('memory_limit', $memoryLimit);
    }
});

it('marks the export as failed with the reason when writing the file fails', function (): void {
    $jobTrack = xlsExportJobTrack('Xls');

    (new Completed($jobTrack, $jobTrack->id, []))->failed(new RuntimeException('XLS limit reached'));

    $jobTrack->refresh();

    expect($jobTrack->state)->toBe(Export::STATE_FAILED)
        ->and($jobTrack->errors)->toBe(['XLS limit reached'])
        ->and($jobTrack->completed_at)->not->toBeNull();
});

it('keeps errors recorded after dispatch when the export fails', function (): void {
    $jobTrack = xlsExportJobTrack('Xls');
    $job = new Completed($jobTrack, $jobTrack->id, []);

    JobTrack::find($jobTrack->id)->update(['errors' => ['Batch error']]);

    $job->failed(new RuntimeException('XLS limit reached'));

    expect($jobTrack->fresh()->errors)->toBe(['Batch error', 'XLS limit reached']);
});
