<?php

namespace Webkul\Core\Helpers\Database\Grammars;

use Webkul\Core\Contracts\Database\Grammar;

class SQLiteGrammar implements Grammar
{
    public function groupConcat(
        string $column,
        ?string $alias = null,
        ?string $orderBy = null,
        bool $distinct = false,
        string $separator = ', '
    ): string {
        // SQLite doesn't support DISTICT or ORDER BY in GROUP_CONCAT easily
        $expr = "GROUP_CONCAT({$column}, '{$separator}')";

        return $alias ? "{$expr} AS {$alias}" : $expr;
    }

    public function concat(string ...$parts): string
    {
        return implode(' || ', $parts);
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
        $jsonPath = '$.'.implode('.', $pathSegments);

        return "json_extract({$column}, '{$jsonPath}')";
    }

    public function orderByField(string $column, array $values, string $type = ''): string
    {
        if (empty($values)) {
            return '';
        }

        $cases = [];

        foreach ($values as $index => $value) {
            $value = is_string($value) ? "'{$value}'" : $value;
            $cases[] = "WHEN {$value} THEN {$index}";
        }

        $caseString = implode(' ', $cases);

        return "CASE {$column} {$caseString} END";
    }

    public function getRegexOperator(): string
    {
        return 'REGEXP';
    }

    public function getBooleanValue(mixed $value)
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }
}
