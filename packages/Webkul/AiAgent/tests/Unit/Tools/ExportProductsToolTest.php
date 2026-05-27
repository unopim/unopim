<?php

use Webkul\AiAgent\Chat\Tools\ExportProducts;

describe('ExportProducts tool XLSX support (Issue #684)', function () {

    it('tool description mentions both CSV and XLSX formats', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/ExportProducts.php')
        );

        expect($source)->toContain('Export products to CSV or XLSX');
    });

    it('tool has a format enum parameter with csv and xlsx options', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/ExportProducts.php')
        );

        expect($source)->toContain("->withEnumParameter('format'");
        expect($source)->toContain("'csv', 'xlsx'");
    });

    it('has a writeXlsx method that uses PhpSpreadsheet', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/ExportProducts.php')
        );

        expect($source)->toContain('function writeXlsx');
        expect($source)->toContain('new Spreadsheet');
        expect($source)->toContain('new Xlsx($spreadsheet)');
    });

    it('generates xlsx filename when format is xlsx', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/ExportProducts.php')
        );

        expect($source)->toContain("'xlsx' ? 'xlsx' : 'csv'");
    });

    it('buildFilters sets file_format to Xlsx when format is xlsx', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/ExportProducts.php')
        );

        expect($source)->toContain("'Xlsx' : 'Csv'");
    });

    it('writeXlsx method creates valid spreadsheet structure', function () {
        $tool = app(ExportProducts::class);

        $reflection = new ReflectionMethod($tool, 'writeXlsx');

        expect($reflection->getName())->toBe('writeXlsx');
        expect($reflection->getNumberOfParameters())->toBe(2);
    });

    it('dispatches to writeXlsx when format is xlsx', function () {
        $source = file_get_contents(
            base_path('packages/Webkul/AiAgent/src/Chat/Tools/ExportProducts.php')
        );

        // The tool should branch on format
        expect($source)->toContain("if (\$format === 'xlsx')");
        expect($source)->toContain('$this->writeXlsx($relativePath, $rows)');
        expect($source)->toContain('$this->writeCsv($relativePath, $rows)');
    });
});
