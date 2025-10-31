<?php

namespace Webkul\Core\Contracts\Database;

interface Grammar
{
    public function groupConcat(
        string $column,
        ?string $alias = null,
        ?string $orderBy = null,
        bool $distinct = false,
        string $separator = ', '
    ): string;

    public function concat(string ...$parts): string;

    public function coalesce(array $columns, ?string $alias = null): string;

    public function length(string $column): string;

    public function jsonExtract(string $column, string ...$pathSegments): string;

    public function orderByField(string $column, array $ids, string $type = ''): string;

    public function getRegexOperator(): string;

    public function getBooleanValue(mixed $value);
}
