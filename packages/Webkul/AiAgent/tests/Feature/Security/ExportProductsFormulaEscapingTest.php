<?php

use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Webkul\AiAgent\Chat\Tools\ExportProducts;
use Webkul\DataTransfer\Support\FormulaGuard;

const DDE_PAYLOAD = '=cmd|\'/c calc.exe\'!A0';

it('prefixes a formula-shaped CSV cell so it is read as text', function () {
    Storage::fake('public');

    app(ExportProducts::class)->writeCsv('security/export.csv', [
        ['sku', 'name'],
        ['SKU-1', DDE_PAYLOAD],
    ]);

    expect(Storage::disk('public')->get('security/export.csv'))->toContain("'".DDE_PAYLOAD);
});

it('pins a formula-shaped XLSX cell to text so it never becomes a live formula', function () {
    Storage::fake('public');

    app(ExportProducts::class)->writeXlsx('security/export.xlsx', [
        ['sku', 'name', 'qty'],
        ['SKU-1', '=1+1', 42],
    ]);

    $sheet = IOFactory::load(Storage::disk('public')->path('security/export.xlsx'))->getActiveSheet();

    expect($sheet->getCell('B2')->getDataType())->toBe(DataType::TYPE_STRING)
        ->and($sheet->getCell('B2')->getValue())->toBe('=1+1');
});

it('leaves a numeric XLSX cell typed as a number', function () {
    Storage::fake('public');

    app(ExportProducts::class)->writeXlsx('security/numeric.xlsx', [
        ['sku', 'qty'],
        ['SKU-1', 42],
    ]);

    $cell = IOFactory::load(Storage::disk('public')->path('security/numeric.xlsx'))
        ->getActiveSheet()
        ->getCell('B2');

    expect($cell->getDataType())->toBe(DataType::TYPE_NUMERIC);
});

it('leaves ordinary and numeric values untouched', function () {
    expect(FormulaGuard::escape('Blue Shirt'))->toBe('Blue Shirt')
        ->and(FormulaGuard::escape('19.99'))->toBe('19.99')
        ->and(FormulaGuard::escape('-5'))->toBe('-5')
        ->and(FormulaGuard::escape(null))->toBeNull();
});

it('flags every character a spreadsheet treats as a formula opener', function () {
    foreach (['=1+1', '+cmd|calc', '-cmd', '@SUM(A1)', "\t=1", "\r=1", '  =1'] as $value) {
        expect(FormulaGuard::isFormula($value))->toBeTrue();
    }
});

it('treats a signed number as a number, not a formula', function () {
    expect(FormulaGuard::isFormula('+1'))->toBeFalse()
        ->and(FormulaGuard::isFormula('-5.5'))->toBeFalse();
});
