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
        $operators = count($pathSegments) > 1 ? array_map(fn ($part) => "'{$part}'", $pathSegments) : $pathSegments;

        $lastKey = array_pop($operators);

        $jsonString = ! empty($operators)
            ? "{$column}->".implode('->', $operators)."->>{$lastKey}"
            : "{$column}->>'{$lastKey}'";

        return $jsonString;
    }

    public function orderByField(string $column, array $values, string $type = 'int'): string
    {
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
