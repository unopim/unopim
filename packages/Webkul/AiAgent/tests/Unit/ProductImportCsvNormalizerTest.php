<?php

use Webkul\AiAgent\Services\ProductImportCsvNormalizer;
use Webkul\AiAgent\Services\ProductWriterService;
use Webkul\DataTransfer\Helpers\Formatters\EscapeFormulaOperators;

/**
 * Parse the generated (semicolon-delimited) CSV back into header + rows.
 *
 * @return array{header: array<int, string>, rows: array<int, array<string, string>>}
 */
function parseGeneratedCsv(string $csv): array
{
    $lines = array_values(array_filter(explode("\n", trim($csv)), fn ($l) => $l !== ''));
    $header = str_getcsv(array_shift($lines), ProductImportCsvNormalizer::DELIMITER);

    $rows = [];
    foreach ($lines as $line) {
        $rows[] = array_combine($header, str_getcsv($line, ProductImportCsvNormalizer::DELIMITER));
    }

    return ['header' => $header, 'rows' => $rows];
}

function makeNormalizer(): ProductImportCsvNormalizer
{
    // resolveSelectValuePublic is only called for select/multiselect attributes;
    // the fixtures below use none, so the writer service is never invoked.
    return new ProductImportCsvNormalizer(Mockery::mock(ProductWriterService::class));
}

$familyAttrs = [
    'name'     => ['type' => 'text', 'value_per_locale' => true, 'value_per_channel' => true, 'attribute_id' => 10],
    'price'    => ['type' => 'price', 'value_per_locale' => false, 'value_per_channel' => false, 'attribute_id' => 11],
    'in_stock' => ['type' => 'boolean', 'value_per_locale' => false, 'value_per_channel' => false, 'attribute_id' => 12],
];

it('emits all six permanent columns the core importer requires', function () use ($familyAttrs) {
    $csv = makeNormalizer()->toCsv(
        [['sku' => 'SHOE-1', 'name' => 'Red Shoe']],
        $familyAttrs,
        ['USD'],
        'default',
        'default',
        'en_US',
    );

    $parsed = parseGeneratedCsv($csv);

    foreach (['sku', 'locale', 'channel', 'type', 'parent', 'attribute_family'] as $required) {
        expect($parsed['header'])->toContain($required);
    }
});

it('injects family code, channel, locale, type and default-active status per row', function () use ($familyAttrs) {
    $csv = makeNormalizer()->toCsv(
        [['sku' => 'SHOE-1', 'name' => 'Red Shoe']],
        $familyAttrs,
        ['USD'],
        'default',
        'default',
        'en_US',
    );

    $row = parseGeneratedCsv($csv)['rows'][0];

    expect($row['sku'])->toBe('SHOE-1');
    expect($row['type'])->toBe('simple');
    expect($row['attribute_family'])->toBe('default');
    expect($row['channel'])->toBe('default');
    expect($row['locale'])->toBe('en_US');
    expect($row['parent'])->toBe('');
    expect($row['status'])->toBe('true'); // core only enables on literal "true"
});

it('expands a single price value into one column per active currency as bare decimals', function () use ($familyAttrs) {
    $csv = makeNormalizer()->toCsv(
        [['sku' => 'SHOE-1', 'price' => '19.9']],
        $familyAttrs,
        ['USD', 'EUR'],
        'default',
        'default',
        'en_US',
    );

    $parsed = parseGeneratedCsv($csv);

    expect($parsed['header'])->toContain('price (USD)');
    expect($parsed['header'])->toContain('price (EUR)');
    expect($parsed['rows'][0]['price (USD)'])->toBe('19.9');
    expect($parsed['rows'][0]['price (EUR)'])->toBe('19.9');
});

it('formats boolean attributes as the true/false literals core requires', function () use ($familyAttrs) {
    $csv = makeNormalizer()->toCsv(
        [
            ['sku' => 'A', 'in_stock' => '1'],
            ['sku' => 'B', 'in_stock' => '0'],
        ],
        $familyAttrs,
        ['USD'],
        'default',
        'default',
        'en_US',
    );

    $rows = parseGeneratedCsv($csv)['rows'];

    expect($rows[0]['in_stock'])->toBe('true');
    expect($rows[1]['in_stock'])->toBe('false');
});

it('honours an explicit inactive status', function () use ($familyAttrs) {
    $csv = makeNormalizer()->toCsv(
        [['sku' => 'A', 'status' => 'inactive', 'name' => 'X']],
        $familyAttrs,
        ['USD'],
        'default',
        'default',
        'en_US',
    );

    expect(parseGeneratedCsv($csv)['rows'][0]['status'])->toBe('false');
});

it('never emits an unknown column that would hard-fail the whole core import', function () use ($familyAttrs) {
    $csv = makeNormalizer()->toCsv(
        [['sku' => 'A', 'name' => 'X', 'totally_unknown_col' => 'boom']],
        $familyAttrs,
        ['USD'],
        'default',
        'default',
        'en_US',
    );

    expect(parseGeneratedCsv($csv)['header'])->not->toContain('totally_unknown_col');
});

it('uses a semicolon delimiter so comma multi-value cells stay intact', function () use ($familyAttrs) {
    $csv = makeNormalizer()->toCsv(
        [['sku' => 'A', 'name' => 'X', 'categories' => 'root,men,shirts']],
        $familyAttrs,
        ['USD'],
        'default',
        'default',
        'en_US',
    );

    expect(ProductImportCsvNormalizer::DELIMITER)->toBe(';');

    $parsed = parseGeneratedCsv($csv);
    expect($parsed['header'])->toContain('categories');
    expect($parsed['rows'][0]['categories'])->toBe('root,men,shirts');
});

it('escapes spreadsheet formula operators in cell values to prevent CSV injection', function () use ($familyAttrs) {
    $csv = makeNormalizer()->toCsv(
        [['sku' => 'A', 'name' => '=cmd']],
        $familyAttrs,
        ['USD'],
        'default',
        'default',
        'en_US',
    );

    // The stored CSV is downloadable from import history and may be opened in
    // a spreadsheet, so an operator-leading value must be neutralized.
    expect(parseGeneratedCsv($csv)['rows'][0]['name'])->toBe("'=cmd'");
});

it('escaped values round-trip back to the original through the core unescaper', function () use ($familyAttrs) {
    $csv = makeNormalizer()->toCsv(
        [['sku' => 'A', 'name' => '=cmd']],
        $familyAttrs,
        ['USD'],
        'default',
        'default',
        'en_US',
    );

    $stored = parseGeneratedCsv($csv)['rows'][0]['name'];

    expect(EscapeFormulaOperators::unescapeValue($stored))->toBe('=cmd');
});

it('auto-generates url_key from the product name when the family defines it and the row omits it', function () {
    $attrs = [
        'name'    => ['type' => 'text', 'value_per_locale' => true, 'value_per_channel' => true, 'attribute_id' => 10],
        'url_key' => ['type' => 'text', 'value_per_locale' => false, 'value_per_channel' => false, 'attribute_id' => 13],
    ];

    $csv = makeNormalizer()->toCsv(
        [['sku' => 'SHOE-1', 'name' => 'Red Running Shoe']],
        $attrs,
        ['USD'],
        'default',
        'default',
        'en_US',
    );

    $parsed = parseGeneratedCsv($csv);
    expect($parsed['header'])->toContain('url_key');
    expect($parsed['rows'][0]['url_key'])->toBe('red-running-shoe');
});
