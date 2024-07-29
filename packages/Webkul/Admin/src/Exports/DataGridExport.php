<?php

namespace Webkul\Admin\Exports;

use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithCustomCsvSettings;

class DataGridExport implements FromGenerator, WithCustomCsvSettings
{
    /**
     * Create a new instance.
     *
     * @param mixed DataGrid
     * @return void
     */
    public function __construct(protected $gridData = []) {}

    /**
     * generator to create large excel files without loading everything in memory at once with generator
     */
    public function generator(): \Generator
    {
        [$columns, $records] = $this->getColumnsAndRecords();

        yield $columns;

        foreach ($records as $record) {
            yield $this->getRecordData($record, $columns);
        }
    }

    /**
     * return columns and records from grid data
     */
    protected function getColumnsAndRecords(): array
    {
        if (isset($this->gridData['columns']) && is_array($this->gridData['columns'])) {
            return [
                $this->gridData['columns'],
                $this->gridData['records'],
            ];
        }

        $columns = [];

        $records = $this->gridData;

        foreach ($this->gridData as $key => $gridData) {
            $columns = array_keys((array) $gridData);

            break;
        }

        return [$columns, $records];
    }

    /**
     * format record data in sort order of columns to display correct data for each column
     */
    protected function getRecordData(mixed $record, array $columns): array
    {
        $record = (array) $record;

        $recordData = [];

        foreach ($columns as $column) {
            $recordData[$column] = $record[$column] ?? '';
        }

        return $recordData;
    }

    /**
     * Settings for csv export
     */
    public function getCsvSettings(): array
    {
        return [
            'delimiter' => ';',
        ];
    }
}
