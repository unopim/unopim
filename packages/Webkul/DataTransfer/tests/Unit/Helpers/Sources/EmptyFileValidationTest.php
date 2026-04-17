<?php

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Webkul\DataTransfer\Helpers\Sources\CSV;
use Webkul\DataTransfer\Helpers\Sources\Excel;

describe('Empty file import validation', function () {

    it('throws a user-friendly error when importing an empty CSV file', function () {
        Storage::disk('private')->put('test-empty.csv', '');

        try {
            new CSV('test-empty.csv', ',');
            $this->fail('Expected LogicException was not thrown');
        } catch (LogicException $e) {
            expect($e->getMessage())->toBe(trans('data_transfer::app.validation.errors.file-empty'));
        } finally {
            Storage::disk('private')->delete('test-empty.csv');
        }
    });

    it('throws a user-friendly error when importing an empty XLS/XLSX file', function () {
        $spreadsheet = new Spreadsheet;
        $writer = new Xlsx($spreadsheet);

        $path = Storage::disk('private')->path('test-empty.xlsx');
        $writer->save($path);

        try {
            new Excel('test-empty.xlsx');
            $this->fail('Expected LogicException was not thrown');
        } catch (LogicException $e) {
            expect($e->getMessage())->toBe(trans('data_transfer::app.validation.errors.file-empty'));
        } finally {
            Storage::disk('private')->delete('test-empty.xlsx');
        }
    });

    it('throws a user-friendly error when importing a CSV file with only empty lines', function () {
        Storage::disk('private')->put('test-blank-lines.csv', "\n\n\n");

        try {
            new CSV('test-blank-lines.csv', ',');
            $this->fail('Expected LogicException was not thrown');
        } catch (LogicException $e) {
            expect($e->getMessage())->toBe(trans('data_transfer::app.validation.errors.file-empty'));
        } finally {
            Storage::disk('private')->delete('test-blank-lines.csv');
        }
    });

    it('reads CSV files with valid headers successfully', function () {
        $csvContent = "sku,name,status\n";
        $csvContent .= "prod-1,\"Product One\",1\n";

        Storage::disk('private')->put('test-valid.csv', $csvContent);

        $source = new CSV('test-valid.csv', ',');

        expect($source->getColumnNames())->toBe(['sku', 'name', 'status']);

        Storage::disk('private')->delete('test-valid.csv');
    });

    it('reads XLSX files with valid headers successfully', function () {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'sku');
        $sheet->setCellValue('B1', 'name');
        $sheet->setCellValue('C1', 'status');
        $sheet->setCellValue('A2', 'prod-1');
        $sheet->setCellValue('B2', 'Product One');
        $sheet->setCellValue('C2', '1');

        $writer = new Xlsx($spreadsheet);
        $path = Storage::disk('private')->path('test-valid.xlsx');
        $writer->save($path);

        $source = new Excel('test-valid.xlsx');

        expect($source->getColumnNames())->toBe(['sku', 'name', 'status']);

        Storage::disk('private')->delete('test-valid.xlsx');
    });
});

describe('AbstractSource::getTotalColumns returns correct integer', function () {

    it('returns the correct column count for CSV source', function () {
        $csvContent = "sku,name,status\n";
        $csvContent .= "prod-1,\"Product One\",1\n";

        Storage::disk('private')->put('test-columns.csv', $csvContent);

        $source = new CSV('test-columns.csv', ',');

        expect($source->getTotalColumns())->toBe(3)
            ->and($source->getTotalColumns())->toBeInt();

        Storage::disk('private')->delete('test-columns.csv');
    });

    it('returns the correct column count for Excel source', function () {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'sku');
        $sheet->setCellValue('B1', 'name');
        $sheet->setCellValue('A2', 'prod-1');
        $sheet->setCellValue('B2', 'Product One');

        $writer = new Xlsx($spreadsheet);
        $path = Storage::disk('private')->path('test-columns.xlsx');
        $writer->save($path);

        $source = new Excel('test-columns.xlsx');

        expect($source->getTotalColumns())->toBe(2)
            ->and($source->getTotalColumns())->toBeInt();

        Storage::disk('private')->delete('test-columns.xlsx');
    });
});
