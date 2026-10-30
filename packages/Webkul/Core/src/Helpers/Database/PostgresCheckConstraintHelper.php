<?php

namespace Webkul\Core\Helpers\Database;

use Illuminate\Support\Facades\DB;

class PostgresCheckConstraintHelper
{
    public static function dropColumnChecks(string $table, string $column): void
    {
        $wrappedTable = DB::getQueryGrammar()->wrapTable($table);

        $constraints = DB::select(
            "SELECT c.conname FROM pg_constraint c JOIN pg_attribute a ON a.attrelid = c.conrelid AND a.attnum = ANY (c.conkey) WHERE c.conrelid = ?::regclass AND c.contype = 'c' AND a.attname = ?",
            [$wrappedTable, $column]
        );

        foreach ($constraints as $constraint) {
            DB::statement("ALTER TABLE {$wrappedTable} DROP CONSTRAINT IF EXISTS ".DB::getQueryGrammar()->wrap($constraint->conname));
        }
    }
}
