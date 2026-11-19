<?php

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

function xlsExportFileBuffer(string $fileFormat): FlatItemBuffer
{
    $exporter = app(Exporter::class);
    $exporter->setExport(xlsExportJobTrack($fileFormat));
    $exporter->getFilters();

    return $exporter->initializeFileBuffer();
}

afterEach(function () {
    if (isset($this->exportPath)) {
        @unlink($this->exportPath);
    }
});

it('writes a genuine Excel 97-2003 file when the export job asks for the Xls format', function () {
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

it('keeps writing an xlsx file when the export job asks for the Xlsx format', function () {
    $buffer = xlsExportFileBuffer('Xlsx');
    $buffer->addData([['sku' => 'XLSX-1']]);
    $buffer->writerClose();

    $this->exportPath = $buffer->getFilePath()->getLocalPath();

    expect($this->exportPath)->toEndWith('.xlsx')
        ->and(IOFactory::identify($this->exportPath))->toBe('Xlsx');
});

it('stops an Xls export that has more columns than an XLS sheet can hold', function () {
    $buffer = xlsExportFileBuffer('Xls');

    $this->exportPath = $buffer->getFilePath()->getLocalPath();

    $row = array_fill_keys(
        array_map(fn (int $index) => 'column_'.$index, range(1, XlsFileConverter::MAX_COLUMNS + 1)),
        'value',
    );

    expect(fn () => $buffer->addData([$row]))->toThrow(
        RuntimeException::class,
        trans('data_transfer::app.exporters.xls-too-many-columns', ['limit' => XlsFileConverter::MAX_COLUMNS]),
    );
});

it('stops an Xls export that has more rows than an XLS sheet can hold', function () {
    $buffer = xlsExportFileBuffer('Xls');

    $this->exportPath = $buffer->getFilePath()->getLocalPath();

    $rows = (function () {
        for ($index = 1; $index <= XlsFileConverter::MAX_ROWS; $index++) {
            yield ['sku' => 'XLS-'.$index];
        }
    })();

    expect(fn () => $buffer->addData($rows))->toThrow(
        RuntimeException::class,
        trans('data_transfer::app.exporters.xls-too-many-rows', ['limit' => number_format(XlsFileConverter::MAX_ROWS)]),
    );

    expect($buffer->count())->toBe(XlsFileConverter::MAX_ROWS - 1);
});

it('removes the partial xls file when the conversion fails', function () {
    $temporaryFile = tempnam(sys_get_temp_dir(), 'xls-convert-');
    unlink($temporaryFile);

    $this->exportPath = $temporaryFile.'.xls';

    $writer = new XlsxWriter;
    $writer->openToFile($this->exportPath);
    $writer->addRow(Row::fromValues(array_fill(0, XlsFileConverter::MAX_COLUMNS + 1, 'value')));
    $writer->close();

    expect(fn () => (new XlsFileConverter)->convert($this->exportPath))
        ->toThrow(SpreadsheetWriterException::class);

    expect(file_exists($this->exportPath.'.converting'))->toBeFalse();
});

it('marks the export as failed with the reason when writing the file fails', function () {
    $jobTrack = xlsExportJobTrack('Xls');

    (new Completed($jobTrack, $jobTrack->id, []))->failed(new RuntimeException('XLS limit reached'));

    $jobTrack->refresh();

    expect($jobTrack->state)->toBe(Export::STATE_FAILED)
        ->and($jobTrack->errors)->toBe(['XLS limit reached'])
        ->and($jobTrack->completed_at)->not->toBeNull();
});
