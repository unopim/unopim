<?php

namespace Webkul\Core\Helpers\Database\Grammars;

class PostgresGrammar implements BaseGrammar
{
    public function groupConcat(
        string $column,
        ?string $alias = null,
        ?string $orderBy = null,
        bool $distinct = false,
        string $separator = ', '
    ): string {
        $colExpr = $distinct ? "DISTINCT {$column}" : $column;
        $expr = "STRING_AGG({$colExpr}, '{$separator}'";

        if ($orderBy) {
            $expr .= " ORDER BY {$orderBy} ASC";
        }

        $expr .= ')';

        return $alias ? "{$expr} AS {$alias}" : $expr;
    }

    public function concat(string ...$parts): string
    {
        return '('.implode(' || ', $parts).')';
    }

    public function coalesce(array $columns, ?string $alias = null): string
    {
        $expr = 'COALESCE('.implode(', ', $columns).')';

        return $alias ? "{$expr} AS {$alias}" : $expr;
    }

    public function length(string $column): string
    {
        return "LENGTH({$column})";
    }
}
