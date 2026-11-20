<?php

namespace Webkul\Installer\Database\Seeders\Concerns;

use Illuminate\Database\Query\Builder;
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
            if ($this->rowIsStored($table, Arr::only($row, $matchColumns))) {
                continue;
            }

            $insertedIds[$row['id']] = DB::table($table)->insertGetId(Arr::except($row, 'id'));
        }

        return $insertedIds;
    }

    /**
     * Resolve the stored ids of these codes, matching case-insensitively and preferring an exact match.
     *
     * @param  list<string>  $codes
     * @return array<string, int>
     */
    protected function idsByCode(Builder $query, array $codes): array
    {
        $rows = $query
            ->whereIn(DB::raw('LOWER('.$query->getGrammar()->wrap('code').')'), array_map(mb_strtolower(...), $codes))
            ->get(['id', 'code']);

        return collect($codes)
            ->mapWithKeys(fn (string $code): array => [
                $code => ($rows->firstWhere('code', $code) ?? $rows->first(fn (object $row): bool => mb_strtolower($row->code) === mb_strtolower($code)))?->id,
            ])
            ->filter()
            ->map(fn (int|string $id): int => (int) $id)
            ->all();
    }

    /**
     * Check whether a row with these values is stored, comparing text case-insensitively on every database.
     *
     * @param  array<string, mixed>  $values
     */
    protected function rowIsStored(string $table, array $values): bool
    {
        $query = DB::table($table);

        foreach ($values as $column => $value) {
            is_string($value)
                ? $query->whereRaw('LOWER('.$query->getGrammar()->wrap($column).') = ?', [mb_strtolower($value)])
                : $query->where($column, $value);
        }

        return $query->exists();
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
