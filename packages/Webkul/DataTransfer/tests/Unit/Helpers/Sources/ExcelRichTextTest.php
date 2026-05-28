<?php

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\RichText\Run;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Webkul\DataTransfer\Helpers\Sources\Excel;

it('should read cells containing RichText (bold/styled) as plain strings without errors', function () {
    $spreadsheet = new Spreadsheet;
    $sheet = $spreadsheet->getActiveSheet();

    // Header row — plain strings
    $sheet->setCellValue('A1', 'sku');
    $sheet->setCellValue('B1', 'name');
    $sheet->setCellValue('C1', 'description');

    // Data row — sku is plain, name has RichText with bold
    $sheet->setCellValue('A2', 'TEST-SKU-001');

    $richText = new RichText;
    $boldRun = new Run('Bold Product Name');
    $boldRun->getFont()->setBold(true);
    $richText->addText($boldRun);
    $sheet->getCell('B2')->setValue($richText);

    $richDescription = new RichText;
    $normalRun = new Run('This is ');
    $richDescription->addText($normalRun);
    $boldPart = new Run('formatted');
    $boldPart->getFont()->setBold(true);
    $richDescription->addText($boldPart);
    $plainPart = new Run(' description text.');
    $richDescription->addText($plainPart);
    $sheet->getCell('C2')->setValue($richDescription);

    // Write to a temp file
    $writer = new Xlsx($spreadsheet);
    $tempPath = 'test-richtext-import.xlsx';
    $writer->save(Storage::disk('private')->path($tempPath));

    // Read via the Excel source — this should NOT throw
    $source = new Excel($tempPath);
    $source->rewind();

    expect($source->valid())->toBeTrue();

    $row = $source->current();

    expect($row['sku'])->toBe('TEST-SKU-001');
    expect($row['name'])->toBeString();
    expect($row['name'])->toBe('Bold Product Name');
    expect($row['description'])->toBeString();
    expect($row['description'])->toContain('formatted');
    expect($row['description'])->toBe('This is formatted description text.');

    // Cleanup
    Storage::disk('private')->delete($tempPath);
});
