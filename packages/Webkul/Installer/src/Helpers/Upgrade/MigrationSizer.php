<?php

namespace Webkul\Installer\Helpers\Upgrade;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Estimates how long the pending migrations will hold the installation offline,
 * so a maintenance window can be booked before anything is downloaded.
 *
 * The estimate is deliberately pessimistic: the per-table throughputs in
 * `config('upgrade.sizing_tables')` assume a modest server under load, and a
 * fast one will simply finish early.
 */
class MigrationSizer
{
    public function __construct(protected MigrationInspector $migrations) {}

    /**
     * @return array{tables: array<string, int>, pending: array<int, array{name: string, irreversible: bool}>, seconds: int}
     */
    public function estimate(): array
    {
        $tables = $this->rowCounts();

        $pending = $this->classifyPending();

        return [
            'tables'  => $tables,
            'pending' => $pending,
            'seconds' => $this->estimateSeconds($tables, count($pending)),
        ];
    }

    /**
     * @return array<string, int>
     */
    protected function rowCounts(): array
    {
        $counts = [];

        foreach (array_keys((array) config('upgrade.sizing_tables')) as $table) {
            if (Schema::hasTable($table)) {
                $counts[$table] = DB::table($table)->count();
            }
        }

        return $counts;
    }

    /**
     * @return array<int, array{name: string, irreversible: bool}>
     */
    protected function classifyPending(): array
    {
        $irreversible = (array) config('upgrade.irreversible_migrations');

        return array_map(
            fn (string $name): array => [
                'name'         => $name,
                'irreversible' => in_array($name, $irreversible, true),
            ],
            $this->migrations->pending()
        );
    }

    /**
     * @param  array<string, int>  $tables
     */
    protected function estimateSeconds(array $tables, int $pendingCount): int
    {
        $throughputs = (array) config('upgrade.sizing_tables');

        $seconds = 0;

        foreach ($tables as $table => $rows) {
            $perSecond = max(1, (int) ($throughputs[$table] ?? 1000));

            $seconds += (int) ceil($rows / $perSecond);
        }

        /**
         * Schema-only migrations still cost a round trip each; without this the
         * estimate reads as zero on an empty catalog and understates the
         * window on installations whose data lives in tables not sized here.
         */
        return $seconds + $pendingCount;
    }
}
