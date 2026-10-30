<?php

namespace Webkul\Core\Helpers\Database;

use Illuminate\Support\Facades\DB;

class DatabaseSequenceHelper
{
    /**
     * Fix sequence for one or more tables in PostgreSQL.
     * Table names should be provided without table prefix table prefix will be added here
     */
    public static function fixSequences(array $tables): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $tablePrefix = DB::getTablePrefix();

        foreach ($tables as $table) {
            static::fixSequence($table, $tablePrefix);
        }
    }

    public static function fixSequence(string $table, ?string $tablePrefix = null): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        $tableName = DB::getQueryGrammar()->wrapTable($table, $tablePrefix);

        DB::statement("
            SELECT setval(
                pg_get_serial_sequence(?, 'id'),
                (SELECT COALESCE(MAX(id), 0) + 1 FROM {$tableName}),
                false
            )
        ", [$tableName]);
    }
}
