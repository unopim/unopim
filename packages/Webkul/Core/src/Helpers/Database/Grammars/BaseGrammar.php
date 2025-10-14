<?php

namespace Webkul\Core\Helpers\Database\Grammars;

interface BaseGrammar
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
}
