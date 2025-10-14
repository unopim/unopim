<?php

namespace Webkul\Core\Helpers\Database\Grammars;

class MySQLGrammar implements BaseGrammar
{
    public function groupConcat(
        string $column,
        ?string $alias = null,
        ?string $orderBy = null,
        bool $distinct = false,
        string $separator = ', '
    ): string {
        $distinctSql = $distinct ? 'DISTINCT ' : '';

        $orderBySql = $orderBy ? " ORDER BY {$orderBy} ASC" : '';

        $expr = "GROUP_CONCAT({$distinctSql}{$column}{$orderBySql} SEPARATOR '{$separator}')";

        return $alias ? "{$expr} AS {$alias}" : $expr;
    }

    public function concat(string ...$parts): string
    {
        $joined = implode(', ', $parts);

        return "CONCAT({$joined})";
    }

    public function coalesce(array $columns, ?string $alias = null): string
    {
        $expr = 'COALESCE('.implode(', ', $columns).')';

        return $alias ? "{$expr} AS {$alias}" : $expr;
    }

    public function length(string $column): string
    {
        return "CHAR_LENGTH({$column})";
    }
}
