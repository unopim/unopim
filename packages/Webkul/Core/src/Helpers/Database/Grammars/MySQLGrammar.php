<?php

namespace Webkul\Core\Helpers\Database\Grammars;

use Webkul\Core\Contracts\Database\Grammar;

class MySQLGrammar implements Grammar
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

    public function jsonExtract(string $column, string ...$pathSegments): string
    {
        $jsonPath = '$.'.implode('.', $pathSegments);

        return "JSON_UNQUOTE(JSON_EXTRACT({$column}, '{$jsonPath}'))";
    }

    public function orderByField(string $column, array $ids, string $type = ''): string
    {
        $idList = implode(',', $ids);

        return "FIELD({$column}, {$idList})";
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
