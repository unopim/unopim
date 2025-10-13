<?php

namespace Webkul\Core\Helpers;

use Illuminate\Support\Facades\DB;

class DatabaseGrammarBuilder
{
    protected array $parts = [];

    public function add(string $sql): static
    {
        $this->parts[] = $sql;

        return $this;
    }

    public function concat(...$parts): string
    {
        $driver = DB::getDriverName();
        $joined = implode(', ', $parts);

        if ($driver === 'pgsql') {
            return '('.implode(' || ', $parts).')';
        }

        return "CONCAT({$joined})";
    }

    public function coalesce(array $columns, ?string $alias = null): string
    {
        $expr = 'COALESCE('.implode(', ', $columns).')';

        return $alias ? "{$expr} AS {$alias}" : $expr;
    }

    public function groupConcat(
        string $column,
        ?string $alias = null,
        ?string $orderBy = null,
        bool $distinct = false,
        string $separator = ', '
    ): string {
        $driver = DB::getDriverName();
        $distinctSql = $distinct ? 'DISTINCT ' : '';
        $orderBySql = $orderBy ? " ORDER BY {$orderBy} ASC" : '';

        if ($driver === 'pgsql') {
            $colExpr = $distinct ? "DISTINCT {$column}" : $column;
            $expr = "STRING_AGG({$colExpr}, '{$separator}'";
            if ($orderBy) {
                $expr .= " ORDER BY {$orderBy} ASC";
            }
            $expr .= ')';
        } else {
            $expr = "GROUP_CONCAT({$distinctSql}{$column}{$orderBySql} SEPARATOR '{$separator}')";
        }

        return $alias ? "{$expr} AS {$alias}" : $expr;
    }

    public function jsonAgg(string $column, ?string $alias = null, bool $distinct = false): string
    {
        $driver = DB::getDriverName();
        $distinctSql = $distinct ? 'DISTINCT ' : '';

        if ($driver === 'pgsql') {
            $expr = "JSON_AGG({$distinctSql}{$column})";
        } else {
            $expr = "JSON_ARRAYAGG({$distinctSql}{$column})";
        }

        return $alias ? "{$expr} AS {$alias}" : $expr;
    }

    public function case(string $condition, string $then, ?string $else = null, ?string $alias = null): string
    {
        $expr = "CASE WHEN {$condition} THEN {$then}";
        if ($else !== null) {
            $expr .= " ELSE {$else}";
        }
        $expr .= ' END';

        return $alias ? "{$expr} AS {$alias}" : $expr;
    }

    public function toSql(): string
    {
        return implode(', ', $this->parts);
    }

    public function toRaw()
    {
        return DB::raw($this->toSql());
    }
}
