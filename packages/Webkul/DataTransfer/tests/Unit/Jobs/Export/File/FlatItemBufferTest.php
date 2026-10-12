<?php

use Webkul\DataTransfer\Jobs\Export\File\FlatItemBuffer;

function bufferProp(object $buffer, string $property, mixed $value): void
{
    $reflection = new ReflectionProperty($buffer, $property);
    $reflection->setAccessible(true);
    $reflection->setValue($buffer, $value);
}

/**
 * Minimal writer stub that records the rows handed to addRow() so the buffer's header
 * behaviour can be asserted without touching the filesystem or OpenSpout.
 */
function recordingWriter(): object
{
    return new class
    {
        public array $rows = [];

        public function addRow($row): void
        {
            $this->rows[] = array_map(fn ($cell) => $cell->getValue(), $row->getCells());
        }
    };
}

describe('header label mapping', function () {
    it('returns the keys unchanged when no label map is set', function () {
        expect((new FlatItemBuffer)->buildHeaders(['sku', 'name']))->toBe(['sku', 'name']);
    });

    it('substitutes labels for mapped keys and leaves structural columns as-is', function () {
        $buffer = new FlatItemBuffer;
        bufferProp($buffer, 'headerLabels', ['name' => 'Name', 'color' => 'Colour']);

        expect($buffer->buildHeaders(['sku', 'name', 'color', 'channel']))
            ->toBe(['sku', 'Name', 'Colour', 'channel']);
    });
});

describe('header row toggle', function () {
    it('writes the header row followed by the data row by default', function () {
        $buffer = new FlatItemBuffer;
        $writer = recordingWriter();
        bufferProp($buffer, 'writer', $writer);

        $buffer->addData([['sku' => 'A', 'name' => 'Shirt']]);

        expect($writer->rows)->toBe([
            ['sku', 'name'],
            ['A', 'Shirt'],
        ]);
    });

    it('skips the header row when writeHeaders is disabled', function () {
        $buffer = new FlatItemBuffer;
        $writer = recordingWriter();
        bufferProp($buffer, 'writer', $writer);
        bufferProp($buffer, 'writeHeaders', false);

        $buffer->addData([['sku' => 'A', 'name' => 'Shirt']]);

        expect($writer->rows)->toBe([
            ['A', 'Shirt'],
        ]);
    });
});
