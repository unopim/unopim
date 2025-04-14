<?php

namespace Webkul\ElasticSearch;

class QueryString
{
    /**
     * Escapes particular values prior to doing a search query escaping whitespace, newlines or reserved characters.
     *
     * This is useful when using ES 'query_string' clauses in a search query.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html#_reserved_characters
     */
    public static function escapeValue(?string $value): string
    {
        $regex = '#[-+=|! &(){}\[\]^"~*<>?:/\\\]#';

        return preg_replace($regex, '\\\$0', $value);
    }

    public static function escapeArrayValue(?array $values): array
    {
        return array_map(fn ($value) => is_string($value) ? strtolower($value) : $value, $values);
    }
}
