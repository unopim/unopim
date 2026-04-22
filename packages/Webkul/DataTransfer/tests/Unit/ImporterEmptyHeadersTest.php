<?php

it('should ignore trailing empty headers when validating spreadsheet columns (Issue #725)', function () {
    $source = new class
    {
        public function getColumnNames(): array
        {
            // Simulate an XLSX that has 6 real columns followed by 1000 empty trailing ones.
            return array_merge(['locale', 'code', 'parent', 'name', 'description', 'productCounts'], array_fill(0, 1000, ''));
        }
    };

    $importer = new class($source)
    {
        public function __construct(public $src) {}

        public function run(): array
        {
            $errors = [];
            $columnNames = $this->src->getColumnNames();
            while (! empty($columnNames) && empty(trim((string) end($columnNames)))) {
                array_pop($columnNames);
            }

            foreach ($columnNames as $columnNumber => $columnName) {
                if (empty(trim((string) $columnName))) {
                    $errors[] = $columnNumber + 1;
                }
            }

            return $errors;
        }
    };

    expect($importer->run())->toBe([]);
})->group('importer-headers');
