<?php

namespace Webkul\Core\Helpers\Database\Grammars;

use Webkul\Core\Contracts\Database\Grammar;

class PostgresGrammar implements Grammar
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

    public function jsonExtract(string $column, string ...$pathSegments): string
    {
        $pathSegments = array_map([$this, 'escapeJsonPathSegment'], $pathSegments);

        $operators = count($pathSegments) > 1 ? array_map(fn ($part) => "'{$part}'", $pathSegments) : $pathSegments;

        $lastKey = array_pop($operators);

        // Escape column name — handles both 'values' and 'table.values'
        $parts = explode('.', $column);
        $quotedColumn = implode('.', array_map(fn ($p) => '"'.$p.'"', $parts));

        $jsonString = ! empty($operators)
            ? "{$quotedColumn}->".implode('->', $operators)."->>{$lastKey}"
            : "{$quotedColumn}->>'{$lastKey}'";

        return $jsonString;
    }

    public function jsonContains(string $column, array $pathSegments, string $value): string
    {
        $pathSegments = array_map([$this, 'escapeJsonPathSegment'], $pathSegments);

        $parts = explode('.', $column);
        $quotedColumn = implode('.', array_map(fn ($p) => '"'.$p.'"', $parts));
        $operators = array_map(fn ($p) => "'{$p}'", $pathSegments);
        $jsonExpr = $quotedColumn.'->'.implode('->', $operators);

        return "({$jsonExpr})::jsonb @> {$value}::jsonb";
    }

    /**
     * Escape a JSON path segment so it cannot break out of the single-quoted
     * SQL string literal the path is embedded in (SQL injection guard).
     */
    protected function escapeJsonPathSegment(string $segment): string
    {
        return str_replace("'", "''", $segment);
    }

    public function orderByField(string $column, array $values, string $type = 'int'): string
    {
        if ($type === 'int') {
            $values = array_map('intval', $values);
        }

        $idList = implode(',', $values);

        return "array_position(ARRAY[{$idList}]::{$type}[], {$column})";
    }

    public function getRegexOperator(): string
    {
        return '~';
    }

    public function getBooleanValue(mixed $value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 't' : 'f';
    }
}
