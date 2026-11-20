<?php

namespace Webkul\Installer\Database\Seeders\Concerns;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Webkul\Core\Helpers\Database\DatabaseSequenceHelper;

trait SeedsMissingRows
{
    /**
     * Insert the default rows that are not stored yet and return their stored ids keyed by the seeded id.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $matchColumns
     * @return array<int, int>
     */
    protected function insertMissingRows(string $table, array $rows, array $matchColumns = ['code']): array
    {
        if (! DB::table($table)->exists()) {
            DB::table($table)->insert($rows);

            return array_combine(array_column($rows, 'id'), array_column($rows, 'id'));
        }

        DatabaseSequenceHelper::fixSequences([$table]);

        $insertedIds = [];

        foreach ($rows as $row) {
            if (DB::table($table)->where(Arr::only($row, $matchColumns))->exists()) {
                continue;
            }

            $insertedIds[$row['id']] = DB::table($table)->insertGetId(Arr::except($row, 'id'));
        }

        return $insertedIds;
    }

    /**
     * Keep only the rows that belong to a newly inserted row, pointed at its stored id.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  array<int, int>  $insertedIds
     * @return list<array<string, mixed>>
     */
    protected function rowsOfInserted(array $rows, string $column, array $insertedIds): array
    {
        return collect($rows)
            ->filter(fn (array $row): bool => isset($insertedIds[$row[$column]]))
            ->map(fn (array $row): array => [...$row, $column => $insertedIds[$row[$column]]])
            ->values()
            ->all();
    }
}
